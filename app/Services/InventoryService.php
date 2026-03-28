<?php

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function recordMovement(
        Variant $variant,
        Location $location,
        StockMovementType $type,
        StockMovementReason $reason,
        int $quantity,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $userId = null,
    ): StockMovement {
        $movement = StockMovement::create([
            'variant_id' => $variant->id,
            'location_id' => $location->id,
            'type' => $type,
            'reason' => $reason,
            'quantity' => $quantity,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
            'user_id' => $userId,
        ]);

        $inventoryLevel = InventoryLevel::firstOrCreate(
            ['variant_id' => $variant->id, 'location_id' => $location->id],
            ['quantity' => 0, 'min_stock' => 0],
        );

        $delta = $this->calculateDelta($type, $quantity);
        $inventoryLevel->increment('quantity', $delta);

        return $movement;
    }

    private function calculateDelta(StockMovementType $type, int $quantity): int
    {
        return match ($type) {
            StockMovementType::In => $quantity,
            StockMovementType::Out => -$quantity,
            StockMovementType::Adjustment => $quantity, // quantity can be positive or negative for adjustments
        };
    }

    public function transfer(
        Variant $variant,
        Location $from,
        Location $to,
        int $quantity,
        ?string $notes = null,
        ?int $userId = null,
        ?Model $reference = null,
    ): array {
        return DB::transaction(function () use ($variant, $from, $to, $quantity, $notes, $userId, $reference) {
            $outMovement = $this->recordMovement(
                variant: $variant,
                location: $from,
                type: StockMovementType::Out,
                reason: StockMovementReason::TransferOut,
                quantity: $quantity,
                reference: $reference,
                notes: $notes,
                userId: $userId,
            );

            $inMovement = $this->recordMovement(
                variant: $variant,
                location: $to,
                type: StockMovementType::In,
                reason: StockMovementReason::TransferIn,
                quantity: $quantity,
                reference: $reference,
                notes: $notes,
                userId: $userId,
            );

            return [$outMovement, $inMovement];
        });
    }
}
