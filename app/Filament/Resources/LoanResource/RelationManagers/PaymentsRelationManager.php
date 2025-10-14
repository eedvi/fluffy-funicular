<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    protected static ?string $recordTitleAttribute = 'payment_number';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'card' => 'warning',
                        'check' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referencia')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
