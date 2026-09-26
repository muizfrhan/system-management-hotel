<template>
  <div class="space-y-6">
    <PageHeader title="Reservasi" description="Kelola reservasi tamu dan status booking.">
      <template #actions>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Cari kode, nama, email, telp, kamar..."
          class="input-field w-full sm:!w-64"
          aria-label="Cari reservasi"
          @input="onSearch"
        />
         <select v-model="filterStatus" aria-label="Filter status reservasi" @change="onStatusFilter" class="select-field !w-auto">
          <option value="">Semua Status</option>
          <option value="pending">Tertunda</option>
          <option value="confirmed">Dikonfirmasi</option>
          <option value="checked_in">Check-In</option>
          <option value="checked_out">Check-Out</option>
          <option value="cancelled">Dibatalkan</option>
        </select>
        <BaseButton @click="openCreate">+ Reservasi Manual</BaseButton>
      </template>
    </PageHeader>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">Kode</th>
            <th scope="col">Tamu</th>
            <th scope="col">Kamar</th>
            <th scope="col">Check-in</th>
            <th scope="col">Check-out</th>
            <th scope="col">Status</th>
            <th scope="col">Pembayaran</th>
            <th scope="col">Total</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in reservations" :key="r.id">
            <td class="font-mono font-semibold text-primary">{{ r.reservation_code }}</td>
            <td>{{ r.guest?.name }}</td>
            <td>{{ r.room?.room_number }} — {{ r.room?.room_type?.name }}</td>
            <td>{{ formatDate(r.check_in_date) }}</td>
            <td>{{ formatDate(r.check_out_date) }}</td>
            <td><StatusBadge :status="r.status" :label="statusLabel(r.status)" /></td>
            <td><StatusBadge :status="rowPaymentStatus(r)" :label="rowPaymentStatus(r) === 'paid' ? 'Lunas' : 'Belum'" /></td>
            <td class="font-semibold">{{ formatCurrency(r.total_price) }}</td>
            <td>
              <div class="flex flex-wrap gap-1">
                <BaseButton variant="ghost" size="sm" class="min-h-10" @click="openDetail(r)">Detail</BaseButton>
                <BaseButton
                  v-if="['pending', 'confirmed'].includes(r.status)"
                  variant="ghost"
                  size="sm"
                  class="min-h-10"
                  @click="openEdit(r)"
                >
                  Edit
                </BaseButton>
                <BaseButton
                  v-if="['pending', 'confirmed'].includes(r.status)"
                  variant="ghost"
                  size="sm"
                  class="min-h-10 !text-danger hover:!bg-danger-soft"
                  @click="askCancel(r)"
                >
                  Batalkan
                </BaseButton>
              </div>
            </td>
          </tr>
          <tr v-if="!reservations.length">
            <td colspan="9" class="!p-0">
              <EmptyState
                :title="searchQuery || filterStatus ? 'Tidak ada hasil' : 'Tidak ada reservasi'"
                :description="
                  searchQuery || filterStatus
                    ? 'Coba ubah kata kunci atau filter status.'
                    : 'Reservasi baru akan muncul di sini. Anda juga dapat membuat reservasi manual.'
                "
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav
      v-if="pagination && pagination.last_page > 1"
      class="flex flex-wrap justify-center gap-2"
      aria-label="Navigasi halaman"
    >
      <button
        v-for="p in visiblePages"
        :key="p"
        type="button"
        @click="page = p; fetchData()"
        :class="[
          'btn btn-ghost btn-sm min-h-10 min-w-10',
          p === page ? '!bg-primary !text-white hover:!bg-primary-hover' : '',
        ]"
        :aria-current="p === page ? 'page' : undefined"
      >
        {{ p }}
      </button>
    </nav>

    <!-- Modal Create -->
    <BaseModal v-model="showForm" title="Reservasi Manual" size="md">
      <AlertBox v-if="formError" variant="error" class="mb-4">{{ formError }}</AlertBox>
      <form @submit.prevent="createReservation" class="space-y-4">
        <BaseInput v-model="form.guest_name" label="Nama Tamu" required />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="form.guest_email" label="Email" type="email" />
          <BaseInput v-model="form.guest_phone" label="Telepon" />
        </div>
        <BaseSelect v-model="form.room_id" label="Kamar" required>
          <option value="" disabled>Pilih kamar</option>
          <option v-for="r in roomOptions" :key="r.id" :value="r.id">
            {{ r.room_number }} — {{ r.room_type?.name }}
          </option>
        </BaseSelect>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="form.check_in_date" label="Check-in" type="date" :min="today" required />
          <BaseInput
            v-model="form.check_out_date"
            label="Check-out"
            type="date"
            :min="form.check_in_date || today"
            required
          />
        </div>
        <BaseInput v-model="form.number_of_guests" label="Jumlah Tamu" type="number" min="1" required />
        <BaseTextarea v-model="form.special_requests" label="Permintaan Khusus" :rows="2" />
        <p v-if="nights > 0 && selectedRoom" class="text-body text-ink-secondary">
          Estimasi total:
          <strong class="text-ink">{{ formatCurrency(nights * (selectedRoom.room_type?.base_price || 0)) }}</strong>
          ({{ nights }} malam)
        </p>
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton variant="ghost" type="button" @click="showForm = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">Simpan</BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Modal Edit -->
    <BaseModal
      v-model="showEdit"
      title="Edit Reservasi"
      :description="editing?.reservation_code"
      size="md"
    >
      <AlertBox v-if="formError" variant="error" class="mb-4">{{ formError }}</AlertBox>
      <form @submit.prevent="updateReservation" class="space-y-4">
        <BaseSelect v-model="editForm.room_id" label="Kamar" required>
          <option v-for="r in roomOptions" :key="r.id" :value="r.id">
            {{ r.room_number }} — {{ r.room_type?.name }}
          </option>
        </BaseSelect>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="editForm.check_in_date" label="Check-in" type="date" :min="today" required />
          <BaseInput
            v-model="editForm.check_out_date"
            label="Check-out"
            type="date"
            :min="editForm.check_in_date || today"
            required
          />
        </div>
        <BaseInput
          v-model="editForm.number_of_guests"
          label="Jumlah Tamu"
          type="number"
          min="1"
          required
        />
        <BaseTextarea v-model="editForm.special_requests" label="Permintaan Khusus" :rows="2" />
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton variant="ghost" type="button" @click="showEdit = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">Simpan Perubahan</BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Modal Detail + Folio -->
    <BaseModal v-model="detailOpen" title="Detail Reservasi" size="lg" @close="detail = null">
      <template v-if="detail">
        <div class="flex items-center justify-between gap-3 mb-4">
          <p class="font-mono text-body text-primary">{{ detail.reservation_code }}</p>
          <StatusBadge :status="detail.status" :label="statusLabel(detail.status)" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-body mb-5">
          <div>
            <span class="text-ink-secondary">Tamu:</span>
            <strong>{{ detail.guest?.name }}</strong>
          </div>
          <div>
            <span class="text-ink-secondary">Email:</span> {{ detail.guest?.email || '-' }}
          </div>
          <div>
            <span class="text-ink-secondary">Telepon:</span> {{ detail.guest?.phone || '-' }}
          </div>
          <div>
            <span class="text-ink-secondary">Jumlah Tamu:</span> {{ detail.number_of_guests }} orang
          </div>
          <div>
            <span class="text-ink-secondary">Kamar:</span>
            {{ detail.room?.room_number }} — {{ detail.room?.room_type?.name }}
          </div>
          <div class="sm:col-span-2">
            <span class="text-ink-secondary">Menginap:</span> {{ formatDate(detail.check_in_date) }} →
            {{ formatDate(detail.check_out_date) }}
          </div>
          <div class="sm:col-span-2" v-if="detail.special_requests">
            <span class="text-ink-secondary">Permintaan:</span> {{ detail.special_requests }}
          </div>
        </div>

        <!-- Folio -->
        <div class="border-t border-border-subtle pt-4">
          <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-sm">Folio / Biaya Tambahan</h4>
            <span class="text-caption text-ink-secondary">
              Kamar: {{ formatCurrency(detail.total_price) }}
            </span>
          </div>

          <div class="overflow-x-auto mb-3">
            <table v-if="detail.charges?.length" class="w-full min-w-[320px] text-body">
              <thead>
                <tr class="text-left text-xs text-ink-secondary border-b border-border-subtle">
                  <th class="py-2">Item</th>
                  <th>Qty</th>
                  <th class="text-right">Jumlah</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in detail.charges" :key="c.id" class="border-b border-border-subtle">
                  <td class="py-2">
                    {{ c.description }}
                    <span class="text-xs uppercase text-ink-secondary">({{ c.category }})</span>
                  </td>
                  <td>{{ c.quantity }}</td>
                  <td class="text-right font-semibold">{{ formatCurrency(c.total_price) }}</td>
                  <td class="text-right">
                    <button
                      v-if="!['cancelled', 'checked_out'].includes(detail.status)"
                      type="button"
                      class="btn btn-ghost btn-sm !text-danger hover:!bg-danger-soft"
                      :loading="removeChargeBusy"
                      :disabled="removeChargeBusy"
                      @click="removeCharge(c.id)"
                    >
                      Hapus
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="!detail.charges?.length" class="text-caption text-ink-secondary mb-3">Belum ada biaya tambahan.</p>

          <div
            v-if="!['cancelled', 'checked_out'].includes(detail.status)"
            class="bg-surface-muted rounded-xl p-3 mb-3"
          >
            <div class="grid grid-cols-2 sm:grid-cols-6 md:grid-cols-12 gap-2 items-end">
              <div class="col-span-2 sm:col-span-6 md:col-span-4">
                <label class="label !text-xs" for="charge-item">Item</label>
                <input
                  id="charge-item"
                  v-model="chargeForm.description"
                  class="input-field !min-h-10 text-sm"
                  placeholder="Minibar, Laundry..."
                />
              </div>
              <div class="col-span-1 sm:col-span-3 md:col-span-3">
                <label class="label !text-xs" for="charge-cat">Kategori</label>
                <select id="charge-cat" v-model="chargeForm.category" class="select-field !min-h-10 text-sm">
                  <option value="minibar">Minibar</option>
                  <option value="laundry">Laundry</option>
                  <option value="restaurant">Restoran</option>
                  <option value="damage">Kerusakan</option>
                  <option value="other">Lainnya</option>
                </select>
              </div>
              <div class="col-span-1 md:col-span-1">
                <label class="label !text-xs" for="charge-qty">Qty</label>
                <input
                  id="charge-qty"
                  v-model.number="chargeForm.quantity"
                  type="number"
                  min="1"
                  class="input-field !min-h-10 text-sm"
                />
              </div>
              <div class="col-span-1 sm:col-span-3 md:col-span-2">
                <label class="label !text-xs" for="charge-price">Harga</label>
                <input
                  id="charge-price"
                  v-model.number="chargeForm.unit_price"
                  type="number"
                  min="0"
                  class="input-field !min-h-10 text-sm"
                  placeholder="0"
                />
              </div>
              <div class="col-span-2 sm:col-span-3 md:col-span-2">
                 <BaseButton size="sm" class="w-full" :loading="chargeBusy" :disabled="chargeBusy" @click="addCharge">Tambah</BaseButton>
              </div>
            </div>
          </div>

          <div class="flex justify-between items-center bg-primary-soft/60 rounded-xl px-4 py-3">
            <span class="text-sm font-semibold">Total Folio</span>
            <span class="text-lg font-bold text-primary">{{ formatCurrency(folioTotal) }}</span>
          </div>
        </div>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="detailOpen = false">Tutup</BaseButton>
      </template>
    </BaseModal>

    <ConfirmModal
      :visible="showCancelConfirm"
      title="Batalkan Reservasi?"
      :message="`Reservasi ${cancelTarget?.reservation_code} akan dibatalkan dan kamar akan dilepas.`"
      confirm-text="Ya, Batalkan"
       danger
       :loading="cancelBusy"
       @confirm="confirmCancel"
      @cancel="showCancelConfirm = false"
    />

    <ConfirmModal
      :visible="showChargeConfirm"
      title="Hapus Biaya Tambahan?"
      :message="`Item ${chargeTarget?.description || ''} akan dihapus dari folio.`"
      confirm-text="Ya, Hapus"
       danger
       :loading="removeChargeBusy"
       @confirm="confirmRemoveCharge"
      @cancel="showChargeConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import { pageWindow } from '../../utils/pagination'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseSelect from '../../components/ui/BaseSelect.vue'
