import { ref, onMounted } from 'vue'
import roomApi from '../services/roomApi'

export function useRooms(initialParams = {}) {
    const rooms = ref([])
    const loading = ref(false)

    async function fetchAll(params = initialParams) {
        loading.value = true
        try {
            const res = await roomApi.getAll(params)
            rooms.value = res.data
        } finally {
            loading.value = false
        }
    }

    onMounted(() => fetchAll())

    return { rooms, loading, fetchAll }
}

export default useRooms
