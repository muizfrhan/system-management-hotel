<template>
  <div class="space-y-6">
    <h1 class="page-title">Data Tamu</h1>
    <div class="table-container">
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Reservasi</th></tr></thead>
        <tbody>
          <tr v-for="g in guests" :key="g.id">
            <td class="font-semibold">{{ g.name }}</td>
            <td>{{ g.email || '-' }}</td>
            <td>{{ g.phone || '-' }}</td>
            <td>{{ g.reservations_count }} kali</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'

const auth = useAuthStore()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const guests = ref([])
onMounted(async () => { const res = await api.get(`${prefix}/guests`); guests.value = res.data.data || res.data })
</script>
