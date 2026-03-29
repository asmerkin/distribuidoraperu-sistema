<?php

namespace App\Filament\Pages;

use App\Enums\PriceListImportStatus;
use App\Jobs\ParsePriceListImportJob;
use App\Models\PriceListImport;
use App\Models\SupplierVariant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PriceListReviewPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $slug = 'price-list-import/{record}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.price-list-review';

    public PriceListImport $record;

    public array $changedItems = [];

    public array $unchangedItems = [];

    public array $unmatchedItems = [];

    public bool $isDirty = false;

    public function mount(PriceListImport $record): void
    {
        $this->record = $record;

        if ($this->record->isEditable() || $this->record->status === PriceListImportStatus::Completed) {
            $this->loadDraftData();
        }
    }

    public function getTitle(): string
    {
        return "Importación — {$this->record->supplier->name}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            PriceListUploadPage::getUrl() => 'Importar Precios',
            '' => $this->record->file_name,
        ];
    }

    public function checkStatus(): void
    {
        $this->record->refresh();

        if ($this->record->isEditable()) {
            $this->loadDraftData();
        }
    }

    private function loadDraftData(): void
    {
        $this->changedItems = $this->record->getChangedItems();
        $this->unchangedItems = $this->record->getUnchangedItems();
        $this->unmatchedItems = $this->record->getUnmatchedItems();
        $this->isDirty = false;
    }

    public function toggleItem(int $index): void
    {
        if (isset($this->changedItems[$index])) {
            $this->changedItems[$index]['selected'] = ! ($this->changedItems[$index]['selected'] ?? false);
            $this->isDirty = true;
        }
    }

    public function selectAll(): void
    {
        foreach ($this->changedItems as $index => $item) {
            $this->changedItems[$index]['selected'] = true;
        }
        $this->isDirty = true;
    }

    public function deselectAll(): void
    {
        foreach ($this->changedItems as $index => $item) {
            $this->changedItems[$index]['selected'] = false;
        }
        $this->isDirty = true;
    }

    public function saveDraft(): void
    {
        $this->record->update([
            'draft_data' => [
                'changed' => $this->changedItems,
                'unchanged' => $this->unchangedItems,
                'unmatched' => $this->unmatchedItems,
            ],
        ]);

        $this->isDirty = false;

        Notification::make()
            ->title('Borrador guardado')
            ->success()
            ->send();
    }

    public function getSelectedPriceCount(): int
    {
        return count(array_filter($this->changedItems, fn ($item) => $item['selected'] ?? false));
    }

    public function applyChanges(): void
    {
        $this->record->refresh();

        if ($this->record->status !== PriceListImportStatus::Draft) {
            Notification::make()
                ->title('Esta importación ya no está en borrador')
                ->danger()
                ->send();

            return;
        }

        // Save draft first
        $this->record->update([
            'draft_data' => [
                'changed' => $this->changedItems,
                'unchanged' => $this->unchangedItems,
                'unmatched' => $this->unmatchedItems,
            ],
            'status' => PriceListImportStatus::Processing,
        ]);

        DB::transaction(function () {
            $pricesUpdated = 0;

            foreach ($this->changedItems as $item) {
                if (! ($item['selected'] ?? false)) {
                    continue;
                }

                $sv = SupplierVariant::find($item['supplier_variant_id']);
                if ($sv) {
                    $sv->update(['cost_price' => $item['new_price']]);
                    $pricesUpdated++;
                }
            }

            $this->record->update([
                'status' => PriceListImportStatus::Completed,
                'items_updated' => $pricesUpdated,
                'completed_at' => now(),
            ]);
        });

        $this->record->refresh();

        Notification::make()
            ->title('Importación completada')
            ->body("Se actualizaron {$this->record->items_updated} precios")
            ->success()
            ->send();
    }

    public function markCompleted(): void
    {
        $this->record->update([
            'status' => PriceListImportStatus::Completed,
            'items_updated' => 0,
            'completed_at' => now(),
        ]);

        $this->record->refresh();

        Notification::make()
            ->title('Importación completada')
            ->body('No había precios para actualizar.')
            ->success()
            ->send();
    }

    public function retryParsing(): void
    {
        $this->record->update([
            'status' => PriceListImportStatus::Uploading,
            'error_message' => null,
        ]);

        ParsePriceListImportJob::dispatch($this->record);
    }
}
