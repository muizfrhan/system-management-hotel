import { ref, onMounted } from 'vue'
import reservationApi from '../services/reservationApi'

export function useReservations(rolePrefix = '/admin') {
    const reservations = ref([])
    const loading = ref(false)

    async function fetchAll(params = {}) {
        loading.value = true
        try {
            const res = await reservationApi.getAll(params, rolePrefix)
            reservations.value = res.data.data || res.data
        } finally {
            loading.value = false
        }
    }

    onMounted(() => fetchAll())

    return { reservations, loading, fetchAll }
}

export default useReservations
