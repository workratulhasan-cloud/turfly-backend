<?php

namespace App\Filament\Resources\Pitches\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'pricingRules';

    protected static ?string $title = 'Pricing';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('day_type')
                ->options(['all' => 'All days', 'weekday' => 'Weekday', 'weekend' => 'Weekend (Fri, Sat)'])
                ->default('all')->required(),
            TimePicker::make('start_time')->seconds(false)->required(),
            TimePicker::make('end_time')->seconds(false)->required(),
            TextInput::make('price_taka')
                ->label('Price (taka)')
                ->numeric()->minValue(0)->required()
                ->dehydrated(false)
                ->afterStateHydrated(fn ($component, $record) => $component->state($record ? $record->price_poysha / 100 : null)),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_type')->badge(),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('price_poysha')->label('Price (taka)')
                    ->formatStateUsing(fn ($state) => number_format($state / 100)),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => $this->withPoysha($data)),
            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data) => $this->withPoysha($data)),
                DeleteAction::make(),
            ]);
    }

    private function withPoysha(array $data): array
    {
        $taka = $this->mountedActions ? ($this->mountedActions[0]['data']['price_taka'] ?? 0) : 0;
        $data['price_poysha'] = (int) round(((float) $taka) * 100);

        return $data;
    }
}