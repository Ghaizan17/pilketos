<?php

namespace App\Filament\Resources\Candidates;

use App\Filament\Resources\Candidates\Pages\ManageCandidates;
use App\Models\Candidate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Paslon';
    protected static string | \UnitEnum | null $navigationGroup = 'Pemilihan';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Paslon';

    protected static ?string $pluralModelLabel = 'Paslon';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('Nomor Urut')
                    ->helperText('1–9, tampil berurutan di bilik suara.')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(9)
                    ->unique(ignoreRecord: true),
                TextInput::make('ketua_name')
                    ->label('Nama Ketua')
                    ->required()
                    ->maxLength(255),
                TextInput::make('wakil_name')
                    ->label('Nama Wakil')
                    ->required()
                    ->maxLength(255),
                TextInput::make('tagline')
                    ->label('Tagline')
                    ->placeholder('Motto paslon…')
                    ->maxLength(255)
                    ->columnSpanFull(),
                FileUpload::make('photo_path')
                    ->label('Foto Paslon')
                    ->helperText('Rasio 4:5, maksimal 2 MB. Bila kosong, kartu menampilkan nomor besar.')
                    ->image()
                    ->imageCropAspectRatio('4:5')
                    ->maxSize(2048)
                    ->disk('public')
                    ->directory('candidates')
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn (): string => 'https://placehold.co/100x125?text=No+Foto'),
                TextColumn::make('number')
                    ->label('No.')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('ketua_name')
                    ->label('Ketua')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('wakil_name')
                    ->label('Wakil')
                    ->searchable(),
                TextColumn::make('tagline')
                    ->label('Tagline')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->defaultSort('number')
            ->emptyStateHeading('Belum ada paslon')
            ->emptyStateDescription('Tambahkan paslon melalui tombol di atas.')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCandidates::route('/'),
        ];
    }
}
