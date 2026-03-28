<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Locations ──
        $deposito = Location::firstOrCreate(['name' => 'Depósito Principal']);
        $showroom = Location::create(['name' => 'Showroom', 'is_active' => true]);

        // ── Categories ──
        $papeleria = Category::create(['name' => 'Papelería']);
        $escritura = Category::create(['name' => 'Escritura']);
        $oficina = Category::create(['name' => 'Insumos de Oficina']);
        $tecnologia = Category::create(['name' => 'Tecnología']);
        $limpieza = Category::create(['name' => 'Limpieza']);

        // ── Suppliers ──
        $ledesma = Supplier::create([
            'name' => 'Ledesma S.A.',
            'tax_id' => '30-50059544-3',
            'contact_name' => 'Carlos Méndez',
            'email' => 'ventas@ledesma.com.ar',
            'phone' => '0800-888-5337',
            'address' => 'Av. Corrientes 415, CABA',
            'payment_terms' => '30 días fecha factura',
        ]);

        $bic = Supplier::create([
            'name' => 'BIC Argentina',
            'tax_id' => '30-65789012-8',
            'contact_name' => 'María López',
            'email' => 'pedidos@bic.com.ar',
            'phone' => '011-4567-8901',
            'address' => 'Parque Industrial Pilar, Buenos Aires',
            'payment_terms' => '15 días fecha factura',
        ]);

        $maped = Supplier::create([
            'name' => 'Maped Argentina',
            'tax_id' => '30-71234567-5',
            'contact_name' => 'Juan Pérez',
            'email' => 'comercial@maped.com.ar',
            'phone' => '011-5432-1098',
            'address' => 'Zona Industrial Avellaneda, Buenos Aires',
            'payment_terms' => '30 días fecha factura',
        ]);

        $papelera = Supplier::create([
            'name' => 'Papelera del Plata',
            'tax_id' => '30-55667788-1',
            'contact_name' => 'Roberto Gómez',
            'email' => 'ventas@papeleradelplata.com.ar',
            'phone' => '0261-456-7890',
            'address' => 'Godoy Cruz, Mendoza',
            'payment_terms' => 'Contado',
        ]);

        $distribuidoraTec = Supplier::create([
            'name' => 'TecnoSuministros SRL',
            'tax_id' => '30-44556677-9',
            'contact_name' => 'Laura Fernández',
            'email' => 'info@tecnosuministros.com.ar',
            'phone' => '011-3456-7890',
            'address' => 'Microcentro, CABA',
            'payment_terms' => '60 días fecha factura',
        ]);

        // ── Products & Variants ──

        // 1. Resma A4 (producto simple, una sola variante)
        $resmaA4 = Product::create([
            'name' => 'Resma Ledesma A4 75g',
            'description' => 'Resma de papel A4 75 gramos, 500 hojas',
            'category_id' => $papeleria->id,
            'unit_of_measure' => 'ream',
        ]);
        $resmaA4Variant = Variant::create([
            'product_id' => $resmaA4->id,
            'sku' => 'RESMA-A4-75',
            'name' => 'Default',
        ]);

        // 2. Resma Oficio
        $resmaOficio = Product::create([
            'name' => 'Resma Ledesma Oficio 75g',
            'description' => 'Resma de papel tamaño oficio 75 gramos, 500 hojas',
            'category_id' => $papeleria->id,
            'unit_of_measure' => 'ream',
        ]);
        $resmaOficioVariant = Variant::create([
            'product_id' => $resmaOficio->id,
            'sku' => 'RESMA-OF-75',
            'name' => 'Default',
        ]);

        // 3. Lapicera BIC Cristal (con opciones de color)
        $lapiceraBic = Product::create([
            'name' => 'Lapicera BIC Cristal',
            'description' => 'Lapicera descartable punta media',
            'category_id' => $escritura->id,
            'unit_of_measure' => 'unit',
        ]);
        $colorOption = ProductOption::create([
            'product_id' => $lapiceraBic->id,
            'name' => 'Color',
        ]);
        $azul = ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Azul']);
        $negro = ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Negro']);
        $rojo = ProductOptionValue::create(['product_option_id' => $colorOption->id, 'value' => 'Rojo']);

        $bicAzul = Variant::create(['product_id' => $lapiceraBic->id, 'sku' => 'BIC-CRIS-AZ', 'name' => 'Azul']);
        $bicAzul->optionValues()->attach($azul);
        $bicNegro = Variant::create(['product_id' => $lapiceraBic->id, 'sku' => 'BIC-CRIS-NG', 'name' => 'Negro']);
        $bicNegro->optionValues()->attach($negro);
        $bicRojo = Variant::create(['product_id' => $lapiceraBic->id, 'sku' => 'BIC-CRIS-RJ', 'name' => 'Rojo']);
        $bicRojo->optionValues()->attach($rojo);

        // 4. Marcador Maped
        $marcador = Product::create([
            'name' => 'Marcador Maped Fluo',
            'description' => 'Resaltador fluorescente punta biselada',
            'category_id' => $escritura->id,
            'unit_of_measure' => 'unit',
        ]);
        $marcadorColorOpt = ProductOption::create(['product_id' => $marcador->id, 'name' => 'Color']);
        $amarillo = ProductOptionValue::create(['product_option_id' => $marcadorColorOpt->id, 'value' => 'Amarillo']);
        $verde = ProductOptionValue::create(['product_option_id' => $marcadorColorOpt->id, 'value' => 'Verde']);
        $rosa = ProductOptionValue::create(['product_option_id' => $marcadorColorOpt->id, 'value' => 'Rosa']);

        $marcAmarillo = Variant::create(['product_id' => $marcador->id, 'sku' => 'MAPED-FLUO-AM', 'name' => 'Amarillo']);
        $marcAmarillo->optionValues()->attach($amarillo);
        $marcVerde = Variant::create(['product_id' => $marcador->id, 'sku' => 'MAPED-FLUO-VE', 'name' => 'Verde']);
        $marcVerde->optionValues()->attach($verde);
        $marcRosa = Variant::create(['product_id' => $marcador->id, 'sku' => 'MAPED-FLUO-RS', 'name' => 'Rosa']);
        $marcRosa->optionValues()->attach($rosa);

        // 5. Cinta Scotch
        $cinta = Product::create([
            'name' => 'Cinta adhesiva Scotch 12mm x 30m',
            'category_id' => $oficina->id,
            'unit_of_measure' => 'unit',
        ]);
        $cintaVariant = Variant::create([
            'product_id' => $cinta->id,
            'sku' => 'CINTA-SCT-12',
            'name' => 'Default',
        ]);

        // 6. Sobre Manila A4
        $sobre = Product::create([
            'name' => 'Sobre Manila A4',
            'description' => 'Sobre manila tamaño A4, 80g',
            'category_id' => $papeleria->id,
            'unit_of_measure' => 'unit',
        ]);
        $sobreVariant = Variant::create([
            'product_id' => $sobre->id,
            'sku' => 'SOBRE-MAN-A4',
            'name' => 'Default',
        ]);

        // 7. Toner HP
        $toner = Product::create([
            'name' => 'Toner HP 85A Compatible',
            'description' => 'Toner compatible para HP LaserJet P1102/M1132',
            'category_id' => $tecnologia->id,
            'unit_of_measure' => 'unit',
        ]);
        $tonerVariant = Variant::create([
            'product_id' => $toner->id,
            'sku' => 'TONER-HP85A',
            'name' => 'Default',
        ]);

        // 8. Clips
        $clips = Product::create([
            'name' => 'Clips metálicos N°4',
            'description' => 'Clips metálicos niquelados N°4',
            'category_id' => $oficina->id,
            'unit_of_measure' => 'unit',
        ]);
        $clipsVariant = Variant::create([
            'product_id' => $clips->id,
            'sku' => 'CLIPS-N4',
            'name' => 'Default',
        ]);

        // 9. Carpeta A4
        $carpeta = Product::create([
            'name' => 'Carpeta presentación A4',
            'description' => 'Carpeta con tapa transparente y lomo de cartulina',
            'category_id' => $oficina->id,
            'unit_of_measure' => 'unit',
        ]);
        $carpetaColorOpt = ProductOption::create(['product_id' => $carpeta->id, 'name' => 'Color']);
        $azulCarp = ProductOptionValue::create(['product_option_id' => $carpetaColorOpt->id, 'value' => 'Azul']);
        $negroCarp = ProductOptionValue::create(['product_option_id' => $carpetaColorOpt->id, 'value' => 'Negro']);

        $carpetaAzul = Variant::create(['product_id' => $carpeta->id, 'sku' => 'CARP-A4-AZ', 'name' => 'Azul']);
        $carpetaAzul->optionValues()->attach($azulCarp);
        $carpetaNegra = Variant::create(['product_id' => $carpeta->id, 'sku' => 'CARP-A4-NG', 'name' => 'Negro']);
        $carpetaNegra->optionValues()->attach($negroCarp);

        // 10. Jabón líquido
        $jabon = Product::create([
            'name' => 'Jabón líquido para manos 5L',
            'category_id' => $limpieza->id,
            'unit_of_measure' => 'unit',
        ]);
        $jabonVariant = Variant::create([
            'product_id' => $jabon->id,
            'sku' => 'JABON-LIQ-5L',
            'name' => 'Default',
        ]);

        // ══════════════════════════════════════════════════
        // ── Supplier Variants (con unidades de compra) ──
        // ══════════════════════════════════════════════════

        // Ledesma → Resma A4 (vende por caja de 10 resmas Y por resma suelta)
        SupplierVariant::create([
            'supplier_id' => $ledesma->id,
            'variant_id' => $resmaA4Variant->id,
            'supplier_code' => 'LED-RA4-CJ10',
            'cost_price' => 42000,       // $42.000 la caja de 10
            'purchase_unit' => 'Caja x10',
            'purchase_unit_qty' => 10,
            'is_default' => true,
        ]);
        SupplierVariant::create([
            'supplier_id' => $ledesma->id,
            'variant_id' => $resmaA4Variant->id,
            'supplier_code' => 'LED-RA4-UN',
            'cost_price' => 4500,        // $4.500 la resma suelta
            'purchase_unit' => null,
            'purchase_unit_qty' => 1,
        ]);

        // Ledesma → Resma Oficio (solo caja de 10)
        SupplierVariant::create([
            'supplier_id' => $ledesma->id,
            'variant_id' => $resmaOficioVariant->id,
            'supplier_code' => 'LED-ROF-CJ10',
            'cost_price' => 48000,
            'purchase_unit' => 'Caja x10',
            'purchase_unit_qty' => 10,
            'is_default' => true,
        ]);

        // Papelera del Plata → Resma A4 (alternativa, vende por caja de 5)
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $resmaA4Variant->id,
            'supplier_code' => 'PP-RESMA-A4-5',
            'cost_price' => 22000,
            'purchase_unit' => 'Caja x5',
            'purchase_unit_qty' => 5,
        ]);

        // BIC → Lapiceras (vende por caja de 50 y por display de 12)
        foreach ([
            ['variant' => $bicAzul, 'color' => 'AZ'],
            ['variant' => $bicNegro, 'color' => 'NG'],
            ['variant' => $bicRojo, 'color' => 'RJ'],
        ] as $bv) {
            SupplierVariant::create([
                'supplier_id' => $bic->id,
                'variant_id' => $bv['variant']->id,
                'supplier_code' => "BIC-CRIS-{$bv['color']}-CJ50",
                'cost_price' => 15000,       // $15.000 la caja de 50
                'purchase_unit' => 'Caja x50',
                'purchase_unit_qty' => 50,
                'is_default' => true,
            ]);
            SupplierVariant::create([
                'supplier_id' => $bic->id,
                'variant_id' => $bv['variant']->id,
                'supplier_code' => "BIC-CRIS-{$bv['color']}-DP12",
                'cost_price' => 4200,        // $4.200 el display de 12
                'purchase_unit' => 'Display x12',
                'purchase_unit_qty' => 12,
            ]);
        }

        // Maped → Marcadores (vende por pack de 6 colores surtidos y por unidad)
        foreach ([
            ['variant' => $marcAmarillo, 'code' => 'AM'],
            ['variant' => $marcVerde, 'code' => 'VE'],
            ['variant' => $marcRosa, 'code' => 'RS'],
        ] as $mv) {
            SupplierVariant::create([
                'supplier_id' => $maped->id,
                'variant_id' => $mv['variant']->id,
                'supplier_code' => "MPD-FLUO-{$mv['code']}-CJ24",
                'cost_price' => 18000,
                'purchase_unit' => 'Caja x24',
                'purchase_unit_qty' => 24,
                'is_default' => true,
            ]);
            SupplierVariant::create([
                'supplier_id' => $maped->id,
                'variant_id' => $mv['variant']->id,
                'supplier_code' => "MPD-FLUO-{$mv['code']}-UN",
                'cost_price' => 950,
                'purchase_unit' => null,
                'purchase_unit_qty' => 1,
            ]);
        }

        // Papelera del Plata → Cinta Scotch (caja de 12 y unidad)
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $cintaVariant->id,
            'supplier_code' => 'PP-SCOTCH-12-CJ12',
            'cost_price' => 9600,
            'purchase_unit' => 'Caja x12',
            'purchase_unit_qty' => 12,
            'is_default' => true,
        ]);
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $cintaVariant->id,
            'supplier_code' => 'PP-SCOTCH-12-UN',
            'cost_price' => 900,
            'purchase_unit' => null,
            'purchase_unit_qty' => 1,
        ]);

        // Papelera del Plata → Sobre Manila (paquete de 100)
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $sobreVariant->id,
            'supplier_code' => 'PP-SOBRE-A4-100',
            'cost_price' => 12000,
            'purchase_unit' => 'Paquete x100',
            'purchase_unit_qty' => 100,
            'is_default' => true,
        ]);

        // TecnoSuministros → Toner HP (unidad)
        SupplierVariant::create([
            'supplier_id' => $distribuidoraTec->id,
            'variant_id' => $tonerVariant->id,
            'supplier_code' => 'TS-HP85A',
            'cost_price' => 25000,
            'purchase_unit' => null,
            'purchase_unit_qty' => 1,
            'is_default' => true,
        ]);

        // Papelera del Plata → Clips (caja de 100 clips y bolsa de 1000)
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $clipsVariant->id,
            'supplier_code' => 'PP-CLIPS-N4-100',
            'cost_price' => 800,
            'purchase_unit' => 'Cajita x100',
            'purchase_unit_qty' => 100,
            'is_default' => true,
        ]);
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $clipsVariant->id,
            'supplier_code' => 'PP-CLIPS-N4-1000',
            'cost_price' => 6500,
            'purchase_unit' => 'Bolsa x1000',
            'purchase_unit_qty' => 1000,
        ]);

        // Papelera del Plata → Carpetas (paquete de 25)
        foreach ([
            ['variant' => $carpetaAzul, 'code' => 'AZ'],
            ['variant' => $carpetaNegra, 'code' => 'NG'],
        ] as $cv) {
            SupplierVariant::create([
                'supplier_id' => $papelera->id,
                'variant_id' => $cv['variant']->id,
                'supplier_code' => "PP-CARP-{$cv['code']}-PQ25",
                'cost_price' => 7500,
                'purchase_unit' => 'Paquete x25',
                'purchase_unit_qty' => 25,
                'is_default' => true,
            ]);
        }

        // Papelera del Plata → Jabón líquido (bidón)
        SupplierVariant::create([
            'supplier_id' => $papelera->id,
            'variant_id' => $jabonVariant->id,
            'supplier_code' => 'PP-JABON-5L',
            'cost_price' => 5500,
            'purchase_unit' => null,
            'purchase_unit_qty' => 1,
            'is_default' => true,
        ]);

        // ══════════════════════════════════════
        // ── Inventory Levels (stock inicial) ──
        // ══════════════════════════════════════

        $stockData = [
            [$resmaA4Variant, $deposito, 45],
            [$resmaA4Variant, $showroom, 5],
            [$resmaOficioVariant, $deposito, 20],
            [$bicAzul, $deposito, 120],
            [$bicAzul, $showroom, 24],
            [$bicNegro, $deposito, 80],
            [$bicRojo, $deposito, 36],
            [$marcAmarillo, $deposito, 48],
            [$marcVerde, $deposito, 48],
            [$marcRosa, $deposito, 24],
            [$cintaVariant, $deposito, 30],
            [$sobreVariant, $deposito, 200],
            [$tonerVariant, $deposito, 3],       // bajo stock!
            [$clipsVariant, $deposito, 500],
            [$carpetaAzul, $deposito, 50],
            [$carpetaNegra, $deposito, 25],
            [$jabonVariant, $deposito, 2],        // bajo stock!
        ];

        foreach ($stockData as [$variant, $location, $qty]) {
            InventoryLevel::create([
                'variant_id' => $variant->id,
                'location_id' => $location->id,
                'quantity' => $qty,
                'min_stock' => max(5, intval($qty * 0.2)),
            ]);
        }

        $this->command->info('Dummy data created: 5 suppliers, 10 products, 17 variants, 30 supplier-variant links, inventory levels.');
    }
}
