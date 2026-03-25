<?php

namespace App\Filament\Pages;

use App\Enums\StockMovementReason;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Services\InventoryService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StockTransferPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Transferencias';

    protected static ?string $title = 'Transferencia de Stock';

    protected static ?string $slug = 'stock-transfer';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.stock-transfer';

    public string $searchCode = '';
    public ?string $foundVariantId = null;
    public ?string $fromLocationId = null;
    public ?string $toLocationId = null;
    public ?int $quantity = null;
    public string $notes = '';

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

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name !== 'Default' ? $variant->name : null,
        ];
    }

    public function getOriginStock(): ?int
    {
        if (! $this->foundVariantId || ! $this->fromLocationId) {
            return null;
        }

        $level = InventoryLevel::where('variant_id', $this->foundVariantId)
            ->where('location_id', $this->fromLocationId)
            ->first();

        return $level?->quantity ?? 0;
    }

    public function searchVariant(): void
    {
        $code = trim($this->searchCode);

        if (empty($code)) {
            return;
        }

        $variant = Variant::where('sku', $code)
            ->orWhere('barcode', $code)
            ->orWhere('sku', 'like', "%{$code}%")
            ->orWhere('barcode', 'like', "%{$code}%")
            ->first();

        if (! $variant) {
            $variant = Variant::whereHas('product', fn ($q) => $q->where('name', 'like', "%{$code}%"))
                ->first();
        }

        if (! $variant) {
            Notification::make()
                ->title('No encontrado')
                ->body("No se encontró variante con \"{$code}\".")
                ->danger()
                ->send();

            $this->searchCode = '';

            return;
        }

        $this->foundVariantId = $variant->id;
        $this->quantity = 1;
        $this->searchCode = '';
    }

    public function cancelSearch(): void
    {
        $this->foundVariantId = null;
        $this->quantity = null;
        $this->searchCode = '';
    }

    public function transfer(): void
    {
        if (! $this->foundVariantId || ! $this->fromLocationId || ! $this->toLocationId || ! $this->quantity) {
            Notification::make()
                ->title('Completá todos los campos')
                ->warning()
                ->send();

            return;
        }

        if ($this->fromLocationId === $this->toLocationId) {
            Notification::make()
                ->title('El origen y el destino deben ser distintos')
                ->danger()
                ->send();

            return;
        }

        if ($this->quantity <= 0) {
            Notification::make()
                ->title('La cantidad debe ser mayor a 0')
                ->danger()
                ->send();

            return;
        }

        $originStock = $this->getOriginStock();
        if ($originStock !== null && $this->quantity > $originStock) {
            Notification::make()
                ->title('Stock insuficiente')
                ->body("Hay {$originStock} unidades disponibles en el origen.")
                ->danger()
                ->send();

            return;
        }

        $variant = Variant::with('product')->find($this->foundVariantId);
        $from = Location::find($this->fromLocationId);
        $to = Location::find($this->toLocationId);

        if (! $variant || ! $from || ! $to) {
            return;
        }

        app(InventoryService::class)->transfer(
            variant: $variant,
            from: $from,
            to: $to,
            quantity: $this->quantity,
            notes: $this->notes ?: null,
            userId: auth()->id(),
        );

        $label = "[{$variant->sku}] {$variant->product->name}";
        if ($variant->name !== 'Default') {
            $label .= " — {$variant->name}";
        }

        Notification::make()
            ->title('Transferencia realizada')
            ->body("{$label}: {$this->quantity} ud. de {$from->name} → {$to->name}")
            ->success()
            ->send();

        $this->foundVariantId = null;
        $this->quantity = null;
        $this->notes = '';
        $this->searchCode = '';
    }

    public function getRecentTransfers(): \Illuminate\Support\Collection
    {
        return StockMovement::with(['variant.product', 'location', 'user'])
            ->where('reason', StockMovementReason::TransferenciaEntrada)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (StockMovement $movement) {
                $variant = $movement->variant;
                $label = "[{$variant->sku}] {$variant->product->name}";
                if ($variant->name !== 'Default') {
                    $label .= " — {$variant->name}";
                }

                return [
                    'label' => $label,
                    'quantity' => $movement->quantity,
                    'location' => $movement->location->name,
                    'notes' => $movement->notes,
                    'user' => $movement->user?->name ?? '—',
                    'date' => $movement->created_at->format('d/m/Y H:i'),
                ];
            });
    }
}
