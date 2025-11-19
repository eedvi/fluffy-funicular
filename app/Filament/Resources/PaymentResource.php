<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Seleccionar Préstamo')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->description('Seleccione el préstamo al que aplicar el pago')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('payment_number')
                                        ->label('Número de Pago')
                                        ->required()
                                        ->default(fn () => Payment::generatePaymentNumber())
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true)
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('loan_id')
                                        ->label('Préstamo')
                                        ->relationship('loan', 'loan_number', function (Builder $query) {
                                            return $query->whereIn('status', ['active', 'overdue', 'pending'])
                                                ->where('principal_remaining', '>', 0);
                                        })
                                        ->getOptionLabelFromRecordUsing(fn ($record) =>
                                            "{$record->loan_number} - {$record->customer->full_name} - Q" .
                                            number_format($record->total_amount, 2) .
                                            ($record->requires_minimum_payment ? " 💰" : "")
                                        )
                                        ->searchable(['loan_number'])
                                        ->required()
                                        ->preload()
                                        ->live()
                                        ->helperText('💰 = Requiere pago mínimo mensual')
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('branch_id')
                                        ->label('Sucursal')
                                        ->relationship('branch', 'name')
                                        ->preload()
                                        ->required()
                                        ->searchable()
                                        ->helperText('Sucursal donde se registra el pago')
                                        ->columnSpanFull(),
                                ]),
                            Forms\Components\Placeholder::make('loan_info')
                                ->label('')
                                ->content(function (Get $get) {
                                    $loanId = $get('loan_id');
                                    if (!$loanId) {
                                        return new \Illuminate\Support\HtmlString('<div class="rounded-lg bg-gray-50 dark:bg-gray-900/20 p-4 text-sm text-gray-600 dark:text-gray-400">Seleccione un préstamo para ver los detalles</div>');
                                    }

                                    $loan = \App\Models\Loan::find($loanId);
                                    if (!$loan) return null;

                                    $html = "<div class='space-y-3'>";

                                    // Información general del préstamo
                                    $html .= "<div class='rounded-lg bg-primary-50 dark:bg-primary-900/20 p-4'>";
                                    $html .= "<h4 class='font-semibold text-primary-600 dark:text-primary-400 mb-2'>📋 Información del Préstamo</h4>";
                                    $html .= "<div class='grid grid-cols-2 gap-2 text-sm text-gray-700 dark:text-gray-300'>";
                                    $html .= "<div><span class='text-gray-500'>Cliente:</span> <strong>{$loan->customer->full_name}</strong></div>";
                                    $html .= "<div><span class='text-gray-500'>Capital Original:</span> <strong>Q" . number_format($loan->loan_amount, 2) . "</strong></div>";
                                    $html .= "<div><span class='text-gray-500'>Capital Restante:</span> <strong>Q" . number_format($loan->principal_remaining, 2) . "</strong></div>";
                                    $html .= "<div><span class='text-gray-500'>Interés Acumulado:</span> <strong>Q" . number_format($loan->interest_amount, 2) . "</strong></div>";
                                    $html .= "<div class='col-span-2'><span class='text-gray-500'>Total a Pagar:</span> <strong class='text-lg text-success-600 dark:text-success-400'>Q" . number_format($loan->total_amount, 2) . "</strong></div>";
                                    $html .= "</div></div>";

                                    // Información de pago mínimo (si aplica)
                                    if ($loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0) {
                                        $bgColor = $loan->is_at_risk ? 'bg-red-50 dark:bg-red-900/20 border-red-300' : 'bg-warning-50 dark:bg-warning-900/20 border-warning-300';
                                        $textColor = $loan->is_at_risk ? 'text-red-600 dark:text-red-400' : 'text-warning-600 dark:text-warning-400';

                                        $html .= "<div class='rounded-lg {$bgColor} border p-4'>";
                                        $html .= "<h4 class='font-semibold {$textColor} mb-2'>💰 Pago Mínimo Mensual</h4>";
                                        $html .= "<div class='text-sm text-gray-700 dark:text-gray-300 space-y-1'>";
                                        $html .= "<p><strong>Pago Mínimo Requerido:</strong> Q" . number_format($loan->minimum_monthly_payment, 2) . "</p>";

                                        if ($loan->next_minimum_payment_date) {
                                            $isOverdue = $loan->isMinimumPaymentOverdue();
                                            $dateClass = $isOverdue ? 'text-red-600 font-bold' : '';
                                            $html .= "<p><strong>Próximo Pago Vence:</strong> <span class='{$dateClass}'>" . $loan->next_minimum_payment_date->format('d/m/Y');
                                            if ($isOverdue) $html .= " ⚠️ VENCIDO";
                                            $html .= "</span></p>";
                                        }

                                        if ($loan->is_at_risk) {
                                            $html .= "<p class='text-red-600 dark:text-red-400 font-semibold mt-2'>⚠️ PRÉSTAMO EN RIESGO</p>";
                                            $html .= "<p class='text-sm'>Período de gracia hasta: <strong>" .
                                                    ($loan->grace_period_end_date ? $loan->grace_period_end_date->format('d/m/Y') : 'N/A') .
                                                    "</strong></p>";
                                            $html .= "<p class='text-sm'>Pagos consecutivos perdidos: <strong>{$loan->consecutive_missed_payments}</strong></p>";
                                        }

                                        $html .= "</div></div>";
                                    }

                                    $html .= "</div>";
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ]),

                    Wizard\Step::make('Monto del Pago')
                        ->icon('heroicon-o-banknotes')
                        ->description('Ingrese el monto que el cliente pagará')
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Monto del Pago')
                                ->required()
                                ->numeric()
                                ->prefix('Q')
                                ->default(function (Get $get) {
                                    $loanId = $get('loan_id');
                                    if ($loanId) {
                                        $loan = \App\Models\Loan::find($loanId);
                                        if ($loan && $loan->requires_minimum_payment) {
                                            return $loan->minimum_monthly_payment;
                                        }
                                    }
                                    return 0;
                                })
                                ->minValue(0)
                                ->rules([
                                    function (Get $get) {
                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $loanId = $get('loan_id');
                                            if ($loanId && $value) {
                                                $loan = \App\Models\Loan::find($loanId);
                                                if ($loan && $value > $loan->total_amount) {
                                                    $fail("El monto (Q" . number_format($value, 2) . ") no puede exceder el total a pagar de Q" . number_format($loan->total_amount, 2) . " (Capital: Q" . number_format($loan->principal_remaining, 2) . " + Interés: Q" . number_format($loan->interest_amount, 2) . ")");
                                                }
                                            }
                                        };
                                    },
                                ])
                                ->live(onBlur: true)
                                ->columnSpanFull(),
                            Forms\Components\Placeholder::make('amount_info')
                                ->label('')
                                ->content(function (Get $get) {
                                    $loanId = $get('loan_id');
                                    $amount = (float) $get('amount');

                                    if (!$loanId) {
                                        return null;
                                    }

                                    $loan = \App\Models\Loan::find($loanId);
                                    if (!$loan) return null;

                                    $html = "<div class='space-y-3'>";

                                    // Desglose del préstamo
                                    $html .= "<div class='rounded-lg bg-info-50 dark:bg-info-900/20 p-4'>";
                                    $html .= "<h4 class='font-semibold text-info-600 dark:text-info-400 mb-2'>Desglose del Préstamo</h4>";
                                    $html .= "<div class='text-sm text-gray-700 dark:text-gray-300 space-y-1'>";
                                    $html .= "<p>Capital Restante: <strong>Q" . number_format($loan->principal_remaining, 2) . "</strong></p>";
                                    $html .= "<p>Interés Acumulado: <strong>Q" . number_format($loan->interest_amount, 2) . "</strong></p>";
                                    $html .= "<p>Total a Pagar: <strong class='text-lg'>Q" . number_format($loan->total_amount, 2) . "</strong></p>";
                                    $html .= "</div></div>";

                                    // Advertencias
                                    if ($loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0) {
                                        if ($amount > 0 && $amount < $loan->minimum_monthly_payment) {
                                            $html .= "<div class='rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 p-4'>";
                                            $html .= "<p class='text-sm text-red-600 dark:text-red-400 font-semibold'>⚠️ ADVERTENCIA</p>";
                                            $html .= "<p class='text-sm text-gray-700 dark:text-gray-300'>El monto de Q" . number_format($amount, 2) . " es menor que el pago mínimo requerido de Q" . number_format($loan->minimum_monthly_payment, 2) . "</p>";
                                            $html .= "</div>";
                                        }

                                        if ($loan->is_at_risk) {
                                            $html .= "<div class='rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 p-4'>";
                                            $html .= "<p class='text-sm text-red-600 dark:text-red-400 font-semibold'>⚠️ PRÉSTAMO EN RIESGO</p>";
                                            $html .= "<p class='text-sm text-gray-700 dark:text-gray-300'>Este préstamo tiene pagos vencidos. Se recomienda pagar al menos el pago mínimo para evitar confiscación.</p>";
                                            $html .= "</div>";
                                        }
                                    }

                                    $html .= "</div>";
                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ]),

                    Wizard\Step::make('Detalles del Pago')
                        ->icon('heroicon-o-credit-card')
                        ->description('Método de pago y referencias')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('payment_date')
                                        ->label('Fecha de Pago')
                                        ->required()
                                        ->default(now())
                                        ->displayFormat('d/m/Y'),
                                    Forms\Components\Select::make('payment_method')
                                        ->label('Método de Pago')
                                        ->required()
                                        ->options([
                                            'cash' => 'Efectivo',
                                            'card' => 'Tarjeta',
                                            'transfer' => 'Transferencia',
                                            'check' => 'Cheque',
                                        ])
                                        ->default('cash')
                                        ->native(false),
                                    Forms\Components\TextInput::make('reference_number')
                                        ->label('Número de Referencia')
                                        ->maxLength(100)
                                        ->placeholder('Ej: #123456, Cheque #7890')
                                        ->helperText('Número de transacción, cheque, autorización, etc.')
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('status')
                                        ->label('Estado del Pago')
                                        ->required()
                                        ->options([
                                            'completed' => 'Completado',
                                            'pending' => 'Pendiente',
                                            'cancelled' => 'Cancelado',
                                        ])
                                        ->default('completed')
                                        ->native(false)
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notas y Observaciones')
                                        ->rows(3)
                                        ->placeholder('Agregue cualquier información relevante sobre este pago...')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Wizard\Step::make('Revisión')
                        ->icon('heroicon-o-check-circle')
                        ->description('Revise todos los datos antes de guardar')
                        ->schema([
                            Forms\Components\Placeholder::make('review_info')
                                ->label('')
                                ->live()
                                ->content(function (Get $get) {
                                    $loanId = $get('loan_id');
                                    $amount = (float) $get('amount');
                                    $paymentMethod = match($get('payment_method')) {
                                        'cash' => 'Efectivo',
                                        'card' => 'Tarjeta',
                                        'transfer' => 'Transferencia',
                                        'check' => 'Cheque',
                                        default => 'N/A'
                                    };
                                    $status = match($get('status')) {
                                        'completed' => 'Completado',
                                        'pending' => 'Pendiente',
                                        'cancelled' => 'Cancelado',
                                        default => 'N/A'
                                    };
                                    $paymentDate = $get('payment_date') ? \Carbon\Carbon::parse($get('payment_date'))->format('d/m/Y') : 'N/A';
                                    $reference = $get('reference_number') ?? 'N/A';

                                    $html = "<div class='space-y-4'>";

                                    if ($loanId) {
                                        $loan = \App\Models\Loan::find($loanId);
                                        if ($loan) {
                                            $html .= "<div class='rounded-lg bg-primary-50 dark:bg-primary-900/20 p-4'>";
                                            $html .= "<h3 class='text-lg font-semibold text-primary-600 dark:text-primary-400 mb-3'>Resumen del Pago</h3>";
                                            $html .= "<div class='grid grid-cols-2 gap-3 text-sm'>";
                                            $html .= "<div class='col-span-2'><span class='text-gray-500 dark:text-gray-400'>Préstamo:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$loan->loan_number}</span></div>";
                                            $html .= "<div class='col-span-2'><span class='text-gray-500 dark:text-gray-400'>Cliente:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$loan->customer->full_name}</span></div>";
                                            $html .= "<div><span class='text-gray-500 dark:text-gray-400'>Monto del Pago:</span> <span class='ml-2 font-semibold text-success-600 dark:text-success-400 text-lg'>Q" . number_format($amount, 2) . "</span></div>";
                                            $html .= "<div><span class='text-gray-500 dark:text-gray-400'>Método:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$paymentMethod}</span></div>";
                                            $html .= "<div><span class='text-gray-500 dark:text-gray-400'>Fecha:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$paymentDate}</span></div>";
                                            $html .= "<div><span class='text-gray-500 dark:text-gray-400'>Estado:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$status}</span></div>";
                                            $html .= "<div class='col-span-2'><span class='text-gray-500 dark:text-gray-400'>Referencia:</span> <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$reference}</span></div>";
                                            $html .= "</div></div>";

                                            // Información del saldo del préstamo
                                            $newBalance = $loan->total_amount - $amount;
                                            $html .= "<div class='rounded-lg bg-success-50 dark:bg-success-900/20 p-4'>";
                                            $html .= "<h4 class='font-semibold text-success-600 dark:text-success-400 mb-2'>Saldo del Préstamo</h4>";
                                            $html .= "<div class='grid grid-cols-2 gap-2 text-sm text-gray-700 dark:text-gray-300'>";
                                            $html .= "<div>Saldo Actual: <strong>Q" . number_format($loan->total_amount, 2) . "</strong></div>";
                                            $html .= "<div>Monto a Pagar: <strong>Q" . number_format($amount, 2) . "</strong></div>";
                                            $html .= "<div class='col-span-2'>Nuevo Saldo: <strong class='text-lg'>Q" . number_format(max(0, $newBalance), 2) . "</strong></div>";
                                            $html .= "</div></div>";

                                            // Advertencias si aplica
                                            if ($loan->requires_minimum_payment && $amount > 0 && $amount < $loan->minimum_monthly_payment) {
                                                $html .= "<div class='rounded-lg bg-warning-50 dark:bg-warning-900/20 border border-warning-300 p-4'>";
                                                $html .= "<p class='text-sm text-warning-600 dark:text-warning-400 font-semibold'>⚠️ El monto es menor que el pago mínimo requerido (Q" . number_format($loan->minimum_monthly_payment, 2) . ")</p>";
                                                $html .= "</div>";
                                            }

                                            if ($newBalance <= 0) {
                                                $html .= "<div class='rounded-lg bg-success-50 dark:bg-success-900/20 border border-success-300 p-4'>";
                                                $html .= "<p class='text-sm text-success-600 dark:text-success-400 font-semibold'>🎉 Este pago liquidará completamente el préstamo</p>";
                                                $html .= "</div>";
                                            }
                                        }
                                    }

                                    $html .= "<div class='rounded-lg bg-info-50 dark:bg-info-900/20 p-4'>";
                                    $html .= "<p class='text-sm text-info-600 dark:text-info-400'>";
                                    $html .= "ℹ️ Revise toda la información antes de guardar. El saldo del préstamo se actualizará automáticamente.";
                                    $html .= "</p></div></div>";

                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ]),
                ])
                ->columnSpanFull()
                ->persistStepInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('Préstamo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name'])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'card' => 'warning',
                        'check' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
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

                // Imprimir Recibo
                Tables\Actions\Action::make('imprimir_recibo')
                    ->label('Imprimir Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Payment $record): string => route('pdf.payment-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
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