import BaseTextarea from '../../components/ui/BaseTextarea.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import { localToday } from '../../utils/dates'

const auth = useAuthStore()
const toast = useToast()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const reservations = ref([])
const roomOptions = ref([])
const pagination = ref(null)
const page = ref(1)
const filterStatus = ref('')
const searchQuery = ref('')
const visiblePages = computed(() => pageWindow(page.value, pagination.value?.last_page))
let searchTimer = null
let listSeq = 0

const showForm = ref(false)
const showEdit = ref(false)
const showCancelConfirm = ref(false)
const showChargeConfirm = ref(false)
const detail = ref(null)
const detailOpen = ref(false)
const editing = ref(null)
const cancelTarget = ref(null)
const chargeTarget = ref(null)
const formError = ref(null)

watch(detailOpen, (open) => {
  if (!open) detail.value = null
})

const today = localToday()

const emptyForm = {
  guest_name: '',
  guest_email: '',
  guest_phone: '',
  room_id: '',
  check_in_date: '',
  check_out_date: '',
  number_of_guests: 1,
  special_requests: '',
}
const form = ref({ ...emptyForm })
const editForm = ref({
  room_id: '',
  check_in_date: '',
  check_out_date: '',
  number_of_guests: 1,
  special_requests: '',
})
const chargeForm = ref({ description: '', category: 'minibar', quantity: 1, unit_price: 0 })
const saving = ref(false)
const chargeBusy = ref(false)
const cancelBusy = ref(false)
const removeChargeBusy = ref(false)
let createIdempotencyKey = ''
let createKeyFingerprint = ''

