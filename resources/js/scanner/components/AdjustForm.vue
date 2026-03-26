<template>
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 overflow-hidden">
        <div class="p-4">
            <p class="text-[10px] uppercase tracking-widest font-semibold text-zinc-400 mb-3 text-center">Cantidad contada</p>

            <!-- Big quantity input -->
            <div class="flex items-center justify-center mb-4">
                <input
                    ref="qtyInput"
                    type="number"
                    v-model.number="quantity"
                    min="0"
                    class="w-32 text-center text-5xl font-extrabold text-zinc-900 bg-zinc-50 border-2 border-zinc-200 rounded-2xl py-3 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 tabular-nums transition-colors"
                    inputmode="numeric"
                />
            </div>

            <!-- Difference badge -->
            <div class="flex justify-center mb-4">
                <span
                    v-if="diff !== 0"
                    class="inline-flex items-center gap-1 text-sm font-bold px-4 py-1.5 rounded-full transition-all"
                    :class="diff > 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'"
                >
                    <svg v-if="diff > 0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4">
                        <path d="M8 2a.75.75 0 0 1 .75.75v8.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.22 3.22V2.75A.75.75 0 0 1 8 2Z" transform="rotate(180, 8, 8)"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4">
                        <path d="M8 2a.75.75 0 0 1 .75.75v8.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.22 3.22V2.75A.75.75 0 0 1 8 2Z"/>
                    </svg>
                    {{ diff > 0 ? '+' : '' }}{{ diff }}
                </span>
                <span v-else class="text-xs text-zinc-400 py-1.5">Sin diferencia</span>
            </div>

            <!-- Adjustment grid -->
            <div class="grid grid-cols-6 gap-2 mb-5">
                <button
                    v-for="delta in [-10, -5, -1, 1, 5, 10]"
                    :key="delta"
                    @click="adjust(delta)"
                    class="h-14 rounded-xl text-base font-bold transition-all active:scale-90"
                    :class="delta < 0
                        ? 'bg-red-50 text-red-600 border border-red-100 active:bg-red-100'
                        : 'bg-emerald-50 text-emerald-600 border border-emerald-100 active:bg-emerald-100'"
                >
                    {{ delta > 0 ? '+' : '' }}{{ delta }}
                </button>
            </div>

            <!-- Actions -->
            <button
                @click="$emit('confirm', quantity)"
                :disabled="submitting"
                class="w-full h-14 bg-red-600 text-white rounded-xl font-bold text-base active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <svg v-if="submitting" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ submitting ? 'Guardando...' : 'Confirmar conteo' }}
            </button>
            <button
                @click="$emit('cancel')"
                class="w-full mt-2 py-2 text-zinc-400 text-sm font-medium active:text-zinc-600 transition-colors"
            >
                Cancelar
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    currentStock: {
        type: Number,
        required: true,
    },
    submitting: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['confirm', 'cancel'])

const quantity = ref(props.currentStock)

const diff = computed(() => quantity.value - props.currentStock)

function adjust(delta) {
    quantity.value = Math.max(0, quantity.value + delta)
}
</script>
