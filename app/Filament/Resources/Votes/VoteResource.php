<?php

namespace App\Filament\Resources\Votes;

use App\Filament\Resources\Votes\Pages\ManageVotes;
use App\Models\Candidate;
use App\Models\Vote;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Suara';
    protected static string | \UnitEnum | null $navigationGroup = 'Pemilihan';
    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Suara';

    protected static ?string $pluralModelLabel = 'Suara';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Murid')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('candidate_display')
                    ->label('Paslon')
                    ->badge()
                    ->color('primary')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->state(fn (Vote $record): string => "No. {$record->candidate->number} — {$record->candidate->ketua_name} & {$record->candidate->wakil_name}")
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('candidate', fn (Builder $q): Builder => $q
                            ->where('ketua_name', 'like', "%{$search}%")
                            ->orWhere('wakil_name', 'like', "%{$search}%")
                            ->orWhere('number', $search));
                    }),
                TextColumn::make('voted_at')
                    ->label('Waktu Memilih')
                    ->dateTime()
                    ->sortable()
                    ->icon(Heroicon::OutlinedClock),
            ])
            ->emptyStateHeading('Belum ada suara masuk')
            ->emptyStateDescription('Suara tercatat otomatis dari bilik suara.')
            ->filters([
                SelectFilter::make('candidate_id')
                    ->label('Paslon')
                    ->relationship('candidate', 'ketua_name')
                    ->getOptionLabelFromRecordUsing(fn (Candidate $record): string => "No. {$record->number} — {$record->ketua_name} & {$record->wakil_name}")
                    ->searchable()
                    ->preload(),
                SelectFilter::make('classroom')
                    ->label('Kelas')
                    ->relationship('student.classroom', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('voted_at')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('voted_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('voted_at', '<=', $date));
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVotes::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
