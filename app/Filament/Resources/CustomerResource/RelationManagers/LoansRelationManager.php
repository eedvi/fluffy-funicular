<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    protected static ?string $title = 'Préstamos';

    protected static ?string $recordTitleAttribute = 'loan_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Préstamo')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('loan_number')
                                    ->label('Número de Préstamo')
                                    ->required()
                                    ->default(fn () => Loan::generateLoanNumber())
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('item_id')
                                    ->label('Artículo')
                                    ->relationship('item', 'name', function (Builder $query) {
                                        return $query->where('status', 'available')
                                            ->where('customer_id', $this->getOwnerRecord()->id)
                                            ->with(['branch', 'category']);
                                    })
                                    ->searchable(['name', 'description'])
                                    ->required()
                                    ->preload()
                                    ->helperText('Solo se muestran artículos disponibles del cliente'),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
                                    ->default(auth()->user()->branch_id)
                                    ->helperText('Sucursal donde se registra el préstamo'),
                            ]),
                    ]),

                Forms\Components\Section::make('Montos')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('loan_amount')
                                    ->label('Monto del Préstamo')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateLoanAmounts($get, $set);
                                    }),
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
                                        self::calculateLoanAmounts($get, $set);
                                    }),
                                Forms\Components\TextInput::make('interest_amount')
                                    ->label('Monto de Interés')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Monto Total')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('balance_remaining')
                                    ->label('Saldo Pendiente')
                                    ->numeric()
                                    ->prefix('Q')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Hidden::make('principal_remaining')
                                    ->default(fn (Get $get) => $get('loan_amount') ?? 0),
                                Forms\Components\Hidden::make('loan_term_days')
                                    ->default(null)
                                    ->dehydrated(),
                                Forms\Components\Hidden::make('due_date')
                                    ->default(null)
                                    ->dehydrated(),
                            ]),
                    ]),

                Forms\Components\Section::make('Fecha de Inicio')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio del Préstamo')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->helperText('El préstamo no tiene fecha de vencimiento. El cliente paga mensualmente hasta liquidar.'),
                    ]),

                Forms\Components\Section::make('Estado y Notas')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->options([
                                Loan::STATUS_PENDING => 'Pendiente',
                                Loan::STATUS_ACTIVE => 'Activo',
                                Loan::STATUS_PAID => 'Pagado',
                                Loan::STATUS_OVERDUE => 'Vencido',
                                Loan::STATUS_FORFEITED => 'Confiscado',
                            ])
                            ->default(Loan::STATUS_ACTIVE)
                            ->live()
                            ->native(false)
                            ->helperText('Las fechas de pago y confiscación se registran automáticamente'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Plan de Pago')
                    ->description('Elija el tipo de plan de pago para este préstamo.')
                    ->schema([
                        Forms\Components\Select::make('payment_plan_type')
                            ->label('Tipo de Plan')
                            ->required()
                            ->options([
                                'minimum_payment' => 'Pago Mínimo Mensual',
                                'installments' => 'Cuotas Fijas',
                            ])
                            ->default('minimum_payment')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateLoanAmounts($get, $set);
                            })
                            ->helperText('Pago Mínimo: el cliente paga mensualmente los intereses. Cuotas: se divide el préstamo en cuotas iguales.'),

                        // Sección de Pago Mínimo Mensual
                        Forms\Components\Section::make('Configuración de Pago Mínimo')
                            ->description('El cliente debe pagar mensualmente los intereses generados hasta liquidar la deuda.')
                            ->schema([
                                Forms\Components\Hidden::make('requires_minimum_payment')
                                    ->default(true)
                                    ->dehydrated(),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('minimum_monthly_payment')
                                            ->label('Pago Mínimo Mensual (Auto-calculado)')
                                            ->numeric()
                                            ->prefix('Q')
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Se calcula automáticamente como: Capital × Tasa de Interés'),
                                        Forms\Components\TextInput::make('grace_period_days')
                                            ->label('Días de Gracia')
                                            ->required()
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(0)
                                            ->maxValue(30)
                                            ->suffix('días')
                                            ->helperText('Días de tolerancia después de vencer el pago mensual'),
                                    ]),
                            ])
                            ->visible(fn (Get $get) => $get('payment_plan_type') === 'minimum_payment'),

                        // Sección de Cuotas
                        Forms\Components\Section::make('Configuración de Cuotas')
                            ->description('El préstamo se divide en cuotas iguales con amortización (capital + interés).')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('number_of_installments')
                                            ->label('Número de Cuotas')
                                            ->required()
                                            ->numeric()
                                            ->default(12)
                                            ->minValue(1)
                                            ->maxValue(60)
                                            ->suffix('cuotas')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set) {
                                                self::calculateInstallmentAmount($get, $set);
                                            })
                                            ->helperText('Total de cuotas en las que se dividirá el préstamo'),
                                        Forms\Components\TextInput::make('installment_frequency_days')
                                            ->label('Frecuencia de Pago')
                                            ->required()
                                            ->numeric()
                                            ->default(30)
                                            ->minValue(1)
                                            ->maxValue(90)
                                            ->suffix('días')
                                            ->helperText('Días entre cada cuota (30 = mensual)'),
                                        Forms\Components\TextInput::make('installment_amount')
                                            ->label('Monto de Cuota (Auto-calculado)')
                                            ->numeric()
                                            ->prefix('Q')
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Calculado usando sistema de amortización francesa'),
                                        Forms\Components\TextInput::make('late_fee_percentage')
                                            ->label('Porcentaje de Mora')
                                            ->required()
                                            ->numeric()
                                            ->default(5.00)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->helperText('Mora aplicada sobre el saldo pendiente por cuota vencida'),
                                    ]),
                            ])
                            ->visible(fn (Get $get) => $get('payment_plan_type') === 'installments'),
                    ]),
            ]);
    }

    protected static function calculateLoanAmounts(Get $get, Set $set): void
    {
        $loanAmount = (float) $get('loan_amount') ?: 0;
        $interestRate = (float) $get('interest_rate') ?: 0;

        $amounts = Loan::calculateLoanAmounts($loanAmount, $interestRate);

        $set('interest_amount', $amounts['interest_amount']);
        $set('total_amount', $amounts['total_amount']);
        $set('balance_remaining', $amounts['balance_remaining']);
        // Initialize principal_remaining with full loan amount
        $set('principal_remaining', $loanAmount);

        // SIEMPRE calcular el pago mínimo mensual (son los intereses)
        $minimumPayment = round($loanAmount * ($interestRate / 100), 2);
        $set('minimum_monthly_payment', $minimumPayment);

        // Si es plan de cuotas, calcular el monto de cuota
        if ($get('payment_plan_type') === 'installments') {
            self::calculateInstallmentAmount($get, $set);
        }
    }

    protected static function calculateInstallmentAmount(Get $get, Set $set): void
    {
        $loanAmount = (float) $get('loan_amount') ?: 0;
        $interestRate = (float) $get('interest_rate') ?: 0;
        $numberOfInstallments = (int) $get('number_of_installments') ?: 12;

        if ($loanAmount <= 0 || $numberOfInstallments <= 0) {
            $set('installment_amount', 0);
            return;
        }

        // Tasa mensual (asumiendo que interest_rate es anual)
        $monthlyRate = ($interestRate / 100) / 12;

        // Calcular cuota usando fórmula de amortización francesa
        // PMT = P * [r(1 + r)^n] / [(1 + r)^n - 1]
        if ($monthlyRate > 0) {
            $installmentAmount = $loanAmount *
                ($monthlyRate * pow(1 + $monthlyRate, $numberOfInstallments)) /
                (pow(1 + $monthlyRate, $numberOfInstallments) - 1);
        } else {
            // Si no hay interés, simplemente dividir el principal
            $installmentAmount = $loanAmount / $numberOfInstallments;
        }

        $set('installment_amount', round($installmentAmount, 2));
    }


    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('loan_number')
            ->columns([
                Tables\Columns\TextColumn::make('loan_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Artículo')
                    ->limit(30),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_remaining')
                    ->label('Saldo')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Loan::STATUS_PENDING => 'Pendiente',
                        Loan::STATUS_ACTIVE => 'Activo',
                        Loan::STATUS_PAID => 'Pagado',
                        Loan::STATUS_OVERDUE => 'Vencido',
                        Loan::STATUS_FORFEITED => 'Confiscado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Loan::STATUS_PENDING => 'gray',
                        Loan::STATUS_ACTIVE => 'success',
                        Loan::STATUS_PAID => 'info',
                        Loan::STATUS_OVERDUE => 'warning',
                        Loan::STATUS_FORFEITED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_at_risk')
                    ->label('Riesgo')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->is_at_risk ?
                        'Pago mínimo vencido - Gracia hasta: ' . ($record->grace_period_end_date?->format('d/m/Y') ?? 'N/A') :
                        'Al corriente con pagos mínimos')
                    ->visible(fn ($record) => $record->requires_minimum_payment ?? false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_full')
                    ->label('Ver Completo')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record): string => \App\Filament\Resources\LoanResource::getUrl('view', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
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
                                ->label('Monto del Préstamo')
                                ->money('GTQ')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('interest_rate')
                                ->label('Tasa de Interés')
                                ->suffix('%'),
                            Infolists\Components\TextEntry::make('interest_amount')
                                ->label('Interés')
                                ->money('GTQ'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Total a Pagar')
                                ->money('GTQ')
                                ->weight('bold'),
                            Infolists\Components\TextEntry::make('amount_paid')
                                ->label('Total Pagado')
                                ->money('GTQ')
                                ->color('success'),
                            Infolists\Components\TextEntry::make('balance_remaining')
                                ->label('Saldo Pendiente')
                                ->money('GTQ')
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
