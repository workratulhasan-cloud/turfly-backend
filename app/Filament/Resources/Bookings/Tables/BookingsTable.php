<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('pitch.turf.name')->label('Turf'),
                TextColumn::make('pitch.name')->label('Pitch'),
                TextColumn::make('start_at')->label('Start')
                    ->dateTime('d M Y, h:i A', 'Asia/Dhaka')->sortable(),
                TextColumn::make('customer')
                    ->state(fn ($record) => $record->user?->name ?? $record->customer_name)
                    ->description(fn ($record) => $record->user?->phone ?? $record->customer_phone),
                TextColumn::make('total_amount_poysha')->label('Amount (taka)')
                    ->formatStateUsing(fn ($state) => number_format($state / 100)),
                TextColumn::make('source')->badge(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CONFIRMED', 'COMPLETED' => 'success',
                        'PENDING' => 'warning',
                        'CANCELLED', 'EXPIRED', 'REFUNDED', 'NO_SHOW' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'PENDING' => 'Pending',
                    'CONFIRMED' => 'Confirmed',
                    'CANCELLED' => 'Cancelled',
                    'EXPIRED' => 'Expired',
                    'COMPLETED' => 'Completed',
                    'NO_SHOW' => 'No show',
                ]),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['PENDING', 'CONFIRMED']))
                    ->action(fn ($record) => $record->update([
                        'status' => 'CANCELLED',
                        'cancelled_at' => now(),
                    ])),
                Action::make('no_show')
                    ->label('No show')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'CONFIRMED' && $record->end_at->isPast())
                    ->action(fn ($record) => $record->update(['status' => 'NO_SHOW'])),
            ]);
    }
}