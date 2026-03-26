<template>
    <div class="p-4 max-w-lg mx-auto space-y-4">
        <!-- Idle state -->
        <template v-if="!variant && !scanning && !loading">
            <button
                @click="startScanning"
                class="w-full h-20 bg-gradient-to-b from-red-600 to-red-700 text-white rounded-2xl font-bold text-xl active:scale-[0.98] transition-all shadow-lg shadow-red-600/20 flex items-center justify-center gap-3"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-7 h-7">
                    <path d="M2 4h2v16H2V4zm4 0h1v16H6V4zm2 0h2v16H8V4zm3 0h2v16h-2V4zm3 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                </svg>
                Escanear codigo
            </button>

            <div class="relative">
                <input
                    v-model="manualCode"
                    type="text"
                    placeholder="Buscar por codigo o SKU..."
                    @keydown.enter="lookupCode(manualCode)"
                    class="w-full pl-10 pr-16 py-3.5 bg-white border border-zinc-200 rounded-xl text-sm placeholder-zinc-400 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 transition-all"
                    inputmode="text"
                />
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4.5 h-4.5 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                </svg>
                <button
                    v-if="manualCode"
                    @click="lookupCode(manualCode)"
                    class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-zinc-900 text-white rounded-lg text-xs font-bold active:scale-95 transition-transform"
                >
                    Buscar
                </button>
            </div>
        </template>

        <!-- Scanning state -->
        <template v-if="scanning">
            <BarcodeScanner @scanned="onBarcodeScanned" @error="onScanError" />
            <button
                @click="scanning = false"
                class="w-full py-3.5 bg-zinc-100 text-zinc-600 rounded-xl font-semibold border border-zinc-200 active:scale-[0.98] transition-all"
            >
                Cancelar
            </button>
        </template>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-16">
            <div class="w-10 h-10 border-3 border-zinc-200 border-t-red-600 rounded-full animate-spin mb-3"></div>
            <p class="text-zinc-400 text-sm font-medium">Buscando producto...</p>
        </div>

        <!-- Found variant -->
        <transition name="slide-up">
            <div v-if="variant && !loading" class="space-y-4">
                <ProductCard :variant="variant" />
                <AdjustForm
                    :current-stock="variant.current_stock"
                    :submitting="submitting"
                    @confirm="submitAdjustment"
                    @cancel="reset"
                />
            </div>
        </transition>

        <!-- Success feedback -->
        <transition name="slide-up">
            <div
                v-if="lastResult"
                class="rounded-2xl p-5 text-center border"
                :class="lastResult.diff === 0
                    ? 'bg-zinc-50 text-zinc-500 border-zinc-200'
                    : lastResult.diff > 0
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-red-50 text-red-700 border-red-200'"
            >
                <!-- Checkmark -->
                <div class="flex justify-center mb-3">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center check-pop"
                        :class="lastResult.diff === 0 ? 'bg-zinc-200' : lastResult.diff > 0 ? 'bg-emerald-200' : 'bg-red-200'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <p class="font-bold text-base">{{ lastResult.message }}</p>
                <p v-if="lastResult.diff !== 0" class="text-sm mt-1 font-mono">
                    {{ lastResult.previous_stock }}
                    <span class="mx-1">→</span>
                    {{ lastResult.counted }}
                    <span class="ml-1 font-bold">({{ lastResult.diff > 0 ? '+' : '' }}{{ lastResult.diff }})</span>
                </p>
            </div>
        </transition>

        <!-- Error -->
        <transition name="fade">
            <div v-if="error" class="shake">
                <p class="text-red-600 text-sm text-center bg-red-50 border border-red-200 p-3 rounded-xl font-medium">
                    {{ error }}
                </p>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'
import BarcodeScanner from '../components/BarcodeScanner.vue'
import ProductCard from '../components/ProductCard.vue'
import AdjustForm from '../components/AdjustForm.vue'

const scanning = ref(false)
const loading = ref(false)
const submitting = ref(false)
const manualCode = ref('')
const variant = ref(null)
const error = ref('')
const lastResult = ref(null)

const history = window.__scannerHistory = window.__scannerHistory || []

function startScanning() {
    error.value = ''
    lastResult.value = null
    scanning.value = true
}

function onScanError(msg) {
    error.value = msg
    scanning.value = false
}

function onBarcodeScanned(code) {
    scanning.value = false
    lookupCode(code)
}

async function lookupCode(code) {
    if (!code) return

    loading.value = true
    error.value = ''
    lastResult.value = null
    variant.value = null

    try {
        const { data } = await api.get('/lookup', { params: { code } })
        variant.value = data.variant
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al buscar'
    } finally {
        loading.value = false
        manualCode.value = ''
    }
}

async function submitAdjustment(countedQuantity) {
    if (!variant.value) return

    submitting.value = true
    error.value = ''

    try {
        const { data } = await api.post('/adjust', {
            variant_id: variant.value.id,
            counted_quantity: countedQuantity,
        })

        lastResult.value = data

        history.unshift({
            sku: variant.value.sku,
            product_name: variant.value.product_name,
            previous_stock: data.previous_stock,
            counted: data.counted,
            diff: data.diff,
            timestamp: new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }),
        })

        variant.value = null

        // Auto-dismiss after 2.5s
        setTimeout(() => {
            lastResult.value = null
        }, 2500)
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al ajustar stock'
    } finally {
        submitting.value = false
    }
}

function reset() {
    variant.value = null
    error.value = ''
    lastResult.value = null
}
</script>
