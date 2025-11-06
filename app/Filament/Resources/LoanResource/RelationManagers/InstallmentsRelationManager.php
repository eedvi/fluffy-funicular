<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use App\Models\Installment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Plan de Cuotas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only form - installments are auto-generated
                Forms\Components\Placeholder::make('info')
                    ->content('Las cuotas se generan automáticamente según el plan de pago configurado.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('installment_number')
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Cuota #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fecha de Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto Total')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('principal_amount')
                    ->label('Capital')
                    ->money('GTQ')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('interest_amount')
                    ->label('Interés')
                    ->money('GTQ')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Pagado')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_remaining')
                    ->label('Saldo')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Días Mora')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('late_fee')
                    ->label('Cargo Mora')
                    ->money('GTQ')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Installment::STATUS_PENDING => 'Pendiente',
                        Installment::STATUS_PAID => 'Pagada',
                        Installment::STATUS_OVERDUE => 'Vencida',
                        Installment::STATUS_PARTIALLY_PAID => 'Pago Parcial',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Installment::STATUS_PENDING => 'warning',
                        Installment::STATUS_PAID => 'success',
                        Installment::STATUS_OVERDUE => 'danger',
                        Installment::STATUS_PARTIALLY_PAID => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Installment::STATUS_PENDING => 'Pendiente',
                        Installment::STATUS_PAID => 'Pagada',
                        Installment::STATUS_OVERDUE => 'Vencida',
                        Installment::STATUS_PARTIALLY_PAID => 'Pago Parcial',
                    ]),
            ])
            ->defaultSort('installment_number')
            ->headerActions([
                // No create action - installments are auto-generated
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Cuota #' . $record->installment_number)
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Información de la Cuota')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('installment_number')
                                    ->label('Número de Cuota'),
                                \Filament\Infolists\Components\TextEntry::make('due_date')
                                    ->label('Fecha de Vencimiento')
                                    ->date('d/m/Y'),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        Installment::STATUS_PENDING => 'Pendiente',
                                        Installment::STATUS_PAID => 'Pagada',
                                        Installment::STATUS_OVERDUE => 'Vencida',
                                        Installment::STATUS_PARTIALLY_PAID => 'Pago Parcial',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        Installment::STATUS_PENDING => 'warning',
                                        Installment::STATUS_PAID => 'success',
                                        Installment::STATUS_OVERDUE => 'danger',
                                        Installment::STATUS_PARTIALLY_PAID => 'info',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(3),
                        \Filament\Infolists\Components\Section::make('Montos')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('amount')
                                    ->label('Monto Total de Cuota')
                                    ->money('GTQ'),
                                \Filament\Infolists\Components\TextEntry::make('principal_amount')
                                    ->label('Capital')
                                    ->money('GTQ'),
                                \Filament\Infolists\Components\TextEntry::make('interest_amount')
                                    ->label('Interés')
                                    ->money('GTQ'),
                                \Filament\Infolists\Components\TextEntry::make('paid_amount')
                                    ->label('Monto Pagado')
                                    ->money('GTQ'),
                                \Filament\Infolists\Components\TextEntry::make('balance_remaining')
                                    ->label('Saldo Pendiente')
                                    ->money('GTQ'),
                            ])
                            ->columns(2),
                        \Filament\Infolists\Components\Section::make('Mora')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('days_overdue')
                                    ->label('Días de Mora')
                                    ->suffix(' días'),
                                \Filament\Infolists\Components\TextEntry::make('late_fee')
                                    ->label('Cargo por Mora')
                                    ->money('GTQ'),
                            ])
                            ->columns(2)
                            ->visible(fn ($record) => $record->days_overdue > 0),
                        \Filament\Infolists\Components\Section::make('Fechas')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('paid_date')
                                    ->label('Fecha de Pago Completo')
                                    ->date('d/m/Y')
                                    ->placeholder('No pagada'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('Creada')
                                    ->dateTime('d/m/Y H:i'),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->emptyStateHeading('Sin cuotas')
            ->emptyStateDescription('Este préstamo no tiene un plan de cuotas. Las cuotas se generan automáticamente cuando se crea un préstamo con plan de cuotas.')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Only show this relation manager for installment plans
        return $ownerRecord->isInstallmentPlan();
    }
}
