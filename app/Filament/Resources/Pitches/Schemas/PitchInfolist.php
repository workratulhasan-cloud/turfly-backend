<?php

namespace App\Filament\Resources\Pitches\Schemas;

use App\Models\Pitch;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PitchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('turf.name')
                    ->label('Turf'),
                TextEntry::make('name'),
                TextEntry::make('sport_type'),
                TextEntry::make('size')
                    ->placeholder('-'),
                TextEntry::make('slot_minutes')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Pitch $record): bool => $record->trashed()),
            ]);
    }
}
