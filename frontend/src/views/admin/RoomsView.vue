<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Daftar Kamar</h1>
      <BaseButton type="button" @click="openCreate">+ Tambah Kamar</BaseButton>
    </div>

    <div class="flex gap-3 flex-wrap">
      <BaseSelect v-model="filterStatus" label="Status" @update:model-value="onFilterChange">
        <option value="">Semua Status</option>
        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
      </BaseSelect>
      <BaseSelect v-model="filterFloor" label="Lantai" @update:model-value="onFilterChange">
        <option value="">Semua Lantai</option>
        <option v-for="floor in floors" :key="floor" :value="floor">Lantai {{ floor }}</option>
      </BaseSelect>
    </div>

    <AlertBox v-if="fetchError" variant="error" title="Gagal memuat kamar">
      <div class="flex flex-wrap items-center gap-3 mt-1">
        <span>Periksa koneksi Anda lalu coba lagi.</span>
        <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
      </div>
    </AlertBox>

    <div v-else-if="loading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" aria-hidden="true">
      <div v-for="n in 8" :key="`room-skeleton-${n}`" class="card !p-4 text-center">
        <div class="skeleton mx-auto w-12 h-7"></div>
        <div class="skeleton mx-auto mt-3 w-20 h-3"></div>
        <div class="skeleton mx-auto mt-3 w-16 h-5 rounded-full"></div>
      </div>
    </div>

    <div v-else-if="rooms.length === 0" class="card">
      <EmptyState title="Belum ada kamar" description="Tambahkan kamar agar dapat dikelola dan dipesan.">
        <template #action>
          <BaseButton type="button" @click="openCreate">+ Tambah Kamar</BaseButton>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <button
        v-for="room in rooms"
        :key="room.id"
        type="button"
        class="card !p-4 text-center group cursor-pointer hover:border-primary/40 hover:shadow-card"
        :aria-label="`Edit kamar ${room.room_number}`"
        @click="openEdit(room)"
      >
        <p class="text-2xl font-bold text-[var(--primary)] mb-1">{{ room.room_number }}</p>
        <p class="text-xs text-[var(--text-secondary)] mb-2">{{ room.room_type?.name }} · Lt.{{ room.floor }}</p>
        <span :class="`badge badge-${room.status}`">{{ statusLabel(room.status) }}</span>
      </button>
    </div>

    <BaseModal v-model="showForm" :title="editing ? 'Edit Kamar' : 'Tambah Kamar'" size="sm">
      <AlertBox v-if="formError" variant="error" class="mb-4" :title="formError" />
      <form @submit.prevent="saveItem" class="space-y-4">
        <BaseInput v-model="form.room_number" label="Nomor Kamar" required />
        <BaseSelect v-model="form.room_type_id" label="Tipe Kamar" required>
          <option value="" disabled>Pilih tipe kamar</option>
          <option v-for="roomType in roomTypes" :key="roomType.id" :value="roomType.id">{{ roomType.name }}</option>
        </BaseSelect>
        <BaseInput v-model="form.floor" label="Lantai" required />
        <BaseSelect v-if="editing" v-model="form.status" label="Status" required>
          <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
        </BaseSelect>
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton type="button" variant="ghost" @click="showForm = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">{{ editing ? 'Simpan' : 'Tambah' }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../services/api'
import AlertBox from '../../components/ui/AlertBox.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseSelect from '../../components/ui/BaseSelect.vue'
import EmptyState from '../../components/ui/EmptyState.vue'

const rooms = ref([])
const roomTypes = ref([])
const showForm = ref(false)
const editing = ref(null)
const filterStatus = ref('')
const filterFloor = ref('')
const form = ref({ room_number: '', room_type_id: '', floor: '', status: 'available' })
const loading = ref(false)
const saving = ref(false)
const fetchError = ref('')
const formError = ref('')
const allFloors = ref([])
let fetchSeq = 0

const statuses = [
  { value: 'available', label: 'Tersedia' },
  { value: 'occupied', label: 'Terisi' },
  { value: 'reserved', label: 'Dipesan' },
  { value: 'cleaning', label: 'Perlu Dibersihkan' },
  { value: 'maintenance', label: 'Pemeliharaan' },
]

const floors = computed(() => allFloors.value)

onMounted(async () => {
  await fetchData()
  try {
    const res = await api.get('/admin/room-types')
    roomTypes.value = res.data || []
  } catch {
    formError.value = 'Tipe kamar tidak dapat dimuat.'
  }
})

async function fetchData() {
  const seq = ++fetchSeq
  loading.value = true
  fetchError.value = ''
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterFloor.value) params.floor = filterFloor.value
    const res = await api.get('/admin/rooms', { params })
    if (seq !== fetchSeq) return
    rooms.value = res.data || []
    allFloors.value = [...new Set([...allFloors.value, ...rooms.value.map((room) => String(room.floor)).filter(Boolean)])].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }))
  } catch (error) {
    if (seq !== fetchSeq) return
    fetchError.value = error.response?.data?.message || 'Kamar tidak dapat dimuat.'
  } finally {
    if (seq === fetchSeq) loading.value = false
  }
}

function onFilterChange() {
  fetchData()
}

function resetForm() {
  form.value = { room_number: '', room_type_id: '', floor: '', status: 'available' }
  formError.value = ''
}

function openCreate() {
  editing.value = null
  resetForm()
  showForm.value = true
}

function openEdit(room) {
  editing.value = room.id
  form.value = { room_number: room.room_number, room_type_id: room.room_type_id, floor: room.floor, status: room.status }
  formError.value = ''
  showForm.value = true
}

async function saveItem() {
  if (saving.value) return
  saving.value = true
  formError.value = ''
  try {
    if (editing.value) await api.put(`/admin/rooms/${editing.value}`, form.value)
    else await api.post('/admin/rooms', form.value)
    showForm.value = false
    await fetchData()
  } catch (error) {
    const data = error.response?.data
    formError.value = data?.message || Object.values(data?.errors || {}).flat()[0] || 'Kamar gagal disimpan.'
  } finally {
    saving.value = false
  }
}

function statusLabel(status) {
  return statuses.find((item) => item.value === status)?.label || status
}
</script>
