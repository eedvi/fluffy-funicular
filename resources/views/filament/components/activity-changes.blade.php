@php
    $old = $getRecord()->properties['old'] ?? [];
    $new = $getRecord()->properties['attributes'] ?? [];
    $event = $getRecord()->event;

    // Field labels in Spanish
    $fieldLabels = [
        'loan_number' => 'Número de Préstamo',
        'customer_id' => 'Cliente',
        'item_id' => 'Artículo',
        'loan_amount' => 'Monto del Préstamo',
        'interest_rate' => 'Tasa de Interés',
        'total_amount' => 'Total a Pagar',
        'amount_paid' => 'Total Pagado',
        'balance_remaining' => 'Saldo Pendiente',
        'status' => 'Estado',
        'payment_date' => 'Fecha de Pago',
        'payment_method' => 'Método de Pago',
        'amount' => 'Monto',
        'first_name' => 'Nombre',
        'last_name' => 'Apellido',
        'email' => 'Email',
        'phone' => 'Teléfono',
        'address' => 'Dirección',
        'name' => 'Nombre',
        'description' => 'Descripción',
        'sale_price' => 'Precio de Venta',
        'final_price' => 'Precio Final',
        'due_date' => 'Fecha de Vencimiento',
        'start_date' => 'Fecha de Inicio',
        'notes' => 'Notas',
    ];

    // Status translations
    $statusLabels = [
        'active' => 'Activo',
        'paid' => 'Pagado',
        'overdue' => 'Vencido',
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ];

    // Payment method translations
    $paymentMethodLabels = [
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'transfer' => 'Transferencia',
        'check' => 'Cheque',
    ];
@endphp

<div class="space-y-3">
    @if ($event === 'created')
        <div class="rounded-lg bg-success-50 dark:bg-success-950 p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <h3 class="text-sm font-semibold text-success-800 dark:text-success-200">Registro Creado</h3>
            </div>
            <div class="space-y-2">
                @foreach ($new as $key => $value)
                    @if (!in_array($key, ['created_at', 'updated_at', 'deleted_at']))
                        <div class="text-sm">
                            <span class="font-medium text-success-700 dark:text-success-300">
                                {{ $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}:
                            </span>
                            <span class="text-success-900 dark:text-success-100">
                                @if (in_array($key, ['loan_amount', 'amount', 'sale_price', 'final_price', 'total_amount', 'amount_paid', 'balance_remaining']))
                                    ${{ number_format($value, 2) }}
                                @elseif ($key === 'status')
                                    {{ $statusLabels[$value] ?? $value }}
                                @elseif ($key === 'payment_method')
                                    {{ $paymentMethodLabels[$value] ?? $value }}
                                @elseif (is_array($value))
                                    {{ json_encode($value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @elseif ($event === 'deleted')
        <div class="rounded-lg bg-danger-50 dark:bg-danger-950 p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-danger-600 dark:text-danger-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <h3 class="text-sm font-semibold text-danger-800 dark:text-danger-200">Registro Eliminado</h3>
            </div>
            <p class="text-sm text-danger-700 dark:text-danger-300">Este registro fue eliminado del sistema.</p>
        </div>
    @elseif ($event === 'updated')
        @php
            $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
            $changes = collect($allKeys)
                ->filter(fn($key) => !in_array($key, ['created_at', 'updated_at', 'deleted_at']))
                ->filter(fn($key) => isset($old[$key]) || isset($new[$key]))
                ->filter(fn($key) => ($old[$key] ?? null) != ($new[$key] ?? null));
        @endphp

        @if ($changes->isEmpty())
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">No se detectaron cambios en los campos principales.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($changes as $key)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-gray-200 dark:divide-gray-700">
                            <div class="px-4 py-3 bg-red-50 dark:bg-red-950/20">
                                <div class="text-xs font-medium text-red-700 dark:text-red-400 mb-1">Anterior</div>
                                <div class="text-sm text-red-900 dark:text-red-100">
                                    @php
                                        $oldValue = $old[$key] ?? 'N/A';
                                    @endphp
                                    @if (in_array($key, ['loan_amount', 'amount', 'sale_price', 'final_price', 'total_amount', 'amount_paid', 'balance_remaining']))
                                        <span class="font-mono">${{ number_format($oldValue, 2) }}</span>
                                    @elseif ($key === 'status')
                                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                            {{ $statusLabels[$oldValue] ?? $oldValue }}
                                        </span>
                                    @elseif ($key === 'payment_method')
                                        {{ $paymentMethodLabels[$oldValue] ?? $oldValue }}
                                    @elseif (is_array($oldValue))
                                        <code class="text-xs">{{ json_encode($oldValue) }}</code>
                                    @else
                                        {{ $oldValue }}
                                    @endif
                                </div>
                            </div>
                            <div class="px-4 py-3 bg-green-50 dark:bg-green-950/20">
                                <div class="text-xs font-medium text-green-700 dark:text-green-400 mb-1">Nuevo</div>
                                <div class="text-sm text-green-900 dark:text-green-100">
                                    @php
                                        $newValue = $new[$key] ?? 'N/A';
                                    @endphp
                                    @if (in_array($key, ['loan_amount', 'amount', 'sale_price', 'final_price', 'total_amount', 'amount_paid', 'balance_remaining']))
                                        <span class="font-mono">${{ number_format($newValue, 2) }}</span>
                                    @elseif ($key === 'status')
                                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                            {{ $statusLabels[$newValue] ?? $newValue }}
                                        </span>
                                    @elseif ($key === 'payment_method')
                                        {{ $paymentMethodLabels[$newValue] ?? $newValue }}
                                    @elseif (is_array($newValue))
                                        <code class="text-xs">{{ json_encode($newValue) }}</code>
                                    @else
                                        {{ $newValue }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
