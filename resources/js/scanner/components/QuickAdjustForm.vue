<template>
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 overflow-hidden">
        <div class="p-4">
            <p class="text-[10px] uppercase tracking-widest font-semibold text-zinc-400 mb-3 text-center">Ajuste rapido</p>

            <!-- Big delta display -->
            <div class="flex items-center justify-center mb-4">
                <span
                    class="text-5xl font-extrabold tabular-nums transition-colors"
                    :class="quantity === 0 ? 'text-zinc-300' : quantity > 0 ? 'text-emerald-600' : 'text-red-600'"
                >
                    {{ quantity > 0 ? '+' : '' }}{{ quantity }}
                </span>
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
                :disabled="submitting || quantity === 0"
                class="w-full h-14 bg-red-600 text-white rounded-xl font-bold text-base active:scale-[0.98] transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <svg v-if="submitting" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ submitting ? 'Guardando...' : 'Confirmar ajuste' }}
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
import { ref } from 'vue'

defineProps({
    submitting: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['confirm', 'cancel'])

const quantity = ref(0)

function adjust(delta) {
    quantity.value += delta
}
</script>
