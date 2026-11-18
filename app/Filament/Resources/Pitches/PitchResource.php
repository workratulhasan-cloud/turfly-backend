<?php

namespace App\Filament\Resources\Pitches;

use App\Filament\Resources\Pitches\Pages\CreatePitch;
use App\Filament\Resources\Pitches\Pages\EditPitch;
use App\Filament\Resources\Pitches\Pages\ListPitches;
use App\Filament\Resources\Pitches\Pages\ViewPitch;
use App\Filament\Resources\Pitches\Schemas\PitchForm;
use App\Filament\Resources\Pitches\Schemas\PitchInfolist;
use App\Filament\Resources\Pitches\Tables\PitchesTable;
use App\Models\Pitch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PitchResource extends Resource
{
    protected static ?string $model = Pitch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PitchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PitchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PitchesTable::configure($table);
    }

    public static function getRelations(): array
{
    return [
        \App\Filament\Resources\Pitches\RelationManagers\PricingRulesRelationManager::class,
    ];
}

    public static function getPages(): array
    {
        return [
            'index' => ListPitches::route('/'),
            'create' => CreatePitch::route('/create'),
            'view' => ViewPitch::route('/{record}'),
            'edit' => EditPitch::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
