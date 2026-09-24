import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useReservationStore = defineStore('reservation', () => {
    const reservations = ref([])
    const loading = ref(false)

    async function fetchReservations(params = {}, rolePrefix = '/admin') {
        loading.value = true
        try {
            const res = await api.get(`${rolePrefix}/reservations`, { params })
            reservations.value = res.data.data || res.data
        } finally {
            loading.value = false
        }
    }

    return { reservations, loading, fetchReservations }
})
