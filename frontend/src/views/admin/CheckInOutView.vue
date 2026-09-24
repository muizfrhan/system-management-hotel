<template>
  <div class="space-y-6">
    <h1 class="page-title">Check In / Check Out</h1>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold mb-4 text-emerald-700">Siap Check-In</h3>
        <div v-if="!data.check_ins?.length" class="text-[var(--text-secondary)] text-sm py-4">Tidak ada check-in hari ini</div>
        <div v-for="r in data.check_ins" :key="r.id" class="flex items-center justify-between gap-3 py-3 border-b border-gray-50 last:border-0">
          <div class="min-w-0">
            <p class="font-semibold truncate">{{ r.guest?.name }}</p>
            <p class="text-xs text-[var(--text-secondary)]">{{ r.reservation_code }} · Kamar {{ r.room?.room_number }}</p>
          </div>
          <button @click="checkIn(r.id)" class="btn-primary text-xs shrink-0">Check-In</button>
        </div>
      </div>
      <div class="card">
        <h3 class="text-lg font-bold mb-4 text-blue-700">Siap Check-Out</h3>
        <div v-if="!data.check_outs?.length" class="text-[var(--text-secondary)] text-sm py-4">Tidak ada check-out</div>
        <div v-for="r in data.check_outs" :key="r.id" class="flex items-center justify-between gap-3 py-3 border-b border-gray-50 last:border-0">
          <div class="min-w-0">
            <p class="font-semibold truncate">{{ r.guest?.name }}</p>
            <p class="text-xs text-[var(--text-secondary)]">{{ r.reservation_code }} · Kamar {{ r.room?.room_number }}</p>
          </div>
          <button @click="checkOut(r.id)" class="btn-outline text-xs shrink-0">Check-Out</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'

const auth = useAuthStore()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const data = ref({ check_ins: [], check_outs: [] })
onMounted(() => fetchData())
async function fetchData() { const res = await api.get(`${prefix}/check-in-out`); data.value = res.data }
async function checkIn(id) { await api.put(`${prefix}/check-in-out/${id}/check-in`); fetchData() }
async function checkOut(id) { await api.put(`${prefix}/check-in-out/${id}/check-out`); fetchData() }
</script>
