<template>
  <main class="min-h-screen bg-white overflow-hidden selection:bg-cyan-100 selection:text-cyan-900">

    <nav class="fixed top-4 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-7xl z-50 flex items-center justify-between px-4 sm:px-6 md:px-8 py-3 md:py-4 bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100/80">
      <router-link to="/" class="flex items-center gap-2 min-w-0">
        <div class="text-cyan-400 shrink-0">
          <LogoIcon />
        </div>
        <span class="text-lg sm:text-xl md:text-2xl font-bold tracking-tight text-slate-900 truncate">Lokanata Hotel</span>
      </router-link>
      <router-link to="/rooms" class="px-5 sm:px-6 py-2.5 rounded-full border border-slate-200 font-medium text-slate-700 hover:bg-slate-50 transition-colors text-sm">
        ← <span class="hidden sm:inline">Kembali ke </span>Kamar
      </router-link>
    </nav>

    <section class="max-w-2xl mx-auto px-4 sm:px-6 md:px-8 pt-24 md:pt-28 pb-12 relative z-10">
      <div class="bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] border border-slate-100 p-5 sm:p-8 space-y-6">
        <h1 class="text-2xl font-bold text-slate-900">Pemesanan Kamar</h1>

        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-600">{{ error }}</div>

        <form @submit.prevent="submitBooking" class="space-y-5">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-900">Nama Lengkap</label>
            <input v-model="form.name" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-900">Email</label>
              <input v-model="form.email" type="email" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-900">Telepon</label>
              <input v-model="form.phone" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-900">Check-in</label>
              <input v-model="form.check_in_date" type="date" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold text-slate-900">Check-out</label>
              <input v-model="form.check_out_date" type="date" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-900">Jumlah Tamu</label>
            <input v-model="form.number_of_guests" type="number" min="1" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" required />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-900">Permintaan Khusus (opsional)</label>
            <textarea v-model="form.special_requests" rows="3" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm resize-none"></textarea>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm text-slate-600 bg-slate-50 rounded-2xl px-4 py-3">
            <span>{{ nights }} malam</span>
            <span class="font-bold text-slate-900 text-base text-left sm:text-right">Total: {{ formatCurrency(nights * roomType?.base_price) }}</span>
          </div>

          <button type="submit" :disabled="loading || !datesValid" class="w-full py-3.5 rounded-full bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-base disabled:opacity-50 disabled:cursor-not-allowed">
            <span v-if="loading">Memproses...</span>
            <span v-else>Konfirmasi Pemesanan</span>
          </button>
        </form>
      </div>
    </section>

    <footer class="border-t border-slate-100 py-12 mt-12 relative z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 text-center text-slate-500 text-sm">
        Copyright &copy; {{ new Date().getFullYear() }} | Developed by Cybha.
      </div>
    </footer>
  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LogoIcon from '../../components/LogoIcon.vue'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const error = ref(null)
const roomType = ref(null)
const form = ref({ name: '', email: '', phone: '', check_in_date: '', check_out_date: '', number_of_guests: 1, special_requests: '' })

const nights = computed(() => {
  if (!form.value.check_in_date || !form.value.check_out_date) return 0
  return Math.max(0, Math.round((new Date(form.value.check_out_date) - new Date(form.value.check_in_date)) / 86400000))
})

const datesValid = computed(() => nights.value > 0)

onMounted(async () => {
  if (route.query.check_in) form.value.check_in_date = route.query.check_in
  if (route.query.check_out) form.value.check_out_date = route.query.check_out
  if (route.query.guests) form.value.number_of_guests = parseInt(route.query.guests) || 1
  try {
    const res = await api.get(`/guest/room-types/${route.params.roomTypeId}`)
    roomType.value = res.data.room_type
  } catch (e) { console.error(e) }
})

async function submitBooking() {
  loading.value = true; error.value = null
  try {
    const res = await api.post('/guest/booking', { ...form.value, room_type_id: route.params.roomTypeId })
    router.push(`/booking/success/${res.data.reservation_code}`)
  } catch (err) { error.value = err.response?.data?.message || 'Terjadi kesalahan.' }
  finally { loading.value = false }
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}
</script>
