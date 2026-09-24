import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useRoomStore = defineStore('room', () => {
    const rooms = ref([])
    const roomTypes = ref([])
    const loading = ref(false)

    async function fetchRooms(params = {}) {
        loading.value = true
        try {
            const res = await api.get('/admin/rooms', { params })
            rooms.value = res.data
        } finally {
            loading.value = false
        }
    }

    async function fetchRoomTypes() {
        const res = await api.get('/admin/room-types')
        roomTypes.value = res.data
    }

    return { rooms, roomTypes, loading, fetchRooms, fetchRoomTypes }
})
