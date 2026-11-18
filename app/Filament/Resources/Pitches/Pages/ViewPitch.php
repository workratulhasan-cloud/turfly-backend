<?php

namespace App\Filament\Resources\Pitches\Pages;

use App\Filament\Resources\Pitches\PitchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPitch extends ViewRecord
{
    protected static string $resource = PitchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
