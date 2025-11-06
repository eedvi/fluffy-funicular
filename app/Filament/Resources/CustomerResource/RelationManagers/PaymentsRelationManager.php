<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Historial de Pagos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pago')
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
                                    ->dehydrated(),
                                Forms\Components\Select::make('loan_id')
                                    ->label('Préstamo')
                                    ->relationship(
                                        'loan',
                                        'loan_number',
                                        fn (Builder $query) => $query
                                            ->where('customer_id', $this->getOwnerRecord()->id)
                                            ->whereIn('status', ['active', 'overdue', 'pending'])
                                            ->where('principal_remaining', '>', 0)
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                                        "{$record->loan_number} - Total: GTQ" . number_format($record->total_amount, 2) .
                                        " (Capital: GTQ" . number_format($record->principal_remaining, 2) .
                                        " + Interés: GTQ" . number_format($record->interest_amount, 2) . ")" .
                                        ($record->requires_minimum_payment ? " 💰" : "")
                                    )
                                    ->searchable(['loan_number'])
                                    ->required()
                                    ->preload()
                                    ->live()
                                    ->helperText('Solo se muestran préstamos con saldo pendiente del cliente. 💰 = Requiere pago mínimo'),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
                                    ->default(fn () => $this->getOwnerRecord()->branch_id)
                                    ->helperText('Sucursal donde se registra el pago'),
                            ]),
                    ]),

                Forms\Components\Section::make('Información de Pago Mínimo')
                    ->description('Este préstamo requiere pagos mínimos mensuales')
                    ->schema([
                        Forms\Components\Placeholder::make('minimum_payment_info')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $loanId = $get('loan_id');
                                if ($loanId) {
                                    $loan = \App\Models\Loan::find($loanId);
                                    if ($loan && $loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0) {
                                        $info = "💰 Pago mínimo mensual requerido: GTQ" . number_format($loan->minimum_monthly_payment, 2);

                                        if ($loan->next_minimum_payment_date) {
                                            $info .= "\n📅 Próximo pago vence: " . $loan->next_minimum_payment_date->format('d/m/Y');

                                            if ($loan->isMinimumPaymentOverdue()) {
                                                $info .= " ⚠️ VENCIDO";
                                            }
                                        }

                                        if ($loan->is_at_risk) {
                                            $info .= "\n⚠️ PRÉSTAMO EN RIESGO - Período de gracia hasta: " .
                                                    ($loan->grace_period_end_date ? $loan->grace_period_end_date->format('d/m/Y') : 'N/A');
                                            $info .= "\n❌ Pagos consecutivos perdidos: " . $loan->consecutive_missed_payments;
                                        }

                                        return new \Illuminate\Support\HtmlString('<div style="white-space: pre-line; padding: 12px; background-color: #fef3c7; border-radius: 6px; border: 1px solid #f59e0b; color: #92400e;">' . nl2br(htmlspecialchars($info)) . '</div>');
                                    }
                                }
                                return null;
                            }),
                    ])
                    ->visible(function (Forms\Get $get) {
                        $loanId = $get('loan_id');
                        if ($loanId) {
                            $loan = \App\Models\Loan::find($loanId);
                            return $loan && $loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0;
                        }
                        return false;
                    })
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Detalles del Pago')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Monto')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(function (Forms\Get $get) {
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
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $loanId = $get('loan_id');
                                                if ($loanId && $value) {
                                                    $loan = \App\Models\Loan::find($loanId);
                                                    if ($loan && $value > $loan->total_amount) {
                                                        $fail("El monto (GTQ" . number_format($value, 2) . ") no puede exceder el total a pagar de GTQ" . number_format($loan->total_amount, 2) . " (Capital: GTQ" . number_format($loan->principal_remaining, 2) . " + Interés: GTQ" . number_format($loan->interest_amount, 2) . ")");
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->helperText(function (Forms\Get $get) {
                                        $loanId = $get('loan_id');
                                        $amount = (float) $get('amount');

                                        if ($loanId) {
                                            $loan = \App\Models\Loan::find($loanId);
                                            if ($loan) {
                                                $text = "Total a pagar: GTQ" . number_format($loan->total_amount, 2) .
                                                       " (Capital: GTQ" . number_format($loan->principal_remaining, 2) .
                                                       " + Interés: GTQ" . number_format($loan->interest_amount, 2) . ")";

                                                // Add minimum payment info and warning if applicable
                                                if ($loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0) {
                                                    $text .= " | Pago mínimo: GTQ" . number_format($loan->minimum_monthly_payment, 2);

                                                    if ($amount > 0 && $amount < $loan->minimum_monthly_payment) {
                                                        $text .= " ⚠️ ADVERTENCIA: El monto es menor que el pago mínimo requerido";
                                                    }

                                                    if ($loan->is_at_risk) {
                                                        $text .= " | ⚠️ PRÉSTAMO EN RIESGO";
                                                    }
                                                }

                                                return $text;
                                            }
                                        }
                                        return null;
                                    })
                                    ->live(onBlur: true),
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
                            ]),
                    ]),

                Forms\Components\Section::make('Estado y Referencias')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Número de Referencia')
                                    ->maxLength(100)
                                    ->helperText('Número de transacción, cheque, etc.'),
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'completed' => 'Completado',
                                        'pending' => 'Pendiente',
                                        'cancelled' => 'Cancelado',
                                    ])
                                    ->default('completed')
                                    ->native(false),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Número de Pago')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('Préstamo')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.loans.edit', $record->loan_id)),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('GTQ')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Fecha de Pago')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'transfer' => 'warning',
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
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'check' => 'Cheque',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-asignar customer_id desde el loan seleccionado
                        // (ya está relacionado porque el loan es del cliente)
                        return $data;
                    })
                    ->successNotificationTitle('Pago Registrado Exitosamente')
                    ->after(function ($record) {
                        // El PaymentObserver se encarga de actualizar el balance del préstamo
                        // y potencialmente recalcular el credit score del cliente
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Imprimir Recibo
                Tables\Actions\Action::make('imprimir_recibo')
                    ->label('Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Payment $record): string => route('pdf.payment-receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Sin Pagos Registrados')
            ->emptyStateDescription('Este cliente aún no tiene pagos registrados. Registra el primer pago usando el botón de arriba.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Pago')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('payment_number')
                                ->label('Número de Pago'),
                            Infolists\Components\TextEntry::make('payment_date')
                                ->label('Fecha de Pago')
                                ->date('d/m/Y H:i'),
                            Infolists\Components\TextEntry::make('amount')
                                ->label('Monto')
                                ->money('GTQ')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight('bold'),
                        ])->columns(3),
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('payment_method')
                                ->label('Método de Pago')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'cash' => 'Efectivo',
                                    'card' => 'Tarjeta',
                                    'transfer' => 'Transferencia',
                                    'check' => 'Cheque',
                                    default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'cash' => 'success',
                                    'card' => 'info',
                                    'transfer' => 'warning',
                                    'check' => 'gray',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'completed' => 'Completado',
                                    'pending' => 'Pendiente',
                                    'cancelled' => 'Cancelado',
                                    default => $state,
                                })
                                ->color(fn (string $state): string => match ($state) {
                                    'completed' => 'success',
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('reference_number')
                                ->label('Número de Referencia')
                                ->placeholder('N/A'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Préstamo Relacionado')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan.loan_number')
                                ->label('Número de Préstamo')
                                ->url(fn ($record) => route('filament.admin.resources.loans.edit', $record->loan_id))
                                ->color('primary'),
                            Infolists\Components\TextEntry::make('loan.customer.full_name')
                                ->label('Cliente'),
                            Infolists\Components\TextEntry::make('loan.item.name')
                                ->label('Artículo'),
                        ])->columns(3),
                    ]),

                Infolists\Components\Section::make('Estado del Préstamo Después de este Pago')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('loan.total_amount')
                                ->label('Total del Préstamo')
                                ->money('GTQ'),
                            Infolists\Components\TextEntry::make('loan.amount_paid')
                                ->label('Total Pagado')
                                ->money('GTQ')
                                ->color('success'),
                            Infolists\Components\TextEntry::make('loan.balance_remaining')
                                ->label('Saldo Pendiente')
                                ->money('GTQ')
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                                ->weight('bold'),
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
