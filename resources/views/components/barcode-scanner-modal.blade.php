{{-- Global barcode scanner modal — rendered once via AdminPanelProvider renderHook --}}
<div
    x-data="barcodeScanner"
    x-on:open-barcode-scanner.window="openScanner($event.detail)"
    x-on:keydown.escape.window="close()"
>
    <template x-teleport="body">
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="barcode-scanner-backdrop"
            x-on:click.self="close()"
        >
            <div
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="barcode-scanner-panel"
                x-on:click.stop
            >
                {{-- Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">
                        Escanear código de barras
                    </h3>
                    <button
                        type="button"
                        x-on:click="close()"
                        style="color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0.25rem;"
                    >
                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scanner area --}}
                <div style="position: relative; background: black; min-height: 300px;">
                    <div id="filament-barcode-scanner" style="width: 100%; min-height: 300px;"></div>

                    {{-- Loading state --}}
                    <div x-show="!cameraReady && !errorMsg" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10;">
                        <div style="width: 2rem; height: 2rem; border: 2px solid rgba(255,255,255,0.2); border-top-color: rgba(255,255,255,0.8); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.75rem; margin-top: 0.75rem;">Iniciando cámara...</p>
                    </div>

                    {{-- Error state --}}
                    <div x-show="errorMsg" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 1.5rem; z-index: 10;">
                        <svg style="width: 2.5rem; height: 2.5rem; color: #f87171; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <p style="color: #f87171; font-size: 0.875rem; text-align: center;" x-text="errorMsg"></p>
                    </div>
                </div>

                {{-- Success feedback --}}
                <div
                    x-show="scannedValue"
                    x-transition
                    style="padding: 0.75rem 1.25rem; background: #ecfdf5; border-top: 1px solid #a7f3d0;"
                >
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #10b981; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span style="font-size: 0.875rem; font-family: monospace; color: #065f46; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="scannedValue"></span>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }

    .barcode-scanner-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 99999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
    }
    .barcode-scanner-backdrop[style*="display: none"] {
        display: none !important;
    }

    .barcode-scanner-panel {
        width: 100%;
        max-width: 28rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        overflow: hidden;
    }

    /* Ensure html5-qrcode video is visible */
    #filament-barcode-scanner video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
    }
    #filament-barcode-scanner img[alt="Scanner Paused"] {
        display: none;
    }
</style>
