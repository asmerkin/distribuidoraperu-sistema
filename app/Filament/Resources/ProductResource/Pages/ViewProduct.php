<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\UnitOfMeasure;
use App\Filament\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductMergeService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        return "Producto: {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ActionGroup::make([
                Action::make('change_brand')
                    ->label('Cambiar marca')
                    ->icon('heroicon-o-bookmark')
                    ->modalHeading('Cambiar marca del producto')
                    ->modalSubmitActionLabel('Guardar')
                    ->fillForm(fn (): array => ['brand_id' => $this->getRecord()->brand_id])
                    ->form([
                        Select::make('brand_id')
                            ->label('Marca')
                            ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->action(function (array $data): void {
                        $this->getRecord()->update(['brand_id' => $data['brand_id']]);
                        Notification::make()->success()->title('Marca actualizada')->send();
                    }),
                Action::make('change_category')
                    ->label('Cambiar categoría')
                    ->icon('heroicon-o-tag')
                    ->modalHeading('Cambiar categoría del producto')
                    ->modalSubmitActionLabel('Guardar')
                    ->fillForm(fn (): array => ['category_id' => $this->getRecord()->category_id])
                    ->form([
                        Select::make('category_id')
                            ->label('Categoría')
                            ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->action(function (array $data): void {
                        $this->getRecord()->update(['category_id' => $data['category_id']]);
                        Notification::make()->success()->title('Categoría actualizada')->send();
                    }),
                Action::make('merge')
                    ->label('Fusionar con otro producto')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->modalHeading('Fusionar este producto en otro')
                    ->modalDescription('Las variantes de este producto se moverán al producto seleccionado y este producto será eliminado. Stock, historial y precios se conservan.')
                    ->modalSubmitActionLabel('Fusionar')
                    ->modalWidth('lg')
                    ->form(fn () => [
                        Select::make('target_product_id')
                            ->label('Producto destino (se conserva)')
                            ->options(fn () => Product::query()
                                ->where('id', '!=', $this->getRecord()->id)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $source = $this->getRecord();
                        $target = Product::findOrFail($data['target_product_id']);

                        $result = app(ProductMergeService::class)->merge($source, $target);

                        $body = "Se movieron {$result['variants_moved']} variante(s) a «{$target->name}».";
                        if ($result['variants_renamed'] > 0) {
                            $body .= " {$result['variants_renamed']} renombrada(s) desde «Default».";
                        }

                        Notification::make()
                            ->success()
                            ->title('Productos fusionados')
                            ->body($body)
                            ->send();

                        $this->redirect(ProductResource::getUrl('view', ['record' => $target->id]));
                    }),
            ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->label('Más acciones')
                ->button()
                ->color('gray'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')
                ->schema([
                    Grid::make(3)->schema([
                        Group::make([
                            Grid::make(2)->schema([
                                TextEntry::make('name')
                                    ->label('Nombre')
                                    ->weight(FontWeight::Bold)
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->label('Descripción')
                                    ->placeholder('—')
                                    ->columnSpanFull(),

                                TextEntry::make('brand.name')
                                    ->label('Marca')
                                    ->placeholder('—'),

                                TextEntry::make('category.name')
                                    ->label('Categoría')
                                    ->placeholder('—'),

                                TextEntry::make('unit_of_measure')
                                    ->label('Unidad de Medida')
                                    ->badge()
                                    ->formatStateUsing(fn (UnitOfMeasure $state): string => $state->label())
                                    ->color('gray'),

                                IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),
                            ]),
                        ])->columnSpan(2),

                        Group::make([
                            ImageEntry::make('images')
                                ->label('Imágenes')
                                ->disk('public')
                                ->placeholder('Sin imágenes')
                                ->columnSpanFull(),
                        ])->columnSpan(1),
                    ]),
                ]),
        ]);
    }
}
