<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('pitch_id')
                    ->relationship('pitch', 'name')
                    ->required(),
                DateTimePicker::make('start_at')
                    ->required(),
                DateTimePicker::make('end_at')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('PENDING'),
                TextInput::make('source')
                    ->required()
                    ->default('app'),
                TextInput::make('total_amount_poysha')
                    ->required()
                    ->numeric(),
                TextInput::make('paid_amount_poysha')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('expires_at'),
                TextInput::make('customer_name'),
                TextInput::make('customer_phone')
                    ->tel(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('cancelled_at'),
            ]);
    }
}
