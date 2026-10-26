<?php

namespace App\Filament\Pages;

use App\Services\StudentImportRunner;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Import murid dengan progress bar tanpa queue worker.
 *
 * Alur: upload file Dapodik/template → "Mulai Import" → state run
 * disimpan di cache → tiap poll Livewire (`tick`) memproses 200 baris
 * → progress bar terupdate → selesai: ringkasan + contoh baris skip.
 */
class ImportStudents extends Page
{
    protected static ?string $slug = 'import-murid';

    protected string $view = 'filament.pages.import-students';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Import Murid';

    protected static ?string $title = 'Import Murid';

    protected static ?int $navigationSort = 31;
    protected static string | \UnitEnum | null $navigationGroup = 'Data Pemilih';

    public ?array $data = [];

    public ?string $runId = null;

    /** @var array<string, mixed> */
    public array $progress = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->progress = [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('file')
                    ->label('File murid (.xlsx, .xls, .csv)')
                    ->helperText(
                        'Upload file Dapodik (Daftar Peserta Didik) langsung tanpa diubah, '.
                        'atau file template (header baris 1: nama | nipd | nisn | jk | kelas). '.
                        'Hanya kolom Nama, NIPD, JK, NISN, dan Rombel/Kelas yang dipakai; '.
                        'kolom lain diabaikan. Baris tanpa nama/NIPD di-skip. '.
                        'CSV dibaca dengan delimiter koma. Maksimal 10 MB.'
                    )
                    ->required()
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->maxSize(10240),
            ]);
    }

    public function startImport(): void
    {
        try {
            $file = $this->form->getState()['file'] ?? null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Upload bermasalah')
                ->body('File upload tidak ditemukan (mungkin kedaluwarsa). Silakan upload ulang file lalu klik Mulai Import.')
                ->danger()
                ->send();

            return;
        }

        $cleanup = false;

        if ($file instanceof TemporaryUploadedFile) {
            if (! $file->exists()) {
                Notification::make()
                    ->title('File upload kedaluwarsa')
                    ->body('Silakan upload ulang file lalu klik Mulai Import.')
                    ->danger()
                    ->send();

                return;
            }

            // Salin ke lokasi stabil: file livewire-tmp bisa dibersihkan
            // otomatis sebelum polling `tick()` selesai membacanya.
            $stored = 'imports/import-'.(string) Str::uuid().'.'.$file->getClientOriginalExtension();
            Storage::disk('local')->put($stored, $file->readStream());
            $path = Storage::disk('local')->path($stored);
            $cleanup = true;
        } elseif (is_string($file) && $file !== '') {
            $path = Storage::disk('local')->path($file);

            if (! is_file($path)) {
                Notification::make()
                    ->title('File tidak ditemukan')
                    ->body('Silakan upload ulang file lalu klik Mulai Import.')
                    ->danger()
                    ->send();

                return;
            }
        } else {
            Notification::make()
                ->title('Pilih file dulu')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->runId = StudentImportRunner::start($path, $cleanup);
        } catch (\InvalidArgumentException $e) {
            if ($cleanup) {
                @unlink($path);
            }

            Notification::make()
                ->title('Format tidak dikenali')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (\Throwable $e) {
            if ($cleanup) {
                @unlink($path);
            }

            report($e);

            Notification::make()
                ->title('Import gagal dimulai')
                ->body('Terjadi kesalahan saat membaca file. Coba lagi atau pastikan file tidak rusak.')
                ->danger()
                ->send();

            return;
        }

        $this->progress = StudentImportRunner::state($this->runId);
        $this->tick();
    }

    public function tick(): void
    {
        if ($this->runId === null) {
            return;
        }

        try {
            $this->progress = StudentImportRunner::tick($this->runId);
        } catch (\Throwable $e) {
            report($e);

            $this->progress = [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses file. Silakan coba lagi.',
                'total' => (int) ($this->progress['total'] ?? 0),
                'processed' => (int) ($this->progress['processed'] ?? 0),
                'imported' => (int) ($this->progress['imported'] ?? 0),
                'skipped' => (int) ($this->progress['skipped'] ?? 0),
                'skips' => [],
            ];

            Notification::make()
                ->title('Import gagal')
                ->body($this->progress['message'])
                ->danger()
                ->send();

            return;
        }

        if ($this->progress['status'] === 'done') {
            Notification::make()
                ->title('Import selesai')
                ->body("{$this->progress['imported']} imported, {$this->progress['skipped']} skipped")
                ->success()
                ->send();
        }

        if ($this->progress['status'] === 'error') {
            Notification::make()
                ->title('Import gagal')
                ->body($this->progress['message'] ?? 'Terjadi kesalahan.')
                ->danger()
                ->send();
        }
    }

    public function resetImport(): void
    {
        if ($this->runId !== null) {
            $state = StudentImportRunner::state($this->runId);

            if (($state['cleanup'] ?? false) && isset($state['path']) && is_string($state['path']) && is_file($state['path'])) {
                @unlink($state['path']);
            }

            StudentImportRunner::forget($this->runId);
        }

        $this->runId = null;
        $this->progress = [];
        $this->form->fill();
    }

    public function getProgressPercentProperty(): int
    {
        $total = (int) ($this->progress['total'] ?? 0);

        if ($total <= 0) {
            return 0;
        }

        return (int) min(100, round(((int) ($this->progress['processed'] ?? 0) / $total) * 100));
    }
}
