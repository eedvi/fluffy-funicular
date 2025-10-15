<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterestChargeResource\Pages;
use App\Models\InterestCharge;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterestChargeResource extends Resource
{
    protected static ?string $model = InterestCharge::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Cargo de Interés';

    protected static ?string $pluralModelLabel = 'Cargos de Interés';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Préstamo')
                    ->schema([
                        Forms\Components\Select::make('loan_id')
                            ->label('Préstamo')
                            ->relationship('loan', 'loan_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $loan = Loan::find($state);
                                    if ($loan) {
                                        $set('principal_amount', $loan->current_balance ?? $loan->total_amount);
                                        $set('interest_rate', $loan->overdue_interest_rate ?? $loan->interest_rate);
                                        $set('balance_before', $loan->current_balance ?? $loan->total_amount);
                                    }
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->loan_number} - {$record->customer->first_name} {$record->customer->last_name}")
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\Placeholder::make('loan_details')
                            ->label('Detalles del Préstamo')
                            ->content(function ($get) {
                                $loanId = $get('loan_id');
                                if (!$loanId) {
                                    return 'Seleccione un préstamo para ver los detalles';
                                }

                                $loan = Loan::with(['customer', 'item'])->find($loanId);
                                if (!$loan) {
                                    return 'Préstamo no encontrado';
                                }

                                return sprintf(
                                    'Cliente: %s | Artículo: %s | Monto: Q%s | Balance Actual: Q%s | Vence: %s',
                                    $loan->customer->first_name . ' ' . $loan->customer->last_name,
                                    $loan->item->name ?? 'N/A',
                                    number_format($loan->loan_amount, 2),
                                    number_format($loan->current_balance ?? $loan->total_amount, 2),
                                    $loan->due_date ? $loan->due_date->format('d/m/Y') : 'N/A'
                                );
                            })
                            ->hidden(fn ($get) => !$get('loan_id')),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Detalles del Cargo')
                    ->schema([
                        Forms\Components\DatePicker::make('charge_date')
                            ->label('Fecha del Cargo')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\TextInput::make('days_overdue')
                            ->label('Días de Mora')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(365)
                            ->suffix('días'),

                        Forms\Components\Select::make('charge_type')
                            ->label('Tipo de Cargo')
                            ->options([
                                'daily' => 'Interés Diario',
                                'overdue' => 'Mora',
                                'penalty' => 'Penalidad',
                                'late_fee' => 'Cargo por Atraso',
                            ])
                            ->default('overdue')
                            ->required(),

                        Forms\Components\Toggle::make('is_applied')
                            ->label('¿Cargo Aplicado?')
                            ->default(true)
                            ->helperText('Indica si el cargo ya fue aplicado al balance del préstamo'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cálculo de Interés')
                    ->schema([
                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Tasa de Interés (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateInterest($get, $set)),

                        Forms\Components\TextInput::make('principal_amount')
                            ->label('Monto Principal')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateInterest($get, $set)),

                        Forms\Components\TextInput::make('interest_amount')
                            ->label('Monto de Interés')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->readOnly(),

                        Forms\Components\TextInput::make('balance_before')
                            ->label('Balance Antes')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => self::calculateBalanceAfter($get, $set)),

                        Forms\Components\TextInput::make('balance_after')
                            ->label('Balance Después')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->readOnly(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }

    protected static function calculateInterest(Forms\Get $get, Forms\Set $set): void
    {
        $principalAmount = (float) $get('principal_amount');
        $interestRate = (float) $get('interest_rate');
        $daysOverdue = (int) $get('days_overdue');

        if ($principalAmount > 0 && $interestRate > 0) {
            // Calculate daily interest: (principal × rate%) / 30
            $dailyInterest = ($principalAmount * $interestRate / 100) / 30;
            $totalInterest = $dailyInterest * max(1, $daysOverdue);

            $set('interest_amount', round($totalInterest, 2));
            self::calculateBalanceAfter($get, $set);
        }
    }

    protected static function calculateBalanceAfter(Forms\Get $get, Forms\Set $set): void
    {
        $balanceBefore = (float) $get('balance_before');
        $interestAmount = (float) $get('interest_amount');

        if ($balanceBefore >= 0 && $interestAmount >= 0) {
            $set('balance_after', round($balanceBefore + $interestAmount, 2));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('Préstamo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loan.customer.first_name')
                    ->label('Cliente')
                    ->formatStateUsing(fn ($record) => "{$record->loan->customer->first_name} {$record->loan->customer->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('charge_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('charge_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'info',
                        'overdue' => 'warning',
                        'penalty' => 'danger',
                        'late_fee' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Diario',
                        'overdue' => 'Mora',
                        'penalty' => 'Penalidad',
                        'late_fee' => 'Atraso',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Días Mora')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('interest_rate')
                    ->label('Tasa %')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('interest_amount')
                    ->label('Interés')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Balance Antes')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance Después')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_applied')
                    ->label('Aplicado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loan_id')
                    ->label('Préstamo')
                    ->relationship('loan', 'loan_number')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('charge_type')
                    ->label('Tipo de Cargo')
                    ->options([
                        'daily' => 'Interés Diario',
                        'overdue' => 'Mora',
                        'penalty' => 'Penalidad',
                        'late_fee' => 'Cargo por Atraso',
                    ]),

                Tables\Filters\Filter::make('is_applied')
                    ->label('Solo Aplicados')
                    ->query(fn (Builder $query): Builder => $query->where('is_applied', true)),

                Tables\Filters\Filter::make('charge_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('charge_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('charge_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('charge_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterestCharges::route('/'),
            'create' => Pages\CreateInterestCharge::route('/create'),
            'view' => Pages\ViewInterestCharge::route('/{record}'),
            'edit' => Pages\EditInterestCharge::route('/{record}/edit'),
        ];
    }
}