const nights = computed(() => {
  if (!form.value.check_in_date || !form.value.check_out_date) return 0
  return Math.max(
    0,
    (new Date(form.value.check_out_date) - new Date(form.value.check_in_date)) / 86400000
  )
})
const selectedRoom = computed(() => roomOptions.value.find((r) => Number(r.id) === Number(form.value.room_id)))
const folioTotal = computed(() => {
  if (!detail.value) return 0
  return (
    (parseFloat(detail.value.total_price) || 0) +
    (detail.value.charges || []).reduce((s, c) => s + parseFloat(c.total_price), 0)
  )
})

onMounted(async () => {
  await fetchData()
  try {
    const roomRes = await api.get(`${prefix}/rooms`)
    roomOptions.value = (roomRes.data || [])
      .filter((r) => r.status !== 'maintenance')
      .sort((a, b) => a.room_number.localeCompare(b.room_number))
  } catch {
    roomOptions.value = []
    toast.error('Gagal memuat daftar kamar.')
  }
})

function onSearch() {
  page.value = 1
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchData, 350)
}

function onStatusFilter() {
  page.value = 1
  fetchData()
}

onUnmounted(() => {
  clearTimeout(searchTimer)
  listSeq += 1
})

async function fetchData() {
  const seq = ++listSeq
  const params = { page: page.value }
  if (filterStatus.value) params.status = filterStatus.value
  if (searchQuery.value.trim()) params.q = searchQuery.value.trim()
  try {
    const res = await api.get(`${prefix}/reservations`, { params })
    if (seq !== listSeq) return
    reservations.value = res.data.data || res.data
    pagination.value = res.data.data ? res.data : null
  } catch {
    if (seq === listSeq) toast.error('Gagal memuat reservasi.')
  }
}

