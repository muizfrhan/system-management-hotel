<template>
  <PublicLayout>
    <div class="public-container public-inner-page">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Booking' }, { label: 'Konfirmasi' }]" />

    <!-- Loading -->
    <section v-if="loading" class="public-container-narrow" aria-busy="true">
      <div class="public-panel p-6 sm:p-10 space-y-4" aria-hidden="true">
        <div class="skeleton h-16 w-16 rounded-full mx-auto"></div>
        <div class="skeleton h-7 w-2/3 mx-auto"></div>
        <div class="skeleton h-14 w-full"></div>
        <div class="skeleton h-4 w-4/5 mx-auto"></div>
        <div class="skeleton h-4 w-3/5 mx-auto"></div>
        <div class="skeleton h-12 w-full"></div>
      </div>
      <p class="sr-only" role="status">Memuat detail reservasi...</p>
    </section>

    <!-- Not found -->
    <section v-else-if="notFound" class="public-container-narrow">
      <div class="public-panel p-6 text-center sm:p-10">
        <div class="w-16 h-16 bg-danger-soft rounded-full flex items-center justify-center mx-auto mb-5">
          <XCircle class="w-8 h-8 text-danger" aria-hidden="true" />
        </div>
        <h1 class="mb-2 font-display text-3xl leading-tight text-luxury-ink sm:text-4xl">Reservasi tidak ditemukan</h1>
        <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto leading-relaxed">
          Kode <strong class="font-mono text-slate-700">{{ code }}</strong> tidak terdaftar. Periksa kembali kode reservasi Anda.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <router-link to="/track" class="btn btn-outline rounded-full btn-lg px-6">Lacak Reservasi</router-link>
          <router-link to="/" class="btn btn-primary rounded-full btn-lg px-6">Beranda</router-link>
        </div>
      </div>
    </section>

    <!-- Success / details -->
    <section v-else-if="reservation" class="public-container-narrow">
      <div class="text-center mb-6">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-success-soft rounded-full flex items-center justify-center mx-auto mb-4 animate-success-pop">
          <CheckCircle class="w-8 h-8 sm:w-10 sm:h-10 text-success" aria-hidden="true" />
        </div>
        <h1 class="mb-2 font-display text-4xl leading-none tracking-[-0.04em] text-luxury-ink sm:text-5xl">Pemesanan Berhasil!</h1>
        <p class="text-sm text-slate-500 animate-fade-in" style="animation-delay: 80ms">Simpan kode reservasi di bawah ini untuk melacak statusnya.</p>
      </div>

      <div class="public-panel overflow-hidden">
        <!-- Code -->
        <div class="bg-primary-soft px-4 sm:px-8 py-5 text-center border-b border-primary/10">
          <p class="text-xs font-bold uppercase tracking-widest text-primary mb-2">Kode Reservasi</p>
          <p class="text-2xl sm:text-3xl font-mono font-bold text-slate-900 tracking-wider break-all">
            {{ reservation.reservation_code }}
          </p>
            <button
              type="button"
              class="btn btn-outline rounded-full px-5 mt-4 min-h-10"
              :aria-label="copied ? 'Kode tersalin' : 'Salin kode reservasi'"
              @click="copyCode"
            >
            <Copy class="w-4 h-4" aria-hidden="true" />
             {{ copied ? 'Tersalin!' : 'Salin Kode' }}
           </button>
           <router-link
             :to="{ path: '/track', query: { code } }"
             class="btn btn-primary rounded-full px-5 mt-3 min-h-10"
           >
             Lacak Reservasi
           </router-link>
         </div>

        <!-- Details -->
        <div class="p-5 sm:p-8 space-y-5">
          <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-semibold text-slate-500">Status</span>
            <span class="badge badge-lg" :class="`badge-${reservation.status}`">{{ statusLabel }}</span>
          </div>

          <dl class="space-y-3 text-sm border-t border-slate-100 pt-4">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Tamu</dt>
              <dd class="font-semibold text-slate-900 text-right wrap-break-word min-w-0">{{ reservation.guest?.name || '-' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Kamar</dt>
              <dd class="font-semibold text-slate-900 text-right">
                {{ reservation.room?.room_type?.name || '-' }}
                <span v-if="reservation.room?.room_number" class="text-slate-500 font-normal">· #{{ reservation.room.room_number }}</span>
              </dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Check-in</dt>
              <dd class="font-semibold text-slate-900 text-right">{{ formatDateID(reservation.check_in_date) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Check-out</dt>
              <dd class="font-semibold text-slate-900 text-right">{{ formatDateID(reservation.check_out_date) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Durasi</dt>
              <dd class="font-semibold text-slate-900 text-right">{{ nights }} malam</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Jumlah Tamu</dt>
              <dd class="font-semibold text-slate-900 text-right">{{ reservation.number_of_guests }} orang</dd>
            </div>
            <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
              <dt class="font-semibold text-slate-900">Total</dt>
              <dd class="font-bold text-primary text-right text-base">{{ formatCurrency(reservation.total_price) }}</dd>
            </div>
          </dl>

          <div class="border-t border-slate-100 pt-4">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Pembayaran</p>
             <p v-if="!reservation.payments || reservation.payments.length === 0" class="text-sm text-slate-500">
               Detail pembayaran tidak ditampilkan pada halaman publik. Tim hotel akan mengonfirmasi status pembayaran.
             </p>
            <ul v-else class="space-y-2">
              <li v-for="p in reservation.payments" :key="p.id" class="flex justify-between gap-3 text-sm">
                <span class="text-slate-500">{{ formatDateID(p.payment_date || p.created_at) }}</span>
                <span class="font-semibold text-slate-900">{{ formatCurrency(p.amount) }}</span>
              </li>
            </ul>
          </div>
        </div>

        <div class="px-5 sm:px-8 pb-6 sm:pb-8">
          <div class="flex flex-col sm:flex-row gap-3">
             <router-link to="/" class="btn btn-primary rounded-full btn-lg px-6 flex-1">
               Kembali ke Beranda
             </router-link>
          </div>
        </div>
      </div>

      <p class="text-xs text-slate-400 text-center mt-5 leading-relaxed max-w-md mx-auto">
        Tim kami akan mengonfirmasi pemesanan Anda dalam waktu singkat melalui email atau telepon.
      </p>
    </section>

    <!-- Load error (network, not 404) -->
    <section v-else class="public-container-narrow">
      <div class="public-panel p-6 text-center sm:p-10">
        <div class="w-16 h-16 bg-warning-soft rounded-full flex items-center justify-center mx-auto mb-5">
          <AlertTriangle class="w-8 h-8 text-warning" aria-hidden="true" />
        </div>
        <h1 class="mb-2 font-display text-3xl leading-tight text-luxury-ink sm:text-4xl">Gagal memuat reservasi</h1>
        <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto leading-relaxed">
          Periksa koneksi Anda lalu coba lagi. Kode reservasi Anda tetap aman.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button type="button" class="btn btn-outline rounded-full btn-lg px-6" @click="fetchReservation">Coba Lagi</button>
          <router-link to="/" class="btn btn-primary rounded-full btn-lg px-6">Beranda</router-link>
        </div>
      </div>
    </section>
    </div>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { AlertTriangle, CheckCircle, Copy, XCircle } from 'lucide-vue-next'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useLandingData } from '../../composables/useLandingData'
import { formatDateID, formatCurrency, nightsBetween } from '../../utils/dates'

const route = useRoute()
const toast = useToast()
const { setting, load } = useLandingData()

const code = String(route.params.code || '')
const loading = ref(true)
const notFound = ref(false)
const loadError = ref(false)
const reservation = ref(null)
const copied = ref(false)

const statusLabels = {
  pending: 'Menunggu Konfirmasi',
  confirmed: 'Dikonfirmasi',
  checked_in: 'Check-in',
  checked_out: 'Check-out',
  cancelled: 'Dibatalkan',
}

const statusLabel = computed(() => statusLabels[reservation.value?.status] || reservation.value?.status || '-')

const nights = computed(() =>
  nightsBetween(reservation.value?.check_in_date, reservation.value?.check_out_date)
)

async function fetchReservation() {
  loading.value = true
  notFound.value = false
  loadError.value = false
  try {
    const res = await api.get(`/guest/track/${code}`, { timeout: 15000 })
    reservation.value = res.data
  } catch (err) {
    reservation.value = null
    if (err.response?.status === 404) notFound.value = true
    else loadError.value = true
  } finally {
    loading.value = false
  }
}

async function copyCode() {
  try {
    await navigator.clipboard.writeText(code)
    copied.value = true
    toast.success('Kode reservasi disalin.')
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch {
    toast.error('Gagal menyalin kode. Salin secara manual.')
  }
}

onMounted(() => {
  fetchReservation()
  load()
    .catch(() => {})
    .finally(() => {
      document.title = `Pemesanan Berhasil — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
    })
})
</script>
