<template>
  <div class="space-y-6">
    <h1 class="page-title">Booking Online <span v-if="bookings.length" class="ml-2 bg-amber-100 text-amber-700 text-sm px-3 py-1 rounded-full font-bold">{{ bookings.length }}</span></h1>
    <div v-if="!bookings.length" class="card text-center py-12 text-[var(--text-secondary)]">Tidak ada booking tertunda</div>
    <div v-else class="space-y-4">
      <div v-for="b in bookings" :key="b.id" class="card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
          <p class="font-bold text-[var(--primary)]">{{ b.reservation_code }}</p>
          <p class="text-sm">{{ b.guest?.name }} · {{ b.guest?.email }}</p>
          <p class="text-xs text-[var(--text-secondary)]">{{ b.room?.room_type?.name }} · Kamar {{ b.room?.room_number }} · {{ formatDate(b.check_in_date) }} → {{ formatDate(b.check_out_date) }}</p>
          <p class="text-sm font-semibold mt-1">{{ formatCurrency(b.total_price) }}</p>
        </div>
        <div class="flex gap-2 shrink-0">
          <button @click="confirmBooking(b.id)" class="btn-primary text-xs flex-1 sm:flex-none">Konfirmasi</button>
          <button @click="rejectBooking(b.id)" class="btn-danger text-xs flex-1 sm:flex-none">Tolak</button>
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

const bookings = ref([])
onMounted(() => fetchData())
async function fetchData() { const res = await api.get(`${prefix}/booking-online`); bookings.value = res.data }
async function confirmBooking(id) { await api.put(`${prefix}/booking-online/${id}/confirm`); fetchData() }
async function rejectBooking(id) { if (!confirm('Tolak booking ini?')) return; await api.put(`${prefix}/booking-online/${id}/reject`); fetchData() }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }
function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0) }
</script>