function rowPaymentStatus(r) {
  const paid = (r.payments || [])
    .filter((p) => p.status === 'paid')
    .reduce((s, p) => s + parseFloat(p.amount || 0), 0)
  const charges = (r.charges || []).reduce((s, c) => s + parseFloat(c.total_price || 0), 0)
  const total = parseFloat(r.total_price || 0) + charges
  return paid >= total && total > 0 ? 'paid' : 'unpaid'
}

function openCreate() {
  form.value = { ...emptyForm }
  formError.value = null
  createIdempotencyKey = ''
  createKeyFingerprint = ''
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
  if (!r?.id) return
  try {
    const res = await api.get(`${prefix}/reservations/${r.id}`)
    detail.value = res.data
    chargeForm.value = { description: '', category: 'minibar', quantity: 1, unit_price: 0 }
    detailOpen.value = true
  } catch (error) {
    toast.error(error.response?.data?.message || 'Detail reservasi gagal dimuat.')
  }
}

function askCancel(r) {
  cancelTarget.value = r
  showCancelConfirm.value = true
}

async function createReservation() {
  if (saving.value) return
  formError.value = null
  saving.value = true
  try {
    const fingerprint = JSON.stringify(form.value)
    const storageKey = `reservation-intent:${form.value.room_id}:${form.value.check_in_date}:${form.value.check_out_date}:${form.value.number_of_guests}`
    if (createKeyFingerprint !== fingerprint) {
      createIdempotencyKey = readCreateKey(storageKey) || createKey()
      createKeyFingerprint = fingerprint
      writeCreateKey(storageKey, createIdempotencyKey)
    }
    await api.post(`${prefix}/reservations`, form.value, { headers: { 'Idempotency-Key': createIdempotencyKey } })
    clearCreateKey(storageKey)
    showForm.value = false
    fetchData()
    toast.success('Reservasi berhasil dibuat.')
  } catch (err) {
    if (err.response?.status === 409) {
      clearCreateKey(`reservation-intent:${form.value.room_id}:${form.value.check_in_date}:${form.value.check_out_date}:${form.value.number_of_guests}`)
      createIdempotencyKey = ''
      createKeyFingerprint = ''
    }
    formError.value = err.response?.data?.message || 'Gagal menyimpan reservasi.'
  } finally {
    saving.value = false
  }
}

