<?php

namespace App\Filament\Resources\Turfs\Pages;

use App\Filament\Resources\Turfs\TurfResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTurf extends EditRecord
{
    protected static string $resource = TurfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
