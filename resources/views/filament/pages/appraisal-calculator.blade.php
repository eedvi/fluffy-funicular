<x-filament-panels::page>
    <form wire:submit="calculate">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="button"
                color="gray"
                wire:click="resetCalculations"
            >
                Limpiar
            </x-filament::button>

            <x-filament::button type="submit">
                Calcular Tasación
            </x-filament::button>
        </div>
    </form>

    @if($calculatedValue !== null)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Resultados de la Tasación
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-lg bg-success-50 dark:bg-success-500/10 p-6">
                    <div class="text-sm font-medium text-success-600 dark:text-success-400">
                        Valor Estimado del Artículo
                    </div>
                    <div class="mt-2 text-3xl font-bold text-success-900 dark:text-success-100">
                        ${{ number_format($calculatedValue, 2) }}
                    </div>
                </div>

                <div class="rounded-lg bg-primary-50 dark:bg-primary-500/10 p-6">
                    <div class="text-sm font-medium text-primary-600 dark:text-primary-400">
                        Monto de Préstamo Sugerido
                    </div>
                    <div class="mt-2 text-3xl font-bold text-primary-900 dark:text-primary-100">
                        ${{ number_format($suggestedLoanAmount, 2) }}
                    </div>
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                        {{ round(($suggestedLoanAmount / $calculatedValue) * 100, 1) }}% del valor estimado
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Notas de Tasación
                </h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li>El valor estimado es una aproximación basada en los datos ingresados</li>
                    <li>El monto del préstamo sugerido es conservador para proteger el negocio</li>
                    <li>Siempre verifique el artículo físicamente antes de finalizar la tasación</li>
                    <li>Para joyería, considere obtener una tasación profesional para piezas valiosas</li>
                </ul>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