async function updateReservation() {
  if (saving.value) return
  formError.value = null
  saving.value = true
  try {
    await api.put(`${prefix}/reservations/${editing.value.id}`, editForm.value)
    showEdit.value = false
    fetchData()
    if (detail.value?.id === editing.value.id) openDetail(editing.value)
    toast.success('Reservasi berhasil diperbarui.')
  } catch (err) {
    formError.value = err.response?.data?.message || 'Gagal mengubah reservasi.'
  } finally {
    saving.value = false
  }
}

async function confirmCancel() {
  if (cancelBusy.value || !cancelTarget.value) return
  cancelBusy.value = true
  showCancelConfirm.value = false
  try {
    await api.delete(`${prefix}/reservations/${cancelTarget.value.id}`)
    await fetchData()
    if (detail.value?.id === cancelTarget.value.id) {
      detail.value = null
      detailOpen.value = false
    }
    toast.success('Reservasi dibatalkan.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal membatalkan reservasi.')
  } finally {
    cancelBusy.value = false
    cancelTarget.value = null
  }
}

async function addCharge() {
  if (chargeBusy.value || !detail.value || !chargeForm.value.description || !chargeForm.value.unit_price) return
  chargeBusy.value = true
  try {
    await api.post(`${prefix}/reservations/${detail.value.id}/charges`, chargeForm.value)
    await openDetail({ id: detail.value.id })
    await fetchData()
    chargeForm.value = { description: '', category: 'minibar', quantity: 1, unit_price: 0 }
    toast.success('Biaya tambahan ditambahkan.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menambah biaya.')
  } finally {
    chargeBusy.value = false
  }
}

function removeCharge(chargeId) {
  const c = (detail.value?.charges || []).find((x) => x.id === chargeId)
  chargeTarget.value = c || { id: chargeId, description: '' }
  showChargeConfirm.value = true
}

async function confirmRemoveCharge() {
  if (removeChargeBusy.value || !chargeTarget.value || !detail.value) return
  removeChargeBusy.value = true
  showChargeConfirm.value = false
  try {
    await api.delete(`${prefix}/charges/${chargeTarget.value.id}`)
    await openDetail({ id: detail.value.id })
    await fetchData()
    toast.success('Biaya tambahan dihapus.')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menghapus biaya.')
  } finally {
    removeChargeBusy.value = false
    chargeTarget.value = null
  }
}

function formatDate(d) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '-'
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(v || 0)
}

function createKey() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  return `reservation-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function readCreateKey(key) {
  try {
    return sessionStorage.getItem(key)
  } catch {
    return null
  }
}

function writeCreateKey(key, value) {
  try {
    sessionStorage.setItem(key, value)
  } catch {}
}

function clearCreateKey(key) {
  try {
    sessionStorage.removeItem(key)
  } catch {}
}

function statusLabel(s) {
  const l = {
    pending: 'Tertunda',
    confirmed: 'Dikonfirmasi',
    checked_in: 'Check-In',
    checked_out: 'Check-Out',
    cancelled: 'Dibatalkan',
  }
  return l[s] || s
}
</script>
