<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\ScannerDevice;
use App\Models\Variant;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScannerApiController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $hash = hash('sha256', $request->otp);

        $device = ScannerDevice::where('otp', $hash)
            ->where('otp_expires_at', '>', now())
            ->where('is_active', true)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'OTP invalido o expirado',
            ], 401);
        }

        $rawToken = Str::random(64);

        $device->update([
            'token' => hash('sha256', $rawToken),
            'otp' => null,
            'otp_expires_at' => null,
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $rawToken,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'location' => [
                    'id' => $device->location->id,
                    'name' => $device->location->name,
                ],
            ],
        ]);
    }

    public function device(Request $request): JsonResponse
    {
        $device = auth('scanner')->user();

        return response()->json([
            'id' => $device->id,
            'name' => $device->name,
            'location' => [
                'id' => $device->location->id,
                'name' => $device->location->name,
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|min:1',
        ]);

        $code = trim($request->code);
        $device = auth('scanner')->user();

        $variant = Variant::where('sku', $code)
            ->orWhere('barcode', $code)
            ->first();

        if (! $variant) {
            $variant = Variant::where('sku', 'like', "%{$code}%")
                ->orWhere('barcode', 'like', "%{$code}%")
                ->first();
        }

        if (! $variant) {
            $variant = Variant::whereHas('product', fn ($q) => $q->where('name', 'like', "%{$code}%"))
                ->first();
        }

        if (! $variant) {
            return response()->json([
                'message' => "No se encontro variante con codigo \"{$code}\"",
            ], 404);
        }

        $currentStock = InventoryLevel::where('variant_id', $variant->id)
            ->where('location_id', $device->location_id)
            ->first()?->quantity ?? 0;

        return response()->json([
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'name' => $variant->name,
                'product_name' => $variant->product->name,
                'cost_price' => $variant->cost_price,
                'current_stock' => $currentStock,
            ],
        ]);
    }

    public function adjust(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|string|exists:variants,id',
            'counted_quantity' => 'required|integer|min:0',
        ]);

        $device = auth('scanner')->user();
        $variant = Variant::findOrFail($request->variant_id);
        $location = $device->location;

        $currentStock = InventoryLevel::where('variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->first()?->quantity ?? 0;

        $counted = (int) $request->counted_quantity;
        $diff = $counted - $currentStock;

        if ($diff === 0) {
            return response()->json([
                'message' => 'Sin diferencia',
                'previous_stock' => $currentStock,
                'counted' => $counted,
                'diff' => 0,
                'new_stock' => $currentStock,
            ]);
        }

        $inventoryService->recordMovement(
            variant: $variant,
            location: $location,
            type: StockMovementType::Adjustment,
            reason: StockMovementReason::StockCount,
            quantity: $diff,
            reference: $device,
            notes: 'Conteo fisico (scanner)',
        );

        return response()->json([
            'message' => 'Stock ajustado',
            'previous_stock' => $currentStock,
            'counted' => $counted,
            'diff' => $diff,
            'new_stock' => $counted,
        ]);
    }

    public function quickAdjust(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|string|exists:variants,id',
            'quantity' => 'required|integer|not_in:0',
        ]);

        $device = auth('scanner')->user();
        $variant = Variant::findOrFail($request->variant_id);
        $location = $device->location;

        $currentStock = InventoryLevel::where('variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->first()?->quantity ?? 0;

        $qty = (int) $request->quantity;

        $type = $qty > 0 ? StockMovementType::In : StockMovementType::Out;

        $inventoryService->recordMovement(
            variant: $variant,
            location: $location,
            type: $type,
            reason: StockMovementReason::StockCount,
            quantity: abs($qty),
            reference: $device,
            notes: 'Ajuste rapido (scanner)',
        );

        return response()->json([
            'message' => $qty > 0 ? "Se agregaron $qty unidades" : 'Se quitaron ' . abs($qty) . ' unidades',
            'previous_stock' => $currentStock,
            'adjustment' => $qty,
            'new_stock' => $currentStock + $qty,
        ]);
    }
}
