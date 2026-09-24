import { ref, onMounted } from 'vue'
import roomTypeApi from '../services/roomTypeApi'

export function useRoomTypes() {
    const roomTypes = ref([])
    const loading = ref(false)

    async function fetchAll() {
        loading.value = true
        try {
            const res = await roomTypeApi.getAll()
            roomTypes.value = res.data
        } finally {
            loading.value = false
        }
    }

    onMounted(fetchAll)

    return { roomTypes, loading, fetchAll }
}

export default useRoomTypes
