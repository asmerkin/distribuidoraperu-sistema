<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Upload form --}}
        <x-filament::section>
            <x-slot name="heading">Subir lista de precios</x-slot>
            <x-slot name="description">Seleccioná el proveedor y subí el archivo. Se aceptan CSV, Excel, PDF e imágenes.</x-slot>

            <form wire:submit="uploadAndDispatch">
                {{ $this->uploadForm }}

                <div style="margin-top: 1.25rem;">
                    <x-filament::button
                        type="submit"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-arrow-up-tray"
                        size="lg"
                    >
                        <span wire:loading.remove wire:target="uploadAndDispatch">Subir y procesar</span>
                        <span wire:loading wire:target="uploadAndDispatch">Subiendo...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Recent imports --}}
        @php $recentImports = $this->getRecentImports(); @endphp
        @if($recentImports->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Importaciones recientes</x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--gray-200);">
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Proveedor</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Archivo</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Estado</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Items</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Fecha</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentImports as $import)
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">{{ $import->supplier->name }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-size: 0.8rem;">{{ implode(', ', $import->file_name ?? []) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;
                                            background: var(--{{ $import->status->color() }}-100);
                                            color: var(--{{ $import->status->color() }}-700);">
                                            {{ $import->status->label() }}
                                        </span>
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center; color: var(--gray-500); font-size: 0.8rem;">
                                        @if($import->items_extracted > 0)
                                            {{ $import->items_extracted }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-400); white-space: nowrap; font-size: 0.8rem;">
                                        {{ $import->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center;">
                                        <a
                                            href="{{ \App\Filament\Pages\PriceListReviewPage::getUrl(['record' => $import->id]) }}"
                                            style="font-size: 0.75rem; color: var(--primary-600); text-decoration: underline;"
                                        >
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
