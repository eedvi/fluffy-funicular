<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Services\CreditScoreService;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gestión';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Información Personal')
                        ->icon('heroicon-o-user')
                        ->description('Datos personales del cliente')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')
                                        ->label('Nombre(s)')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('Ej: Juan Carlos'),
                                    Forms\Components\TextInput::make('last_name')
                                        ->label('Apellido(s)')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('Ej: García López'),
                                    Forms\Components\Select::make('gender')
                                        ->label('Género')
                                        ->options([
                                            'male' => 'Masculino',
                                            'female' => 'Femenino',
                                            'other' => 'Otro',
                                        ])
                                        ->native(false),
                                    Forms\Components\DatePicker::make('date_of_birth')
                                        ->label('Fecha de Nacimiento')
                                        ->displayFormat('d/m/Y')
                                        ->maxDate(now())
                                        ->helperText('Debe ser mayor de edad'),
                                ]),
                        ]),

                    Wizard\Step::make('Documentos de Identidad')
                        ->icon('heroicon-o-identification')
                        ->description('Información de identificación oficial')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('identity_type')
                                        ->label('Tipo de Documento')
                                        ->options([
                                            'dpi' => 'DPI (Documento Personal de Identificación)',
                                            'passport' => 'Pasaporte',
                                            'license' => 'Licencia de Conducir',
                                        ])
                                        ->default('dpi')
                                        ->required()
                                        ->native(false)
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('identity_number')
                                        ->label('Número de Documento')
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('El número de documento debe ser único')
                                        ->placeholder('Ej: 1234 12345 1234'),
                                    Forms\Components\DatePicker::make('identity_expiry')
                                        ->label('Fecha de Vencimiento')
                                        ->displayFormat('d/m/Y')
                                        ->minDate(now())
                                        ->helperText('Fecha de vencimiento del documento'),
                                ]),
                        ]),

                    Wizard\Step::make('Dirección')
                        ->icon('heroicon-o-map-pin')
                        ->description('Dirección de residencia del cliente')
                        ->schema([
                            Forms\Components\Textarea::make('address')
                                ->label('Dirección Completa')
                                ->rows(3)
                                ->columnSpanFull()
                                ->placeholder('Ej: 5ta Avenida 12-45 Zona 10, Colonia Oakland'),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('city')
                                        ->label('Ciudad/Municipio')
                                        ->maxLength(100)
                                        ->placeholder('Ej: Guatemala'),
                                    Forms\Components\TextInput::make('state')
                                        ->label('Departamento')
                                        ->maxLength(100)
                                        ->placeholder('Ej: Guatemala'),
                                    Forms\Components\TextInput::make('postal_code')
                                        ->label('Código Postal')
                                        ->maxLength(20)
                                        ->placeholder('Ej: 01010'),
                                    Forms\Components\TextInput::make('country')
                                        ->label('País')
                                        ->required()
                                        ->default('Guatemala')
                                        ->maxLength(100),
                                ]),
                        ]),

                    Wizard\Step::make('Contacto')
                        ->icon('heroicon-o-phone')
                        ->description('Información de contacto')
                        ->schema([
                            Forms\Components\Section::make('Contacto Principal')
                                ->description('Datos de contacto del cliente')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Teléfono Fijo')
                                                ->tel()
                                                ->maxLength(8)
                                                ->placeholder('Ej: 22345678'),
                                            Forms\Components\TextInput::make('mobile')
                                                ->label('Teléfono Móvil')
                                                ->tel()
                                                ->maxLength(8)
                                                ->placeholder('Ej: 55551234'),
                                            Forms\Components\TextInput::make('email')
                                                ->label('Correo Electrónico')
                                                ->email()
                                                ->maxLength(150)
                                                ->placeholder('Ej: cliente@ejemplo.com')
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                            Forms\Components\Section::make('Contacto de Emergencia')
                                ->description('Persona a contactar en caso de emergencia')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('emergency_contact_name')
                                                ->label('Nombre Completo')
                                                ->maxLength(200)
                                                ->placeholder('Ej: María López García'),
                                            Forms\Components\TextInput::make('emergency_contact_phone')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->maxLength(20)
                                                ->placeholder('Ej: 55559999'),
                                        ]),
                                ])
                                ->collapsible(),
                        ]),

                    Wizard\Step::make('Información Laboral')
                        ->icon('heroicon-o-briefcase')
                        ->description('Datos laborales y de ingresos')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('occupation')
                                        ->label('Ocupación/Profesión')
                                        ->maxLength(100)
                                        ->placeholder('Ej: Ingeniero, Comerciante, etc.')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('employer')
                                        ->label('Empleador/Empresa')
                                        ->maxLength(200)
                                        ->placeholder('Ej: Empresa S.A.')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('monthly_income')
                                        ->label('Ingreso Mensual')
                                        ->required()
                                        ->minValue(1)
                                        ->numeric()
                                        ->prefix('Q')
                                        ->placeholder('Ej: 5000.00')
                                        ->helperText('Este dato es requerido para la evaluación de crédito del cliente')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Wizard\Step::make('Información de Crédito')
                        ->icon('heroicon-o-credit-card')
                        ->description('Configuración de crédito del cliente')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('branch_id')
                                        ->label('Sucursal')
                                        ->relationship('branch', 'name')
                                        ->preload()
                                        ->required()
                                        ->searchable()
                                        ->helperText('Sucursal donde está registrado el cliente')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('credit_limit')
                                        ->label('Límite de Crédito Inicial')
                                        ->required()
                                        ->numeric()
                                        ->default(4000)
                                        ->prefix('Q')
                                        ->helperText('Este límite puede ajustarse después según el historial crediticio'),
                                    Forms\Components\DatePicker::make('registration_date')
                                        ->label('Fecha de Registro')
                                        ->required()
                                        ->default(now())
                                        ->displayFormat('d/m/Y'),
                                ]),
                            Forms\Components\Section::make('Estado del Cliente')
                                ->schema([
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Cliente Activo')
                                        ->required()
                                        ->default(true)
                                        ->helperText('Los clientes inactivos no pueden realizar nuevas operaciones'),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notas y Observaciones')
                                        ->rows(3)
                                        ->placeholder('Agregue cualquier información relevante sobre el cliente...')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                            Forms\Components\Section::make('Puntaje Crediticio')
                                ->description('Estos valores se calculan automáticamente después del primer préstamo')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('credit_score')
                                                ->label('Puntaje de Crédito')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->placeholder('Sin historial')
                                                ->helperText('Se calcula automáticamente'),
                                            Forms\Components\TextInput::make('credit_rating')
                                                ->label('Calificación')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->placeholder('N/A'),
                                            Forms\Components\DateTimePicker::make('credit_score_updated_at')
                                                ->label('Última Actualización')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->displayFormat('d/m/Y H:i')
                                                ->placeholder('N/A'),
                                        ]),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    Wizard\Step::make('Revisión')
                        ->icon('heroicon-o-check-circle')
                        ->description('Revise todos los datos antes de guardar')
                        ->schema([
                            Forms\Components\Placeholder::make('review_info')
                                ->label('')
                                ->live()
                                ->content(function (Get $get) {
                                    $firstName = $get('first_name') ?? 'N/A';
                                    $lastName = $get('last_name') ?? 'N/A';
                                    $identityType = match($get('identity_type')) {
                                        'dpi' => 'DPI',
                                        'passport' => 'Pasaporte',
                                        'license' => 'Licencia',
                                        default => 'N/A'
                                    };
                                    $identityNumber = $get('identity_number') ?? 'N/A';
                                    $phone = $get('phone') ?? $get('mobile') ?? 'N/A';
                                    $email = $get('email') ?? 'N/A';
                                    $address = $get('address') ?? 'N/A';
                                    $city = $get('city') ?? '';
                                    $occupation = $get('occupation') ?? 'N/A';
                                    $monthlyIncome = $get('monthly_income') ? 'Q ' . number_format((float) $get('monthly_income'), 2) : 'N/A';
                                    $creditLimit = $get('credit_limit') ? 'Q ' . number_format((float) $get('credit_limit'), 2) : 'N/A';
                                    $isActive = $get('is_active') ? 'Sí' : 'No';

                                    $html = "
                                    <div class='space-y-4'>
                                        <div class='rounded-lg bg-primary-50 dark:bg-primary-900/20 p-4'>
                                            <h3 class='text-lg font-semibold text-primary-600 dark:text-primary-400 mb-3'>Resumen del Cliente</h3>

                                            <div class='grid grid-cols-2 gap-4 text-sm'>
                                                <div class='col-span-2'>
                                                    <span class='text-gray-500 dark:text-gray-400'>Nombre Completo:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white text-lg'>{$firstName} {$lastName}</span>
                                                </div>

                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Documento:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$identityType}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Número:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$identityNumber}</span>
                                                </div>

                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Teléfono:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$phone}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Email:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$email}</span>
                                                </div>

                                                <div class='col-span-2'>
                                                    <span class='text-gray-500 dark:text-gray-400'>Dirección:</span>
                                                    <span class='ml-2 font-semibold text-gray-900 dark:text-white'>{$address}" . ($city ? ", {$city}" : "") . "</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class='rounded-lg bg-success-50 dark:bg-success-900/20 p-4'>
                                            <h4 class='font-semibold text-success-600 dark:text-success-400 mb-2'>Información Financiera</h4>
                                            <div class='grid grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-300'>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Ocupación:</span>
                                                    <span class='ml-2 font-semibold'>{$occupation}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Ingreso Mensual:</span>
                                                    <span class='ml-2 font-semibold'>{$monthlyIncome}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Límite de Crédito:</span>
                                                    <span class='ml-2 font-semibold text-success-600 dark:text-success-400'>{$creditLimit}</span>
                                                </div>
                                                <div>
                                                    <span class='text-gray-500 dark:text-gray-400'>Cliente Activo:</span>
                                                    <span class='ml-2 font-semibold'>{$isActive}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class='rounded-lg bg-info-50 dark:bg-info-900/20 p-4'>
                                            <p class='text-sm text-info-600 dark:text-info-400'>
                                                ℹ️ Revise toda la información antes de guardar. El puntaje crediticio se calculará automáticamente después del primer préstamo.
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite de Crédito')
                    ->money('GTQ')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('credit_score')
                    ->label('Puntaje')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 750 => 'success',
                        $state >= 650 => 'info',
                        $state >= 550 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? $state . ' (' . ucfirst($record->credit_rating ?? 'N/A') . ')'
                        : 'Sin Historial'
                    )
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->preload()
                    ->searchable(),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('calcular_puntaje')
                    ->label('Calcular Puntaje')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Calcular Puntaje de Crédito')
                    ->modalDescription('Esto recalculará el puntaje basado en el historial actual. Requiere al menos 1 préstamo completado.')
                    ->action(function (Customer $record) {
                        $service = new CreditScoreService();
                        $service->updateCustomerCreditScore($record);

                        if ($record->credit_score === null) {
                            Notification::make()
                                ->warning()
                                ->title('Sin Historial Suficiente')
                                ->body('El cliente necesita al menos 1 préstamo completado (pagado o confiscado) para calcular un puntaje crediticio.')
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Puntaje de Crédito Actualizado')
                                ->body("Puntaje: {$record->credit_score} ({$record->credit_rating})")
                                ->send();
                        }
                    })
                    ->hidden(fn (Customer $record) =>
                        $record->loans()->whereIn('status', [\App\Models\Loan::STATUS_PAID, \App\Models\Loan::STATUS_FORFEITED])->count() === 0
                    ),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\LoansRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
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
