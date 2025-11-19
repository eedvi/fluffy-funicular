<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?string $modelLabel = 'Préstamo';

    protected static ?string $pluralModelLabel = 'Préstamos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Información Básica')
                        ->icon('heroicon-o-document-text')
                        ->description('Seleccione el cliente, artículo y sucursal')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('loan_number')
                                        ->label('Número de Préstamo')
                                        ->required()
                                        ->default(fn () => Loan::generateLoanNumber())
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true)
                                        ->disabled()
                                        ->dehydrated(),
                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('Fecha de Inicio')
                                        ->required()
                                        ->default(now())
                                        ->displayFormat('d/m/Y')
                                        ->helperText('Fecha en que se otorga el préstamo'),
                                    Forms\Components\Select::make('customer_id')
                                        ->label('Cliente')
                                        ->relationship('customer', 'first_name')
                                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                        ->searchable(['first_name', 'last_name'])
                                        ->required()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('item_id', null))
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('item_id')
                                        ->label('Artículo en Garantía')
                                        ->options(function (Get $get) {
                                            $customerId = $get('customer_id');
                                            if (!$customerId) {
                                                return [];
                                            }
                                            return \App\Models\Item::where('status', 'available')
                                                ->where('customer_id', $customerId)
                                                ->with(['category'])
                                                ->get()
                                                ->mapWithKeys(function ($item) {
                                                    return [$item->id => $item->name . ' - ' .
                                                           ($item->category?->name ?? 'Sin categoría') .
                                                           ' (Q' . number_format($item->appraised_value, 2) . ')'];
                                                });
                                        })
                                        ->searchable()
                                        ->required()
                                        ->helperText(fn (Get $get) => $get('customer_id')
                                            ? 'Solo se muestran artículos disponibles del cliente seleccionado'
                                            : 'Primero seleccione un cliente')
                                        ->disabled(fn (Get $get) => !$get('customer_id'))
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('branch_id')
                                        ->label('Sucursal')
                                        ->relationship('branch', 'name')
                                        ->preload()
                                        ->required()
                                        ->searchable()
                                        ->helperText('Sucursal donde se registra el préstamo')
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columns(2),

                    Wizard\Step::make('Montos del Préstamo')
                        ->icon('heroicon-o-currency-dollar')
                        ->description('Configure el capital y la tasa de interés')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('loan_amount')
                                        ->label('Capital del Préstamo')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Q')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::calculateLoanAmounts($get, $set);
                                        })
                                        ->helperText('Monto principal que se otorga al cliente'),
                                    Forms\Components\TextInput::make('interest_rate')
                                        ->label('Tasa de Interés Mensual (%)')
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
                                ]),
                            Forms\Components\Section::make('Cálculos Automáticos')
                                ->description('Estos valores se calculan automáticamente')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('interest_amount')
                                                ->label('Interés Mensual')
                                                ->required()
                                                ->numeric()
                                                ->prefix('Q')
                                                ->default(0)
                                                ->disabled()
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('total_amount')
                                                ->label('Total Préstamo + Interés')
                                                ->required()
                                                ->numeric()
                                                ->prefix('Q')
                                                ->default(0)
                                                ->disabled()
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('balance_remaining')
                                                ->label('Saldo Pendiente Inicial')
                                                ->numeric()
                                                ->prefix('Q')
                                                ->disabled()
                                                ->dehydrated(),
                                        ]),
                                ])
                                ->collapsible(),
                            Forms\Components\Hidden::make('principal_remaining')
                                ->default(fn (Get $get) => $get('loan_amount') ?? 0),
                            Forms\Components\Hidden::make('loan_term_days')
                                ->default(null)
                                ->dehydrated(),
                            Forms\Components\Hidden::make('due_date')
                                ->default(null)
                                ->dehydrated(),
                        ]),

                    Wizard\Step::make('Plan de Pago')
                        ->icon('heroicon-o-calendar')
                        ->description('Elija cómo el cliente pagará el préstamo')
                        ->schema([
                            Forms\Components\Select::make('payment_plan_type')
                                ->label('Tipo de Plan de Pago')
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
                        ]),

                    Wizard\Step::make('Configuración del Plan')
                        ->icon('heroicon-o-cog')
                        ->description('Configure los detalles del plan seleccionado')
                        ->schema([
                            // Configuración de Pago Mínimo
                            Forms\Components\Section::make('Configuración de Pago Mínimo Mensual')
                                ->description('El cliente debe pagar mensualmente los intereses generados. El capital se puede liquidar en cualquier momento.')
                                ->icon('heroicon-o-banknotes')
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
                                                ->helperText('Cálculo: Capital × Tasa de Interés')
                                                ->columnSpanFull(),
                                            Forms\Components\TextInput::make('grace_period_days')
                                                ->label('Días de Gracia')
                                                ->required()
                                                ->numeric()
                                                ->default(5)
                                                ->minValue(0)
                                                ->maxValue(30)
                                                ->suffix('días')
                                                ->helperText('Días de tolerancia después del vencimiento del pago mensual'),
                                        ]),
                                ])
                                ->visible(fn (Get $get) => $get('payment_plan_type') === 'minimum_payment'),

                            // Configuración de Cuotas
                            Forms\Components\Section::make('Configuración de Cuotas Fijas')
                                ->description('El préstamo se divide en cuotas iguales usando el sistema de amortización francesa (capital + interés).')
                                ->icon('heroicon-o-calculator')
                                ->schema([
                                    Forms\Components\Grid::make(2)
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
                                                ->helperText('Total de cuotas para pagar el préstamo'),
                                            Forms\Components\TextInput::make('installment_frequency_days')
                                                ->label('Frecuencia de Pago')
                                                ->required()
                                                ->numeric()
                                                ->default(30)
                                                ->minValue(1)
                                                ->maxValue(90)
                                                ->suffix('días')
                                                ->helperText('Días entre cada cuota (30 = mensual, 15 = quincenal)'),
                                            Forms\Components\TextInput::make('installment_amount')
                                                ->label('Monto de Cada Cuota (Auto-calculado)')
                                                ->numeric()
                                                ->prefix('Q')
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText('Calculado con sistema de amortización francesa')
                                                ->columnSpanFull(),
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

                    Wizard\Step::make('Estado y Notas')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->description('Configure el estado inicial y agregue notas')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Estado del Préstamo')
                                ->required()
                                ->options([
                                    Loan::STATUS_PENDING => 'Pendiente',
                                    Loan::STATUS_ACTIVE => 'Activo',
                                    Loan::STATUS_PAID => 'Pagado',
                                    Loan::STATUS_OVERDUE => 'Vencido',
                                    Loan::STATUS_FORFEITED => 'Confiscado',
                                ])
                                ->default(Loan::STATUS_ACTIVE)
                                ->native(false)
                                ->helperText('Las fechas de pago y confiscación se registran automáticamente'),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notas y Observaciones')
                                ->rows(4)
                                ->placeholder('Agregue cualquier nota relevante sobre este préstamo...')
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Revisión')
                        ->icon('heroicon-o-check-circle')
                        ->description('Revise todos los datos antes de guardar')
                        ->schema([
                            Forms\Components\Placeholder::make('review_info')
                                ->label('')
                                ->live()
                                ->content(function (Get $get) {
                                    $loanAmount = number_format((float) $get('loan_amount'), 2);
                                    $interestRate = $get('interest_rate');
                                    $interestAmount = number_format((float) $get('interest_amount'), 2);
                                    $totalAmount = number_format((float) $get('total_amount'), 2);
                                    $planType = $get('payment_plan_type') === 'minimum_payment' ? 'Pago Mínimo Mensual' : 'Cuotas Fijas';

                                    $html = "
                                    <div class='space-y-4'>
                                        <div class='rounded-lg bg-primary-50 dark:bg-primary-900/20 p-4'>
                                            <h3 class='text-lg font-semibold text-primary-600 dark:text-primary-400 mb-3'>Resumen del Préstamo</h3>

                                            <div class='grid grid-cols-2 gap-3 text-sm'>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Capital:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>Q {$loanAmount}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Tasa de Interés:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$interestRate}%</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Interés Mensual:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>Q {$interestAmount}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Total:</span>
                                                    <span class='ml-2 font-semibold text-success-600 dark:text-success-400'>Q {$totalAmount}</span>
                                                </div>
                                                <div class='col-span-2'>
                                                    <span class='text-gray-500 dark:text-gray-400'>Plan de Pago:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$planType}</span>
                                                </div>
                                            </div>
                                        </div>";

                                    if ($get('payment_plan_type') === 'minimum_payment') {
                                        $minPayment = number_format((float) $get('minimum_monthly_payment'), 2);
                                        $graceDays = $get('grace_period_days');
                                        $html .= "
                                        <div class='rounded-lg bg-warning-50 dark:bg-warning-900/20 p-4'>
                                            <h4 class='font-semibold text-warning-600 dark:text-warning-400 mb-2'>Pago Mínimo Mensual</h4>
                                            <p class='text-sm text-gray-600 dark:text-gray-300'>
                                                El cliente debe pagar <strong>Q {$minPayment}</strong> mensualmente.
                                                <br>Días de gracia: <strong>{$graceDays} días</strong>
                                            </p>
                                        </div>";
                                    } else {
                                        $numInstallments = $get('number_of_installments');
                                        $installmentAmount = number_format((float) $get('installment_amount'), 2);
                                        $frequency = $get('installment_frequency_days');
                                        $html .= "
                                        <div class='rounded-lg bg-success-50 dark:bg-success-900/20 p-4'>
                                            <h4 class='font-semibold text-success-600 dark:text-success-400 mb-2'>Plan de Cuotas Fijas</h4>
                                            <p class='text-sm text-gray-600 dark:text-gray-300'>
                                                <strong>{$numInstallments} cuotas</strong> de <strong>Q {$installmentAmount}</strong> c/u
                                                <br>Frecuencia: cada <strong>{$frequency} días</strong>
                                            </p>
                                        </div>";
                                    }

                                    $html .= "
                                        <div class='rounded-lg bg-info-50 dark:bg-info-900/20 p-4'>
                                            <p class='text-sm text-info-600 dark:text-info-400'>
                                                ℹ️ Revise toda la información antes de guardar. Podrá editar el préstamo posteriormente si es necesario.
                                            </p>
                                        </div>
                                    </div>";

                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ]),
                ])
                ->columnSpanFull()
                ->persistStepInQueryString(),
            ]);
    }

    protected static function calculateLoanAmounts(Get $get, Set $set): void
    {
        $loanAmount = (float) $get('loan_amount') ?: 0;
        $interestRate = (float) $get('interest_rate') ?: 0;

        $amounts = Loan::calculateLoanAmounts($loanAmount, $interestRate);

        $set('interest_amount', $amounts['interest_amount']);
        $set('total_amount', $amounts['total_amount']);
        // For new loans, balance_remaining should be the loan_amount (capital only)
        $set('balance_remaining', $loanAmount);
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

        // Tasa mensual (la tasa ingresada ya es mensual)
        $monthlyRate = $interestRate / 100;

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


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Capital Original')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('principal_remaining')
                    ->label('Capital Restante')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('interest_amount')
                    ->label('Interés Acumulado')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total a Pagar')
                    ->money('GTQ')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Total Pagado')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->searchable()
                    ->visible(fn () => auth()->user()->can('view_all_branches')),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Imprimir Contrato
                Tables\Actions\Action::make('imprimir_contrato')
                    ->label('Contrato')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn (Loan $record): string => route('pdf.loan-contract', $record))
                    ->openUrlInNewTab(),

                // Imprimir Recibo
                Tables\Actions\Action::make('imprimir_recibo')
                    ->label('Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Loan $record): string => route('pdf.loan-receipt', $record))
                    ->openUrlInNewTab(),

                // Renovar Préstamo
                Tables\Actions\Action::make('renovar')
                    ->label('Renovar Préstamo')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Loan $record): bool => in_array($record->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]))
                    ->form([
                        Forms\Components\Hidden::make('loan_amount')
                            ->default(fn (Loan $record) => $record->loan_amount),
                        Forms\Components\Hidden::make('current_due_date')
                            ->default(fn (Loan $record) => $record->due_date),
                        Forms\Components\TextInput::make('extension_days')
                            ->label('Días de Extensión')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->suffix('días')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $extensionDays = (int) $get('extension_days') ?: 30;
                                $currentDueDate = $get('current_due_date');
                                if ($currentDueDate) {
                                    $newDueDate = \Carbon\Carbon::parse($currentDueDate)->addDays($extensionDays);
                                    $set('new_due_date', $newDueDate->format('Y-m-d'));
                                }
                            }),
                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Tasa de Interés (%)')
                            ->required()
                            ->numeric()
                            ->default(fn (Loan $record) => $record->interest_rate)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $loanAmount = (float) $get('loan_amount');
                                $interestRate = (float) $get('interest_rate') ?: 0;
                                $interestAmount = $loanAmount * ($interestRate / 100);
                                $set('interest_amount', round($interestAmount, 2));
                            }),
                        Forms\Components\TextInput::make('renewal_fee')
                            ->label('Cargo por Renovación')
                            ->required()
                            ->numeric()
                            ->prefix('Q')
                            ->default(0)
                            ->minValue(0),
                        Forms\Components\TextInput::make('interest_amount')
                            ->label('Monto de Interés')
                            ->required()
                            ->numeric()
                            ->prefix('Q')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn (Loan $record) => round($record->loan_amount * ($record->interest_rate / 100), 2)),
                        Forms\Components\DatePicker::make('new_due_date')
                            ->label('Nueva Fecha de Vencimiento')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->displayFormat('d/m/Y')
                            ->default(fn (Loan $record) => \Carbon\Carbon::parse($record->due_date)->addDays(30)),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        \DB::transaction(function () use ($record, $data) {
                            $previousDueDate = $record->due_date;
                            $extensionDays = (int) $data['extension_days'];
                            $interestRate = (float) $data['interest_rate'];
                            $renewalFee = (float) $data['renewal_fee'];
                            $interestAmount = (float) $data['interest_amount'];
                            $newDueDate = \Carbon\Carbon::parse($record->due_date)->addDays($extensionDays);

                            // Calculate total charges
                            $totalCharges = $interestAmount + $renewalFee;

                            // Create loan renewal record
                            \App\Models\LoanRenewal::create([
                                'loan_id' => $record->id,
                                'previous_due_date' => $previousDueDate,
                                'new_due_date' => $newDueDate,
                                'extension_days' => $extensionDays,
                                'renewal_fee' => $renewalFee,
                                'interest_rate' => $interestRate,
                                'interest_amount' => $interestAmount,
                                'notes' => $data['notes'] ?? null,
                                'processed_by' => auth()->id(),
                            ]);

                            // Update loan
                            $record->update([
                                'due_date' => $newDueDate,
                                'interest_amount' => $record->interest_amount + $interestAmount,
                                'total_amount' => $record->total_amount + $totalCharges,
                                'balance_remaining' => $record->balance_remaining + $totalCharges,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Préstamo Renovado')
                                ->body("El préstamo {$record->loan_number} ha sido renovado exitosamente. Nueva fecha de vencimiento: " . $newDueDate->format('d/m/Y'))
                                ->send();
                        });
                    }),

                // Confiscar Artículo
                Tables\Actions\Action::make('confiscar')
                    ->label('Confiscar Artículo')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->visible(fn (Loan $record): bool => $record->status === Loan::STATUS_OVERDUE)
                    ->requiresConfirmation()
                    ->modalHeading('Confiscar Artículo')
                    ->modalDescription('¿Está seguro de que desea confiscar este artículo? Esta acción actualizará el estado del préstamo a "Confiscado" y el artículo asociado también será marcado como confiscado.')
                    ->form([
                        Forms\Components\Textarea::make('confiscation_notes')
                            ->label('Notas de Confiscación')
                            ->rows(3)
                            ->helperText('Opcional: Agregue detalles sobre el proceso de confiscación'),
                        Forms\Components\TextInput::make('auction_price')
                            ->label('Precio de Subasta (Opcional)')
                            ->numeric()
                            ->prefix('Q')
                            ->helperText('Precio estimado para la subasta del artículo'),
                        Forms\Components\DatePicker::make('auction_date')
                            ->label('Fecha de Subasta (Opcional)')
                            ->displayFormat('d/m/Y')
                            ->helperText('Fecha programada para la subasta'),
                    ])
                    ->modalSubmitActionLabel('Sí, Confiscar')
                    ->action(function (Loan $record, array $data): void {
                        \DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => Loan::STATUS_FORFEITED,
                                'forfeited_date' => now(),
                            ]);

                            if ($record->item) {
                                $record->item->update([
                                    'status' => 'forfeited',
                                    'confiscated_date' => now(),
                                    'confiscation_notes' => $data['confiscation_notes'] ?? null,
                                    'auction_price' => $data['auction_price'] ?? null,
                                    'auction_date' => $data['auction_date'] ?? null,
                                ]);
                            }

                            Notification::make()
                                ->warning()
                                ->title('Artículo Confiscado')
                                ->body("El artículo del préstamo {$record->loan_number} ha sido confiscado.")
                                ->send();
                        });
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

                    // Marcar como Vencido
                    Tables\Actions\BulkAction::make('marcar_vencido')
                        ->label('Marcar como Vencido')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Marcar Préstamos como Vencidos')
                        ->modalDescription('¿Está seguro de que desea marcar los préstamos seleccionados como vencidos?')
                        ->modalSubmitActionLabel('Sí, Marcar como Vencido')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === Loan::STATUS_ACTIVE) {
                                    $record->update(['status' => Loan::STATUS_OVERDUE]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Préstamos Actualizados')
                                ->body("{$count} préstamo(s) han sido marcados como vencidos.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionHistoryRelationManager::class,
            RelationManagers\InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'view' => Pages\ViewLoan::route('/{record}'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
