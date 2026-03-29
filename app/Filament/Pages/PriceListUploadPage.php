<?php

namespace App\Filament\Pages;

use App\Enums\PriceListImportStatus;
use App\Jobs\ParsePriceListImportJob;
use App\Models\PriceListImport;
use App\Models\Supplier;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class PriceListUploadPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Importar Precios';

    protected static ?string $title = 'Importar Lista de Precios';

    protected static ?string $slug = 'price-list-import';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.price-list-upload';

    public ?string $supplierId = null;

    public ?array $fileUpload = [];

    public function mount(): void
    {
        if (request()->has('supplier')) {
            $this->supplierId = request()->get('supplier');
        }
    }

    public function uploadForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('supplierId')
                ->label('Proveedor')
                ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            FileUpload::make('fileUpload')
                ->label('Archivos')
                ->disk('public')
                ->directory('price-lists/temp')
                ->multiple()
                ->acceptedFileTypes([
                    'text/csv',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])
                ->maxSize(10240)
                ->required()
                ->helperText('CSV, Excel, PDF o imagen — máx. 10MB por archivo. Podés subir varios a la vez.'),
        ]);
    }

    public function getRecentImports(): \Illuminate\Support\Collection
    {
        return PriceListImport::with('supplier', 'user')
            ->latest()
            ->limit(15)
            ->get();
    }

    public function uploadAndDispatch(): void
    {
        $this->uploadForm->getState();

        if (! $this->supplierId || empty($this->fileUpload)) {
            return;
        }

        $filePaths = [];
        $fileNames = [];
        $timestamp = now()->format('Y-m-d_His');
        $directory = "price-lists/{$this->supplierId}";

        foreach ($this->fileUpload as $tempPath) {
            $originalName = basename($tempPath);
            $finalPath = "{$directory}/{$timestamp}_{$originalName}";
            Storage::disk('public')->move($tempPath, $finalPath);

            $filePaths[] = $finalPath;
            $fileNames[] = $originalName;
        }

        $import = PriceListImport::create([
            'supplier_id' => $this->supplierId,
            'user_id' => auth()->id(),
            'file_path' => $filePaths,
            'file_name' => $fileNames,
            'status' => PriceListImportStatus::Uploading,
        ]);

        ParsePriceListImportJob::dispatch($import);

        $this->redirect(PriceListReviewPage::getUrl(['record' => $import->id]));
    }
}
