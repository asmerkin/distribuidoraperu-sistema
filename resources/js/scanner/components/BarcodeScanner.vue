<template>
    <div class="viewfinder-container w-full rounded-2xl bg-black">
        <div :id="scannerId"></div>

        <!-- Loading placeholder (visible until camera stream starts) -->
        <div v-if="!cameraReady" class="viewfinder-placeholder">
            <div class="w-8 h-8 border-2 border-white/20 border-t-white/80 rounded-full animate-spin"></div>
            <p class="text-white/50 text-xs mt-3 font-medium">Iniciando camara...</p>
        </div>

        <!-- Viewfinder overlay -->
        <div v-if="cameraReady" class="viewfinder-overlay">
            <!-- Semi-transparent backdrop -->
            <div class="absolute inset-0 bg-black/30"></div>

            <!-- Clear scanning zone -->
            <div class="absolute top-[20%] bottom-[20%] left-[12%] right-[12%] bg-transparent" style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.3);"></div>

            <!-- Corner brackets -->
            <div class="viewfinder-corner viewfinder-corner--tl"></div>
            <div class="viewfinder-corner viewfinder-corner--tr"></div>
            <div class="viewfinder-corner viewfinder-corner--bl"></div>
            <div class="viewfinder-corner viewfinder-corner--br"></div>

            <!-- Scanning line -->
            <div class="scan-line absolute left-[14%] right-[14%] h-0.5 bg-red-500 shadow-[0_0_8px_rgba(220,38,38,0.6)]"></div>
        </div>

        <!-- Label -->
        <div v-if="cameraReady" class="absolute bottom-3 inset-x-0 text-center z-20">
            <span class="text-white/80 text-xs font-medium bg-black/40 px-3 py-1 rounded-full backdrop-blur-sm">
                {{ label }}
            </span>
        </div>
    </div>

    <p v-if="error" class="text-red-500 text-sm mt-3 text-center shake">{{ error }}</p>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'

const props = defineProps({
    formats: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Apunta al codigo de barras',
    },
})

const emit = defineEmits(['scanned', 'error'])

const scannerId = `scanner-${Date.now()}`
const error = ref('')
const cameraReady = ref(false)
let html5Qrcode = null

onMounted(async () => {
    html5Qrcode = new Html5Qrcode(scannerId)

    try {
        await html5Qrcode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                formatsToSupport: props.formats.length
                    ? props.formats
                    : undefined,
            },
            (decodedText) => {
                emit('scanned', decodedText)
            },
            () => {},
        )
        cameraReady.value = true
    } catch (err) {
        error.value = 'No se pudo acceder a la camara. Verifica los permisos.'
        emit('error', err.message)
    }
})

onUnmounted(async () => {
    if (html5Qrcode?.isScanning) {
        try {
            await html5Qrcode.stop()
        } catch {
            // ignore cleanup errors
        }
    }
})
</script>
