<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Models\Student;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Cetak kartu QR murid ke PDF (kecil, 20 kartu per halaman A4).
 * Filter: semua murid atau per kelas.
 *
 * Generate berjalan bertahap via polling Livewire (tanpa queue worker):
 * tiap tick menyiapkan cache QR 50 murid, tick terakhir merender PDF
 * lalu tombol unduh muncul. Progress tampil selama proses.
 */
class PrintQrCards extends Page
{
    protected static ?string $slug = 'cetak-qr';

    protected string $view = 'filament.pages.print-qr-cards';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'Cetak QR';

    protected static ?string $title = 'Cetak Kartu QR';

    protected static ?int $navigationSort = 32;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Pemilih';

    protected const CHUNK_SIZE = 100;

    protected const CACHE_TTL = 3600;

    public ?array $data = [
        'scope' => 'all',
        'classroom_id' => null,
    ];

    public ?string $jobId = null;

    /** @var array<string, mixed> */
    public array $job = [];

    public function mount(): void
    {
        $this->form->fill($this->data);
        $this->job = [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Radio::make('scope')
                    ->label('Cakupan')
                    ->options([
                        'all' => 'Semua murid',
                        'classroom' => 'Per kelas',
                    ])
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function (): void {
                        $this->data['classroom_id'] = null;
                        $this->resetJob();
                    }),
                Select::make('classroom_id')
                    ->label('Kelas')
                    ->options(fn (): array => Classroom::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (): mixed => $this->resetJob())
                    ->visible(fn (): bool => ($this->data['scope'] ?? 'all') === 'classroom')
                    ->required(fn (): bool => ($this->data['scope'] ?? 'all') === 'classroom'),
                \Filament\Schemas\Components\Section::make('Pratinjau')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Text::make(fn (): string => $this->previewText()),
                    ])
                    ->footerActions([
                        \Filament\Actions\Action::make('generate')
                            ->label('Buat PDF')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->action('startPdf')
                            ->disabled(fn (): bool => $this->getPreviewProperty()['count'] === 0 || $this->isProcessing()),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
        ]);
    }

    public function startPdf(): void
    {
        if ($this->getPreviewProperty()['count'] === 0) {
            return;
        }

        $ids = $this->matchingStudentsQuery()->pluck('students.id')->all();

        $this->jobId = (string) \Illuminate\Support\Str::uuid();
        $this->job = [
            'ids' => $ids,
            'label' => $this->scopeLabel(),
            'total' => count($ids),
            'processed' => 0,
            'status' => 'processing',
            'message' => null,
            'file' => null,
        ];
        Cache::put($this->jobKey($this->jobId), $this->job, static::CACHE_TTL);

        $this->tickPdf();
    }

    public function tickPdf(): void
    {
        if ($this->jobId === null) {
            return;
        }

        $this->job = Cache::get($this->jobKey($this->jobId), []);

        if (($this->job['status'] ?? null) !== 'processing') {
            return;
        }

        set_time_limit(120);

        $ids = $this->job['ids'] ?? [];
        $batchSize = 100;
        $batchIndex = (int) ($this->job['batches_done'] ?? 0);
        $slice = array_slice($ids, $batchIndex * $batchSize, $batchSize);

        if ($slice === []) {
            $this->finishPdfJob($ids);

            return;
        }

        // Satu tick = cache QR + render 1 part PDF (100 murid, ~3 detik).
        $students = Student::query()
            ->whereIn('id', $slice)
            ->with('classroom')
            ->orderBy('name')
            ->get();

        $qrUris = [];

        foreach ($students as $student) {
            $this->ensureQrCached($student->id);
            $qrUris[$student->id] = $this->qrDataUri($student);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('students.qr-cards-pdf', [
            'students' => $students,
            'scopeLabel' => $this->job['label'],
            'qrUris' => $qrUris,
        ])->setPaper('a4', 'portrait');

        $part = "qr-{$this->jobId}-p{$batchIndex}.pdf";
        Storage::disk('local')->put("qr-pdf/{$part}", $pdf->output());

        $parts = $this->job['parts'] ?? [];
        $parts[] = $part;
        $this->job['parts'] = $parts;
        $this->job['batches_done'] = $batchIndex + 1;
        $this->job['processed'] = min($this->job['total'], ($batchIndex + 1) * $batchSize);

        Cache::put($this->jobKey($this->jobId), $this->job, static::CACHE_TTL);
    }

    protected function finishPdfJob(array $ids): void
    {
        try {
            $parts = $this->job['parts'] ?? [];

            if ($parts === []) {
                throw new \RuntimeException('Tidak ada halaman untuk digabung.');
            }

            // Reset akumulasi memori dompdf antar tick dengan proses merge terpisah.
            $filename = "qr-{$this->jobId}.pdf";
            $this->mergePdfParts($parts, $filename);

            foreach ($parts as $part) {
                Storage::disk('local')->delete("qr-pdf/{$part}");
            }

            $this->job['status'] = 'done';
            $this->job['file'] = $filename;
        } catch (\Throwable $e) {
            $this->job['status'] = 'error';
            $this->job['message'] = 'Gagal merender PDF: '.$e->getMessage();
        }

        Cache::put($this->jobKey($this->jobId), $this->job, static::CACHE_TTL);
    }

    protected function mergePdfParts(array $parts, string $filename): void
    {
        $disk = Storage::disk('local');

        if (count($parts) === 1) {
            $disk->move("qr-pdf/{$parts[0]}", "qr-pdf/{$filename}");

            return;
        }

        $gs = trim((string) shell_exec('command -v gs'));

        if ($gs === '') {
            // Tanpa Ghostscript: gabung tidak tersedia — pakai bagian pertama saja.
            throw new \RuntimeException('PDF terlalu besar dan Ghostscript tidak tersedia di server.');
        }

        $inputs = implode(' ', array_map(
            fn (string $p): string => escapeshellarg(Storage::path("qr-pdf/{$p}")),
            $parts
        ));
        $output = escapeshellarg(Storage::path("qr-pdf/{$filename}"));
        $cmd = "{$gs} -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile={$output} {$inputs} 2>&1";
        $result = shell_exec($cmd);

        if (! $disk->exists("qr-pdf/{$filename}")) {
            throw new \RuntimeException('Penggabungan PDF gagal: '.(string) $result);
        }
    }

    public function resetJob(): mixed
    {
        if ($this->jobId !== null) {
            Cache::forget($this->jobKey($this->jobId));
        }

        $this->jobId = null;
        $this->job = [];

        return null;
    }

    public function isProcessing(): bool
    {
        return ($this->job['status'] ?? null) === 'processing';
    }

    public function getJobPercentProperty(): int
    {
        $total = (int) ($this->job['total'] ?? 0);

        if ($total <= 0) {
            return 0;
        }

        // Progress = porsi murid yang sudah jadi part PDF.
        $ratio = ((int) ($this->job['processed'] ?? 0)) / $total;

        return (int) min(99, round($ratio * 100));
    }

    public function getDownloadUrlProperty(): ?string
    {
        if (($this->job['status'] ?? null) !== 'done' || empty($this->job['file'])) {
            return null;
        }

        return route('students.qr-pdf-file', ['job' => $this->jobId]);
    }

    /** @return array{count: int, pages: int, label: string} */
    public function getPreviewProperty(): array
    {
        $count = $this->matchingStudentsQuery()->count();

        return [
            'count' => $count,
            'pages' => (int) ceil($count / 20),
            'label' => $this->scopeLabel(),
        ];
    }

    protected function previewText(): string
    {
        $preview = $this->getPreviewProperty();

        return "Cakupan: {$preview['label']} — Jumlah kartu: {$preview['count']} — Perkiraan halaman A4: {$preview['pages']} (20 kartu/halaman)";
    }

    protected function matchingStudentsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Student::query();
        $scope = $this->data['scope'] ?? 'all';

        if ($scope === 'classroom' && filled($this->data['classroom_id'] ?? null)) {
            $query->where('classroom_id', $this->data['classroom_id']);
        } elseif ($scope !== 'all') {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected function scopeLabel(): string
    {
        $scope = $this->data['scope'] ?? 'all';

        if ($scope === 'classroom' && filled($this->data['classroom_id'] ?? null)) {
            $name = Classroom::find($this->data['classroom_id'])?->name;

            return $name ? "Kelas {$name}" : '—';
        }

        return 'Semua murid';
    }

    protected function jobKey(string $jobId): string
    {
        return "qr-pdf-job:{$jobId}";
    }

    protected function ensureQrCached(int $studentId): void
    {
        if (Storage::disk('local')->exists("qr-svg/{$studentId}.svg")) {
            return;
        }

        $student = Student::find($studentId);

        if ($student === null) {
            return;
        }

        $svg = preg_replace(
            '/<\?xml[^?]*\?>\s*/',
            '',
            (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate($student->uuid)
        );
        Storage::disk('local')->put("qr-svg/{$studentId}.svg", $svg);
    }

    protected function qrDataUri(\App\Models\Student $student): string
    {
        $this->ensureQrCached($student->id);

        return 'data:image/svg+xml;base64,'.base64_encode((string) Storage::disk('local')->get("qr-svg/{$student->id}.svg"));
    }
}
