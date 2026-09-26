<template>
  <div class="space-y-6">
    <PageHeader
      title="Ketersediaan Kamar"
      description="Pantau status semua kamar secara real-time."
    >
      <template #actions>
        <select v-model="filterStatus" class="select-field !w-auto" aria-label="Filter status">
          <option value="">Semua Status</option>
          <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <select v-model="filterFloor" class="select-field !w-auto" aria-label="Filter lantai">
          <option value="">Semua Lantai</option>
          <option v-for="f in floors" :key="f" :value="f">Lantai {{ f }}</option>
        </select>
        <BaseButton variant="outline" size="sm" :loading="loading" @click="fetchData">
          Segarkan
        </BaseButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
      <StatCard
        v-for="s in summaryCards"
        :key="s.label"
        :label="s.label"
        :value="s.value"
        :color="s.color"
      />
    </div>

     <AlertBox v-if="loadError" variant="error" title="Gagal memuat kamar">
       <div class="flex flex-wrap items-center gap-3 mt-1">
         <span>{{ loadError }}</span>
         <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
       </div>
     </AlertBox>

     <div v-else-if="loading && !rooms.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div v-for="i in 8" :key="i" class="card !p-4">
        <div class="skeleton skeleton-title mx-auto mb-2" style="width: 50%; height: 2rem"></div>
        <div class="skeleton skeleton-text mx-auto" style="width: 60%"></div>
      </div>
    </div>

    <div v-else-if="filteredRooms.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div
        v-for="room in filteredRooms"
        :key="room.id"
        class="card !p-4 text-center"
        :aria-label="`Kamar ${room.room_number}, status ${statusLabel(room.status)}`"
      >
        <p class="text-2xl font-bold text-primary mb-1">{{ room.room_number }}</p>
        <p class="text-xs text-ink-secondary mb-2 truncate">
          {{ room.room_type?.name }} · Lt.{{ room.floor }}
        </p>
        <StatusBadge :status="room.status" :label="statusLabel(room.status)" />
      </div>
    </div>

    <div v-else class="card">
      <EmptyState
        title="Tidak ada kamar"
        description="Tidak ada kamar yang cocok dengan filter."
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../services/api'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import StatCard from '../../components/ui/StatCard.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import AlertBox from '../../components/ui/AlertBox.vue'

const rooms = ref([])
const loading = ref(false)
const filterStatus = ref('')
const filterFloor = ref('')
const loadError = ref('')
const allFloors = ref([])

const statuses = [
  { value: 'available', label: 'Tersedia' },
  { value: 'occupied', label: 'Terisi' },
  { value: 'reserved', label: 'Dipesan' },
  { value: 'cleaning', label: 'Perlu Dibersihkan' },
  { value: 'maintenance', label: 'Pemeliharaan' },
]
const floors = computed(() => allFloors.value)

const filteredRooms = computed(() =>
  rooms.value.filter((r) => {
    if (filterStatus.value && r.status !== filterStatus.value) return false
    if (filterFloor.value && r.floor !== filterFloor.value) return false
    return true
  })
)

const summaryCards = computed(() => {
  const counts = {}
  for (const s of statuses) counts[s.value] = 0
  for (const r of rooms.value) counts[r.status] = (counts[r.status] || 0) + 1
  const colors = {
    available: 'var(--color-success)',
    occupied: 'var(--color-info)',
    reserved: 'var(--color-primary)',
    cleaning: 'var(--color-warning)',
    maintenance: 'var(--color-danger)',
  }
  return statuses.map((s) => ({
    label: s.label,
    value: counts[s.value] || 0,
    color: colors[s.value],
  }))
})

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.get('/receptionist/rooms')
    rooms.value = res.data || []
    allFloors.value = [...new Set(rooms.value.map((room) => String(room.floor)).filter(Boolean))].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }))
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Data kamar tidak dapat dimuat.'
  } finally {
    loading.value = false
  }
}

function statusLabel(s) {
  return statuses.find((st) => st.value === s)?.label || s
}
</script>
