<?php

namespace App\Jobs;

use App\Enums\PriceListImportStatus;
use App\Models\PriceListImport;
use App\Services\PriceListMatcherService;
use App\Services\PriceListParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParsePriceListImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        public PriceListImport $import,
    ) {}

    public function handle(
        PriceListParserService $parser,
        PriceListMatcherService $matcher,
    ): void {
        $this->import->update(['status' => PriceListImportStatus::Parsing]);

        $allItems = [];

        foreach ($this->import->file_path as $filePath) {
            $items = $parser->parse($filePath, 'public');
            $allItems = array_merge($allItems, $items);
        }

        // Deduplicate by code — last occurrence wins (in case of overlapping lists)
        $deduplicated = collect($allItems)
            ->keyBy(fn ($item) => strtoupper(trim($item['code'])))
            ->values()
            ->all();

        if (empty($deduplicated)) {
            $this->import->update([
                'status' => PriceListImportStatus::Failed,
                'error_message' => 'No se pudieron extraer productos de los archivos.',
            ]);

            return;
        }

        $result = $matcher->match($this->import->supplier_id, $deduplicated);

        $draftData = [
            'changed' => collect($result['changed'])->map(fn ($item) => [...$item, 'selected' => true])->all(),
            'unchanged' => $result['unchanged'],
            'unmatched' => collect($result['unmatched'])->map(fn ($item) => [...$item, 'selected' => false])->all(),
        ];

        $this->import->update([
            'status' => PriceListImportStatus::Draft,
            'draft_data' => $draftData,
            'items_extracted' => count($deduplicated),
            'items_matched' => count($result['changed']) + count($result['unchanged']),
            'items_unchanged' => count($result['unchanged']),
            'items_unmatched' => count($result['unmatched']),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->import->update([
            'status' => PriceListImportStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
