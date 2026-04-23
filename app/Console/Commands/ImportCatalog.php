<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ImportCatalog extends Command
{
    protected $signature = 'catalog:import
                            {file : Path to the xlsx file (jerarquico format)}
                            {--dry-run : Parse and report without writing to DB}';

    protected $description = 'Import product catalog from jerarquico xlsx (Productos + Variaciones sheets)';

    /** @var array<string, string> excel_product_id => Product name */
    private array $productNames = [];

    /** @var array<string, string> name => brand_id */
    private array $brandCache = [];

    /** @var array<string, string> "parent|child" => category_id (child is the one assigned to products) */
    private array $categoryCache = [];

    /** @var array<string, string> excel_product_id => internal Product ULID */
    private array $productIdMap = [];

    private int $skippedDiscontinued = 0;

    private int $skippedExistingSku = 0;

    private int $createdProducts = 0;

    private int $createdVariants = 0;

    private int $createdBrands = 0;

    private int $createdCategories = 0;

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("Archivo no encontrado: {$file}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info('→ Leyendo hoja Productos...');
        $this->loadProductNames($file);
        $this->info(sprintf('  %d productos padre cargados', count($this->productNames)));

        $this->info('→ Leyendo hoja Variaciones...');
        $rows = $this->loadVariantRows($file);
        $this->info(sprintf('  %d filas de variantes', count($rows)));

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: no se escribe en DB');
            $this->report($rows, dryRun: true);

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            $this->preloadExistingData();
            $this->importRows($rows);
        });

        $this->report($rows, dryRun: false);

        return self::SUCCESS;
    }

    private function loadProductNames(string $file): void
    {
        $reader = new XlsxReader;
        $reader->open($file);

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() !== 'Productos') {
                continue;
            }

            $header = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($header) {
                    $header = false;

                    continue;
                }
                $cells = $row->toArray();
                [$pid, $name] = [$cells[0] ?? null, $cells[1] ?? null];
                if ($pid && $name) {
                    $this->productNames[(string) $pid] = trim((string) $name);
                }
            }
            break;
        }

        $reader->close();
    }

    /** @return array<int, array{sku:string, pid:string, brand:?string, cat:string, subcat:string, desc_original:?string, desc_variante:?string, estado:?string}> */
    private function loadVariantRows(string $file): array
    {
        $reader = new XlsxReader;
        $reader->open($file);

        $out = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() !== 'Variaciones') {
                continue;
            }

            $header = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($header) {
                    $header = false;

                    continue;
                }
                $cells = $row->toArray();
                $sku = isset($cells[0]) ? trim((string) $cells[0]) : '';
                $pid = isset($cells[1]) ? trim((string) $cells[1]) : '';
                if ($sku === '' || $pid === '') {
                    continue;
                }
                $out[] = [
                    'sku' => $sku,
                    'pid' => $pid,
                    'brand' => $this->nullableString($cells[2] ?? null),
                    'cat' => trim((string) ($cells[3] ?? '')),
                    'subcat' => trim((string) ($cells[4] ?? '')),
                    'desc_original' => $this->nullableString($cells[5] ?? null),
                    'desc_variante' => $this->nullableString($cells[6] ?? null),
                    'estado' => $this->nullableString($cells[8] ?? null),
                ];
            }
            break;
        }

        $reader->close();

        return $out;
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function preloadExistingData(): void
    {
        foreach (Brand::all(['id', 'name']) as $b) {
            $this->brandCache[mb_strtolower($b->name)] = $b->id;
        }
        foreach (Variant::pluck('sku')->all() as $sku) {
            // track existing skus via a flag set
            $this->existingSkus[$sku] = true;
        }
    }

    /** @var array<string, true> */
    private array $existingSkus = [];

    private function importRows(array $rows): void
    {
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $r) {
            if (($r['estado'] ?? null) === 'Descontinuado') {
                $this->skippedDiscontinued++;
                $bar->advance();

                continue;
            }
            if (isset($this->existingSkus[$r['sku']])) {
                $this->skippedExistingSku++;
                $bar->advance();

                continue;
            }

            $brandId = $r['brand'] ? $this->getOrCreateBrand($r['brand']) : null;
            $categoryId = $this->getOrCreateCategory($r['cat'], $r['subcat']);
            $productId = $this->getOrCreateProduct($r['pid'], $brandId, $categoryId);

            Variant::create([
                'product_id' => $productId,
                'sku' => $r['sku'],
                'barcode' => null,
                'name' => $r['desc_variante'] ?? 'Default',
                'is_active' => true,
            ]);
            $this->createdVariants++;
            $this->existingSkus[$r['sku']] = true;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function getOrCreateBrand(string $name): string
    {
        $key = mb_strtolower($name);
        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }
        $brand = Brand::create(['name' => $name]);
        $this->brandCache[$key] = $brand->id;
        $this->createdBrands++;

        return $brand->id;
    }

    private function getOrCreateCategory(string $parentName, string $childName): string
    {
        $key = $parentName.'|'.$childName;
        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key];
        }

        $parent = Category::firstOrCreate(['name' => $parentName, 'parent_id' => null]);
        if (! $parent->wasRecentlyCreated) {
            // no-op
        } else {
            $this->createdCategories++;
        }

        $child = Category::firstOrCreate([
            'name' => $childName,
            'parent_id' => $parent->id,
        ]);
        if ($child->wasRecentlyCreated) {
            $this->createdCategories++;
        }

        $this->categoryCache[$key] = $child->id;

        return $child->id;
    }

    private function getOrCreateProduct(string $excelPid, ?string $brandId, string $categoryId): string
    {
        if (isset($this->productIdMap[$excelPid])) {
            return $this->productIdMap[$excelPid];
        }

        $name = $this->productNames[$excelPid] ?? $excelPid;

        $product = Product::create([
            'name' => $name,
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'is_active' => true,
        ]);
        $this->productIdMap[$excelPid] = $product->id;
        $this->createdProducts++;

        return $product->id;
    }

    private function report(array $rows, bool $dryRun): void
    {
        $this->newLine();
        $this->info($dryRun ? '=== DRY-RUN REPORT ===' : '=== IMPORT SUMMARY ===');

        if ($dryRun) {
            $byStatus = ['Activo' => 0, 'Descontinuado' => 0, 'otro' => 0];
            $brandsNovel = [];
            $catPairs = [];
            $uniquePids = [];
            $noBrand = 0;
            foreach ($rows as $r) {
                $status = $r['estado'] ?? 'otro';
                $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
                if ($r['estado'] === 'Descontinuado') {
                    continue;
                }
                if ($r['brand']) {
                    $brandsNovel[mb_strtolower($r['brand'])] = $r['brand'];
                } else {
                    $noBrand++;
                }
                $catPairs[$r['cat'].'|'.$r['subcat']] = true;
                $uniquePids[$r['pid']] = true;
            }
            $this->table(['Métrica', 'Valor'], [
                ['Filas totales', count($rows)],
                ['Activos (a importar)', $byStatus['Activo']],
                ['Descontinuados (skip)', $byStatus['Descontinuado']],
                ['Productos padre únicos (activos)', count($uniquePids)],
                ['Marcas únicas (de activos)', count($brandsNovel)],
                ['Variantes activas sin marca', $noBrand],
                ['Pares categoría/subcategoría', count($catPairs)],
            ]);

            return;
        }

        $this->table(['Métrica', 'Valor'], [
            ['Marcas creadas', $this->createdBrands],
            ['Categorías creadas', $this->createdCategories],
            ['Productos creados', $this->createdProducts],
            ['Variantes creadas', $this->createdVariants],
            ['Saltadas (descontinuadas)', $this->skippedDiscontinued],
            ['Saltadas (SKU ya existía)', $this->skippedExistingSku],
        ]);
    }
}
