<?php

namespace App\Filament\Resources\Pitches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PitchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('turf_id')
                    ->label('Turf')
                    ->relationship('turf', 'name')
                    ->searchable()->preload()->required(),
                TextInput::make('name')->required()->maxLength(255),
                Select::make('sport_type')
                    ->options(['football' => 'Football', 'cricket' => 'Cricket'])
                    ->required(),
                TextInput::make('size')->placeholder('5v5, 7v7'),
                Select::make('slot_minutes')
                    ->options([60 => '60 minutes', 90 => '90 minutes'])
                    ->default(60)->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('active')->required(),
            ]);
    }
}