<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Daftar Kamar</h1>
      <button @click="showForm = true; editing = null; resetForm()" class="btn-primary">+ Tambah Kamar</button>
    </div>
    <div class="flex gap-3 flex-wrap">
      <select v-model="filterStatus" @change="fetchData" class="select-field !w-auto">
        <option value="">Semua Status</option>
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
      <select v-model="filterFloor" @change="fetchData" class="select-field !w-auto">
        <option value="">Semua Lantai</option>
        <option v-for="f in floors" :key="f" :value="f">Lantai {{ f }}</option>
      </select>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div v-for="room in rooms" :key="room.id" class="card !p-4 text-center group cursor-pointer" @click="editItem(room)">
        <p class="text-2xl font-bold text-[var(--primary)] mb-1">{{ room.room_number }}</p>
        <p class="text-xs text-[var(--text-secondary)] mb-2">{{ room.room_type?.name }} · Lt.{{ room.floor }}</p>
        <span :class="'badge badge-' + room.status" class="badge text-[10px]">{{ statusLabel(room.status) }}</span>
      </div>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content p-6 animate-slide-up">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Edit' : 'Tambah' }} Kamar</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div><label class="label">Nomor Kamar</label><input v-model="form.room_number" class="input-field" required /></div>
          <div><label class="label">Tipe Kamar</label>
            <select v-model="form.room_type_id" class="select-field" required>
              <option v-for="rt in roomTypes" :key="rt.id" :value="rt.id">{{ rt.name }}</option>
            </select>
          </div>
          <div><label class="label">Lantai</label><input v-model="form.floor" class="input-field" required /></div>
          <div v-if="editing"><label class="label">Status</label>
            <select v-model="form.status" class="select-field">
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div class="flex gap-2 justify-end pt-2">
            <button type="button" @click="showForm = false" class="btn-ghost">Batal</button>
            <button type="submit" class="btn-primary">{{ editing ? 'Simpan' : 'Tambah' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const rooms = ref([])
const roomTypes = ref([])
const showForm = ref(false)
const editing = ref(null)
const filterStatus = ref('')
const filterFloor = ref('')
const form = ref({ room_number: '', room_type_id: '', floor: '', status: 'available' })
const statuses = [
  { value: 'available', label: 'Tersedia' }, { value: 'occupied', label: 'Terisi' },
  { value: 'reserved', label: 'Dipesan' }, { value: 'cleaning', label: 'Dibersihkan' },
  { value: 'maintenance', label: 'Pemeliharaan' },
]
const floors = ['1', '2', '3']

onMounted(async () => {
  await fetchData()
  const rtRes = await api.get('/admin/room-types')
  roomTypes.value = rtRes.data
})

async function fetchData() {
  const params = {}
  if (filterStatus.value) params.status = filterStatus.value
  if (filterFloor.value) params.floor = filterFloor.value
  const res = await api.get('/admin/rooms', { params })
  rooms.value = res.data
}

function resetForm() { form.value = { room_number: '', room_type_id: '', floor: '', status: 'available' } }
function editItem(room) { editing.value = room.id; form.value = { ...room }; showForm.value = true }

async function saveItem() {
  if (editing.value) {
    await api.put(`/admin/rooms/${editing.value}`, form.value)
  } else {
    await api.post('/admin/rooms', form.value)
  }
  showForm.value = false
  fetchData()
}

function statusLabel(s) {
  return statuses.find(st => st.value === s)?.label || s
}
</script>
