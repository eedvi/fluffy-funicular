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
                                    ->live()
                                    ->native(false),
                                Forms\Components\DatePicker::make('forfeited_date')
                                    ->label('Fecha de Confiscación')
                                    ->displayFormat('d/m/Y')
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
                Tables\Actions\Action::make('registrar_pago')
                    ->label('Pago')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Loan $record): bool =>
                        in_array($record->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_PENDING])
                        && $record->balance_remaining > 0
                    )
                    ->url(fn (Loan $record): string =>
                        \App\Filament\Resources\PaymentResource::getUrl('create', [
                            'loan_id' => $record->id,
                        ])
                    ),
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
