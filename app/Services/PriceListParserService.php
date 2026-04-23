<?php

namespace App\Services;

use App\Adapters\GeminiAdapter;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

class PriceListParserService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a data extraction assistant. Extract all product items from this supplier price list.
For each item, extract:
- "code": the supplier's product code/SKU (alphanumeric identifier)
- "barcode": the product's barcode/EAN/UPC if available, otherwise empty string
- "description": the product description/name
- "price": the unit price as a number (no currency symbols)

Return ONLY a raw JSON array. No markdown, no code fences, no explanation. Example:
[{"code": "ABC123", "barcode": "7791234567890", "description": "Resma A4 75g", "price": 1500.00}]

Rules:
- If prices include taxes (IVA), extract the price as shown.
- If there are multiple price columns, use the one that appears to be the unit price.
- Round prices to 2 decimal places.
- Ignore header rows, totals, subtotals, and any non-product rows.
- If you cannot extract any items, return an empty array: []
- Do NOT wrap the output in markdown code fences.
PROMPT;

    private const RESPONSE_SCHEMA = [
        'type' => 'ARRAY',
        'items' => [
            'type' => 'OBJECT',
            'properties' => [
                'code' => ['type' => 'STRING'],
                'barcode' => ['type' => 'STRING'],
                'description' => ['type' => 'STRING'],
                'price' => ['type' => 'NUMBER'],
            ],
            'required' => ['code', 'description', 'price'],
        ],
    ];

    public function __construct(
        private GeminiAdapter $gemini,
    ) {}

    /**
     * Parse a price list file and return extracted items.
     *
     * @return array<int, array{code: string, description: string, price: float}>
     */
    public function parse(string $filePath, string $disk = 'public'): array
    {
        $fullPath = Storage::disk($disk)->path($filePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

        $parts = match (true) {
            in_array($extension, ['csv']) => $this->buildSpreadsheetParts($fullPath, 'csv'),
            in_array($extension, ['xlsx', 'xls']) => $this->buildSpreadsheetParts($fullPath, 'xlsx'),
            str_starts_with($mimeType, 'image/') => $this->buildFileParts($fullPath, $mimeType),
            $extension === 'pdf' || $mimeType === 'application/pdf' => $this->buildFileParts($fullPath, 'application/pdf'),
            default => throw new RuntimeException("Formato de archivo no soportado: {$extension}"),
        };

        if (empty($parts)) {
            return [];
        }

        $items = $this->gemini->generateJson(self::SYSTEM_PROMPT, $parts, self::RESPONSE_SCHEMA);

        return $this->normalizeItems($items);
    }

    private function buildSpreadsheetParts(string $fullPath, string $type): array
    {
        $reader = $type === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($fullPath);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($cell) => trim((string) $cell->getValue()), $row->getCells());
                if (implode('', $cells) !== '') {
                    $rows[] = implode(' | ', $cells);
                }
                if (count($rows) >= 2000) {
                    break 2;
                }
            }
        }
        $reader->close();

        if (empty($rows)) {
            return [];
        }

        return [
            $this->gemini->textPart("Extract products from this price list:\n\n".implode("\n", $rows)),
        ];
    }

    private function buildFileParts(string $fullPath, string $mimeType): array
    {
        return [
            $this->gemini->filePart($fullPath, $mimeType),
            $this->gemini->textPart('Extract all products with their codes, descriptions, and prices from this price list.'),
        ];
    }

    /**
     * @return array<int, array{code: string, description: string, price: float}>
     */
    private function normalizeItems(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (! is_array($item) || ! isset($item['code'], $item['price'])) {
                return null;
            }

            return [
                'code' => trim((string) $item['code']),
                'barcode' => trim((string) ($item['barcode'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'price' => round((float) $item['price'], 2),
            ];
        }, $items)));
    }
}
