<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryMergeService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ActionGroup::make([
                Action::make('merge')
                    ->label(fn (): string => $this->getRecord()->parent_id === null
                        ? 'Fusionar con otra categoría raíz'
                        : 'Fusionar con otra subcategoría')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->modalHeading(fn (): string => $this->getRecord()->parent_id === null
                        ? 'Fusionar esta categoría raíz en otra'
                        : 'Fusionar esta subcategoría en otra')
                    ->modalDescription(fn (): string => $this->getRecord()->parent_id === null
                        ? 'Las subcategorías con el mismo nombre se fusionarán; las demás se moverán bajo la categoría destino. Esta categoría se elimina al final.'
                        : 'Los productos se moverán a la subcategoría seleccionada y esta se elimina.')
                    ->modalSubmitActionLabel('Fusionar')
                    ->modalWidth('lg')
                    ->form(fn () => [
                        Select::make('target_id')
                            ->label('Categoría destino (se conserva)')
                            ->options(function () {
                                $source = $this->getRecord();
                                if ($source->parent_id === null) {
                                    return Category::query()
                                        ->whereNull('parent_id')
                                        ->where('id', '!=', $source->id)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }

                                return Category::query()
                                    ->whereNotNull('parent_id')
                                    ->where('id', '!=', $source->id)
                                    ->with('parent:id,name')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Category $c) => [
                                        $c->id => ($c->parent?->name ?? '').' › '.$c->name,
                                    ]);
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $source = $this->getRecord();
                        $target = Category::findOrFail($data['target_id']);

                        $result = app(CategoryMergeService::class)->merge($source, $target);

                        $parts = ["{$result['products_moved']} producto(s) reasignado(s)"];
                        if ($result['children_merged'] > 0) {
                            $parts[] = "{$result['children_merged']} subcategoría(s) fusionada(s)";
                        }
                        if ($result['children_moved'] > 0) {
                            $parts[] = "{$result['children_moved']} subcategoría(s) reubicada(s)";
                        }

                        Notification::make()
                            ->success()
                            ->title('Categorías fusionadas')
                            ->body('En «'.$target->name.'»: '.implode(', ', $parts).'.')
                            ->send();

                        $this->redirect(CategoryResource::getUrl('edit', ['record' => $target->id]));
                    }),
            ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->label('Más acciones')
                ->button()
                ->color('gray'),
        ];
    }
}
