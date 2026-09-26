import { ref } from 'vue'
import api from '../services/api'

const setting = ref(null)
const roomTypes = ref([])
const loading = ref(true)
const error = ref(null)
const cacheTtl = 60_000
let loadedAt = null
let inflight = null

export function useLandingData() {
    async function load(force = false) {
        if (inflight) return inflight
        if (!force && loadedAt !== null && Date.now() - loadedAt < cacheTtl) {
            return { setting: setting.value, room_types: roomTypes.value }
        }

        loading.value = true
        error.value = null
        inflight = api
            .get('/guest/landing')
            .then((res) => {
                setting.value = res.data.setting
                roomTypes.value = res.data.room_types || []
                loadedAt = Date.now()
                return res.data
            })
            .catch((requestError) => {
                error.value = requestError
                throw requestError
            })
            .finally(() => {
                loading.value = false
                inflight = null
            })
        return inflight
    }

    return { setting, roomTypes, loading, error, load }
}
