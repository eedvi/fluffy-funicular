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
                                            ->where('balance_remaining', '>', 0)
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                                        "{$record->loan_number} - Saldo: Q" . number_format($record->balance_remaining, 2)
                                    )
                                    ->searchable(['loan_number'])
                                    ->required()
                                    ->preload()
                                    ->helperText('Solo se muestran préstamos con saldo pendiente del cliente'),
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

                Forms\Components\Section::make('Detalles del Pago')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Monto')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Q')
                                    ->default(0)
                                    ->minValue(0)
                                    ->rules([
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $loanId = $get('loan_id');
                                                if ($loanId && $value) {
                                                    $loan = \App\Models\Loan::find($loanId);
                                                    if ($loan && $value > $loan->balance_remaining) {
                                                        $fail("El monto (Q" . number_format($value, 2) . ") no puede exceder el saldo pendiente de Q" . number_format($loan->balance_remaining, 2));
                                                    }
                                                }
                                            };
                                        },
                                    ]),
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
