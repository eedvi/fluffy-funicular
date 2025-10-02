<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
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
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Apellido')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Select::make('gender')
                                    ->label('Género')
                                    ->options([
                                        'M' => 'Masculino',
                                        'F' => 'Femenino',
                                        'Otro' => 'Otro',
                                    ]),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label('Fecha de Nacimiento')
                                    ->displayFormat('d/m/Y')
                                    ->maxDate(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('Documentos')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('identity_type')
                                    ->label('Tipo de Documento')
                                    ->options([
                                        'DNI' => 'DNI',
                                        'Pasaporte' => 'Pasaporte',
                                        'Cédula' => 'Cédula',
                                        'Otro' => 'Otro',
                                    ]),
                                Forms\Components\TextInput::make('identity_number')
                                    ->label('Número de Documento')
                                    ->maxLength(50),
                                Forms\Components\DatePicker::make('identity_expiry')
                                    ->label('Fecha de Vencimiento')
                                    ->displayFormat('d/m/Y'),
                            ]),
                    ]),

                Forms\Components\Section::make('Dirección')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label('Ciudad')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('state')
                                    ->label('Estado/Provincia')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('Código Postal')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('country')
                                    ->label('País')
                                    ->required()
                                    ->default('Argentina')
                                    ->maxLength(100),
                            ]),
                    ]),

                Forms\Components\Section::make('Información Laboral')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('occupation')
                                    ->label('Ocupación')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('employer')
                                    ->label('Empleador')
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('monthly_income')
                                    ->label('Ingreso Mensual')
                                    ->numeric()
                                    ->prefix('$'),
                            ]),
                    ]),

                Forms\Components\Section::make('Contacto')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('mobile')
                                    ->label('Móvil')
                                    ->tel()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->maxLength(150),
                            ]),
                    ]),

                Forms\Components\Section::make('Contacto de Emergencia')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('emergency_contact_name')
                                    ->label('Nombre de Contacto')
                                    ->maxLength(200),
                                Forms\Components\TextInput::make('emergency_contact_phone')
                                    ->label('Teléfono de Contacto')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),

                Forms\Components\Section::make('Información de Crédito')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('credit_limit')
                                    ->label('Límite de Crédito')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('credit_score')
                                    ->label('Puntaje de Crédito')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1000),
                                Forms\Components\DatePicker::make('registration_date')
                                    ->label('Fecha de Registro')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d/m/Y'),
                            ]),
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->required()
                                    ->default(true),
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
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite de Crédito')
                    ->money('USD')
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
                TrashedFilter::make(),
            ])
            ->actions([
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
            RelationManagers\LoansRelationManager::class,
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
