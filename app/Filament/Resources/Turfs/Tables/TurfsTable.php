<?php

namespace App\Filament\Resources\Turfs\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TurfsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Turf')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Account')->sortable(),
                TextColumn::make('owner.phone')->label('Phone'),
                TextColumn::make('address')->limit(40),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending'
                        && auth()->user()?->role === 'admin')
                    ->action(function ($record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['status' => 'active']);

                            DB::table('owner_profiles')
                                ->where('user_id', $record->owner_id)
                                ->update([
                                    'verification_status' => 'approved',
                                    'verified_at' => now(),
                                    'updated_at' => now(),
                                ]);

                            User::where('id', $record->owner_id)
                                ->where('role', 'player')
                                ->update(['role' => 'turf']);
                        });

                        Notification::make()->title('Turf approved')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }
}