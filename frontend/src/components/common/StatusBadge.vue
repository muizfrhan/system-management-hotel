<template>
  <span :class="badgeClass" class="badge">{{ displayLabel }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const statusMap = {
  available: 'Tersedia',
  occupied: 'Terisi',
  reserved: 'Dipesan',
  cleaning: 'Dibersihkan',
  maintenance: 'Pemeliharaan',
  pending: 'Tertunda',
  confirmed: 'Dikonfirmasi',
  checked_in: 'Check-In',
  checked_out: 'Check-Out',
  cancelled: 'Dibatalkan',
  paid: 'Lunas',
  unpaid: 'Belum Bayar',
}

const displayLabel = computed(() => statusMap[props.status] || props.status)
const badgeClass = computed(() => `badge-${props.status}`)
</script>
