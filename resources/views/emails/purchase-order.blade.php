<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.6;">
    <p>Estimado/a {{ $purchaseOrder->supplier->contact_name ?? $purchaseOrder->supplier->name }},</p>

    <p>Adjuntamos la <strong>Orden de Compra {{ $purchaseOrder->po_number }}</strong> con fecha {{ $purchaseOrder->order_date->format('d/m/Y') }}.</p>

    @if($purchaseOrder->expected_date)
        <p>Fecha de entrega estimada: <strong>{{ $purchaseOrder->expected_date->format('d/m/Y') }}</strong></p>
    @endif

    <p>Total: <strong>$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}</strong></p>

    @if($purchaseOrder->notes_for_supplier)
        <p style="padding: 10px; background-color: #f5f5f5; border-left: 3px solid #dc2626;">
            {{ $purchaseOrder->notes_for_supplier }}
        </p>
    @endif

    <p>El detalle completo se encuentra en el PDF adjunto.</p>

    <p>
        Saludos,<br>
        <strong>{{ $companyName ?? 'Distribuidora Perú' }}</strong>
    </p>
</body>
</html>
