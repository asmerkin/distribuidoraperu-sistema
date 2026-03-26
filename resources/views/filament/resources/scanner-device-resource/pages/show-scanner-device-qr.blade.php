<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem; padding: 2rem 0;">

        <x-filament::section>
            <x-slot name="heading">{{ $record->name }}</x-slot>
            <x-slot name="description">Ubicacion: {{ $record->location->name }}</x-slot>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                {{-- QR Code Container --}}
                <div id="qr-code" style="background: white; padding: 1.5rem; border-radius: 0.75rem; border: 2px solid var(--gray-200);"></div>

                {{-- Expiration notice --}}
                <div style="text-align: center;">
                    <p style="font-size: 0.875rem; color: var(--gray-500);">
                        Este codigo expira a las <strong>{{ $expiresAt }}</strong> (15 minutos)
                    </p>
                </div>

                {{-- Manual OTP fallback --}}
                <details style="width: 100%; max-width: 400px;">
                    <summary style="cursor: pointer; font-size: 0.875rem; color: var(--gray-500); text-align: center;">
                        Ingreso manual
                    </summary>
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: var(--gray-50); border-radius: 0.5rem; text-align: center;">
                        <code style="font-size: 0.75rem; word-break: break-all; user-select: all;">{{ $rawOtp }}</code>
                    </div>
                </details>

                {{-- Instructions --}}
                <div style="max-width: 400px; text-align: center;">
                    <p style="font-size: 0.875rem; color: var(--gray-600);">
                        Abri la app <strong>Scanner</strong> en el dispositivo y escaneá este codigo QR para vincularlo.
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
    @endassets

    @script
    <script>
        const qrData = @js($qrData);
        const container = document.getElementById('qr-code');

        if (container && typeof QRCode !== 'undefined') {
            container.innerHTML = '';
            const canvas = document.createElement('canvas');
            container.appendChild(canvas);

            QRCode.toCanvas(canvas, qrData, {
                width: 360,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#ffffff',
                },
            });
        }
    </script>
    @endscript
</x-filament-panels::page>
