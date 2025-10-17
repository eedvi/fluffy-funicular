<?php

namespace App\Filament\Resources\InterestChargeResource\Pages;

use App\Filament\Resources\InterestChargeResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInterestCharge extends ViewRecord
{
    protected static string $resource = InterestChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
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

                Infolists\Components\Section::make('Detalles del Cargo por Mora')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('days_overdue')
                                ->label('Días de Atraso')
                                ->suffix(' días')
                                ->badge()
                                ->color('danger'),
                            Infolists\Components\TextEntry::make('interest_rate')
                                ->label('Tasa de Interés Moratorio')
                                ->suffix('%'),
                            Infolists\Components\TextEntry::make('charge_date')
                                ->label('Fecha del Cargo')
                                ->date('d/m/Y'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Cálculo del Cargo')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('principal_amount')
                                ->label('Monto Principal')
                                ->money('GTQ'),
                            Infolists\Components\TextEntry::make('interest_amount')
                                ->label('Interés Moratorio')
                                ->money('GTQ')
                                ->color('warning')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (?string $state): string => match ($state) {
                                    'pending' => 'Pendiente',
                                    'applied' => 'Aplicado',
                                    'cancelled' => 'Cancelado',
                                    default => $state ?? 'N/A',
                                })
                                ->color(fn (?string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'applied' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Balance del Préstamo')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('balance_before')
                                ->label('Balance Antes')
                                ->money('GTQ'),
                            Infolists\Components\TextEntry::make('balance_after')
                                ->label('Balance Después')
                                ->money('GTQ')
                                ->color('danger'),
                            Infolists\Components\TextEntry::make('processed_by')
                                ->label('Procesado Por')
                                ->state(fn ($record) => $record->processedBy?->name ?? 'Sistema')
                                ->badge()
                                ->color('info'),
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
