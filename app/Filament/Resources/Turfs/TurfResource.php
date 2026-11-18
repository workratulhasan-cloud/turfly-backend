<?php

namespace App\Filament\Resources\Turfs;

use App\Filament\Resources\Turfs\Pages\CreateTurf;
use App\Filament\Resources\Turfs\Pages\EditTurf;
use App\Filament\Resources\Turfs\Pages\ListTurfs;
use App\Filament\Resources\Turfs\Schemas\TurfForm;
use App\Filament\Resources\Turfs\Tables\TurfsTable;
use App\Models\Turf;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TurfResource extends Resource
{
    protected static ?string $model = Turf::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TurfForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TurfsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTurfs::route('/'),
            'create' => CreateTurf::route('/create'),
            'edit' => EditTurf::route('/{record}/edit'),
        ];
    }
}
