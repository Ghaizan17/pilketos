<?php

namespace App\Filament\Pages;

use App\Models\ElectionSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageElection extends Page
{
    protected static ?string $slug = 'pemilihan';

    protected string $view = 'filament.pages.manage-election';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pemilihan';

    protected static ?string $title = 'Pengaturan Pemilihan';

    protected static ?int $navigationSort = 100;
    protected static string | \UnitEnum | null $navigationGroup = 'Pemilihan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'is_open' => ElectionSetting::isOpen(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Toggle::make('is_open')
                    ->label('Pemilihan Dibuka')
                    ->helperText('Tutup untuk mengunci bilik suara dan membekukan quick-count.')
                    ->live()
                    ->afterStateUpdated(fn (): mixed => $this->save()),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $isOpen = (bool) ($data['is_open'] ?? false);

        ElectionSetting::query()->updateOrCreate(
            ['key' => 'is_open'],
            ['value' => $isOpen ? '1' : '0'],
        );

        Notification::make()
            ->title($isOpen ? 'Pemilihan dibuka' : 'Pemilihan ditutup')
            ->body($isOpen ? 'Bilik suara menerima suara.' : 'Hasil quick-count dibekukan sebagai hasil final.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->action('save'),
            Action::make('reset-votes')
                ->label(fn (): string => 'Reset Suara ('.\App\Models\Vote::count().')')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset semua suara?')
                ->modalDescription('Seluruh data suara akan dihapus permanen dan tidak bisa dikembalikan. Lakukan hanya sebelum pemungutan suara dimulai ulang.')
                ->modalSubmitActionLabel('Ya, hapus semua suara')
                ->disabled(fn (): bool => \App\Models\Vote::count() === 0 || ElectionSetting::isOpen())
                ->action(function (): void {
                    $deleted = \App\Models\Vote::count();
                    \App\Models\Vote::query()->delete();

                    Notification::make()
                        ->title('Suara direset')
                        ->body("{$deleted} suara dihapus. Bilik siap untuk pemungutan ulang.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
