<template>
  <PublicLayout>
    <section class="public-container public-container-narrow public-inner-page">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Lacak Reservasi' }]" />
      <PublicPageHeader
        eyebrow="Reservasi"
        title="Lacak Reservasi"
        description="Masukkan kode reservasi Anda untuk melihat status pemesanan."
        align="center"
      />

      <div class="public-panel p-5 sm:p-8">
        <form @submit.prevent="track" class="flex flex-col sm:flex-row gap-3">
          <label for="track-code" class="sr-only">Kode reservasi</label>
          <input
            id="track-code"
            v-model="code"
            placeholder="Contoh: RSV260924ABC"
            autocomplete="off"
            class="input-field rounded-2xl flex-1 min-w-0 font-mono uppercase tracking-wide"
            required
          />
          <button type="submit" :disabled="loading || !code.trim()" class="btn btn-primary rounded-2xl px-7 py-3.5">
            <LoadingSpinner v-if="loading" size="sm" />
            <span>{{ loading ? 'Mencari...' : 'Lacak' }}</span>
          </button>
        </form>

        <AlertBox v-if="error" variant="error" class="mt-4" dismissible @dismiss="error = null">
          <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <span>{{ error }}</span>
            <button v-if="code.trim()" type="button" class="btn btn-outline rounded-full px-5 w-max shrink-0" @click="track">
              Coba Lagi
            </button>
          </div>
        </AlertBox>
      </div>

      <!-- Initial hint -->
      <EmptyState
        v-if="!reservation && !loading && !error"
        title="Belum ada pencarian"
        description="Masukkan kode reservasi yang Anda terima saat pemesanan untuk melihat status terkini."
        :icon="Search"
        class="public-panel mt-8"
      />

      <!-- Loading result -->
      <div v-if="loading && !reservation" class="public-panel mt-8 p-5 sm:p-8 space-y-4" aria-hidden="true">
        <div class="skeleton h-6 w-1/2"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="skeleton h-16 rounded-2xl"></div>
          <div class="skeleton h-16 rounded-2xl"></div>
          <div class="skeleton h-16 rounded-2xl"></div>
          <div class="skeleton h-16 rounded-2xl"></div>
        </div>
      </div>

      <!-- Result -->
      <article v-if="reservation" class="public-panel mt-8 p-5 sm:p-8 animate-slide-up" aria-live="polite">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
          <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Kode Reservasi</p>
            <p class="text-xl sm:text-2xl font-mono font-bold text-primary tracking-wide break-all">{{ reservation.reservation_code }}</p>
          </div>
          <span class="badge badge-lg self-start sm:self-auto" :class="`badge-${reservation.status}`">
            <component :is="statusIcon" class="w-3.5 h-3.5" aria-hidden="true" />
            {{ statusLabel(reservation.status) }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
          <div class="public-subpanel p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Tamu</p>
            <p class="font-semibold text-slate-900">{{ reservation.guest?.name || '-' }}</p>
          </div>
          <div class="public-subpanel p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Kamar</p>
            <p class="font-semibold text-slate-900">
              <template v-if="reservation.room">{{ reservation.room.room_number }} — {{ reservation.room.room_type?.name }}</template>
              <template v-else>Belum ditentukan</template>
            </p>
          </div>
          <div class="public-subpanel p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Check-in</p>
            <p class="font-semibold text-slate-900">{{ formatDate(reservation.check_in_date) }}</p>
          </div>
          <div class="public-subpanel p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Check-out</p>
            <p class="font-semibold text-slate-900">{{ formatDate(reservation.check_out_date) }}</p>
          </div>
          <div class="public-subpanel p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Jumlah Tamu</p>
            <p class="font-semibold text-slate-900">{{ reservation.number_of_guests }} orang</p>
          </div>
          <div class="bg-primary-soft rounded-2xl p-4">
            <p class="text-primary text-xs font-semibold uppercase tracking-wider mb-1">Total</p>
            <p class="font-bold text-primary text-lg">{{ formatCurrency(reservation.total_price) }}</p>
          </div>
        </div>

        <div v-if="reservation.special_requests" class="mt-4 public-subpanel p-4 text-sm">
          <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Permintaan Khusus</p>
          <p class="text-slate-700">{{ reservation.special_requests }}</p>
        </div>

        <div v-if="['pending', 'confirmed'].includes(reservation.status)" class="mt-6 pt-5 border-t border-slate-100">
          <button
            type="button"
            class="btn rounded-full px-6 border-2 border-danger/30 text-danger bg-transparent hover:bg-danger-soft"
            :disabled="cancelling"
            @click="showCancelConfirm = true"
          >
            <LoadingSpinner v-if="cancelling" size="sm" />
            <span>{{ cancelling ? 'Membatalkan...' : 'Batalkan Reservasi' }}</span>
          </button>
        </div>
      </article>
    </section>

    <ConfirmModal
      :visible="showCancelConfirm"
      title="Batalkan reservasi?"
      message="Reservasi yang dibatalkan tidak dapat dikembalikan. Lanjutkan?"
      confirm-text="Ya, Batalkan"
       danger
       :loading="cancelling"
       @confirm="cancelBooking"
      @cancel="showCancelConfirm = false"
    />

  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { CheckCircle2, Clock, LogIn, LogOut, Search, XCircle } from 'lucide-vue-next'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'
import PublicPageHeader from '../../components/ui/PublicPageHeader.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useLandingData } from '../../composables/useLandingData'

const route = useRoute()
const toast = useToast()
const { setting, load } = useLandingData()

const code = ref(route.query.code || '')
const loading = ref(false)
const cancelling = ref(false)
const error = ref(null)
const reservation = ref(null)
const showCancelConfirm = ref(false)

const statusIconMap = {
  pending: Clock,
  confirmed: CheckCircle2,
  checked_in: LogIn,
  checked_out: LogOut,
  cancelled: XCircle,
}

const statusIcon = computed(() => statusIconMap[reservation.value?.status] || Clock)

async function track() {
  const trimmed = code.value.trim().toUpperCase()
  if (!trimmed) return
  loading.value = true
  error.value = null
  reservation.value = null
  try {
    const res = await api.get(`/guest/track/${encodeURIComponent(trimmed)}`)
    reservation.value = res.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan. Silakan coba lagi.'
  } finally {
    loading.value = false
  }
}

async function cancelBooking() {
  if (cancelling.value || !reservation.value) return
  showCancelConfirm.value = false
  cancelling.value = true
  error.value = null
  try {
    await api.put(`/guest/track/${reservation.value.reservation_code}/cancel`)
    const res = await api.get(`/guest/track/${reservation.value.reservation_code}`)
    reservation.value = res.data
    toast.success('Reservasi berhasil dibatalkan.')
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal membatalkan reservasi.'
    toast.error(error.value)
  } finally {
    cancelling.value = false
  }
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}

function statusLabel(s) {
  const labels = {
    pending: 'Menunggu Konfirmasi',
    confirmed: 'Dikonfirmasi',
    checked_in: 'Sudah Check-In',
    checked_out: 'Selesai',
    cancelled: 'Dibatalkan',
  }
  return labels[s] || s
}

onMounted(() => {
  load()
    .catch(() => {})
    .finally(() => {
      document.title = `Lacak Reservasi — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
    })
  if (code.value) track()
})
</script>
