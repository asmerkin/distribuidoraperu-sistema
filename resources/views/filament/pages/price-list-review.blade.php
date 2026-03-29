<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Parsing / Uploading: polling spinner --}}
        @if($record->isParsing())
            <div wire:poll.3s="checkStatus">
                <x-filament::section>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem 1rem;">
                        <div style="width: 3rem; height: 3rem; border: 3px solid var(--gray-200); border-top-color: var(--primary-600); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p style="font-size: 1.125rem; font-weight: 600; color: var(--gray-700);">
                            {{ $record->status->label() }}...
                        </p>
                        <p style="font-size: 0.875rem; color: var(--gray-500);">
                            Estamos analizando el archivo con IA. Podés esperar acá o volver después.
                        </p>
                        <a
                            href="{{ \App\Filament\Pages\PriceListUploadPage::getUrl() }}"
                            style="font-size: 0.875rem; color: var(--primary-600); text-decoration: underline;"
                        >
                            Volver a importaciones
                        </a>
                    </div>
                </x-filament::section>
            </div>
            <style>
                @keyframes spin { to { transform: rotate(360deg); } }
            </style>
        @endif

        {{-- Failed: error message + retry --}}
        @if($record->status === \App\Enums\PriceListImportStatus::Failed)
            <x-filament::section>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2rem 1rem;">
                    <div style="width: 3rem; height: 3rem; background: var(--danger-100); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--danger-600);">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p style="font-size: 1.125rem; font-weight: 600; color: var(--danger-700);">Error al procesar</p>
                    <p style="font-size: 0.875rem; color: var(--gray-600); text-align: center; max-width: 32rem;">
                        {{ $record->error_message }}
                    </p>
                    <div style="display: flex; gap: 0.75rem;">
                        <x-filament::button wire:click="retryParsing" icon="heroicon-o-arrow-path">
                            Reintentar
                        </x-filament::button>
                        <x-filament::button
                            color="gray"
                            tag="a"
                            href="{{ \App\Filament\Pages\PriceListUploadPage::getUrl() }}"
                            icon="heroicon-o-arrow-left"
                        >
                            Volver
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Draft: full review UI --}}
        @if($record->status === \App\Enums\PriceListImportStatus::Draft)

            {{-- Summary bar --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;">
                <div style="padding: 1rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning-600);">{{ count($changedItems) }}</div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">Con cambio de precio</div>
                </div>
                <div style="padding: 1rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--success-600);">{{ count($unchangedItems) }}</div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">Sin cambios</div>
                </div>
                <div style="padding: 1rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: 0.5rem; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger-600);">{{ count($unmatchedItems) }}</div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">No encontrados</div>
                </div>
            </div>

            {{-- Changed items table --}}
            @if(count($changedItems) > 0)
                <x-filament::section>
                    <x-slot name="heading">Precios a actualizar</x-slot>
                    <x-slot name="description">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span>{{ $this->getSelectedPriceCount() }} de {{ count($changedItems) }} seleccionados</span>
                            <button wire:click="selectAll" style="font-size: 0.75rem; color: var(--primary-600); text-decoration: underline; background: none; border: none; cursor: pointer;">
                                Seleccionar todos
                            </button>
                            <button wire:click="deselectAll" style="font-size: 0.75rem; color: var(--gray-500); text-decoration: underline; background: none; border: none; cursor: pointer;">
                                Deseleccionar
                            </button>
                        </div>
                    </x-slot>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-200);">
                                    <th style="text-align: center; padding: 0.5rem 0.5rem; width: 2.5rem;"></th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Cód. Prov.</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Producto</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">SKU</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Unidad</th>
                                    <th style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Precio actual</th>
                                    <th style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Precio nuevo</th>
                                    <th style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Var. %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changedItems as $index => $item)
                                    <tr style="border-bottom: 1px solid var(--gray-100);">
                                        <td style="text-align: center; padding: 0.5rem 0.5rem;">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleItem({{ $index }})"
                                                @if($item['selected'] ?? false) checked @endif
                                                style="width: 1rem; height: 1rem; accent-color: var(--primary-600);"
                                            />
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['code'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">
                                            {{ $item['product_name'] }}
                                            @if($item['variant_name'] ?? null)
                                                <span style="color: var(--gray-400);"> — {{ $item['variant_name'] }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['sku'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-size: 0.8rem;">{{ $item['purchase_unit'] ?? '—' }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right; color: var(--gray-500);">${{ number_format($item['current_price'], 2, ',', '.') }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 600; color: var(--gray-950);">${{ number_format($item['new_price'], 2, ',', '.') }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 600; font-size: 0.8rem;
                                            color: {{ ($item['pct_change'] ?? 0) > 0 ? 'var(--danger-600)' : 'var(--success-600)' }};">
                                            @if(($item['pct_change'] ?? null) !== null)
                                                {{ ($item['pct_change'] > 0 ? '+' : '') }}{{ $item['pct_change'] }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- Unchanged items (collapsed) --}}
            @if(count($unchangedItems) > 0)
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Sin cambios ({{ count($unchangedItems) }})</x-slot>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-200);">
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Cód. Prov.</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Producto</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">SKU</th>
                                    <th style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unchangedItems as $item)
                                    <tr style="border-bottom: 1px solid var(--gray-100);">
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['code'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">
                                            {{ $item['product_name'] }}
                                            @if($item['variant_name'] ?? null)
                                                <span style="color: var(--gray-400);"> — {{ $item['variant_name'] }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['sku'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right; color: var(--gray-500);">${{ number_format($item['current_price'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- Unmatched items (informational) --}}
            @if(count($unmatchedItems) > 0)
                <x-filament::section collapsible>
                    <x-slot name="heading">
                        <span style="color: var(--warning-600);">No encontrados ({{ count($unmatchedItems) }})</span>
                    </x-slot>
                    <x-slot name="description">
                        Estos códigos del proveedor no están vinculados a ningún producto del sistema.
                    </x-slot>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--gray-200);">
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Cód. Prov.</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Cód. Barras</th>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Descripción</th>
                                    <th style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unmatchedItems as $item)
                                    <tr style="border-bottom: 1px solid var(--gray-100);">
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['code'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-family: monospace; font-size: 0.8rem;">{{ $item['barcode'] ?? '—' }}</td>
                                        <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">{{ $item['description'] }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 600; color: var(--gray-950);">
                                            ${{ number_format($item['new_price'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- Action buttons --}}
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                @php $priceCount = $this->getSelectedPriceCount(); @endphp

                @if($priceCount > 0)
                    <x-filament::button
                        wire:click="applyChanges"
                        wire:loading.attr="disabled"
                        wire:confirm="¿Aplicar los cambios seleccionados? Esta acción no se puede deshacer."
                        icon="heroicon-o-check"
                        size="lg"
                    >
                        <span wire:loading.remove wire:target="applyChanges">
                            Aplicar cambios ({{ $priceCount }} precios)
                        </span>
                        <span wire:loading wire:target="applyChanges">Aplicando...</span>
                    </x-filament::button>
                @else
                    <x-filament::button
                        wire:click="markCompleted"
                        wire:confirm="No hay precios para actualizar. ¿Marcar como completada?"
                        icon="heroicon-o-check"
                        color="gray"
                        size="lg"
                    >
                        Marcar como completada
                    </x-filament::button>
                @endif

                <x-filament::button
                    wire:click="saveDraft"
                    color="{{ $isDirty ? 'warning' : 'gray' }}"
                    icon="heroicon-o-bookmark"
                    size="lg"
                >
                    Guardar borrador
                    @if($isDirty)
                        <span style="font-size: 0.7rem; opacity: 0.8;">(sin guardar)</span>
                    @endif
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    tag="a"
                    href="{{ \App\Filament\Pages\PriceListUploadPage::getUrl() }}"
                    size="lg"
                >
                    Volver
                </x-filament::button>
            </div>
        @endif

        {{-- Completed --}}
        @if($record->status === \App\Enums\PriceListImportStatus::Completed)
            <x-filament::section>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2rem 1rem;">
                    <div style="width: 3rem; height: 3rem; background: var(--success-100); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1.5rem; height: 1.5rem; color: var(--success-600);">
                            <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p style="font-size: 1.125rem; font-weight: 600; color: var(--gray-950);">Importación completada</p>
                    <p style="font-size: 0.875rem; color: var(--gray-600); text-align: center;">
                        Se actualizaron <strong>{{ $record->items_updated }}</strong> precios
                        @if($record->items_linked > 0)
                            y se crearon <strong>{{ $record->items_linked }}</strong> vínculos nuevos
                        @endif
                        para <strong>{{ $record->supplier->name }}</strong>.
                    </p>
                    <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Pages\PriceListUploadPage::getUrl() }}"
                            icon="heroicon-o-arrow-path"
                        >
                            Importar otra lista
                        </x-filament::button>
                        <x-filament::button
                            color="gray"
                            tag="a"
                            href="{{ \App\Filament\Resources\SupplierResource::getUrl('view', ['record' => $record->supplier_id]) }}"
                            icon="heroicon-o-arrow-left"
                        >
                            Ver proveedor
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif

    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</x-filament-panels::page>
