<?php

namespace App\Models;

use App\Enums\PriceListImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListImport extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'file_path',
        'file_name',
        'items_extracted',
        'items_matched',
        'items_updated',
        'items_unchanged',
        'items_unmatched',
        'items_linked',
        'draft_data',
        'status',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PriceListImportStatus::class,
            'file_path' => 'array',
            'file_name' => 'array',
            'draft_data' => 'array',
            'items_extracted' => 'integer',
            'items_matched' => 'integer',
            'items_updated' => 'integer',
            'items_unchanged' => 'integer',
            'items_unmatched' => 'integer',
            'items_linked' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEditable(): bool
    {
        return $this->status === PriceListImportStatus::Draft;
    }

    public function isParsing(): bool
    {
        return in_array($this->status, [
            PriceListImportStatus::Uploading,
            PriceListImportStatus::Parsing,
        ]);
    }

    public function getChangedItems(): array
    {
        return $this->draft_data['changed'] ?? [];
    }

    public function getUnchangedItems(): array
    {
        return $this->draft_data['unchanged'] ?? [];
    }

    public function getUnmatchedItems(): array
    {
        return $this->draft_data['unmatched'] ?? [];
    }

    public function getSelectedChangedCount(): int
    {
        return count(array_filter(
            $this->getChangedItems(),
            fn ($item) => $item['selected'] ?? false
        ));
    }

    public function getSelectedLinkedCount(): int
    {
        return count(array_filter(
            $this->getUnmatchedItems(),
            fn ($item) => ($item['selected'] ?? false) && ($item['linked_variant_id'] ?? null)
        ));
    }
}
