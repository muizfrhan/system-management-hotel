import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const loading = ref(false)
    const error = ref(null)

    const isAuthenticated = computed(() => !!user.value)
    const userRole = computed(() => user.value?.role || null)
    const isAdmin = computed(() => userRole.value === 'admin')
    const isReceptionist = computed(() => userRole.value === 'receptionist')
    const isHousekeeper = computed(() => userRole.value === 'housekeeper')

    async function login(email, password) {
        loading.value = true
        error.value = null
        try {
            await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
            const response = await api.post('/auth/login', { email, password })
            user.value = response.data.user
            localStorage.setItem('user', JSON.stringify(user.value))
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Login gagal.'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        try {
            await api.post('/auth/logout')
        } finally {
            user.value = null
            localStorage.removeItem('user')
        }
    }

    async function fetchUser() {
        try {
            const response = await api.get('/auth/user')
            user.value = response.data
            localStorage.setItem('user', JSON.stringify(user.value))
        } catch {
            user.value = null
            localStorage.removeItem('user')
        }
    }

    return {
        user, loading, error,
        isAuthenticated, userRole, isAdmin, isReceptionist, isHousekeeper,
        login, logout, fetchUser,
    }
})
