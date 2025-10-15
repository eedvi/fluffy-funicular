<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanRenewalResource\Pages;
use App\Models\Loan;
use App\Models\LoanRenewal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanRenewalResource extends Resource
{
    protected static ?string $model = LoanRenewal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?string $modelLabel = 'Renovación';

    protected static ?string $pluralModelLabel = 'Renovaciones de Préstamos';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Préstamo')
                    ->schema([
                        Forms\Components\Select::make('loan_id')
                            ->label('Préstamo')
                            ->relationship(
                                name: 'loan',
                                titleAttribute: 'loan_number',
                                modifyQueryUsing: function (Builder $query) {
                                    // Only show active or overdue loans that can be renewed
                                    // Load customer relationship for label display
                                    return $query
                                        ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                                        ->with('customer');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                if (!$record->customer) {
                                    return $record->loan_number . ' ($' . number_format($record->loan_amount, 2) . ')';
                                }
                                return $record->loan_number . ' - ' . $record->customer->full_name . ' ($' . number_format($record->loan_amount, 2) . ')';
                            })
                            ->searchable(['loan_number'])
                            ->required()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    $loan = Loan::find($state);
                                    if ($loan) {
                                        $set('previous_due_date', $loan->due_date);
                                        $set('interest_rate', $loan->interest_rate);
                                        self::calculateRenewalAmounts($get, $set);
                                    }
                                }
                            })
                            ->helperText(function () {
                                $count = Loan::whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])->count();
                                return "Seleccione el préstamo a renovar. Préstamos disponibles: {$count}";
                            }),

                        Forms\Components\Placeholder::make('loan_details')
                            ->label('Detalles del Préstamo')
                            ->content(function (Get $get) {
                                $loanId = $get('loan_id');
                                if (!$loanId) {
                                    return 'Seleccione un préstamo para ver los detalles';
                                }

                                $loan = Loan::with(['customer', 'item'])->find($loanId);
                                if (!$loan) {
                                    return 'Préstamo no encontrado';
                                }

                                return sprintf(
                                    'Cliente: %s | Artículo: %s | Monto: $%s | Saldo: $%s | Vence: %s',
                                    $loan->customer->full_name,
                                    $loan->item->name,
                                    number_format($loan->loan_amount, 2),
                                    number_format($loan->balance_remaining ?? $loan->total_amount, 2),
                                    $loan->due_date->format('d/m/Y')
                                );
                            })
                            ->hidden(fn (Get $get) => !$get('loan_id')),
                    ]),

                Forms\Components\Section::make('Detalles de la Renovación')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('previous_due_date')
                                    ->label('Fecha de Vencimiento Anterior')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->displayFormat('d/m/Y'),

                                Forms\Components\TextInput::make('extension_days')
                                    ->label('Días de Extensión')
                                    ->required()
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->suffix('días')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateNewDueDate($get, $set);
                                        self::calculateRenewalAmounts($get, $set);
                                    }),

                                Forms\Components\DatePicker::make('new_due_date')
                                    ->label('Nueva Fecha de Vencimiento')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->displayFormat('d/m/Y'),
                            ]),
                    ]),

                Forms\Components\Section::make('Costos de Renovación')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('interest_rate')
                                    ->label('Tasa de Interés (%)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(10)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateRenewalAmounts($get, $set);
                                    }),

                                Forms\Components\TextInput::make('interest_amount')
                                    ->label('Monto de Interés')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Calculado automáticamente'),

                                Forms\Components\TextInput::make('renewal_fee')
                                    ->label('Comisión por Renovación')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Opcional: cargo adicional por renovación'),
                            ]),
                    ]),

                Forms\Components\Section::make('Notas y Procesamiento')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Observaciones sobre la renovación'),

                        Forms\Components\Hidden::make('processed_by')
                            ->default(auth()->id()),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('Préstamo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loan.customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('previous_due_date')
                    ->label('Vencimiento Anterior')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('new_due_date')
                    ->label('Nuevo Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('extension_days')
                    ->label('Días Extendidos')
                    ->numeric()
                    ->sortable()
                    ->suffix(' días'),

                Tables\Columns\TextColumn::make('interest_amount')
                    ->label('Interés')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('renewal_fee')
                    ->label('Comisión')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Costo Total')
                    ->getStateUsing(fn ($record) => $record->interest_amount + $record->renewal_fee)
                    ->money('USD')
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Procesado Por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Renovación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loan_id')
                    ->label('Préstamo')
                    ->relationship('loan', 'loan_number')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLoanRenewals::route('/'),
            'create' => Pages\CreateLoanRenewal::route('/create'),
            'view' => Pages\ViewLoanRenewal::route('/{record}'),
            'edit' => Pages\EditLoanRenewal::route('/{record}/edit'),
        ];
    }

    // Helper methods for calculations

    protected static function calculateNewDueDate(Get $get, Set $set): void
    {
        $previousDueDate = $get('previous_due_date');
        $extensionDays = $get('extension_days');

        if ($previousDueDate && $extensionDays) {
            $newDueDate = \Carbon\Carbon::parse($previousDueDate)->addDays($extensionDays);
            $set('new_due_date', $newDueDate->format('Y-m-d'));
        }
    }

    protected static function calculateRenewalAmounts(Get $get, Set $set): void
    {
        $loanId = $get('loan_id');
        $interestRate = $get('interest_rate');
        $extensionDays = $get('extension_days');

        if (!$loanId || !$interestRate || !$extensionDays) {
            return;
        }

        $loan = Loan::find($loanId);
        if (!$loan) {
            return;
        }

        // Validate loan_term_days to prevent division by zero
        if (!$loan->loan_term_days || $loan->loan_term_days <= 0) {
            // Fallback: use 30 days as default or calculate based on dates
            $defaultTermDays = 30;
            if ($loan->start_date && $loan->due_date) {
                $defaultTermDays = $loan->start_date->diffInDays($loan->due_date) ?: 30;
            }
            $loan->loan_term_days = $defaultTermDays;
        }

        // Calculate interest based on loan amount and extension period
        // Formula: (loan_amount * interest_rate / 100) * (extension_days / loan_term_days)
        $dailyInterestRate = ($loan->loan_amount * $interestRate / 100) / $loan->loan_term_days;
        $interestAmount = $dailyInterestRate * $extensionDays;

        $set('interest_amount', round($interestAmount, 2));
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
