<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'Actividad';

    protected static ?string $pluralModelLabel = 'Registro de Actividades';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // View only - no creation or editing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipo de Registro')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'Loan' => 'success',
                        'Customer' => 'info',
                        'Item' => 'warning',
                        'Payment' => 'primary',
                        'Sale' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->default('Sistema'),

                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP')
                    ->searchable()
                    ->toggleable()
                    ->default('N/A')
                    ->icon('heroicon-m-globe-alt'),

                Tables\Columns\TextColumn::make('properties.user_agent')
                    ->label('Navegador/Dispositivo')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->properties['user_agent'] ?? '')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Tipo de Registro')
                    ->options([
                        'App\\Models\\Loan' => 'Préstamo',
                        'App\\Models\\Customer' => 'Cliente',
                        'App\\Models\\Item' => 'Artículo',
                        'App\\Models\\Payment' => 'Pago',
                        'App\\Models\\Sale' => 'Venta',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions - view only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    // Disable create, edit, and delete capabilities
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // Only Admin and Gerente can access activity logs
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['Admin', 'Gerente', 'super_admin']);
    }
}
