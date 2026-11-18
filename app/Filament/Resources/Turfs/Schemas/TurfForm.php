<?php

namespace App\Filament\Resources\Turfs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TurfForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name', fn ($query) => $query->where('role', 'owner'))
                    ->searchable()->preload()->required(),
                Select::make('area_id')
                    ->label('Area')
                    ->options(fn () => \Illuminate\Support\Facades\DB::table('areas')->pluck('name', 'id'))
                    ->searchable()->required(),
                TextInput::make('address')->required()->maxLength(255),
                TextInput::make('contact_phone')->tel(),
                Textarea::make('description')->columnSpanFull(),
                TagsInput::make('facilities')->placeholder('parking, washroom...'),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('pending')->required(),
            ]);
    }
}