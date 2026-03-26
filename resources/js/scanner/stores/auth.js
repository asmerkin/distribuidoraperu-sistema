import { reactive } from 'vue'

const stored = localStorage.getItem('scanner_auth')
const initial = stored ? JSON.parse(stored) : { token: null, device: null }

export const authStore = reactive({
    token: initial.token,
    device: initial.device,

    setAuth(token, device) {
        this.token = token
        this.device = device
        localStorage.setItem('scanner_auth', JSON.stringify({ token, device }))
    },

    logout() {
        this.token = null
        this.device = null
        localStorage.removeItem('scanner_auth')
    },
})
