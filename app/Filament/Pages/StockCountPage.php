<?php

namespace App\Filament\Pages;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Variant;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StockCountPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Conteo Físico';

    protected static ?string $title = 'Conteo Físico de Inventario';

    protected static ?string $slug = 'stock-count';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.stock-count';

    public ?string $locationId = null;
    public string $searchCode = '';
    public ?string $foundVariantId = null;
    public ?int $countedQuantity = null;
    public string $countMode = 'adjust';
    public array $countedItems = [];

    public function getLocations(): array
    {
        return Location::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getFoundVariant(): ?array
    {
        if (! $this->foundVariantId) {
            return null;
        }

        $variant = Variant::with('product')->find($this->foundVariantId);
        if (! $variant) {
            return null;
        }

        $currentStock = 0;
        if ($this->locationId) {
            $level = InventoryLevel::where('variant_id', $variant->id)
                ->where('location_id', $this->locationId)
                ->first();
            $currentStock = $level?->quantity ?? 0;
        }

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name !== 'Default' ? $variant->name : null,
            'cost_price' => $variant->cost_price,
            'current_stock' => $currentStock,
        ];
    }

    public function searchVariant(): void
    {
        $code = trim($this->searchCode);

        if (empty($code)) {
            return;
        }

        if (! $this->locationId) {
            Notification::make()
                ->title('Seleccioná una ubicación primero')
                ->warning()
                ->send();

            return;
        }

        $variant = Variant::where('sku', $code)
            ->orWhere('barcode', $code)
            ->orWhere('sku', 'like', "%{$code}%")
            ->orWhere('barcode', 'like', "%{$code}%")
            ->first();

        // Also try searching by product name if no match
        if (! $variant) {
            $variant = Variant::whereHas('product', fn ($q) => $q->where('name', 'like', "%{$code}%"))
                ->first();
        }

        if (! $variant) {
            Notification::make()
                ->title('No encontrado')
                ->body("No se encontró variante con código \"{$code}\".")
                ->danger()
                ->send();

            $this->searchCode = '';

            return;
        }

        $this->foundVariantId = $variant->id;

        $level = InventoryLevel::where('variant_id', $variant->id)
            ->where('location_id', $this->locationId)
            ->first();

        $this->countedQuantity = $level?->quantity ?? 0;
        $this->searchCode = '';
    }

    public function confirmCount(): void
    {
        if (! $this->foundVariantId || ! $this->locationId) {
            return;
        }

        $variant = Variant::with('product')->find($this->foundVariantId);
        $location = Location::find($this->locationId);

        if (! $variant || ! $location) {
            return;
        }

        $level = InventoryLevel::where('variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->first();

        $currentStock = $level?->quantity ?? 0;
        $counted = (int) $this->countedQuantity;
        $diff = $counted - $currentStock;

        if ($diff !== 0) {
            app(InventoryService::class)->recordMovement(
                variant: $variant,
                location: $location,
                type: StockMovementType::Adjustment,
                reason: StockMovementReason::StockCount,
                quantity: $diff,
                notes: 'Conteo físico',
                userId: auth()->id(),
            );
        }

        // Add to counted list
        $label = "[{$variant->sku}] {$variant->product->name}";
        if ($variant->name !== 'Default') {
            $label .= " — {$variant->name}";
        }

        // Replace if already counted
        $this->countedItems = collect($this->countedItems)
            ->filter(fn ($item) => $item['variant_id'] !== $variant->id)
            ->values()
            ->toArray();

        array_unshift($this->countedItems, [
            'variant_id' => $variant->id,
            'label' => $label,
            'previous' => $currentStock,
            'counted' => $counted,
            'diff' => $diff,
        ]);

        $this->foundVariantId = null;
        $this->countedQuantity = null;

        Notification::make()
            ->title($diff === 0 ? 'Sin diferencia' : 'Stock ajustado')
            ->body("{$label}: {$currentStock} → {$counted}" . ($diff !== 0 ? " ({$diff})" : ''))
            ->color($diff === 0 ? 'info' : 'success')
            ->send();
    }

    public function adjustQuantity(int $amount): void
    {
        $this->countedQuantity = max(0, ((int) $this->countedQuantity) + $amount);
    }

    public function resetQuantity(): void
    {
        if (! $this->foundVariantId || ! $this->locationId) {
            return;
        }

        $level = InventoryLevel::where('variant_id', $this->foundVariantId)
            ->where('location_id', $this->locationId)
            ->first();

        $this->countedQuantity = $level?->quantity ?? 0;
    }

    public function cancelSearch(): void
    {
        $this->foundVariantId = null;
        $this->countedQuantity = null;
        $this->searchCode = '';
    }

    public function clearList(): void
    {
        $this->countedItems = [];
    }
}
