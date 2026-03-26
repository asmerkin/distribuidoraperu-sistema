<template>
    <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 overflow-hidden">
        <!-- Red accent stripe -->
        <div class="h-1 bg-gradient-to-r from-red-600 to-red-500"></div>

        <div class="p-4">
            <!-- Product name + SKU -->
            <div class="flex justify-between items-start gap-3">
                <div class="min-w-0">
                    <h3 class="font-bold text-lg text-zinc-900 leading-tight">{{ variant.product_name }}</h3>
                    <p v-if="variant.name !== 'Default'" class="text-zinc-500 text-sm mt-0.5">{{ variant.name }}</p>
                </div>
                <span class="shrink-0 text-xs font-mono font-semibold bg-zinc-900 text-white px-2.5 py-1 rounded-lg">
                    {{ variant.sku }}
                </span>
            </div>

            <!-- Barcode + cost -->
            <div class="mt-3 flex items-center gap-3 text-xs text-zinc-400">
                <span v-if="variant.barcode" class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5">
                        <path d="M2 4h2v16H2V4zm4 0h1v16H6V4zm2 0h2v16H8V4zm3 0h2v16h-2V4zm3 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                    </svg>
                    <span class="font-mono">{{ variant.barcode }}</span>
                </span>
                <span class="text-zinc-300">|</span>
                <span>${{ Number(variant.cost_price).toFixed(2) }}</span>
            </div>

            <!-- Stock display -->
            <div class="mt-4 bg-zinc-50 rounded-xl p-4 text-center border border-zinc-100">
                <p class="text-[10px] uppercase tracking-widest font-semibold text-zinc-400 mb-1">Stock actual</p>
                <p class="text-5xl font-extrabold text-zinc-900 tabular-nums leading-none">
                    {{ variant.current_stock }}
                </p>
                <p class="text-[10px] uppercase tracking-widest font-semibold text-zinc-400 mt-2">unidades</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    variant: {
        type: Object,
        required: true,
    },
})
</script>
