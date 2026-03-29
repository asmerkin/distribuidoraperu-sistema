/**
 * Alpine.js component for barcode scanning in Filament forms.
 * Listens for 'open-barcode-scanner' events dispatched by BarcodeInput suffix actions.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeScanner', () => ({
        isOpen: false,
        cameraReady: false,
        errorMsg: '',
        scannedValue: '',
        statePath: '',
        wireId: '',
        scannerId: 'filament-barcode-scanner',
        scanner: null,

        init() {
            this.$watch('isOpen', (value) => {
                if (!value) {
                    this.stopScanner();
                }
            });
        },

        openScanner(detail) {
            this.statePath = detail.statePath;
            this.wireId = detail.wireId;
            this.isOpen = true;
            this.cameraReady = false;
            this.errorMsg = '';
            this.scannedValue = '';

            this.$nextTick(() => {
                setTimeout(() => this.startScanner(), 150);
            });
        },

        close() {
            this.isOpen = false;
        },

        async startScanner() {
            if (typeof Html5Qrcode === 'undefined') {
                this.errorMsg = 'La librería de escaneo no se cargó correctamente.';
                return;
            }

            const el = document.getElementById(this.scannerId);
            if (!el) {
                this.errorMsg = 'No se encontró el contenedor del scanner.';
                return;
            }

            try {
                this.scanner = new Html5Qrcode(this.scannerId);

                await this.scanner.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 100 },
                        aspectRatio: 1.5,
                    },
                    (decodedText) => {
                        this.onScanned(decodedText);
                    },
                    () => {},
                );

                this.cameraReady = true;
            } catch (err) {
                console.error('Barcode scanner error:', err);
                this.errorMsg = 'No se pudo acceder a la cámara. Verificá los permisos del navegador.';
            }
        },

        async stopScanner() {
            if (this.scanner && this.scanner.isScanning) {
                try {
                    await this.scanner.stop();
                } catch {
                    // ignore cleanup errors
                }
            }
            this.scanner = null;

            // Clear the container so html5-qrcode can reinitialize on next open
            const el = document.getElementById(this.scannerId);
            if (el) {
                el.innerHTML = '';
            }
        },

        onScanned(value) {
            this.scannedValue = value;

            // Set the field value on the correct Livewire component
            if (this.wireId) {
                const component = Livewire.find(this.wireId);
                if (component) {
                    component.set(this.statePath, value);
                }
            }

            // Brief delay to show success feedback, then close
            setTimeout(() => this.close(), 700);
        },
    }));
});
