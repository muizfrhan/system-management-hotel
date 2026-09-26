<template>
  <div class="space-y-6">
    <PageHeader
      title="Check In / Check Out"
      description="Proses kedatangan dan keberangkatan tamu."
    >
      <template #actions>
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Antrean">
          <button
            type="button"
             id="checkin-tab"
             role="tab"
             aria-controls="checkin-panel"
             :aria-selected="tab === 'in'"
            :class="['btn btn-sm', tab === 'in' ? 'btn-primary' : 'btn-outline']"
            @click="tab = 'in'"
          >
            Check-in ({{ data.check_ins?.length || 0 }})
          </button>
          <button
            type="button"
             id="checkout-tab"
             role="tab"
             aria-controls="checkout-panel"
             :aria-selected="tab === 'out'"
            :class="['btn btn-sm', tab === 'out' ? 'btn-primary' : 'btn-outline']"
            @click="tab = 'out'"
          >
            Check-out ({{ data.check_outs?.length || 0 }})
          </button>
        </div>
        <BaseButton variant="outline" size="sm" :loading="loading" @click="fetchData">
          Segarkan
        </BaseButton>
      </template>
    </PageHeader>

    <div v-if="loading && !loaded" class="card space-y-3">
      <div v-for="i in 4" :key="i" class="skeleton skeleton-table-row"></div>
    </div>

    <template v-else>
      <!-- ARRIVALS -->
      <Transition name="fade">
       <section v-show="tab === 'in'" id="checkin-panel" role="tabpanel" aria-labelledby="checkin-tab">
        <h2 id="arrivals-tab" class="sr-only">Antrean check-in</h2>
        <div v-if="!currentList.length" class="card">
          <EmptyState
            title="Tidak ada check-in"
            description="Tidak ada reservasi menunggu check-in hari ini."
          />
        </div>
        <div v-else class="space-y-3">
          <article
            v-for="r in currentList"
            :key="r.id"
            class="card"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="font-bold text-ink">{{ r.guest?.name }}</p>
                  <StatusBadge :status="r.status" :label="statusLabel(r.status)" />
                  <BaseBadge
                    v-if="isOverdue(r.check_in_date)"
                    tone="danger"
                    label="Terlambat"
                  />
                </div>
                <p class="text-sm text-ink-secondary">
                  <span class="font-mono text-primary">{{ r.reservation_code }}</span>
                  · Kamar {{ r.room?.room_number }} — {{ r.room?.room_type?.name }}
                </p>
                <p class="text-xs text-ink-muted">
                  {{ formatDate(r.check_in_date) }} → {{ formatDate(r.check_out_date) }}
                  · {{ r.number_of_guests }} tamu
                </p>
              </div>
              <div class="flex flex-wrap gap-2 shrink-0">
                <BaseButton variant="outline" size="sm" class="min-h-10" @click="openDetail(r)">
                  Detail
                </BaseButton>
                <BaseButton
                  variant="success"
                  size="sm"
                  class="min-h-10"
                  @click="askAction(r, 'check-in')"
                >
                  Check-In
                </BaseButton>
              </div>
            </div>
          </article>
        </div>
      </section>
      </Transition>

      <!-- DEPARTURES -->
      <Transition name="fade">
       <section v-show="tab === 'out'" id="checkout-panel" role="tabpanel" aria-labelledby="checkout-tab">
        <h2 id="departures-tab" class="sr-only">Antrean check-out</h2>
        <div v-if="!currentList.length" class="card">
          <EmptyState
            title="Tidak ada check-out"
            description="Tidak ada tamu tercatat menginap."
          />
        </div>
        <div v-else class="space-y-3">
          <article
            v-for="r in currentList"
            :key="r.id"
            class="card"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="font-bold text-ink">{{ r.guest?.name }}</p>
                  <StatusBadge :status="r.status" :label="statusLabel(r.status)" />
                  <BaseBadge
                    v-if="isOverdue(r.check_out_date)"
                    tone="danger"
                    label="Terlambat"
                  />
                </div>
                <p class="text-sm text-ink-secondary">
                  <span class="font-mono text-primary">{{ r.reservation_code }}</span>
                  · Kamar {{ r.room?.room_number }} — {{ r.room?.room_type?.name }}
                </p>
                <p class="text-xs text-ink-muted">
                  Jatuh tempo: {{ formatDate(r.check_out_date) }}
                </p>
              </div>
              <div class="flex flex-wrap gap-2 shrink-0">
                <BaseButton variant="outline" size="sm" class="min-h-10" @click="openDetail(r)">
                  Detail
                </BaseButton>
                <BaseButton
                  variant="primary"
                  size="sm"
                  class="min-h-10"
                  @click="askAction(r, 'check-out')"
                >
                  Check-Out
                </BaseButton>
              </div>
            </div>
          </article>
        </div>
      </section>
      </Transition>
    </template>

    <!-- DETAIL MODAL -->
    <BaseModal
      v-model="detailOpen"
      title="Detail Reservasi"
      :description="detail?.reservation_code"
      size="lg"
      @close="detail = null"
    >
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
            <span class="text-ink-secondary">Menginap:</span>
            {{ formatDate(detail.check_in_date) }} → {{ formatDate(detail.check_out_date) }}
          </div>
          <div class="sm:col-span-2" v-if="detail.special_requests">
            <span class="text-ink-secondary">Permintaan:</span> {{ detail.special_requests }}
          </div>
        </div>

        <div class="border-t border-border-subtle pt-4">
          <h4 class="font-bold text-sm mb-3">Informasi Pembayaran</h4>

          <div class="overflow-x-auto mb-3">
            <table v-if="detail.charges?.length" class="w-full min-w-[280px] text-body">
              <thead>
                <tr class="text-left text-xs text-ink-secondary border-b border-border-subtle">
                  <th class="py-2">Item</th>
                  <th>Qty</th>
                  <th class="text-right">Jumlah</th>
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
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="!detail.charges?.length" class="text-caption text-ink-secondary mb-3">Belum ada biaya tambahan.</p>

          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-ink-secondary">Total Kamar</span>
              <span>{{ formatCurrency(detail.total_price) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-ink-secondary">Biaya Tambahan</span>
              <span>{{ formatCurrency(chargesTotal) }}</span>
            </div>
            <div class="flex justify-between font-bold text-base bg-surface-muted rounded-xl px-3 py-2">
              <span>Total Tagihan</span>
              <span class="text-primary">{{ formatCurrency(grandTotal) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-ink-secondary">Sudah Dibayar</span>
              <span>{{ formatCurrency(paidTotal) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="font-semibold">Sisa Tagihan</span>
              <span :class="remaining > 0 ? 'text-danger font-bold' : 'text-success font-bold'">
                {{ formatCurrency(remaining) }}
              </span>
            </div>
            <div class="pt-1">
              <StatusBadge :status="remaining <= 0 ? 'paid' : 'unpaid'" />
            </div>
          </div>

          <p
            v-if="detail.status === 'checked_in' && remaining > 0"
            class="text-xs text-warning mt-3"
          >
            Pembayaran belum lunas. Catat pembayaran sebelum check-out.
          </p>

          <div
            v-if="detail.status === 'checked_in' && remaining > 0"
            class="bg-surface-muted rounded-xl p-3 mt-3"
          >
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end">
              <div class="col-span-1">
                <label class="label !text-xs" for="pay-amount">Jumlah</label>
                <input
                  id="pay-amount"
                  v-model.number="payForm.amount"
                  type="number"
                  min="1"
                  class="input-field !min-h-10 text-sm"
                  placeholder="0"
                />
              </div>
              <div class="col-span-1">
                <label class="label !text-xs" for="pay-method">Metode</label>
                <select id="pay-method" v-model="payForm.payment_method" class="select-field !min-h-10 text-sm">
                  <option value="cash">Tunai</option>
                  <option value="card">Kartu</option>
                  <option value="transfer">Transfer</option>
                  <option value="qris">QRIS</option>
                </select>
              </div>
              <div class="col-span-2">
                 <BaseButton size="sm" class="w-full" :loading="payBusy" :disabled="payBusy" @click="recordPayment">Catat Bayar</BaseButton>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-ghost btn-sm text-primary font-semibold mt-2"
              :disabled="payBusy"
              @click="payForm.amount = remaining"
            >
              Isi sisa tagihan ({{ formatCurrency(remaining) }})
            </button>
          </div>
        </div>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="detailOpen = false">Tutup</BaseButton>
        <BaseButton
          v-if="detail && detail.status === 'confirmed'"
          variant="success"
          @click="askAction(detail, 'check-in'); detailOpen = false"
        >
          Check-In Sekarang
        </BaseButton>
        <BaseButton
          v-else-if="detail && detail.status === 'checked_in'"
          @click="askAction(detail, 'check-out'); detailOpen = false"
        >
          Check-Out Sekarang
        </BaseButton>
      </template>
    </BaseModal>

    <!-- CONFIRM DIALOG -->
    <ConfirmModal
      :visible="confirmOpen"
      :title="pendingAction === 'check-in' ? 'Konfirmasi Check-in' : 'Konfirmasi Check-out'"
      :message="confirmMessage"
      :confirm-text="pendingAction === 'check-in' ? 'Ya, Check-in' : 'Ya, Check-out'"
       :danger="false"
       :loading="actionLoading"
       @confirm="runAction"
      @cancel="confirmOpen = false"
    />

    <!-- SUCCESS STATE -->
    <BaseModal
      v-model="successOpen"
      :title="successTitle"
      size="sm"
      :show-close="true"
      @close="successOpen = false"
    >
      <div class="text-center py-2">
        <div class="w-14 h-14 rounded-full bg-success-soft mx-auto flex items-center justify-center mb-4">
          <CheckCircle2 class="w-8 h-8 text-success" aria-hidden="true" />
        </div>
        <p class="font-semibold text-ink mb-1">{{ successMessage }}</p>
        <p v-if="successSub" class="text-sm text-ink-secondary">{{ successSub }}</p>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="successOpen = false">Tutup</BaseButton>
        <BaseButton @click="successOpen = false; fetchData()">Selesai</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { CheckCircle2 } from 'lucide-vue-next'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import { localToday } from '../../utils/dates'

const auth = useAuthStore()
const toast = useToast()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const tab = ref('in')
const data = ref({ check_ins: [], check_outs: [] })
const loading = ref(false)
const loaded = ref(false)

const detail = ref(null)
const detailOpen = ref(false)

const confirmOpen = ref(false)
const pendingAction = ref('check-in')
const pendingTarget = ref(null)
const actionLoading = ref(false)

const successOpen = ref(false)
const successTitle = ref('')
const successMessage = ref('')
const successSub = ref('')
const payForm = ref({ amount: 0, payment_method: 'cash' })
const payBusy = ref(false)
let paymentIdempotencyKey = ''
let paymentKeyFingerprint = ''

const currentList = computed(() =>
  tab.value === 'in' ? data.value.check_ins || [] : data.value.check_outs || []
)

const chargesTotal = computed(() =>
  detail.value
    ? (detail.value.charges || []).reduce((s, c) => s + parseFloat(c.total_price || 0), 0)
    : 0
)
const grandTotal = computed(() =>
  detail.value ? parseFloat(detail.value.total_price || 0) + chargesTotal.value : 0
)
const paidTotal = computed(() =>
  detail.value
    ? (detail.value.payments || [])
        .filter((p) => p.status === 'paid')
        .reduce((s, p) => s + parseFloat(p.amount || 0), 0)
    : 0
)
const remaining = computed(() => Math.max(0, grandTotal.value - paidTotal.value))
const targetRemaining = computed(() => {
  const reservation = pendingTarget.value
  if (!reservation) return 0
  const total = Number(reservation.total_price || 0) + (reservation.charges || []).reduce((sum, charge) => sum + Number(charge.total_price || 0), 0)
  const paid = (reservation.payments || []).filter((payment) => payment.status === 'paid').reduce((sum, payment) => sum + Number(payment.amount || 0), 0)
  return Math.max(0, total - paid)
})

const confirmMessage = computed(() => {
  const r = pendingTarget.value
  if (!r) return 'Apakah Anda yakin?'
  const who = r.guest?.name || 'tamu'
  const room = r.room?.room_number || '-'
  if (pendingAction.value === 'check-in') {
    return `Check-in ${who} ke kamar ${room} (${r.reservation_code})? Status kamar akan menjadi Terisi.`
  }
  const sisa = targetRemaining.value
  if (sisa > 0) {
    return `Check-out ${who} dari kamar ${room} belum dapat dilakukan. Lunasi sisa tagihan ${formatCurrency(sisa)} terlebih dahulu.`
  }
  return `Check-out ${who} dari kamar ${room} (${r.reservation_code})? Kamar akan dilepas.`
})

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  try {
    const res = await api.get(`${prefix}/check-in-out`)
    data.value = res.data
    loaded.value = true
  } catch {
    toast.error('Gagal memuat data check-in/out.')
  } finally {
    loading.value = false
  }
}

async function openDetail(r) {
  try {
    const res = await api.get(`${prefix}/reservations/${r.id}`)
    detail.value = res.data
    payForm.value = { amount: 0, payment_method: 'cash' }
    paymentIdempotencyKey = ''
    paymentKeyFingerprint = ''
    detailOpen.value = true
  } catch {
    toast.error('Gagal memuat detail reservasi.')
  }
}

async function recordPayment() {
  if (!detail.value || payBusy.value) return
  const amount = Number(payForm.value.amount)
  if (!amount || amount <= 0) {
    toast.warning('Masukkan jumlah pembayaran yang valid.')
    return
  }

  const fingerprint = `${detail.value.id}:${amount}:${payForm.value.payment_method}`
  const storageKey = `payment-intent:${detail.value.id}:${amount}:${payForm.value.payment_method}`
  if (paymentKeyFingerprint !== fingerprint) {
    paymentIdempotencyKey = readPaymentKey(storageKey) || createIdempotencyKey()
    paymentKeyFingerprint = fingerprint
    writePaymentKey(storageKey, paymentIdempotencyKey)
  }

  payBusy.value = true
  try {
    await api.post(`${prefix}/payments`, {
      reservation_id: detail.value.id,
      amount,
      payment_method: payForm.value.payment_method,
    }, { headers: { 'Idempotency-Key': paymentIdempotencyKey } })
    toast.success('Pembayaran tercatat.')
    payForm.value = { amount: 0, payment_method: 'cash' }
    clearPaymentKey(storageKey)
    paymentIdempotencyKey = ''
    paymentKeyFingerprint = ''

    try {
      const res = await api.get(`${prefix}/reservations/${detail.value.id}`)
      detail.value = res.data
      await fetchData()
    } catch {
      toast.warning('Pembayaran tersimpan, tetapi detail terbaru belum dapat dimuat. Muat ulang halaman untuk memeriksa status.')
    }
  } catch (err) {
    if (err.response?.status === 409) {
      clearPaymentKey(storageKey)
      paymentIdempotencyKey = ''
      paymentKeyFingerprint = ''
    }
    toast.error(err.response?.data?.message || 'Gagal mencatat pembayaran.')
  } finally {
    payBusy.value = false
  }
}

function askAction(r, action) {
  pendingTarget.value = r
  pendingAction.value = action
  confirmOpen.value = true
}

async function runAction() {
  if (!pendingTarget.value || actionLoading.value) return
  actionLoading.value = true
  const r = pendingTarget.value
  const action = pendingAction.value
  try {
    const res = await api.put(`${prefix}/check-in-out/${r.id}/${action}`)
    confirmOpen.value = false
    const name = r.guest?.name || 'Tamu'
    const room = r.room?.room_number || '-'
    if (action === 'check-in') {
      successTitle.value = 'Check-in Berhasil'
      successMessage.value = `${name} tercatat masuk.`
      successSub.value = `Kamar ${room} kini berstatus Terisi · ${r.reservation_code}`
      toast.success(res.data?.message || 'Check-in berhasil.')
    } else {
      successTitle.value = 'Check-out Berhasil'
      successMessage.value = `${name} tercatat keluar.`
      successSub.value = `Kamar ${room} dilepas · ${r.reservation_code}`
      toast.success(res.data?.message || 'Check-out berhasil.')
    }
    successOpen.value = true
    await fetchData()
  } catch (err) {
    confirmOpen.value = false
    toast.error(err.response?.data?.message || 'Gagal memproses.')
  } finally {
    actionLoading.value = false
    pendingTarget.value = null
  }
}

function createIdempotencyKey() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  return `payment-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function readPaymentKey(key) {
  try {
    return sessionStorage.getItem(key)
  } catch {
    return null
  }
}

function writePaymentKey(key, value) {
  try {
    sessionStorage.setItem(key, value)
  } catch {}
}

function clearPaymentKey(key) {
  try {
    sessionStorage.removeItem(key)
  } catch {}
}

function isOverdue(dateStr) {
  if (!dateStr) return false
  return dateStr.slice(0, 10) < localToday()
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
