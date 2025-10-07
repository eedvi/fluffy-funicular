<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
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
                                    ->relationship('loan', 'loan_number')
                                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                                        "{$record->loan_number} - {$record->customer->full_name}"
                                    )
                                    ->searchable(['loan_number'])
                                    ->required()
                                    ->preload(),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->required()
                                    ->searchable()
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
                                    ->prefix('$')
                                    ->default(0)
                                    ->minValue(0)
                                    ->rules([
                                        function (Forms\Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $loanId = $get('loan_id');
                                                if ($loanId && $value) {
                                                    $loan = \App\Models\Loan::find($loanId);
                                                    if ($loan && $value > $loan->balance_remaining) {
                                                        $fail("El monto ($" . number_format($value, 2) . ") no puede exceder el saldo pendiente de $" . number_format($loan->balance_remaining, 2));
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
                                        'Efectivo' => 'Efectivo',
                                        'Transferencia' => 'Transferencia',
                                        'Tarjeta de Débito' => 'Tarjeta de Débito',
                                        'Tarjeta de Crédito' => 'Tarjeta de Crédito',
                                        'Cheque' => 'Cheque',
                                        'Otro' => 'Otro',
                                    ])
                                    ->default('Efectivo')
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
                                        'Completado' => 'Completado',
                                        'Pendiente' => 'Pendiente',
                                        'Rechazado' => 'Rechazado',
                                        'Cancelado' => 'Cancelado',
                                    ])
                                    ->default('Completado')
                                    ->native(false),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                    ->money('USD')
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
                        'Efectivo' => 'success',
                        'Transferencia' => 'info',
                        'Tarjeta de Débito', 'Tarjeta de Crédito' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completado' => 'success',
                        'Pendiente' => 'warning',
                        'Rechazado' => 'danger',
                        'Cancelado' => 'gray',
                        default => 'gray',
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
