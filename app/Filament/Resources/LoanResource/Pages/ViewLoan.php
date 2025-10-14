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
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'paid' => 'info',
                                    'overdue' => 'warning',
                                    'defaulted' => 'danger',
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
                                ->money('USD'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Información Financiera')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan_amount')
                                ->label('Monto del Préstamo')
                                ->money('USD')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('interest_rate')
                                ->label('Tasa de Interés')
                                ->suffix('%'),
                            Infolists\Components\TextEntry::make('interest_amount')
                                ->label('Interés')
                                ->money('USD'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Total a Pagar')
                                ->money('USD')
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('amount_paid')
                                ->label('Total Pagado')
                                ->money('USD')
                                ->color('success'),
                            Infolists\Components\TextEntry::make('balance_remaining')
                                ->label('Saldo Pendiente')
                                ->money('USD')
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                                ->weight('bold'),
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
                                            return $daysRemaining . ' días restantes';
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
