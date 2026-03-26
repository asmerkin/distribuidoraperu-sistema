<template>
    <div class="min-h-screen flex flex-col bg-zinc-50">
        <!-- Location bar (thin top strip) -->
        <div v-if="authStore.token" class="bg-zinc-900 text-zinc-400 text-xs tracking-wide px-4 py-1.5 flex items-center gap-2 safe-top">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-3 h-3 text-red-500 shrink-0">
                <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.362 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.235 2.291 5.597a15.591 15.591 0 0 0 2.236 2.235l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
            </svg>
            <span class="truncate uppercase font-medium">{{ authStore.device?.location?.name }}</span>
        </div>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto" :class="authStore.token ? 'pb-20' : ''">
            <router-view v-slot="{ Component }">
                <transition name="view" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>

        <!-- Bottom navigation -->
        <AppHeader v-if="authStore.token" />
    </div>
</template>

<script setup>
import { authStore } from './stores/auth'
import AppHeader from './components/AppHeader.vue'
</script>
