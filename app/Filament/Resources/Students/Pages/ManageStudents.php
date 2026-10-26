<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StudentResource::downloadTemplateAction(),
            Action::make('import-page')
                ->label('Import')
                ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUpTray)
                ->url(\App\Filament\Pages\ImportStudents::getUrl()),
            CreateAction::make(),
        ];
    }
}
