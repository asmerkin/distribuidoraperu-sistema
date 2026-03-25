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
        return DB::transaction(function () use ($variant, $location, $type, $reason, $quantity, $reference, $notes, $userId) {
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
        });
    }

    private function calculateDelta(StockMovementType $type, int $quantity): int
    {
        return match ($type) {
            StockMovementType::Entrada => $quantity,
            StockMovementType::Salida => -$quantity,
            StockMovementType::Ajuste => $quantity, // quantity can be positive or negative for adjustments
            StockMovementType::Transferencia => 0, // handled by transfer method with two movements
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
            $outMovement = $this->recordMovementRaw(
                $variant, $from, StockMovementType::Salida, StockMovementReason::TransferenciaSalida,
                $quantity, $reference, $notes, $userId,
            );

            $inMovement = $this->recordMovementRaw(
                $variant, $to, StockMovementType::Entrada, StockMovementReason::TransferenciaEntrada,
                $quantity, $reference, $notes, $userId,
            );

            return [$outMovement, $inMovement];
        });
    }

    private function recordMovementRaw(
        Variant $variant,
        Location $location,
        StockMovementType $type,
        StockMovementReason $reason,
        int $quantity,
        ?Model $reference,
        ?string $notes,
        ?int $userId,
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

        $delta = match ($type) {
            StockMovementType::Entrada => $quantity,
            StockMovementType::Salida => -$quantity,
            default => 0,
        };

        if ($delta !== 0) {
            $inventoryLevel->increment('quantity', $delta);
        }

        return $movement;
    }
}
