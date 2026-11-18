<?php

namespace App\Filament\Resources\Turfs\Pages;

use App\Filament\Resources\Turfs\TurfResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTurfs extends ListRecords
{
    protected static string $resource = TurfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
