<template>
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-zinc-900">
        <div class="w-full max-w-sm">
            <!-- Logo / Brand -->
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-600 rounded-2xl mb-4 shadow-lg shadow-red-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-white">
                        <path d="M2 4h2v16H2V4zm4 0h1v16H6V4zm2 0h2v16H8V4zm3 0h2v16h-2V4zm3 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Scanner</h1>
                <p class="text-zinc-500 text-sm mt-1 font-medium">Distribuidora Peru</p>
            </div>

            <!-- Main content -->
            <div v-if="!scanning" class="space-y-4">
                <!-- Scan QR button -->
                <button
                    @click="startScanning"
                    class="w-full h-16 bg-red-600 text-white rounded-2xl font-bold text-lg active:scale-[0.98] transition-all pulse-glow flex items-center justify-center gap-3"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path fill-rule="evenodd" d="M3 4.875C3 3.839 3.84 3 4.875 3h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 9.375v-4.5ZM4.875 4.5a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm7.875.375c0-1.036.84-1.875 1.875-1.875h4.5C20.16 3 21 3.84 21 4.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5Zm2.25-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5ZM3 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 19.125v-4.5Zm2.25-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm9-1.125a.75.75 0 0 1 .75.75v1.5h1.5a.75.75 0 0 1 0 1.5h-1.5v1.5a.75.75 0 0 1-1.5 0v-1.5h-1.5a.75.75 0 0 1 0-1.5h1.5v-1.5a.75.75 0 0 1 .75-.75Zm4.5 2.25a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3a.75.75 0 0 1 .75-.75Zm-1.5 3a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-1.5 0v-.75a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                    </svg>
                    Escanear codigo QR
                </button>

                <!-- Manual OTP -->
                <details class="group">
                    <summary class="cursor-pointer text-center text-zinc-500 text-sm font-medium py-2 hover:text-zinc-400 transition-colors list-none">
                        <span class="border-b border-dashed border-zinc-600">Ingreso manual</span>
                    </summary>
                    <div class="mt-3 bg-zinc-800 rounded-2xl p-4 space-y-3">
                        <input
                            v-model="manualOtp"
                            type="text"
                            placeholder="Pegar codigo OTP"
                            class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 text-white rounded-xl text-sm font-mono placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-colors"
                        />
                        <button
                            @click="authenticate(manualOtp)"
                            :disabled="!manualOtp || loading"
                            class="w-full py-3 bg-white text-zinc-900 rounded-xl text-sm font-bold disabled:opacity-30 active:scale-[0.98] transition-all"
                        >
                            {{ loading ? 'Vinculando...' : 'Vincular dispositivo' }}
                        </button>
                    </div>
                </details>
            </div>

            <!-- Scanning state -->
            <div v-else class="space-y-4">
                <BarcodeScanner label="Apunta al codigo QR del admin" @scanned="onQrScanned" @error="onScanError" />
                <button
                    @click="scanning = false"
                    class="w-full py-3 bg-zinc-800 text-zinc-300 rounded-xl font-medium border border-zinc-700 active:scale-[0.98] transition-all"
                >
                    Cancelar
                </button>
            </div>

            <!-- Error -->
            <div v-if="error" class="mt-4 shake">
                <p class="text-red-400 text-sm text-center bg-red-950/50 border border-red-900/50 p-3 rounded-xl">
                    {{ error }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authStore } from '../stores/auth'
import api from '../api'
import BarcodeScanner from '../components/BarcodeScanner.vue'

const router = useRouter()

const scanning = ref(false)
const loading = ref(false)
const error = ref('')
const manualOtp = ref('')

function startScanning() {
    error.value = ''
    scanning.value = true
}

function onScanError(msg) {
    error.value = msg
    scanning.value = false
}

async function onQrScanned(text) {
    scanning.value = false

    try {
        const data = JSON.parse(text)
        if (data.otp) {
            await authenticate(data.otp)
        } else {
            error.value = 'Codigo QR no valido'
        }
    } catch {
        await authenticate(text)
    }
}

async function authenticate(otp) {
    if (!otp) return

    loading.value = true
    error.value = ''

    try {
        const { data } = await api.post('/auth', { otp })
        authStore.setAuth(data.token, data.device)
        router.push('/scanner')
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al vincular dispositivo'
    } finally {
        loading.value = false
    }
}
</script>
