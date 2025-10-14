<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TransactionHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Historial de Transacciones';

    protected static ?string $label = 'Transacción';

    protected static ?string $pluralLabel = 'Transacciones';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Historial de Transacciones')
            ->description('Incluye pagos, renovaciones y cargos por intereses')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'transfer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles'),
                Tables\Actions\Action::make('print_receipt')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record): string => route('pdf.payment-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Pago')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('payment_number')
                                ->label('Número de Pago'),
                            Infolists\Components\TextEntry::make('payment_date')
                                ->label('Fecha de Pago')
                                ->date('d/m/Y H:i'),
                            Infolists\Components\TextEntry::make('amount')
                                ->label('Monto')
                                ->money('USD')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
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
                            Infolists\Components\TextEntry::make('status')
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
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('reference_number')
                                ->label('Número de Referencia')
                                ->placeholder('N/A'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Estado del Préstamo Después de este Pago')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan.total_amount')
                                ->label('Total del Préstamo')
                                ->money('USD'),
                            Infolists\Components\TextEntry::make('loan.amount_paid')
                                ->label('Total Pagado')
                                ->money('USD')
                                ->color('success'),
                            Infolists\Components\TextEntry::make('loan.balance_remaining')
                                ->label('Saldo Pendiente')
                                ->money('USD')
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                                ->weight('bold'),
                        ])->columns(3),
                    ])
                    ->collapsed(),

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
