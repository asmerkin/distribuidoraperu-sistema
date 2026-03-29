<?php

return [
    'purchase_order_status' => [
        'draft' => 'Borrador',
        'sent' => 'Enviada',
        'confirmed' => 'Confirmada',
        'rejected' => 'Rechazada',
        'partially_received' => 'Recibida parcial',
        'received' => 'Recibida',
        'cancelled' => 'Cancelada',
    ],

    'stock_movement_type' => [
        'in' => 'Entrada',
        'out' => 'Salida',
        'adjustment' => 'Ajuste',
        'transfer' => 'Transferencia',
    ],

    'stock_movement_reason' => [
        'purchase' => 'Compra',
        'stock_count' => 'Ajuste por conteo',
        'shrinkage' => 'Merma',
        'return' => 'Devolución',
        'transfer_in' => 'Transferencia (entrada)',
        'transfer_out' => 'Transferencia (salida)',
    ],

    'unit_of_measure' => [
        'unit' => 'Unidad',
        'box' => 'Caja',
        'ream' => 'Resma',
        'pack' => 'Pack',
        'roll' => 'Rollo',
        'meter' => 'Metro',
        'kg' => 'Kilogramo',
    ],

    'supplier_invoice_status' => [
        'unpaid' => 'Impaga',
        'partially_paid' => 'Pago parcial',
        'paid' => 'Pagada',
    ],

    'receipt_status' => [
        'completed' => 'Completada',
        'voided' => 'Anulada',
    ],

    'price_list_import_status' => [
        'uploading' => 'Subiendo',
        'parsing' => 'Procesando',
        'draft' => 'Borrador',
        'processing' => 'Aplicando',
        'completed' => 'Completada',
        'failed' => 'Error',
    ],
];
