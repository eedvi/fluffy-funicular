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

                Components\Section::make('Registro Afectado')
                    ->schema([
                        Components\TextEntry::make('subject_info')
                            ->label('Tipo de Registro')
                            ->state(function ($record) {
                                if (!$record->subject_type || !$record->subject_id) {
                                    return 'No disponible';
                                }

                                $subjectType = class_basename($record->subject_type);
                                $attributes = $record->properties['attributes'] ?? [];

                                $typeLabel = match ($subjectType) {
                                    'Loan' => 'Préstamo',
                                    'Customer' => 'Cliente',
                                    'Item' => 'Artículo',
                                    'Payment' => 'Pago',
                                    'Sale' => 'Venta',
                                    'ItemTransfer' => 'Transferencia',
                                    'Branch' => 'Sucursal',
                                    'Category' => 'Categoría',
                                    'User' => 'Usuario',
                                    default => $subjectType,
                                };

                                // Get identifying information
                                $identifier = '';
                                if (isset($attributes['loan_number'])) {
                                    $identifier = $attributes['loan_number'];
                                } elseif (isset($attributes['payment_number'])) {
                                    $identifier = $attributes['payment_number'];
                                } elseif (isset($attributes['sale_number'])) {
                                    $identifier = $attributes['sale_number'];
                                } elseif (isset($attributes['name'])) {
                                    $identifier = $attributes['name'];
                                } elseif (isset($attributes['first_name']) || isset($attributes['last_name'])) {
                                    $identifier = trim(($attributes['first_name'] ?? '') . ' ' . ($attributes['last_name'] ?? ''));
                                }

                                return $typeLabel . ($identifier ? " - {$identifier}" : '');
                            })
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('subject_id')
                            ->label('ID del Registro')
                            ->formatStateUsing(fn ($state) => "#{$state}"),

                        Components\TextEntry::make('subject_link')
                            ->label('Enlace')
                            ->state(fn ($record) => $record->subject_type && $record->subject_id ? 'Ver registro →' : 'N/A')
                            ->url(function ($record) {
                                if (!$record->subject_type || !$record->subject_id) {
                                    return null;
                                }

                                $subjectType = class_basename($record->subject_type);
                                $subjectId = $record->subject_id;

                                try {
                                    return match ($subjectType) {
                                        'Loan' => \App\Filament\Resources\LoanResource::getUrl('view', ['record' => $subjectId]),
                                        'Customer' => \App\Filament\Resources\CustomerResource::getUrl('view', ['record' => $subjectId]),
                                        'Item' => \App\Filament\Resources\ItemResource::getUrl('edit', ['record' => $subjectId]),
                                        'Payment' => \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $subjectId]),
                                        'Sale' => \App\Filament\Resources\SaleResource::getUrl('view', ['record' => $subjectId]),
                                        'ItemTransfer' => \App\Filament\Resources\ItemTransferResource::getUrl('view', ['record' => $subjectId]),
                                        'Branch' => \App\Filament\Resources\BranchResource::getUrl('edit', ['record' => $subjectId]),
                                        'Category' => \App\Filament\Resources\CategoryResource::getUrl('edit', ['record' => $subjectId]),
                                        'User' => \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $subjectId]),
                                        default => null,
                                    };
                                } catch (\Exception $e) {
                                    return null;
                                }
                            })
                            ->color('primary')
                            ->weight('bold')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->openUrlInNewTab(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->visible(fn ($record) => $record->subject_type && $record->subject_id),

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

                                // Field labels in Spanish
                                $fieldLabels = [
                                    'loan_amount' => 'Monto del Préstamo',
                                    'total_amount' => 'Monto Total',
                                    'amount_paid' => 'Monto Pagado',
                                    'balance_remaining' => 'Saldo Pendiente',
                                    'status' => 'Estado',
                                    'payment_date' => 'Fecha de Pago',
                                    'payment_method' => 'Método de Pago',
                                    'amount' => 'Monto',
                                    'first_name' => 'Nombre',
                                    'last_name' => 'Apellido',
                                    'name' => 'Nombre',
                                    'customer_id' => 'Cliente',
                                    'item_id' => 'Artículo',
                                    'loan_id' => 'Préstamo',
                                    'branch_id' => 'Sucursal',
                                    'category_id' => 'Categoría',
                                    'email' => 'Correo Electrónico',
                                    'phone' => 'Teléfono',
                                    'address' => 'Dirección',
                                    'interest_rate' => 'Tasa de Interés',
                                    'due_date' => 'Fecha de Vencimiento',
                                    'loan_number' => 'Número de Préstamo',
                                    'payment_number' => 'Número de Pago',
                                    'sale_number' => 'Número de Venta',
                                    'notes' => 'Notas',
                                    'reference_number' => 'Número de Referencia',
                                    'quantity' => 'Cantidad',
                                    'price' => 'Precio',
                                    'sale_price' => 'Precio de Venta',
                                    'description' => 'Descripción',
                                    'serial_number' => 'Número de Serie',
                                    'is_active' => 'Activo',
                                    'report_type' => 'Tipo de Reporte',
                                    'format' => 'Formato',
                                    'pdf_type' => 'Tipo de PDF',
                                    'action' => 'Acción',
                                ];

                                // Helper function to translate values
                                $translateValue = function ($key, $value) {
                                    // Status translations
                                    if ($key === 'status') {
                                        return match ($value) {
                                            'active' => 'Activo',
                                            'paid' => 'Pagado',
                                            'overdue' => 'Vencido',
                                            'defaulted' => 'Incumplido',
                                            'completed' => 'Completado',
                                            'pending' => 'Pendiente',
                                            'cancelled' => 'Cancelado',
                                            'available' => 'Disponible',
                                            'collateral' => 'En Garantía',
                                            'sold' => 'Vendido',
                                            default => ucfirst($value),
                                        };
                                    }

                                    // Payment method translations
                                    if ($key === 'payment_method') {
                                        return match ($value) {
                                            'cash' => 'Efectivo',
                                            'card' => 'Tarjeta',
                                            'transfer' => 'Transferencia',
                                            'check' => 'Cheque',
                                            default => ucfirst($value),
                                        };
                                    }

                                    return $value;
                                };

                                // Helper function to convert values to string safely
                                $formatValue = function ($key, $value) use ($translateValue) {
                                    if (is_array($value)) {
                                        return json_encode($value);
                                    }
                                    if (is_bool($value)) {
                                        return $value ? 'Sí' : 'No';
                                    }
                                    if (is_null($value)) {
                                        return 'N/A';
                                    }

                                    $stringValue = (string) $value;

                                    // Translate if applicable
                                    return $translateValue($key, $stringValue);
                                };

                                if ($event === 'created') {
                                    return 'Registro creado exitosamente.';
                                } elseif ($event === 'deleted') {
                                    return 'Registro eliminado del sistema.';
                                } else {
                                    $changes = [];
                                    foreach (array_keys($old + $new) as $key) {
                                        if (!in_array($key, ['created_at', 'updated_at', 'id']) && ($old[$key] ?? null) != ($new[$key] ?? null)) {
                                            $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                            $oldVal = $formatValue($key, $old[$key] ?? null);
                                            $newVal = $formatValue($key, $new[$key] ?? null);
                                            $changes[] = "**{$label}:** `{$oldVal}` → `{$newVal}`";
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
