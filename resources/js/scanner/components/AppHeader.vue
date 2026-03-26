<template>
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-zinc-200 safe-bottom z-50">
        <div class="flex items-stretch max-w-lg mx-auto">
            <router-link
                to="/scanner"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition-colors relative"
                :class="isActive('scanner') ? 'text-red-600' : 'text-zinc-400'"
            >
                <div v-if="isActive('scanner')" class="absolute top-0 inset-x-4 h-0.5 bg-red-600 rounded-full"></div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path d="M2 4h2v16H2V4zm4 0h1v16H6V4zm2 0h2v16H8V4zm3 0h2v16h-2V4zm3 0h2v16h-2V4zm3 0h1v16h-1V4zm2 0h2v16h-2V4z"/>
                </svg>
                <span class="text-[10px] font-semibold tracking-wide uppercase">Scanner</span>
            </router-link>

            <router-link
                to="/scanner/history"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition-colors relative"
                :class="isActive('history') ? 'text-red-600' : 'text-zinc-400'"
            >
                <div v-if="isActive('history')" class="absolute top-0 inset-x-4 h-0.5 bg-red-600 rounded-full"></div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 3A1.5 1.5 0 0012 4.5h4.5A1.5 1.5 0 0015 3h-1.5z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 013 20.625V9.375zm9.586 4.594a.75.75 0 00-1.172-.938l-2.476 3.096-.908-.907a.75.75 0 00-1.06 1.06l1.5 1.5a.75.75 0 001.116-.062l3-3.75z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] font-semibold tracking-wide uppercase">Historial</span>
            </router-link>

            <button
                @click="showDeviceInfo"
                class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition-colors text-zinc-400"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 00-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 00-2.282.819l-.922 1.597a1.875 1.875 0 00.432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 000 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 00-.432 2.385l.922 1.597a1.875 1.875 0 002.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 002.28-.819l.923-1.597a1.875 1.875 0 00-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 000-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 00-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 00-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 00-1.85-1.567h-1.843zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] font-semibold tracking-wide uppercase">Config</span>
            </button>
        </div>
    </nav>

    <!-- Device info drawer -->
    <transition name="fade">
        <div v-if="showDrawer" class="fixed inset-0 bg-black/50 z-[60]" @click="showDrawer = false"></div>
    </transition>
    <transition name="slide-up">
        <div v-if="showDrawer" class="fixed bottom-0 inset-x-0 bg-white rounded-t-2xl z-[70] p-6 safe-bottom">
            <div class="w-10 h-1 bg-zinc-300 rounded-full mx-auto mb-5"></div>
            <h3 class="font-bold text-lg text-zinc-900 mb-4">Dispositivo</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500">Nombre</span>
                    <span class="font-medium text-zinc-900">{{ authStore.device?.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">Ubicacion</span>
                    <span class="font-medium text-zinc-900">{{ authStore.device?.location?.name }}</span>
                </div>
            </div>
            <button
                @click="logout"
                class="mt-6 w-full py-3 border-2 border-red-200 text-red-600 rounded-xl font-semibold text-sm active:scale-95 transition-transform"
            >
                Desvincular dispositivo
            </button>
        </div>
    </transition>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { authStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const showDrawer = ref(false)

function isActive(name) {
    return route.name === name
}

function showDeviceInfo() {
    showDrawer.value = true
}

function logout() {
    if (confirm('Desvincular este dispositivo?')) {
        showDrawer.value = false
        authStore.logout()
        router.push('/scanner/setup')
    }
}
</script>
