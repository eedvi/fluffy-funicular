<?php

namespace App\Filament\Resources\LoanRenewalResource\Pages;

use App\Filament\Resources\LoanRenewalResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanRenewal extends ViewRecord
{
    protected static string $resource = LoanRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Préstamo')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan.loan_number')
                                ->label('Número de Préstamo')
                                ->url(fn ($record) => route('filament.admin.resources.loans.view', $record->loan_id))
                                ->color('primary'),
                            Infolists\Components\TextEntry::make('loan.customer.full_name')
                                ->label('Cliente'),
                            Infolists\Components\TextEntry::make('loan.item.name')
                                ->label('Artículo en Garantía'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Fechas de Renovación')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('previous_due_date')
                                ->label('Vencimiento Anterior')
                                ->date('d/m/Y')
                                ->color('danger'),
                            Infolists\Components\TextEntry::make('extension_days')
                                ->label('Días Extendidos')
                                ->suffix(' días')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('new_due_date')
                                ->label('Nuevo Vencimiento')
                                ->date('d/m/Y')
                                ->badge()
                                ->color('success')
                                ->weight('bold'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Costos de Renovación')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('interest_rate')
                                ->label('Tasa de Interés')
                                ->suffix('%'),
                            Infolists\Components\TextEntry::make('interest_amount')
                                ->label('Interés Calculado')
                                ->money('GTQ'),
                            Infolists\Components\TextEntry::make('renewal_fee')
                                ->label('Comisión por Renovación')
                                ->money('GTQ'),
                        ])->columns(3),
                        Infolists\Components\TextEntry::make('total_cost')
                            ->label('Costo Total de Renovación')
                            ->state(fn ($record) => $record->interest_amount + $record->renewal_fee)
                            ->money('GTQ')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('warning'),
                    ]),

                Infolists\Components\Section::make('Información de Procesamiento')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('processedBy.name')
                                ->label('Procesado Por')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Fecha de Renovación')
                                ->dateTime('d/m/Y H:i:s'),
                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Última Actualización')
                                ->dateTime('d/m/Y H:i:s')
                                ->since(),
                        ])->columns(3),
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
