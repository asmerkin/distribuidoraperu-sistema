<?php

namespace App\Filament\Resources\SupplierResource\Widgets;

use App\Enums\SupplierInvoiceStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class SupplierStatsWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $supplier = $this->record;

        $totalFacturado = (float) $supplier->invoices()->sum('total');
        $totalPagado = (float) $supplier->invoices()->sum('amount_paid');
        $totalAdeudado = $totalFacturado - $totalPagado;
        $facturasImpagas = $supplier->invoices()->where('status', '!=', SupplierInvoiceStatus::Paid)->count();
        $facturasVencidas = $supplier->invoices()
            ->where('status', '!=', SupplierInvoiceStatus::Paid)
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->count();
        $cantidadPOs = $supplier->purchaseOrders()->count();

        return [
            Stat::make('Total Facturado', '$ '.number_format($totalFacturado, 2, ',', '.'))
                ->icon('heroicon-o-document-text'),

            Stat::make('Total Adeudado', '$ '.number_format($totalAdeudado, 2, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color($totalAdeudado > 0 ? 'danger' : 'success'),

            Stat::make('Facturas Impagas', $facturasImpagas.($facturasVencidas > 0 ? " ({$facturasVencidas} vencidas)" : ''))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($facturasVencidas > 0 ? 'danger' : ($facturasImpagas > 0 ? 'warning' : 'success')),

            Stat::make('Órdenes de Compra', $cantidadPOs)
                ->icon('heroicon-o-shopping-cart'),
        ];
    }
}
