<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('print_contract')
                ->label('Imprimir Contrato')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn ($record) => route('pdf.loan-contract', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('print_receipt')
                ->label('Imprimir Recibo')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('pdf.loan-receipt', $record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Préstamo')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan_number')
                                ->label('Número de Préstamo'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending' => 'Pendiente',
                                    'active' => 'Activo',
                                    'paid' => 'Pagado',
                                    'overdue' => 'Vencido',
                                    'forfeited' => 'Confiscado',
                                    default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'pending' => 'gray',
                                    'active' => 'success',
                                    'paid' => 'info',
                                    'overdue' => 'warning',
                                    'forfeited' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('branch.name')
                                ->label('Sucursal')
                                ->badge()
                                ->color('info'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Información del Cliente')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('customer.full_name')
                                ->label('Cliente'),
                            Infolists\Components\TextEntry::make('customer.identity_number')
                                ->label('DPI'),
                            Infolists\Components\TextEntry::make('customer.phone')
                                ->label('Teléfono'),
                            Infolists\Components\TextEntry::make('customer.email')
                                ->label('Email'),
                        ])->columns(2),
                    ]),

                Infolists\Components\Section::make('Artículo en Garantía')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('item.name')
                                ->label('Artículo'),
                            Infolists\Components\TextEntry::make('item.category.name')
                                ->label('Categoría'),
                            Infolists\Components\TextEntry::make('item.appraised_value')
                                ->label('Valor Tasado')
                                ->money('GTQ'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Información Financiera')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan_amount')
                                ->label('Capital Original')
                                ->money('GTQ')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('interest_rate')
                                ->label('Tasa de Interés')
                                ->suffix('%'),
                            Infolists\Components\TextEntry::make('amount_paid')
                                ->label('Total Pagado')
                                ->money('GTQ')
                                ->color('success'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('principal_remaining')
                                ->label('Capital Restante')
                                ->money('GTQ')
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('interest_amount')
                                ->label('Interés Acumulado')
                                ->money('GTQ')
                                ->color('info'),
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Total a Pagar')
                                ->money('GTQ')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Fechas Importantes')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('start_date')
                                ->label('Fecha de Inicio')
                                ->date('d/m/Y'),
                            Infolists\Components\TextEntry::make('due_date')
                                ->label('Fecha de Vencimiento')
                                ->date('d/m/Y')
                                ->color(fn ($record) => $record->due_date->isPast() && $record->balance_remaining > 0 ? 'danger' : 'gray'),
                            Infolists\Components\TextEntry::make('loan_term_days')
                                ->label('Plazo')
                                ->suffix(' días'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Pago Mínimo Mensual')
                    ->description('Información sobre requisitos de pago mínimo mensual')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('minimum_monthly_payment')
                                ->label('Pago Mínimo Mensual')
                                ->money('GTQ')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('next_minimum_payment_date')
                                ->label('Próximo Pago Vence')
                                ->date('d/m/Y')
                                ->color(fn ($record) => $record->isMinimumPaymentOverdue() ? 'danger' : 'gray')
                                ->badge(fn ($record) => $record->isMinimumPaymentOverdue())
                                ->icon(fn ($record) => $record->isMinimumPaymentOverdue() ? 'heroicon-o-exclamation-triangle' : null),
                            Infolists\Components\TextEntry::make('last_minimum_payment_date')
                                ->label('Último Pago Mínimo')
                                ->date('d/m/Y')
                                ->placeholder('Sin pagos aún'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('is_at_risk')
                                ->label('Estado de Riesgo')
                                ->formatStateUsing(fn ($state) => $state ? 'En Riesgo' : 'Al Corriente')
                                ->badge()
                                ->color(fn ($state) => $state ? 'danger' : 'success')
                                ->icon(fn ($state) => $state ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check'),
                            Infolists\Components\TextEntry::make('grace_period_end_date')
                                ->label('Período de Gracia Termina')
                                ->date('d/m/Y')
                                ->color('warning')
                                ->badge()
                                ->visible(fn ($record) => $record->is_at_risk && $record->grace_period_end_date),
                            Infolists\Components\TextEntry::make('consecutive_missed_payments')
                                ->label('Pagos Consecutivos Perdidos')
                                ->formatStateUsing(fn ($state) => $state > 0 ? $state . ' pago(s)' : 'Ninguno')
                                ->badge()
                                ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        ])->columns(3),
                    ])
                    ->visible(fn ($record) => $record->requires_minimum_payment)
                    ->collapsible(),

                Infolists\Components\Section::make('Resumen de Actividad')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('payments_count')
                                    ->label('Total de Pagos')
                                    ->state(fn ($record) => $record->payments()->count())
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('renewals_count')
                                    ->label('Renovaciones')
                                    ->state(fn ($record) => $record->renewals()->count())
                                    ->badge()
                                    ->color('warning'),
                                Infolists\Components\TextEntry::make('interest_charges_count')
                                    ->label('Cargos por Mora')
                                    ->state(fn ($record) => $record->interestCharges()->count())
                                    ->badge()
                                    ->color('danger'),
                                Infolists\Components\TextEntry::make('days_status')
                                    ->label('Estado de Días')
                                    ->state(function ($record) {
                                        if ($record->status === 'paid') {
                                            return 'Pagado';
                                        }
                                        $daysRemaining = now()->diffInDays($record->due_date, false);
                                        if ($daysRemaining < 0) {
                                            return abs(round($daysRemaining)) . ' días vencido';
                                        } else {
                                            return round($daysRemaining) . ' días restantes';
                                        }
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        if ($record->status === 'paid') return 'success';
                                        $daysRemaining = now()->diffInDays($record->due_date, false);
                                        if ($daysRemaining < 0) return 'danger';
                                        if ($daysRemaining <= 7) return 'warning';
                                        return 'info';
                                    }),
                            ]),
                    ]),

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
