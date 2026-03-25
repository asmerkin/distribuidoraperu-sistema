<?php

return [
    'purchase_order_status' => [
        'draft' => 'Borrador',
        'sent' => 'Enviada',
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

    'stock_count_status' => [
        'in_progress' => 'En progreso',
        'completed' => 'Completado',
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
];
