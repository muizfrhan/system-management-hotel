<template>
  <div class="space-y-6">
    <h1 class="page-title">Tata Graha <span v-if="rooms.length" class="ml-2 bg-orange-100 text-orange-700 text-sm px-3 py-1 rounded-full font-bold">{{ rooms.length }}</span></h1>
    <div v-if="!rooms.length" class="card text-center py-12 text-[var(--success)] font-bold">Semua kamar sudah bersih!</div>
    <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div v-for="room in rooms" :key="room.id" class="card !p-4 text-center">
        <div class="mb-3 flex justify-center text-orange-400">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 6c0-1.6-1.4-3-3-3s-3 1.4-3 3c0 2.2 2 4.4 3 6 1-1.6 3-3.8 3-6Z"/><path d="m19 12-6 6"/><path d="M8 12.5C8 12.5 13 8 13 8s-4.5 5-4.5 5Z"/><path d="M7.7 12.8a2 2 0 1 0-2.8 2.8L16 23l2.8-2.8Z"/></svg>
        </div>
        <p class="text-xl font-bold text-[var(--primary)]">{{ room.room_number }}</p>
        <p class="text-xs text-[var(--text-secondary)] mb-3">{{ room.room_type?.name }} · Lt.{{ room.floor }}</p>
        <button @click="markDone(room.id)" class="btn-primary text-xs w-full">Tandai Selesai</button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
const rooms = ref([])
onMounted(() => fetchData())
async function fetchData() { const res = await api.get('/admin/housekeeping'); rooms.value = res.data }
async function markDone(id) { await api.patch(`/admin/housekeeping/${id}/done`); fetchData() }
</script>
