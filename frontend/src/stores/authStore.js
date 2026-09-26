import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { ensureCsrfCookie } from '../services/api'

const STORAGE_KEY = 'user'

function isUserProfile(value) {
    return !!value && typeof value === 'object' && !Array.isArray(value)
}

function clearStoredUser() {
    try {
        localStorage.removeItem(STORAGE_KEY)
    } catch {}
}

function readStoredUser() {
    try {
        const storedUser = localStorage.getItem(STORAGE_KEY)
        if (!storedUser) return null

        const parsedUser = JSON.parse(storedUser)
        if (!isUserProfile(parsedUser)) {
            clearStoredUser()
            return null
        }

        return parsedUser
    } catch {
        clearStoredUser()
        return null
    }
}

function storeUser(profile) {
    if (!isUserProfile(profile)) return
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(profile))
    } catch {}
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref(readStoredUser())
    const loading = ref(false)
    const error = ref(null)
    const initialized = ref(false)
    const initializing = ref(false)
    let initializationPromise = null

    const isAuthenticated = computed(() => !!user.value)
    const userRole = computed(() => user.value?.role || null)
    const isAdmin = computed(() => userRole.value === 'admin')
    const isReceptionist = computed(() => userRole.value === 'receptionist')
    const isHousekeeper = computed(() => userRole.value === 'housekeeper')

    function clearAuthState() {
        user.value = null
        error.value = null
        clearStoredUser()
    }

    async function initialize() {
        if (initializationPromise) return initializationPromise

        initializationPromise = (async () => {
            initializing.value = true
            try {
                await fetchUser()
            } finally {
                initializing.value = false
                initialized.value = true
            }
        })()

        return initializationPromise
    }

    async function login(email, password) {
        loading.value = true
        error.value = null
        try {
            await ensureCsrfCookie()
            const response = await api.post('/auth/login', { email, password })
            const profile = response.data?.user
            if (!isUserProfile(profile)) throw new Error('Respons login tidak valid.')
            user.value = profile
            storeUser(profile)
            try {
                await ensureCsrfCookie(true)
            } catch {}
            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Login gagal.'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        try {
            await api.post('/auth/logout')
        } finally {
            clearAuthState()
        }
    }

    async function fetchUser() {
        try {
            const response = await api.get('/auth/user')
            const profile = response.data?.user ?? response.data
            if (!isUserProfile(profile)) {
                clearAuthState()
                return null
            }
            user.value = profile
            storeUser(profile)
            return profile
        } catch (err) {
            const status = err.response?.status
            if (status === 401 || status === 403) clearAuthState()
            return null
        }
    }

    return {
        user, loading, error, initialized, initializing,
        isAuthenticated, userRole, isAdmin, isReceptionist, isHousekeeper,
        initialize, login, logout, fetchUser,
    }
})
