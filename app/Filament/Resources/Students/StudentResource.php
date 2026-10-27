<?php

namespace App\Filament\Resources\Students;

use App\Exports\StudentsTemplateExport;
use App\Filament\Resources\Students\Pages\ManageStudents;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Murid';
    protected static string | \UnitEnum | null $navigationGroup = 'Data Pemilih';
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Murid';

    protected static ?string $pluralModelLabel = 'Murid';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('nipd')
                    ->label('NIPD')
                    ->helperText('Nomor induk sekolah — kunci upsert saat import.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('nisn')
                    ->label('NISN')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('jk')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->native(false),
                Select::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nipd')
                    ->label('NIPD')
                    ->searchable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('voted')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (Student $record): string|BackedEnum => $record->vote()->exists() ? Heroicon::OutlinedCheckBadge : Heroicon::OutlinedClock)
                    ->color(fn (Student $record): string => $record->vote()->exists() ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('voted')
                    ->label('Status Memilih')
                    ->trueLabel('Sudah Memilih')
                    ->falseLabel('Belum Memilih')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('vote'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('vote'),
                    ),
            ])
            ->recordActions([
                Action::make('qr')
                    ->label('Cetak QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->url(fn (Student $record): string => route('students.qr-card.pdf', $record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('qr-bulk')
                        ->label('Cetak Kartu QR')
                        ->icon(Heroicon::OutlinedQrCode)
                        ->url(fn (Collection $records): string => route('students.qr-cards', ['ids' => $records->pluck('id')->implode(',')]))
                        ->openUrlInNewTab(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->emptyStateHeading('Belum ada murid')
            ->emptyStateDescription('Import file Dapodik melalui halaman Import Murid.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudents::route('/'),
        ];
    }

    public static function downloadTemplateAction(): Action
    {
        return Action::make('download-template')
            ->label('Download Template')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->action(fn (): mixed => Excel::download(new StudentsTemplateExport, 'template-import-murid.xlsx'));
    }
}
