<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramResource\Pages;
use App\Models\LoyaltyProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Programa de Lealtad';

    protected static ?string $pluralModelLabel = 'Programas de Lealtad';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Cliente')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                    ]),

                Forms\Components\Section::make('Estado del Programa')
                    ->schema([
                        Forms\Components\Select::make('tier')
                            ->label('Nivel')
                            ->options([
                                'bronze' => 'Bronce',
                                'silver' => 'Plata',
                                'gold' => 'Oro',
                                'platinum' => 'Platino',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('points')
                            ->label('Puntos Disponibles')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts'),

                        Forms\Components\TextInput::make('points_lifetime')
                            ->label('Puntos Totales (Histórico)')
                            ->numeric()
                            ->disabled()
                            ->suffix('pts'),

                        Forms\Components\DatePicker::make('tier_achieved_at')
                            ->label('Nivel Alcanzado')
                            ->disabled(),

                        Forms\Components\DatePicker::make('last_activity_at')
                            ->label('Última Actividad')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estadísticas')
                    ->schema([
                        Forms\Components\TextInput::make('rewards_earned')
                            ->label('Recompensas Ganadas')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('rewards_redeemed')
                            ->label('Recompensas Canjeadas')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Historial de Actividad')
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tier')
                    ->label('Nivel')
                    ->formatStateUsing(fn ($record) => $record->getTierLabel())
                    ->colors([
                        'orange' => 'bronze',
                        'gray' => 'silver',
                        'warning' => 'gold',
                        'purple' => 'platinum',
                    ]),

                Tables\Columns\TextColumn::make('points')
                    ->label('Puntos')
                    ->sortable()
                    ->suffix(' pts')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('points_lifetime')
                    ->label('Puntos Históricos')
                    ->sortable()
                    ->suffix(' pts')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('points_to_next_tier')
                    ->label('Próximo Nivel')
                    ->state(fn ($record) => $record->getPointsToNextTier())
                    ->formatStateUsing(fn ($state) => $state ? $state . ' pts' : 'Máximo nivel')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rewards_earned')
                    ->label('Recompensas')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Última Actividad')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->label('Nivel')
                    ->options([
                        'bronze' => 'Bronce',
                        'silver' => 'Plata',
                        'gold' => 'Oro',
                        'platinum' => 'Platino',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('add_points')
                    ->label('Agregar Puntos')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Puntos a Agregar')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(10),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (LoyaltyProgram $record, array $data) {
                        $record->addPoints($data['points'], $data['reason']);

                        Notification::make()
                            ->success()
                            ->title('Puntos Agregados')
                            ->body("{$data['points']} puntos agregados. Total: {$record->points} pts")
                            ->send();
                    }),

                Tables\Actions\Action::make('redeem_points')
                    ->label('Canjear Puntos')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Puntos a Canjear')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Textarea::make('reward')
                            ->label('Recompensa')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (LoyaltyProgram $record, array $data) {
                        if ($record->redeemPoints($data['points'], $data['reward'])) {
                            Notification::make()
                                ->success()
                                ->title('Puntos Canjeados')
                                ->body("{$data['points']} puntos canjeados. Restantes: {$record->points} pts")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Puntos insuficientes')
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('points_lifetime', 'desc');
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
            'index' => Pages\ListLoyaltyPrograms::route('/'),
            'create' => Pages\CreateLoyaltyProgram::route('/create'),
            'view' => Pages\ViewLoyaltyProgram::route('/{record}'),
            'edit' => Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('tier', 'platinum')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'purple';
    }
}
