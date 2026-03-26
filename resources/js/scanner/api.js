import axios from 'axios'
import { authStore } from './stores/auth'

const api = axios.create({
    baseURL: '/api/scanner',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
})

api.interceptors.request.use((config) => {
    if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            authStore.logout()
            window.location.href = '/scanner/setup'
        }
        return Promise.reject(error)
    },
)

export default api
