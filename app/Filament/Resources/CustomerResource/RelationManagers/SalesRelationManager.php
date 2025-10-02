<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Ventas';

    protected static ?string $recordTitleAttribute = 'sale_number';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sale_number')
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Artículo')
                    ->limit(30),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completada' => 'success',
                        'Pendiente' => 'warning',
                        'Cancelada' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
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
            ->defaultSort('sale_date', 'desc');
    }
}
