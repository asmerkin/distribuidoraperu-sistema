<x-filament-panels::page>
    <div class="fi-page-stock-count" style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Location selector --}}
        <x-filament::section>
            <x-slot name="heading">Ubicación</x-slot>
            <select
                wire:model.live="locationId"
                style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 0.875rem; background: var(--white); color: var(--gray-950);"
            >
                <option value="">Seleccioná una ubicación...</option>
                @foreach($this->getLocations() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </x-filament::section>

        {{-- Scanner / Search --}}
        @if($locationId)
            <x-filament::section>
                @if(! $foundVariantId)
                    <x-slot name="heading">Escanear o buscar producto</x-slot>

                    <div style="display: flex; gap: 0.75rem; align-items: flex-end;">
                        <div style="flex: 1;">
                            <x-filament::input.wrapper>
                                <input
                                    type="text"
                                    wire:model="searchCode"
                                    wire:keydown.enter="searchVariant"
                                    placeholder="SKU o código de barras..."
                                    autofocus
                                    id="barcode-input"
                                    style="width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; border: none; outline: none; background: transparent; color: var(--gray-950);"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <x-filament::button wire:click="searchVariant" icon="heroicon-o-magnifying-glass">
                            Buscar
                        </x-filament::button>
                        <x-filament::button color="gray" onclick="startCamera()" icon="heroicon-o-camera">
                            Cámara
                        </x-filament::button>
                    </div>

                    {{-- Camera preview --}}
                    <div id="camera-container" style="display: none; margin-top: 1rem;">
                        <div id="camera-reader" style="max-width: 400px; margin: 0 auto; border-radius: 0.5rem; overflow: hidden;"></div>
                        <div style="margin-top: 0.5rem; text-align: center;">
                            <x-filament::button color="danger" size="sm" onclick="stopCamera()" icon="heroicon-o-x-mark">
                                Cerrar cámara
                            </x-filament::button>
                        </div>
                    </div>

                @else
                    {{-- Found variant --}}
                    @php $variant = $this->getFoundVariant(); @endphp
                    @if($variant)
                        <x-slot name="heading">
                            {{ $variant['product_name'] }}
                            @if($variant['variant_name'])
                                — {{ $variant['variant_name'] }}
                            @endif
                        </x-slot>
                        <x-slot name="description">
                            SKU: {{ $variant['sku'] }}
                            @if($variant['barcode'])
                                · Barcode: {{ $variant['barcode'] }}
                            @endif
                            · Costo: ${{ number_format($variant['cost_price'], 2) }}
                        </x-slot>

                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--gray-600);">Stock en sistema:</span>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--gray-950);">{{ $variant['current_stock'] }}</span>
                                <span style="font-size: 0.875rem; color: var(--gray-400); margin-left: 0.5rem;">→</span>
                                <span style="font-size: 0.875rem; color: var(--gray-600);">Contado:</span>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--primary-600);">{{ (int) $countedQuantity }}</span>
                            </div>

                            {{-- Tabs: Ajustar / Sobreescribir --}}
                            <x-filament::tabs>
                                <x-filament::tabs.item
                                    :active="$countMode === 'adjust'"
                                    wire:click="$set('countMode', 'adjust')"
                                    icon="heroicon-o-arrows-up-down"
                                >
                                    Ajustar
                                </x-filament::tabs.item>
                                <x-filament::tabs.item
                                    :active="$countMode === 'overwrite'"
                                    wire:click="$set('countMode', 'overwrite')"
                                    icon="heroicon-o-pencil"
                                >
                                    Sobreescribir
                                </x-filament::tabs.item>
                            </x-filament::tabs>

                            @if($countMode === 'adjust')
                                {{-- Adjust mode: big tappable +/- buttons --}}
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                                    <x-filament::button color="danger" outlined wire:click="adjustQuantity(-10)" size="xl">−10</x-filament::button>
                                    <x-filament::button color="danger" outlined wire:click="adjustQuantity(-5)" size="xl">−5</x-filament::button>
                                    <x-filament::button color="danger" wire:click="adjustQuantity(-1)" size="xl">−1</x-filament::button>
                                    <x-filament::button color="success" wire:click="adjustQuantity(1)" size="xl">+1</x-filament::button>
                                    <x-filament::button color="success" outlined wire:click="adjustQuantity(5)" size="xl">+5</x-filament::button>
                                    <x-filament::button color="success" outlined wire:click="adjustQuantity(10)" size="xl">+10</x-filament::button>
                                </div>
                            @else
                                {{-- Overwrite mode: direct input --}}
                                <div>
                                    <x-filament::input.wrapper>
                                        <input
                                            type="number"
                                            wire:model.blur="countedQuantity"
                                            wire:keydown.enter="confirmCount"
                                            min="0"
                                            autofocus
                                            id="quantity-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            style="width: 100%; padding: 1rem; font-size: 2rem; text-align: center; border: none; outline: none; background: transparent; color: var(--gray-950); font-weight: 700;"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                            @endif

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem; margin-top: 0.5rem;">
                                <x-filament::button color="success" wire:click="confirmCount" icon="heroicon-o-check" size="xl">
                                    Confirmar
                                </x-filament::button>
                                <x-filament::button color="gray" wire:click="cancelSearch" size="xl">
                                    Cancelar
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                @endif
            </x-filament::section>
        @endif

        {{-- Counted items list --}}
        @if(count($countedItems) > 0)
            <x-filament::section>
                <x-slot name="heading">Items contados ({{ count($countedItems) }})</x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button color="danger" size="xs" wire:click="clearList" wire:confirm="¿Limpiar la lista?">
                        Limpiar
                    </x-filament::button>
                </x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--gray-200);">
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Producto</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Sistema</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Contado</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($countedItems as $item)
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">{{ $item['label'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center; color: var(--gray-500);">{{ $item['previous'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center; font-weight: 600; color: var(--gray-950);">{{ $item['counted'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center; font-weight: 600;
                                        @if($item['diff'] > 0) color: var(--success-600);
                                        @elseif($item['diff'] < 0) color: var(--danger-600);
                                        @else color: var(--gray-400);
                                        @endif
                                    ">
                                        @if($item['diff'] > 0)+@endif{{ $item['diff'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>

    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let html5QrCode = null;

        function startCamera() {
            const container = document.getElementById('camera-container');
            container.style.display = 'block';

            html5QrCode = new Html5Qrcode("camera-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 100 },
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.EAN_8,
                        Html5QrcodeSupportedFormats.UPC_A,
                        Html5QrcodeSupportedFormats.UPC_E,
                        Html5QrcodeSupportedFormats.CODE_128,
                        Html5QrcodeSupportedFormats.CODE_39,
                        Html5QrcodeSupportedFormats.QR_CODE,
                    ]
                },
                (decodedText) => {
                    stopCamera();
                    @this.set('searchCode', decodedText);
                    @this.call('searchVariant');
                },
                (errorMessage) => {}
            ).catch((err) => {
                console.error('Camera error:', err);
                container.style.display = 'none';
            });
        }

        function stopCamera() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    html5QrCode = null;
                }).catch(console.error);
            }
            document.getElementById('camera-container').style.display = 'none';
        }
    </script>
    @endpush
</x-filament-panels::page>
