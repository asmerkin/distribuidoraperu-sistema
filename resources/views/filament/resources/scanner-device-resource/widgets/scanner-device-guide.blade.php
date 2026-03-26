<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-information-circle" collapsible collapsed>
        <x-slot name="heading">Como vincular un dispositivo</x-slot>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
            {{-- Paso 1 --}}
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                <div style="flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; background: #fef2f2; color: #dc2626; font-weight: 700; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">
                    1
                </div>
                <div>
                    <p style="font-weight: 600; font-size: 0.875rem; margin: 0;">Crear dispositivo</p>
                    <p style="font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0;">Asigna un nombre y ubicacion al dispositivo.</p>
                </div>
            </div>

            {{-- Paso 2 --}}
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                <div style="flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; background: #fef2f2; color: #dc2626; font-weight: 700; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">
                    2
                </div>
                <div>
                    <p style="font-weight: 600; font-size: 0.875rem; margin: 0;">Generar QR</p>
                    <p style="font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0;">Hace clic en "Generar QR" en la tabla de dispositivos.</p>
                </div>
            </div>

            {{-- Paso 3 --}}
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                <div style="flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; background: #fef2f2; color: #dc2626; font-weight: 700; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">
                    3
                </div>
                <div>
                    <p style="font-weight: 600; font-size: 0.875rem; margin: 0;">Escanear desde el dispositivo</p>
                    <p style="font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0;">
                        Abri <a href="{{ url('/scanner') }}" target="_blank" style="color: #dc2626; text-decoration: underline;">{{ url('/scanner') }}</a> en el celular o tablet y escanea el codigo QR.
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
