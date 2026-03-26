<x-filament-panels::page>
    <div wire:poll.1s="checkLinked" style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem; padding: 2rem 0;">

        @if ($linked)
            {{-- Success state --}}
            <x-filament::section>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2rem 0;">
                    <div style="width: 4rem; height: 4rem; border-radius: 9999px; background: #dcfce7; display: flex; align-items: center; justify-content: center;">
                        <x-filament::icon icon="heroicon-o-check-circle" style="width: 2.5rem; height: 2.5rem; color: #16a34a;" />
                    </div>
                    <div style="text-align: center;">
                        <p style="font-size: 1.125rem; font-weight: 600; margin: 0;">Dispositivo vinculado</p>
                        <p style="font-size: 0.875rem; color: var(--gray-500); margin: 0.5rem 0 0;">
                            <strong>{{ $record->name }}</strong> esta listo para usar.
                        </p>
                    </div>

                    @if ($record->deviceLabel())
                        <div style="margin-top: 0.5rem; padding: 0.625rem 1.25rem; background: var(--gray-50); border-radius: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <x-filament::icon icon="heroicon-o-device-phone-mobile" style="width: 1.25rem; height: 1.25rem; color: var(--gray-400);" />
                            <span style="font-size: 0.875rem; color: var(--gray-600);">{{ $record->deviceLabel() }}</span>
                        </div>
                    @endif

                    <div style="margin-top: 0.5rem;">
                        <x-filament::button
                            :href="$this->getFinishUrl()"
                            tag="a"
                            icon="heroicon-o-check"
                        >
                            Finalizar
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @else
            {{-- QR state --}}
            <x-filament::section>
                <x-slot name="heading">{{ $record->name }}</x-slot>
                <x-slot name="description">Ubicacion: {{ $record->location->name }}</x-slot>

                <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                    {{-- QR Code (server-rendered SVG) --}}
                    <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 2px solid var(--gray-200);">
                        <img src="{{ $qrSvg }}" alt="Codigo QR" width="320" height="320" style="display: block;" />
                    </div>

                    {{-- Expiration notice --}}
                    <div style="text-align: center;">
                        <p style="font-size: 0.875rem; color: var(--gray-500);">
                            Este codigo expira a las <strong>{{ $expiresAt }}</strong> (15 minutos)
                        </p>
                    </div>

                    {{-- Manual OTP fallback --}}
                    <div wire:ignore>
                        <details style="width: 100%; max-width: 400px;">
                            <summary style="cursor: pointer; font-size: 0.875rem; color: var(--gray-500); text-align: center;">
                                Ingreso manual
                            </summary>
                            <div style="margin-top: 0.75rem; padding: 0.75rem; background: var(--gray-50); border-radius: 0.5rem; text-align: center;">
                                <code style="font-size: 0.75rem; word-break: break-all; user-select: all;">{{ $rawOtp }}</code>
                            </div>
                        </details>
                    </div>

                    {{-- Instructions --}}
                    <div style="max-width: 400px; text-align: center;">
                        <p style="font-size: 0.875rem; color: var(--gray-600);">
                            Abri la app <strong>Scanner</strong> en el dispositivo y escaneá este codigo QR para vincularlo.
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
