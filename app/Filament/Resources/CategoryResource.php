<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Services\CategoryMergeService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $modelLabel = 'Categoría';

    protected static ?string $pluralModelLabel = 'Categorías';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->unique(
                    table: Category::class,
                    column: 'name',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule->where('parent_id', $get('parent_id'))
                )
                ->validationMessages([
                    'unique' => 'Ya existe una categoría con ese nombre en el mismo nivel.',
                ])
                ->columnSpanFull(),

            Select::make('parent_id')
                ->label('Categoría padre')
                ->options(function (?Model $record) {
                    return Category::query()
                        ->whereNull('parent_id')
                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->placeholder('Sin categoría padre')
                ->disabled(fn (?Model $record) => $record instanceof Category && $record->children()->exists())
                ->helperText(fn (?Model $record) => $record instanceof Category && $record->children()->exists()
                    ? 'Esta categoría tiene subcategorías, por eso no puede tener un padre (máx. 2 niveles).'
                    : null)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state, Category $record) => $record->parent_id
                        ? new \Illuminate\Support\HtmlString('<span class="ps-6">↳ '.e($state).'</span>')
                        : $state),

                TextColumn::make('children_count')
                    ->label('Subcategorías')
                    ->counts('children')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->badge()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->paginated(false)
            ->groups([
                Group::make('parent.name')
                    ->label('Categoría raíz')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible()
                    ->getKeyFromRecordUsing(fn (Category $record): string => $record->parent_id === null
                        ? $record->name
                        : ($record->parent?->name ?? ''))
                    ->getTitleFromRecordUsing(fn (Category $record): string => $record->parent_id === null
                        ? $record->name
                        : ($record->parent?->name ?? ''))
                    ->orderQueryUsing(function ($query, string $direction) {
                        $dir = strtolower($direction) === 'desc' ? 'desc' : 'asc';

                        return $query
                            ->orderByRaw("COALESCE((SELECT name FROM categories AS p WHERE p.id = categories.parent_id), categories.name) {$dir}")
                            ->orderByRaw('CASE WHEN categories.parent_id IS NULL THEN 0 ELSE 1 END')
                            ->orderBy('categories.name', $dir);
                    }),
            ])
            ->defaultGroup('parent.name')
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Categoría padre')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction('edit')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('merge')
                        ->label('Fusionar seleccionadas')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Fusionar categorías')
                        ->modalDescription('Todas las seleccionadas deben ser del mismo nivel (todas raíces o todas subcategorías). Elegí la canónica: el resto se fusiona en ella.')
                        ->modalSubmitActionLabel('Fusionar')
                        ->modalWidth('lg')
                        ->form(fn (Collection $records) => [
                            Select::make('canonical_id')
                                ->label('Categoría canónica (se conserva)')
                                ->options(function () use ($records) {
                                    $records->loadMissing('parent:id,name');

                                    return $records
                                        ->mapWithKeys(fn (Category $c) => [
                                            $c->id => $c->parent_id === null
                                                ? $c->name.' (raíz, '.$c->products_count.' prod.)'
                                                : ($c->parent?->name ?? '').' › '.$c->name.' ('.$c->products_count.' prod.)',
                                        ])
                                        ->all();
                                })
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            if ($records->count() < 2) {
                                Notification::make()->warning()->title('Seleccioná al menos 2 categorías')->send();

                                return;
                            }

                            $hasRoots = $records->contains(fn (Category $c) => $c->parent_id === null);
                            $hasSubcats = $records->contains(fn (Category $c) => $c->parent_id !== null);
                            if ($hasRoots && $hasSubcats) {
                                Notification::make()
                                    ->danger()
                                    ->title('Niveles mezclados')
                                    ->body('Todas las seleccionadas deben ser del mismo nivel (todas raíces o todas subcategorías).')
                                    ->send();

                                return;
                            }

                            /** @var Category $target */
                            $target = $records->firstWhere('id', $data['canonical_id']);
                            $sources = $records->reject(fn (Category $c) => $c->id === $target->id);

                            $totalProducts = 0;
                            $totalChildrenMerged = 0;
                            $totalChildrenMoved = 0;

                            DB::transaction(function () use ($sources, $target, &$totalProducts, &$totalChildrenMerged, &$totalChildrenMoved): void {
                                $service = app(CategoryMergeService::class);
                                foreach ($sources as $source) {
                                    $result = $service->merge($source, $target);
                                    $totalProducts += $result['products_moved'];
                                    $totalChildrenMerged += $result['children_merged'];
                                    $totalChildrenMoved += $result['children_moved'];
                                }
                            });

                            $parts = ["{$totalProducts} producto(s) reasignado(s)"];
                            if ($totalChildrenMerged > 0) {
                                $parts[] = "{$totalChildrenMerged} subcategoría(s) fusionada(s)";
                            }
                            if ($totalChildrenMoved > 0) {
                                $parts[] = "{$totalChildrenMoved} subcategoría(s) reubicada(s)";
                            }

                            Notification::make()
                                ->success()
                                ->title("Fusionadas {$sources->count()} categoría(s) en «{$target->name}»")
                                ->body(implode(', ', $parts).'.')
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
