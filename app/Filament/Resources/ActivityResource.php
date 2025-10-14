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
                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Módulo')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match (class_basename($state)) {
                        'Loan' => 'Préstamo',
                        'Customer' => 'Cliente',
                        'Item' => 'Artículo',
                        'Payment' => 'Pago',
                        'Sale' => 'Venta',
                        'ItemTransfer' => 'Transferencia',
                        'Branch' => 'Sucursal',
                        'Category' => 'Categoría',
                        default => class_basename($state),
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'Loan' => 'success',
                        'Customer' => 'info',
                        'Item' => 'warning',
                        'Payment' => 'primary',
                        'Sale' => 'danger',
                        'ItemTransfer' => 'purple',
                        'Branch' => 'gray',
                        'Category' => 'orange',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->description)
                    ->formatStateUsing(function ($record) {
                        $subjectType = class_basename($record->subject_type);
                        $event = match ($record->event) {
                            'created' => 'creó',
                            'updated' => 'actualizó',
                            'deleted' => 'eliminó',
                            default => $record->event,
                        };
                        $module = match ($subjectType) {
                            'Loan' => 'préstamo',
                            'Customer' => 'cliente',
                            'Item' => 'artículo',
                            'Payment' => 'pago',
                            'Sale' => 'venta',
                            'ItemTransfer' => 'transferencia',
                            'Branch' => 'sucursal',
                            'Category' => 'categoría',
                            default => strtolower($subjectType),
                        };

                        $identifier = $record->subject_id ? " #{$record->subject_id}" : '';
                        return ucfirst("{$event} {$module}{$identifier}");
                    }),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->default('Sistema')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP')
                    ->searchable()
                    ->toggleable()
                    ->default('N/A')
                    ->icon('heroicon-m-globe-alt')
                    ->copyable()
                    ->copyMessage('IP copiada'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d/m/Y H:i:s')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Módulo')
                    ->options([
                        'App\\Models\\Loan' => 'Préstamo',
                        'App\\Models\\Customer' => 'Cliente',
                        'App\\Models\\Item' => 'Artículo',
                        'App\\Models\\Payment' => 'Pago',
                        'App\\Models\\Sale' => 'Venta',
                        'App\\Models\\ItemTransfer' => 'Transferencia',
                        'App\\Models\\Branch' => 'Sucursal',
                        'App\\Models\\Category' => 'Categoría',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->relationship('causer', 'name')
                    ->searchable(['name', 'email'])
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?? 'Sistema')
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Desde')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta')
                            ->native(false),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Desde ' . \Carbon\Carbon::parse($data['created_from'])->format('d/m/Y'))
                                ->removeField('created_from');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Hasta ' . \Carbon\Carbon::parse($data['created_until'])->format('d/m/Y'))
                                ->removeField('created_until');
                        }
                        return $indicators;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalles')
                    ->icon('heroicon-m-eye'),
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
