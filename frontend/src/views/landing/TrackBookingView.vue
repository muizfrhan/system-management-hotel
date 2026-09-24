<template>
  <main class="min-h-screen bg-white overflow-hidden selection:bg-cyan-100 selection:text-cyan-900">

    <nav class="fixed top-4 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-7xl z-50 flex items-center justify-between px-4 sm:px-6 md:px-8 py-3 md:py-4 bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100/80">
      <router-link to="/" class="flex items-center gap-2 min-w-0">
        <div class="text-cyan-400 shrink-0"><LogoIcon /></div>
        <span class="text-lg sm:text-xl md:text-2xl font-bold tracking-tight text-slate-900 truncate">Lokanata Hotel</span>
      </router-link>
      <router-link to="/" class="px-5 sm:px-6 py-2.5 rounded-full border border-slate-200 font-medium text-slate-700 hover:bg-slate-50 transition-colors text-sm">
        ← <span class="hidden sm:inline">Kembali ke </span>Beranda
      </router-link>
    </nav>

    <section class="max-w-2xl mx-auto px-4 sm:px-6 md:px-8 pt-28 md:pt-32 pb-16 relative z-10">
      <div class="text-center mb-8 md:mb-10">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-3">Lacak Reservasi</h1>
        <p class="text-slate-600 text-sm md:text-base">Masukkan kode reservasi Anda untuk melihat status pemesanan.</p>
      </div>

      <div class="bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] border border-slate-100 p-5 sm:p-8">
        <form @submit.prevent="track" class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="code"
            placeholder="Contoh: RSV260924ABC"
            class="flex-1 min-w-0 px-5 py-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm font-mono uppercase tracking-wide"
            required
          />
          <button type="submit" :disabled="loading" class="px-7 py-3.5 rounded-2xl bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-sm disabled:opacity-50">
            {{ loading ? 'Mencari...' : 'Lacak' }}
          </button>
        </form>

        <p v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Hasil -->
      <div v-if="reservation" class="mt-8 bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] border border-slate-100 p-5 sm:p-8 animate-slide-up">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
          <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Kode Reservasi</p>
            <p class="text-xl sm:text-2xl font-mono font-bold text-cyan-500 tracking-wide break-all">{{ reservation.reservation_code }}</p>
          </div>
          <span class="self-start sm:self-auto px-4 py-2 rounded-full text-sm font-bold shrink-0" :class="statusBadge">{{ statusLabel(reservation.status) }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
          <div class="bg-slate-50 rounded-2xl p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Tamu</p>
            <p class="font-semibold text-slate-900">{{ reservation.guest?.name }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Kamar</p>
            <p class="font-semibold text-slate-900">{{ reservation.room?.room_number }} — {{ reservation.room?.room_type?.name }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Check-in</p>
            <p class="font-semibold text-slate-900">{{ formatDate(reservation.check_in_date) }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Check-out</p>
            <p class="font-semibold text-slate-900">{{ formatDate(reservation.check_out_date) }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4">
            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Jumlah Tamu</p>
            <p class="font-semibold text-slate-900">{{ reservation.number_of_guests }} orang</p>
          </div>
          <div class="bg-cyan-50 rounded-2xl p-4">
            <p class="text-cyan-600 text-xs font-semibold uppercase tracking-wider mb-1">Total</p>
            <p class="font-bold text-cyan-700 text-lg">{{ formatCurrency(reservation.total_price) }}</p>
          </div>
        </div>

        <div v-if="reservation.special_requests" class="mt-4 bg-slate-50 rounded-2xl p-4 text-sm">
          <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Permintaan Khusus</p>
          <p class="text-slate-700">{{ reservation.special_requests }}</p>
        </div>

        <div v-if="['pending', 'confirmed'].includes(reservation.status)" class="mt-6 pt-5 border-t border-slate-100">
          <button @click="cancelBooking" :disabled="cancelling" class="w-full sm:w-auto px-6 py-3 rounded-full border-2 border-red-200 text-red-500 font-semibold text-sm hover:bg-red-50 transition-colors disabled:opacity-50">
            {{ cancelling ? 'Membatalkan...' : 'Batalkan Reservasi' }}
          </button>
        </div>
      </div>
    </section>

    <footer class="border-t border-slate-100 py-12 relative z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 text-center text-slate-500 text-sm">
        Copyright &copy; {{ new Date().getFullYear() }} | Developed by Cybha.
      </div>
    </footer>
  </main>
</template>

<script setup>
import { ref, computed } from 'vue'
import LogoIcon from '../../components/LogoIcon.vue'
import api from '../../services/api'

const code = ref('')
const loading = ref(false)
const cancelling = ref(false)
const error = ref(null)
const reservation = ref(null)

const statusBadge = computed(() => {
  const map = {
    pending: 'bg-amber-100 text-amber-700',
    confirmed: 'bg-emerald-100 text-emerald-700',
    checked_in: 'bg-blue-100 text-blue-700',
    checked_out: 'bg-slate-100 text-slate-600',
    cancelled: 'bg-red-100 text-red-600',
  }
  return map[reservation.value?.status] || 'bg-slate-100 text-slate-600'
})

async function track() {
  loading.value = true
  error.value = null
  reservation.value = null
  try {
    const res = await api.get(`/guest/track/${code.value.trim().toUpperCase()}`)
    reservation.value = res.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Terjadi kesalahan.'
  } finally {
    loading.value = false
  }
}

async function cancelBooking() {
  if (!confirm('Yakin ingin membatalkan reservasi ini?')) return
  cancelling.value = true
  error.value = null
  try {
    await api.put(`/guest/track/${reservation.value.reservation_code}/cancel`)
    const res = await api.get(`/guest/track/${reservation.value.reservation_code}`)
    reservation.value = res.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal membatalkan reservasi.'
  } finally {
    cancelling.value = false
  }
}

function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }
function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0) }
function statusLabel(s) { const l = { pending: 'Menunggu Konfirmasi', confirmed: 'Dikonfirmasi', checked_in: 'Sudah Check-In', checked_out: 'Selesai', cancelled: 'Dibatalkan' }; return l[s] || s }
</script>
