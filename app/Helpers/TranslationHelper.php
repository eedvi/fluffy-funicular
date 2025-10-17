<?php

namespace App\Helpers;

class TranslationHelper
{
    /**
     * Traduce el método de pago al español
     */
    public static function translatePaymentMethod(?string $method): string
    {
        if (!$method) return 'N/A';

        return match (strtolower($method)) {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'check' => 'Cheque',
            default => ucfirst($method),
        };
    }

    /**
     * Traduce el estado de pago al español
     */
    public static function translatePaymentStatus(?string $status): string
    {
        if (!$status) return 'N/A';

        return match (strtolower($status)) {
            'completed' => 'Completado',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
            default => ucfirst($status),
        };
    }

    /**
     * Traduce el estado de venta al español
     */
    public static function translateSaleStatus(?string $status): string
    {
        if (!$status) return 'N/A';

        return match (strtolower($status)) {
            'pending' => 'Pendiente',
            'paid' => 'Pagada',
            'delivered' => 'Entregada',
            'cancelled' => 'Cancelada',
            default => ucfirst($status),
        };
    }

    /**
     * Traduce el estado de préstamo al español
     */
    public static function translateLoanStatus(?string $status): string
    {
        if (!$status) return 'N/A';

        return match (strtolower($status)) {
            'pending' => 'Pendiente',
            'active' => 'Activo',
            'paid' => 'Pagado',
            'overdue' => 'Vencido',
            'forfeited' => 'Confiscado',
            default => ucfirst($status),
        };
    }

    /**
     * Traduce la condición del artículo al español
     */
    public static function translateItemCondition(?string $condition): string
    {
        if (!$condition) return 'N/A';

        return match (strtolower($condition)) {
            'new' => 'Nuevo',
            'used' => 'Usado',
            'excellent' => 'Excelente',
            'good' => 'Bueno',
            'fair' => 'Regular',
            'poor' => 'Malo',
            default => ucfirst($condition),
        };
    }

    /**
     * Traduce el estado del artículo al español
     */
    public static function translateItemStatus(?string $status): string
    {
        if (!$status) return 'N/A';

        return match (strtolower($status)) {
            'available' => 'Disponible',
            'collateral' => 'En Préstamo',
            'sold' => 'Vendido',
            'forfeited' => 'Confiscado',
            default => ucfirst($status),
        };
    }
}
