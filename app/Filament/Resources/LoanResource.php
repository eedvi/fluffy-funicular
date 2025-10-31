<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Models\Payment;
use Filament\Forms;
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
                                Forms\Components\Select::make('customer_id')
                                    ->label('Cliente')
                                    ->relationship('customer', 'first_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable(['first_name', 'last_name'])
                                    ->required()
                                    ->preload(),
                                Forms\Components\Select::make('item_id')
                                    ->label('Artículo')
                                    ->relationship('item', 'name', function (Builder $query) {
                                        return $query->where('status', 'available')
                                            ->with(['branch', 'category']);
                                    })
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        return $record->name . ' - ' .
                                               ($record->category?->name ?? 'Sin categoría') .
                                               ' ($' . number_format($record->appraised_value, 2) . ')';
                                    })
                                    ->searchable(['name', 'description'])
                                    ->required()
                                    ->preload()
                                    ->helperText('Solo se muestran artículos disponibles'),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
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
                                Forms\Components\TextInput::make('loan_term_days')
                                    ->label('Plazo (días)')
                                    ->required()
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(1)
                                    ->suffix('días')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateDueDate($get, $set);
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
                            ]),
                    ]),

                Forms\Components\Section::make('Fechas')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateDueDate($get, $set);
                                    }),
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Fecha de Vencimiento')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\DatePicker::make('paid_date')
                                    ->label('Fecha de Pago')
                                    ->displayFormat('d/m/Y'),
                            ]),
                    ]),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Grid::make(2)
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
                                    ->native(false),
                                Forms\Components\DatePicker::make('forfeited_date')
                                    ->label('Fecha de Confiscación')
                                    ->displayFormat('d/m/Y'),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
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
    }

    protected static function calculateDueDate(Get $get, Set $set): void
    {
        $startDate = $get('start_date');
        $loanTermDays = (int) $get('loan_term_days') ?: 30;

        if ($startDate) {
            $dueDate = Loan::calculateDueDate($startDate, $loanTermDays);
            $set('due_date', $dueDate);
        }
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

                // Registrar Pago Rápido
                Tables\Actions\Action::make('pago_rapido')
                    ->label('Registrar Pago Rápido')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Loan $record): bool => in_array($record->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]))
                    ->form(fn (Loan $record) => [
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->required()
                            ->numeric()
                            ->prefix('Q')
                            ->default($record->balance_remaining)
                            ->minValue(0.01),
                        Forms\Components\Select::make('payment_method')
                            ->label('Método de Pago')
                            ->required()
                            ->options([
                                'cash' => 'Efectivo',
                                'transfer' => 'Transferencia',
                                'card' => 'Tarjeta',
                                'check' => 'Cheque',
                            ])
                            ->default('cash')
                            ->native(false),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Fecha de Pago')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),
                    ])
                    ->action(function (Loan $record, array $data): void {
                        \DB::transaction(function () use ($record, $data) {
                            // Create payment record
                            Payment::create([
                                'loan_id' => $record->id,
                                'payment_number' => Payment::generatePaymentNumber(),
                                'amount' => $data['amount'],
                                'payment_date' => $data['payment_date'],
                                'payment_method' => $data['payment_method'],
                                'status' => 'completed',
                                'branch_id' => $record->branch_id,
                            ]);

                            // Update loan balance
                            $newBalance = $record->balance_remaining - $data['amount'];

                            $updateData = [
                                'balance_remaining' => max(0, $newBalance),
                            ];

                            // If fully paid, update status and paid_date
                            if ($newBalance <= 0) {
                                $updateData['status'] = Loan::STATUS_PAID;
                                $updateData['paid_date'] = now();

                                // Return item to available
                                if ($record->item) {
                                    $record->item->update(['status' => 'available']);
                                }
                            }

                            $record->update($updateData);

                            Notification::make()
                                ->success()
                                ->title('Pago Registrado')
                                ->body("El pago de $" . number_format($data['amount'], 2) . " ha sido registrado exitosamente.")
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
