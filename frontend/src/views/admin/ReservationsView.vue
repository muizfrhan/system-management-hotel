<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Reservasi</h1>
      <div class="flex flex-wrap gap-2 items-center">
        <select v-model="filterStatus" @change="fetchData" class="select-field !w-auto">
          <option value="">Semua Status</option>
          <option value="pending">Tertunda</option>
          <option value="confirmed">Dikonfirmasi</option>
          <option value="checked_in">Check-In</option>
          <option value="checked_out">Check-Out</option>
          <option value="cancelled">Dibatalkan</option>
        </select>
        <button @click="openCreate" class="btn-primary">+ Reservasi Manual</button>
      </div>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Kode</th><th>Tamu</th><th>Kamar</th><th>Check-in</th><th>Check-out</th>
            <th>Status</th><th>Total</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in reservations" :key="r.id">
            <td class="font-mono font-semibold text-[var(--primary)]">{{ r.reservation_code }}</td>
            <td>{{ r.guest?.name }}</td>
            <td>{{ r.room?.room_number }} — {{ r.room?.room_type?.name }}</td>
            <td>{{ formatDate(r.check_in_date) }}</td>
            <td>{{ formatDate(r.check_out_date) }}</td>
            <td><span :class="'badge badge-' + r.status">{{ statusLabel(r.status) }}</span></td>
            <td class="font-semibold">{{ formatCurrency(r.total_price) }}</td>
            <td>
              <div class="flex gap-1">
                <button @click="openDetail(r)" class="btn-ghost text-xs !px-2 !py-1">Detail</button>
                <button
                  v-if="['pending', 'confirmed'].includes(r.status)"
                  @click="openEdit(r)"
                  class="btn-ghost text-xs !px-2 !py-1"
                >Edit</button>
                <button
                  v-if="['pending', 'confirmed'].includes(r.status)"
                  @click="askCancel(r)"
                  class="btn-ghost text-xs !px-2 !py-1 text-red-500 hover:!bg-red-50"
                >Batalkan</button>
              </div>
            </td>
          </tr>
          <tr v-if="!reservations.length">
            <td colspan="8" class="text-center py-8 text-[var(--text-secondary)]">Tidak ada reservasi</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2">
      <button
        v-for="p in pagination.last_page" :key="p"
        @click="page = p; fetchData()"
        :class="['btn-ghost text-xs !px-3', p === page ? '!bg-[var(--primary)] !text-white' : '']"
      >{{ p }}</button>
    </div>

    <!-- Modal Create -->
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content p-6 animate-slide-up">
        <h3 class="text-lg font-bold mb-4">Reservasi Manual</h3>
        <div v-if="formError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 mb-4">{{ formError }}</div>
        <form @submit.prevent="createReservation" class="space-y-4">
          <div><label class="label">Nama Tamu</label><input v-model="form.guest_name" class="input-field" required /></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="label">Email</label><input v-model="form.guest_email" type="email" class="input-field" /></div>
            <div><label class="label">Telepon</label><input v-model="form.guest_phone" class="input-field" /></div>
          </div>
          <div><label class="label">Kamar</label>
            <select v-model="form.room_id" class="select-field" required>
              <option value="" disabled>Pilih kamar</option>
              <option v-for="r in roomOptions" :key="r.id" :value="r.id">{{ r.room_number }} — {{ r.room_type?.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="label">Check-in</label><input v-model="form.check_in_date" type="date" :min="today" class="input-field" required /></div>
            <div><label class="label">Check-out</label><input v-model="form.check_out_date" type="date" :min="form.check_in_date || today" class="input-field" required /></div>
          </div>
          <div><label class="label">Jumlah Tamu</label><input v-model="form.number_of_guests" type="number" min="1" class="input-field" required /></div>
          <div><label class="label">Permintaan Khusus</label><textarea v-model="form.special_requests" class="input-field" rows="2"></textarea></div>
          <p v-if="nights > 0 && selectedRoom" class="text-sm text-[var(--text-secondary)]">
            Estimasi total: <strong class="text-[var(--text-primary)]">{{ formatCurrency(nights * (selectedRoom.room_type?.base_price || 0)) }}</strong>
            ({{ nights }} malam)
          </p>
          <div class="flex gap-2 justify-end">
            <button type="button" @click="showForm = false" class="btn-ghost">Batal</button>
            <button type="submit" class="btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit -->
    <div v-if="showEdit" class="modal-overlay" @click.self="showEdit = false">
      <div class="modal-content p-6 animate-slide-up">
        <h3 class="text-lg font-bold mb-1">Edit Reservasi</h3>
        <p class="text-sm text-[var(--text-secondary)] mb-4 font-mono">{{ editing?.reservation_code }}</p>
        <div v-if="formError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 mb-4">{{ formError }}</div>
        <form @submit.prevent="updateReservation" class="space-y-4">
          <div><label class="label">Kamar</label>
            <select v-model="editForm.room_id" class="select-field" required>
              <option v-for="r in roomOptions" :key="r.id" :value="r.id">{{ r.room_number }} — {{ r.room_type?.name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="label">Check-in</label><input v-model="editForm.check_in_date" type="date" :min="today" class="input-field" required /></div>
            <div><label class="label">Check-out</label><input v-model="editForm.check_out_date" type="date" :min="editForm.check_in_date || today" class="input-field" required /></div>
          </div>
          <div><label class="label">Jumlah Tamu</label><input v-model="editForm.number_of_guests" type="number" min="1" class="input-field" required /></div>
          <div><label class="label">Permintaan Khusus</label><textarea v-model="editForm.special_requests" class="input-field" rows="2"></textarea></div>
          <div class="flex gap-2 justify-end">
            <button type="button" @click="showEdit = false" class="btn-ghost">Batal</button>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Detail + Folio -->
    <div v-if="detail" class="modal-overlay" @click.self="detail = null">
      <div class="modal-content p-6 animate-slide-up" style="max-width: 40rem;">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold">Detail Reservasi</h3>
            <p class="text-sm font-mono text-[var(--primary)]">{{ detail.reservation_code }}</p>
          </div>
          <span :class="'badge badge-' + detail.status">{{ statusLabel(detail.status) }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm mb-5">
          <div><span class="text-[var(--text-secondary)]">Tamu:</span> <strong>{{ detail.guest?.name }}</strong></div>
          <div><span class="text-[var(--text-secondary)]">Email:</span> {{ detail.guest?.email || '-' }}</div>
          <div><span class="text-[var(--text-secondary)]">Telepon:</span> {{ detail.guest?.phone || '-' }}</div>
          <div><span class="text-[var(--text-secondary)]">Jumlah Tamu:</span> {{ detail.number_of_guests }} orang</div>
          <div><span class="text-[var(--text-secondary)]">Kamar:</span> {{ detail.room?.room_number }} — {{ detail.room?.room_type?.name }}</div>
          <div class="sm:col-span-2"><span class="text-[var(--text-secondary)]">Menginap:</span> {{ formatDate(detail.check_in_date) }} → {{ formatDate(detail.check_out_date) }}</div>
          <div class="sm:col-span-2" v-if="detail.special_requests"><span class="text-[var(--text-secondary)]">Permintaan:</span> {{ detail.special_requests }}</div>
        </div>

        <!-- Folio -->
        <div class="border-t border-gray-100 pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-sm">Folio / Biaya Tambahan</h4>
            <span class="text-xs text-[var(--text-secondary)]">Kamar: {{ formatCurrency(detail.total_price) }}</span>
          </div>

          <table v-if="detail.charges?.length" class="w-full text-sm mb-3">
            <thead>
              <tr class="text-left text-xs text-[var(--text-secondary)] border-b border-gray-100">
                <th class="py-2">Item</th><th>Qty</th><th class="text-right">Jumlah</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in detail.charges" :key="c.id" class="border-b border-gray-50">
                <td class="py-2">{{ c.description }} <span class="text-[10px] uppercase text-[var(--text-secondary)]">({{ c.category }})</span></td>
                <td>{{ c.quantity }}</td>
                <td class="text-right font-semibold">{{ formatCurrency(c.total_price) }}</td>
                <td class="text-right">
                  <button
                    v-if="!['cancelled', 'checked_out'].includes(detail.status)"
                    @click="removeCharge(c.id)"
                    class="text-red-400 text-xs hover:text-red-600"
                  >Hapus</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-xs text-[var(--text-secondary)] mb-3">Belum ada biaya tambahan.</p>

          <div v-if="!['cancelled', 'checked_out'].includes(detail.status)" class="bg-slate-50 rounded-xl p-3 mb-3">
            <div class="grid grid-cols-2 sm:grid-cols-6 md:grid-cols-12 gap-2 items-end">
              <div class="col-span-2 sm:col-span-6 md:col-span-4"><label class="label !text-xs">Item</label><input v-model="chargeForm.description" class="input-field !py-2 text-xs" placeholder="Minibar, Laundry..." /></div>
              <div class="col-span-1 sm:col-span-3 md:col-span-3">
                <label class="label !text-xs">Kategori</label>
                <select v-model="chargeForm.category" class="select-field !py-2 text-xs">
                  <option value="minibar">Minibar</option>
                  <option value="laundry">Laundry</option>
                  <option value="restaurant">Restoran</option>
                  <option value="damage">Kerusakan</option>
                  <option value="other">Lainnya</option>
                </select>
              </div>
              <div class="col-span-1 md:col-span-1"><label class="label !text-xs">Qty</label><input v-model.number="chargeForm.quantity" type="number" min="1" class="input-field !py-2 text-xs" /></div>
              <div class="col-span-1 sm:col-span-3 md:col-span-2"><label class="label !text-xs">Harga</label><input v-model.number="chargeForm.unit_price" type="number" min="0" class="input-field !py-2 text-xs" placeholder="0" /></div>
              <div class="col-span-2 sm:col-span-3 md:col-span-2"><button @click="addCharge" class="btn-primary w-full !py-2 text-xs">Tambah</button></div>
            </div>
          </div>

          <div class="flex justify-between items-center bg-[var(--primary)]/5 rounded-xl px-4 py-3">
            <span class="text-sm font-semibold">Total Folio</span>
            <span class="text-lg font-bold text-[var(--primary)]">{{ formatCurrency(folioTotal) }}</span>
          </div>
        </div>

        <div class="flex justify-end mt-4">
          <button @click="detail = null" class="btn-ghost">Tutup</button>
        </div>
      </div>
    </div>

    <ConfirmModal
      :visible="showCancelConfirm"
      title="Batalkan Reservasi?"
      :message="`Reservasi ${cancelTarget?.reservation_code} akan dibatalkan dan kamar akan dilepas.`"
      confirm-text="Ya, Batalkan"
      danger
      @confirm="confirmCancel"
      @cancel="showCancelConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import ConfirmModal from '../../components/common/ConfirmModal.vue'

const auth = useAuthStore()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const reservations = ref([])
const roomOptions = ref([])
const pagination = ref(null)
const page = ref(1)
const filterStatus = ref('')

const showForm = ref(false)
const showEdit = ref(false)
const showCancelConfirm = ref(false)
const detail = ref(null)
const editing = ref(null)
const cancelTarget = ref(null)
const formError = ref(null)

const today = new Date().toISOString().split('T')[0]

const emptyForm = { guest_name: '', guest_email: '', guest_phone: '', room_id: '', check_in_date: '', check_out_date: '', number_of_guests: 1, special_requests: '' }
const form = ref({ ...emptyForm })
const editForm = ref({ room_id: '', check_in_date: '', check_out_date: '', number_of_guests: 1, special_requests: '' })
const chargeForm = ref({ description: '', category: 'minibar', quantity: 1, unit_price: 0 })

const nights = computed(() => {
  if (!form.value.check_in_date || !form.value.check_out_date) return 0
  return Math.max(0, (new Date(form.value.check_out_date) - new Date(form.value.check_in_date)) / 86400000)
})
const selectedRoom = computed(() => roomOptions.value.find(r => r.id === form.value.room_id))
const folioTotal = computed(() => {
  if (!detail.value) return 0
  return (parseFloat(detail.value.total_price) || 0) +
    (detail.value.charges || []).reduce((s, c) => s + parseFloat(c.total_price), 0)
})

onMounted(async () => {
  await fetchData()
  const roomRes = await api.get('/admin/rooms')
  roomOptions.value = roomRes.data
    .filter(r => r.status !== 'maintenance')
    .sort((a, b) => a.room_number.localeCompare(b.room_number))
})

async function fetchData() {
  const params = { page: page.value }
  if (filterStatus.value) params.status = filterStatus.value
  const res = await api.get(`${prefix}/reservations`, { params })
  reservations.value = res.data.data || res.data
  pagination.value = res.data.data ? res.data : null
}

function openCreate() {
  form.value = { ...emptyForm }
  formError.value = null
  showForm.value = true
}

function openEdit(r) {
  editing.value = r
  editForm.value = {
    room_id: r.room_id,
    check_in_date: r.check_in_date?.split('T')[0] || r.check_in_date,
    check_out_date: r.check_out_date?.split('T')[0] || r.check_out_date,
    number_of_guests: r.number_of_guests,
    special_requests: r.special_requests || '',
  }
  formError.value = null
  showEdit.value = true
}

async function openDetail(r) {
  const res = await api.get(`${prefix}/reservations/${r.id}`)
  detail.value = res.data
  chargeForm.value = { description: '', category: 'minibar', quantity: 1, unit_price: 0 }
}

function askCancel(r) {
  cancelTarget.value = r
  showCancelConfirm.value = true
}

async function createReservation() {
  formError.value = null
  try {
    await api.post(`${prefix}/reservations`, form.value)
    showForm.value = false
    fetchData()
  } catch (err) {
    formError.value = err.response?.data?.message || 'Gagal menyimpan reservasi.'
  }
}

async function updateReservation() {
  formError.value = null
  try {
    await api.put(`${prefix}/reservations/${editing.value.id}`, editForm.value)
    showEdit.value = false
    fetchData()
    if (detail.value?.id === editing.value.id) openDetail(editing.value)
  } catch (err) {
    formError.value = err.response?.data?.message || 'Gagal mengubah reservasi.'
  }
}

async function confirmCancel() {
  showCancelConfirm.value = false
  try {
    await api.delete(`${prefix}/reservations/${cancelTarget.value.id}`)
    fetchData()
    if (detail.value?.id === cancelTarget.value.id) detail.value = null
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal membatalkan reservasi.')
  }
}

async function addCharge() {
  if (!chargeForm.value.description || !chargeForm.value.unit_price) return
  try {
    await api.post(`${prefix}/reservations/${detail.value.id}/charges`, chargeForm.value)
    await openDetail({ id: detail.value.id })
    fetchData()
    chargeForm.value = { description: '', category: 'minibar', quantity: 1, unit_price: 0 }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menambah biaya.')
  }
}

async function removeCharge(chargeId) {
  if (!confirm('Hapus biaya tambahan ini?')) return
  try {
    await api.delete(`${prefix}/charges/${chargeId}`)
    await openDetail({ id: detail.value.id })
    fetchData()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus biaya.')
  }
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }
function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0) }
function statusLabel(s) { const l = { pending: 'Tertunda', confirmed: 'Dikonfirmasi', checked_in: 'Check-In', checked_out: 'Check-Out', cancelled: 'Dibatalkan' }; return l[s] || s }
</script>
