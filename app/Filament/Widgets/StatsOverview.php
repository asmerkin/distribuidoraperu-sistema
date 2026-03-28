<?php

namespace App\Filament\Widgets;

use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Variant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $inventoryValue = InventoryLevel::query()
            ->leftJoin('supplier_variants', function ($join) {
                $join->on('inventory_levels.variant_id', '=', 'supplier_variants.variant_id')
                    ->where('supplier_variants.is_default', true);
            })
            ->select(DB::raw('SUM(inventory_levels.quantity * COALESCE(supplier_variants.cost_price, 0)) as total'))
            ->value('total') ?? 0;

        $pendingPOs = PurchaseOrder::query()
            ->whereIn('status', [
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::Confirmed,
                PurchaseOrderStatus::PartiallyReceived,
            ])
            ->count();

        return [
            Stat::make('Total Productos', Product::count())
                ->icon('heroicon-o-cube'),

            Stat::make('Total Variantes', Variant::count())
                ->icon('heroicon-o-squares-2x2'),

            Stat::make('Valor del Inventario', '$' . number_format($inventoryValue, 2, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('POs Pendientes', $pendingPOs)
                ->icon('heroicon-o-shopping-cart')
                ->color($pendingPOs > 0 ? 'warning' : 'success'),
        ];
    }
}
