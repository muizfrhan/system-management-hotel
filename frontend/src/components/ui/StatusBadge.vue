<template>
  <span :class="['badge', badgeClass]">{{ displayLabel }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
})

const statusMap = {
  available: 'Tersedia',
  occupied: 'Terisi',
  reserved: 'Dipesan',
  cleaning: 'Perlu Dibersihkan',
  maintenance: 'Pemeliharaan',
  pending: 'Tertunda',
  confirmed: 'Dikonfirmasi',
  checked_in: 'Check-In',
  checked_out: 'Check-Out',
  cancelled: 'Dibatalkan',
  paid: 'Lunas',
  unpaid: 'Belum Bayar',
}

const displayLabel = computed(() => props.label || statusMap[props.status] || props.status)
const badgeClass = computed(() => `badge-${props.status}`)
</script>
