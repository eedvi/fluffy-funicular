<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información General')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Components\TextEntry::make('log_name')
                            ->label('Tipo de Log')
                            ->badge(),

                        Components\TextEntry::make('event')
                            ->label('Evento')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'created' => 'Creado',
                                'updated' => 'Actualizado',
                                'deleted' => 'Eliminado',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('subject_type')
                            ->label('Tipo de Registro')
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
                            ->badge(),

                        Components\TextEntry::make('subject_id')
                            ->label('ID del Registro'),

                        Components\TextEntry::make('causer.name')
                            ->label('Realizado por')
                            ->default('Sistema')
                            ->icon('heroicon-m-user'),

                        Components\TextEntry::make('created_at')
                            ->label('Fecha y Hora')
                            ->dateTime('d/m/Y H:i:s')
                            ->icon('heroicon-m-clock'),
                    ])
                    ->columns(2),

                Components\Section::make('Detalles de la Sesión')
                    ->schema([
                        Components\TextEntry::make('ip_address')
                            ->label('Dirección IP')
                            ->state(fn ($record) => $record->properties['ip'] ?? 'N/A')
                            ->icon('heroicon-m-globe-alt'),

                        Components\TextEntry::make('user_agent')
                            ->label('Navegador/Dispositivo')
                            ->state(fn ($record) => $record->properties['user_agent'] ?? 'N/A')
                            ->columnSpanFull()
                            ->icon('heroicon-m-computer-desktop'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn ($record) => isset($record->properties['ip']) || isset($record->properties['user_agent'])),

                Components\Section::make('Cambios Realizados')
                    ->schema([
                        Components\TextEntry::make('event')
                            ->label('')
                            ->state(function ($record) {
                                $old = $record->properties['old'] ?? [];
                                $new = $record->properties['attributes'] ?? [];
                                $event = $record->event;

                                // Field labels
                                $fieldLabels = [
                                    'loan_amount' => 'Monto del Préstamo',
                                    'status' => 'Estado',
                                    'payment_date' => 'Fecha de Pago',
                                    'amount' => 'Monto',
                                    'first_name' => 'Nombre',
                                    'last_name' => 'Apellido',
                                    'name' => 'Nombre',
                                ];

                                // Helper function to convert values to string safely
                                $formatValue = function ($value) {
                                    if (is_array($value)) {
                                        return json_encode($value);
                                    }
                                    if (is_bool($value)) {
                                        return $value ? 'Sí' : 'No';
                                    }
                                    if (is_null($value)) {
                                        return 'N/A';
                                    }
                                    return (string) $value;
                                };

                                if ($event === 'created') {
                                    return 'Registro creado exitosamente.';
                                } elseif ($event === 'deleted') {
                                    return 'Registro eliminado del sistema.';
                                } else {
                                    $changes = [];
                                    foreach (array_keys($old + $new) as $key) {
                                        if (!in_array($key, ['created_at', 'updated_at']) && ($old[$key] ?? null) != ($new[$key] ?? null)) {
                                            $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                            $oldVal = $formatValue($old[$key] ?? null);
                                            $newVal = $formatValue($new[$key] ?? null);
                                            $changes[] = "**{$label}:** {$oldVal} → {$newVal}";
                                        }
                                    }
                                    return empty($changes) ? 'Sin cambios detectados.' : implode("\n\n", $changes);
                                }
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->properties['old'] ?? []) || !empty($record->properties['attributes'] ?? [])),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Volver')
                ->url(ActivityResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
