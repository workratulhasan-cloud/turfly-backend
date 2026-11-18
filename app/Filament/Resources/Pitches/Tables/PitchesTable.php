<?php

namespace App\Filament\Resources\Pitches\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PitchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('turf.name')->label('Turf')->searchable()->sortable(),
                TextColumn::make('name')->label('Pitch')->searchable(),
                TextColumn::make('sport_type')->badge(),
                TextColumn::make('size'),
                TextColumn::make('slot_minutes')->label('Slot (min)'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}