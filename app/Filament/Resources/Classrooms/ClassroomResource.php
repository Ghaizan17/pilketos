<?php

namespace App\Filament\Resources\Classrooms;

use App\Filament\Resources\Classrooms\Pages\ManageClassrooms;
use App\Models\Classroom;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Kelas';
    protected static string | \UnitEnum | null $navigationGroup = 'Data Pemilih';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kelas';

    protected static ?string $pluralModelLabel = 'Kelas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kelas')
                    ->placeholder('XII RPL A')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('grade')
                    ->label('Tingkat')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('major')
                    ->label('Jurusan')
                    ->placeholder('—'),
                TextColumn::make('students_count')
                    ->label('Jumlah Murid')
                    ->counts('students')
                    ->numeric()
                    ->sortable()
                    ->icon(Heroicon::OutlinedUsers),
            ])
            ->emptyStateHeading('Belum ada kelas')
            ->emptyStateDescription('Kelas dibuat otomatis saat import, atau tambahkan manual.')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Model $record): void {
                        $count = $record->students()->count();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Kelas tidak dapat dihapus')
                                ->body("Kelas masih dipakai {$count} murid")
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action, $records): void {
                            $blocked = $records->filter(fn (Model $record) => $record->students()->exists());

                            if ($blocked->isNotEmpty()) {
                                $total = $blocked->sum(fn (Model $record) => $record->students()->count());

                                Notification::make()
                                    ->title('Sebagian kelas tidak dapat dihapus')
                                    ->body("Kelas masih dipakai {$total} murid")
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClassrooms::route('/'),
        ];
    }
}
