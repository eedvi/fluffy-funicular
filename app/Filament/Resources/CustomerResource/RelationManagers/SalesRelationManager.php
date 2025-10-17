<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Total')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'paid' => 'Pagada',
                        'delivered' => 'Entregada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
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
                Tables\Actions\Action::make('print_receipt')
                    ->label('Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('pdf.sale-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Venta')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('sale_number')
                                ->label('Número de Venta'),
                            Infolists\Components\TextEntry::make('sale_date')
                                ->label('Fecha de Venta')
                                ->date('d/m/Y H:i'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending' => 'Pendiente',
                                    'paid' => 'Pagada',
                                    'delivered' => 'Entregada',
                                    'cancelled' => 'Cancelada',
                                    default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'paid' => 'info',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('payment_method')
                                ->label('Método de Pago')
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
                                    'card' => 'info',
                                    'transfer' => 'warning',
                                    'check' => 'gray',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('delivery_date')
                                ->label('Fecha de Entrega')
                                ->date('d/m/Y')
                                ->placeholder('Sin entregar'),
                            Infolists\Components\TextEntry::make('invoice_number')
                                ->label('Número de Factura')
                                ->placeholder('N/A'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Artículo Vendido')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('item.name')
                                ->label('Nombre del Artículo')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('item.category')
                                ->label('Categoría'),
                            Infolists\Components\TextEntry::make('item.brand')
                                ->label('Marca')
                                ->placeholder('N/A'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('item.model')
                                ->label('Modelo')
                                ->placeholder('N/A'),
                            Infolists\Components\TextEntry::make('item.serial_number')
                                ->label('Número de Serie')
                                ->placeholder('N/A'),
                            Infolists\Components\TextEntry::make('item.condition')
                                ->label('Condición')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string =>
                                    \App\Helpers\TranslationHelper::translateItemCondition($state)
                                ),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Detalles Financieros')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('sale_price')
                                ->label('Precio de Venta')
                                ->money('GTQ'),
                            Infolists\Components\TextEntry::make('discount')
                                ->label('Descuento')
                                ->money('GTQ')
                                ->color('success'),
                            Infolists\Components\TextEntry::make('final_price')
                                ->label('Precio Final')
                                ->money('GTQ')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Cliente')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('customer.full_name')
                                ->label('Nombre Completo')
                                ->placeholder('Venta sin cliente registrado'),
                            Infolists\Components\TextEntry::make('customer.identity_number')
                                ->label('DPI')
                                ->placeholder('N/A'),
                            Infolists\Components\TextEntry::make('customer.phone')
                                ->label('Teléfono')
                                ->placeholder('N/A'),
                        ])->columns(3),
                    ])
                    ->visible(fn ($record) => $record->customer !== null),

                Infolists\Components\Section::make('Notas')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),
            ]);
    }
}
