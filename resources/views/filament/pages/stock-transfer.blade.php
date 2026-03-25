<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Variant search --}}
        <x-filament::section>
            @if(! $foundVariantId)
                <x-slot name="heading">Buscar producto</x-slot>

                <div style="display: flex; gap: 0.75rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model="searchCode"
                                wire:keydown.enter="searchVariant"
                                placeholder="SKU, código de barras o nombre..."
                                autofocus
                                style="width: 100%; padding: 0.75rem 1rem; font-size: 1.125rem; border: none; outline: none; background: transparent; color: var(--gray-950);"
                            />
                        </x-filament::input.wrapper>
                    </div>
                    <x-filament::button wire:click="searchVariant" icon="heroicon-o-magnifying-glass">
                        Buscar
                    </x-filament::button>
                </div>
            @else
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
                    </x-slot>

                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">

                        {{-- Origin / Destination selects --}}
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem;">
                                    Origen
                                </label>
                                <select
                                    wire:model.live="fromLocationId"
                                    style="width: 100%; padding: 0.625rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 0.875rem; background: var(--white); color: var(--gray-950); min-height: 2.75rem;"
                                >
                                    <option value="">Seleccioná origen...</option>
                                    @foreach($this->getLocations() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem;">
                                    Destino
                                </label>
                                <select
                                    wire:model.live="toLocationId"
                                    style="width: 100%; padding: 0.625rem 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 0.875rem; background: var(--white); color: var(--gray-950); min-height: 2.75rem;"
                                >
                                    <option value="">Seleccioná destino...</option>
                                    @foreach($this->getLocations() as $id => $name)
                                        <option value="{{ $id }}" @if($id === $fromLocationId) disabled @endif>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Origin stock indicator --}}
                        @if($fromLocationId)
                            @php $originStock = $this->getOriginStock(); @endphp
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; background: var(--gray-50); border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                                <span style="font-size: 0.875rem; color: var(--gray-600);">Stock disponible en origen:</span>
                                <span style="font-size: 1.5rem; font-weight: 700; color: {{ $originStock > 0 ? 'var(--success-600)' : 'var(--danger-600)' }};">
                                    {{ $originStock }}
                                </span>
                                <span style="font-size: 0.875rem; color: var(--gray-400);">unidades</span>
                            </div>
                        @endif

                        {{-- Quantity --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem;">
                                Cantidad a transferir
                            </label>
                            <x-filament::input.wrapper>
                                <input
                                    type="number"
                                    wire:model.blur="quantity"
                                    wire:keydown.enter="transfer"
                                    min="1"
                                    @if($fromLocationId) max="{{ $this->getOriginStock() }}" @endif
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder="0"
                                    style="width: 100%; padding: 0.875rem 1rem; font-size: 1.75rem; text-align: center; border: none; outline: none; background: transparent; color: var(--gray-950); font-weight: 700;"
                                />
                            </x-filament::input.wrapper>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.375rem;">
                                Notas <span style="font-weight: 400; color: var(--gray-400);">(opcional)</span>
                            </label>
                            <x-filament::input.wrapper>
                                <textarea
                                    wire:model="notes"
                                    rows="2"
                                    placeholder="Motivo de la transferencia..."
                                    style="width: 100%; padding: 0.625rem 0.75rem; font-size: 0.875rem; border: none; outline: none; background: transparent; color: var(--gray-950); resize: vertical;"
                                ></textarea>
                            </x-filament::input.wrapper>
                        </div>

                        {{-- Actions --}}
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem;">
                            <x-filament::button
                                wire:click="transfer"
                                wire:loading.attr="disabled"
                                icon="heroicon-o-arrows-right-left"
                                size="xl"
                            >
                                <span wire:loading.remove wire:target="transfer">Transferir</span>
                                <span wire:loading wire:target="transfer">Transfiriendo...</span>
                            </x-filament::button>
                            <x-filament::button color="gray" wire:click="cancelSearch" size="xl">
                                Cancelar
                            </x-filament::button>
                        </div>

                    </div>
                @endif
            @endif
        </x-filament::section>

        {{-- Recent transfers --}}
        @php $recentTransfers = $this->getRecentTransfers(); @endphp
        @if($recentTransfers->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">Últimas transferencias recibidas</x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--gray-200);">
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Producto</th>
                                <th style="text-align: center; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Cant.</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Destino</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600); white-space: nowrap;">Notas</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600);">Usuario</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-weight: 600; color: var(--gray-600); white-space: nowrap;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransfers as $transfer)
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-950);">{{ $transfer['label'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: center; font-weight: 600; color: var(--primary-600);">{{ $transfer['quantity'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-700);">{{ $transfer['location'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-500); font-style: {{ $transfer['notes'] ? 'normal' : 'italic' }};">
                                        {{ $transfer['notes'] ?? '—' }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-500);">{{ $transfer['user'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; color: var(--gray-400); white-space: nowrap;">{{ $transfer['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
