import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useDashboardStore = defineStore('dashboard', () => {
    const stats = ref({})
    const loading = ref(false)

    async function fetchStats() {
        loading.value = true
        try {
            const res = await api.get('/admin/dashboard')
            stats.value = res.data
        } finally {
            loading.value = false
        }
    }

    return { stats, loading, fetchStats }
})
