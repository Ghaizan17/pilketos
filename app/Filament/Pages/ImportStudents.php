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
        $file = $this->form->getState()['file'] ?? null;

        if ($file instanceof TemporaryUploadedFile) {
            $path = $file->getRealPath();
        } elseif (is_string($file) && $file !== '') {
            $path = Storage::disk('local')->path($file);
        } else {
            Notification::make()
                ->title('Pilih file dulu')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->runId = StudentImportRunner::start($path);
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Format tidak dikenali')
                ->body($e->getMessage())
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

        $this->progress = StudentImportRunner::tick($this->runId);

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
