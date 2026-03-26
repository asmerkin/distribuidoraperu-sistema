<template>
    <div class="p-4 max-w-lg mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-lg font-extrabold text-zinc-900">Historial</h2>
                <p v-if="history.length" class="text-xs text-zinc-400 font-medium mt-0.5">
                    {{ history.length }} {{ history.length === 1 ? 'ajuste' : 'ajustes' }} en esta sesion
                </p>
            </div>
            <button
                v-if="history.length"
                @click="clearHistory"
                class="text-xs text-zinc-400 font-semibold px-3 py-1.5 rounded-lg border border-zinc-200 active:scale-95 transition-transform hover:text-zinc-600"
            >
                Limpiar
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="!history.length" class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-zinc-100 rounded-2xl flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-zinc-300">
                    <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0 1 18 9.375v9.375a3 3 0 0 0 3-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 0 0-.673-.05A3 3 0 0 0 15 1.5h-1.5a3 3 0 0 0-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6ZM13.5 3A1.5 1.5 0 0 0 12 4.5h4.5A1.5 1.5 0 0 0 15 3h-1.5Z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 0 1 3 20.625V9.375Zm9.586 4.594a.75.75 0 0 0-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 0 0-1.06 1.06l1.5 1.5a.75.75 0 0 0 1.116-.062l3-3.75Z" clip-rule="evenodd" />
                </svg>
            </div>
            <p class="text-zinc-400 font-medium text-sm">No hay ajustes en esta sesion</p>
            <p class="text-zinc-300 text-xs mt-1">Los ajustes aparecen aqui al confirmarlos</p>
        </div>

        <!-- History list -->
        <div v-else class="space-y-2">
            <div
                v-for="(item, index) in history"
                :key="index"
                class="bg-white rounded-xl border border-zinc-100 overflow-hidden stagger-item"
                :style="{ animationDelay: `${index * 50}ms` }"
            >
                <div class="flex items-stretch">
                    <!-- Color accent bar -->
                    <div
                        class="w-1 shrink-0"
                        :class="item.diff === 0 ? 'bg-zinc-200' : item.diff > 0 ? 'bg-emerald-500' : 'bg-red-500'"
                    ></div>

                    <div class="flex-1 p-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-zinc-900 text-sm truncate">{{ item.product_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[11px] font-mono text-zinc-400">{{ item.sku }}</span>
                                <span class="text-zinc-200">·</span>
                                <span class="text-[11px] text-zinc-400">{{ item.timestamp }}</span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="text-xs text-zinc-400 font-mono tabular-nums">
                                {{ item.previous_stock }} → {{ item.counted }}
                            </p>
                            <span
                                class="inline-block mt-0.5 text-xs font-bold px-2 py-0.5 rounded-md tabular-nums"
                                :class="item.diff === 0
                                    ? 'bg-zinc-100 text-zinc-400'
                                    : item.diff > 0
                                        ? 'bg-emerald-50 text-emerald-600'
                                        : 'bg-red-50 text-red-600'"
                            >
                                {{ item.diff > 0 ? '+' : '' }}{{ item.diff }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'

const history = reactive(window.__scannerHistory || [])

function clearHistory() {
    history.splice(0, history.length)
    window.__scannerHistory = history
}
</script>
