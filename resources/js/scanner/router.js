import { createRouter, createWebHistory } from 'vue-router'
import { authStore } from './stores/auth'

const routes = [
    {
        path: '/scanner/setup',
        name: 'setup',
        component: () => import('./views/SetupView.vue'),
    },
    {
        path: '/scanner',
        name: 'scanner',
        component: () => import('./views/ScannerView.vue'),
    },
    {
        path: '/scanner/history',
        name: 'history',
        component: () => import('./views/HistoryView.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    if (to.name !== 'setup' && !authStore.token) {
        return { name: 'setup' }
    }
})

export default router
