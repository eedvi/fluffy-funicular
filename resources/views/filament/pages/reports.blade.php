<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Filtros de Reporte
            </x-slot>

            <x-slot name="description">
                Seleccione el rango de fechas y sucursal para los reportes
            </x-slot>

            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Active Loans Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Préstamos Activos
            </x-slot>

            <x-slot name="description">
                Reporte de todos los préstamos actualmente activos
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateActiveLoansReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateActiveLoansReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Overdue Loans Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Préstamos Vencidos
            </x-slot>

            <x-slot name="description">
                Reporte de préstamos que han superado su fecha de vencimiento
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateOverdueLoansReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateOverdueLoansReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Sales Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Ventas por Período
            </x-slot>

            <x-slot name="description">
                Reporte de ventas realizadas en el rango de fechas seleccionado
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateSalesReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateSalesReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Payments Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Pagos Recibidos
            </x-slot>

            <x-slot name="description">
                Reporte de pagos recibidos en el rango de fechas seleccionado
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generatePaymentsReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generatePaymentsReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Inventory Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Valuación de Inventario
            </x-slot>

            <x-slot name="description">
                Reporte completo del inventario y su valuación
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateInventoryReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateInventoryReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Revenue by Branch Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Ingresos por Sucursal
            </x-slot>

            <x-slot name="description">
                Análisis de ingresos desglosado por sucursal
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateRevenueByBranchReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateRevenueByBranchReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Customer Analytics Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Análisis de Clientes
            </x-slot>

            <x-slot name="description">
                Top 50 clientes por volumen de negocio en el período seleccionado
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateCustomerAnalyticsReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateCustomerAnalyticsReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Confiscated Items Report --}}
        <x-filament::section>
            <x-slot name="heading">
                Artículos Confiscados
            </x-slot>

            <x-slot name="description">
                Reporte detallado de artículos confiscados, incluyendo información de subastas y préstamos asociados
            </x-slot>

            <div class="flex gap-4">
                <x-filament::button
                    wire:click="generateConfiscatedItemsReport('pdf')"
                    icon="heroicon-o-document-arrow-down"
                    color="danger"
                >
                    Exportar PDF
                </x-filament::button>

                <x-filament::button
                    wire:click="generateConfiscatedItemsReport('excel')"
                    icon="heroicon-o-table-cells"
                    color="success"
                >
                    Exportar Excel
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
