<template>
  <main class="min-h-screen bg-white overflow-hidden selection:bg-cyan-100 selection:text-cyan-900">

    <nav class="fixed top-4 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-7xl z-50 flex items-center justify-between px-4 sm:px-6 md:px-8 py-3 md:py-4 bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100/80">
      <router-link to="/" class="flex items-center gap-2 min-w-0">
        <div class="text-cyan-400 shrink-0">
          <LogoIcon />
        </div>
        <span class="text-lg sm:text-xl md:text-2xl font-bold tracking-tight text-slate-900 truncate">Lokanata Hotel</span>
      </router-link>

      <div class="hidden md:flex items-center gap-8 font-medium text-slate-700">
        <router-link to="/" class="hover:text-cyan-500 transition-colors">Beranda</router-link>
        <router-link to="/rooms" class="text-cyan-500 font-semibold">Kamar</router-link>
        <router-link to="/track" class="hover:text-cyan-500 transition-colors">Lacak Reservasi</router-link>
      </div>

      <router-link to="/" class="px-6 py-2.5 rounded-full border border-slate-200 font-medium text-slate-700 hover:bg-slate-50 transition-colors hidden md:inline-flex">
        ← Kembali
      </router-link>

      <!-- Hamburger mobile -->
      <button
        class="md:hidden p-2 -mr-2 text-slate-600 hover:text-cyan-600 rounded-xl transition-colors"
        aria-label="Buka menu"
        @click="mobileMenuOpen = !mobileMenuOpen"
      >
        <X v-if="mobileMenuOpen" class="w-6 h-6" />
        <Menu v-else class="w-6 h-6" />
      </button>

      <div
        v-if="mobileMenuOpen"
        class="md:hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 p-4 space-y-1"
      >
        <router-link to="/" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50" @click="mobileMenuOpen = false">Beranda</router-link>
        <router-link to="/rooms" class="block px-4 py-3 rounded-xl text-sm font-semibold text-cyan-600 bg-cyan-50" @click="mobileMenuOpen = false">Kamar</router-link>
        <router-link to="/track" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50" @click="mobileMenuOpen = false">Lacak Reservasi</router-link>
        <router-link to="/" class="block px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50" @click="mobileMenuOpen = false">← Kembali</router-link>
      </div>
    </nav>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pt-24 md:pt-28 pb-12 relative z-10">
      <div class="text-center max-w-2xl mx-auto mb-10">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mb-4">Pilih Kamar Ideal Anda</h1>
        <p class="text-slate-600">Dari kamar superior yang nyaman hingga suite mewah, kami memiliki ruang yang sempurna untuk istirahat Anda.</p>
      </div>

      <!-- Filter tanggal -->
      <div class="max-w-3xl mx-auto mb-10 md:mb-12 bg-slate-50 border border-slate-100 rounded-2xl sm:rounded-3xl p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 items-end">
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-900">Check-in</label>
            <input v-model="searchForm.check_in" type="date" :min="today" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-900">Check-out</label>
            <input v-model="searchForm.check_out" type="date" :min="searchForm.check_in || today" class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-white outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 transition text-sm" />
          </div>
          <button @click="applySearch" class="w-full py-3.5 rounded-2xl bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-sm">
            Cek Ketersediaan
          </button>
        </div>
        <p v-if="hasDates" class="text-xs text-slate-500 mt-3 text-center">
          Menampilkan ketersediaan untuk <strong class="text-slate-700">{{ formatDate(searchForm.check_in) }}</strong> → <strong class="text-slate-700">{{ formatDate(searchForm.check_out) }}</strong>
          <button @click="clearDates" class="ml-2 text-cyan-500 underline underline-offset-2">Hapus filter</button>
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
        <div v-for="rt in roomTypes" :key="rt.id" class="group cursor-pointer flex flex-col h-full">
          <div class="relative h-48 sm:h-56 md:h-64 w-full rounded-2xl sm:rounded-3xl overflow-hidden mb-4 shrink-0 bg-slate-100">
            <img :src="getRoomImage(rt, 0)" :alt="rt.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-slate-700">
              {{ rt.capacity }} Tamu
            </div>
          </div>

          <div class="space-y-2 flex-1 flex flex-col">
            <div class="flex items-center gap-1 text-sm">
              <Star class="w-4 h-4 fill-amber-400 text-amber-400" />
              <span class="font-semibold text-slate-900">5.0</span>
              <span :class="(rt.available_rooms_count || 0) > 0 ? 'text-slate-500' : 'text-red-400 font-semibold'">
                {{ (rt.available_rooms_count || 0) > 0 ? `${rt.available_rooms_count} tersedia` : 'Tidak tersedia' }}
              </span>
            </div>

            <h3 class="text-xl font-bold text-slate-900">{{ rt.name }}</h3>
            <p class="text-sm text-slate-500 mb-1">{{ rt.bed_type || '-' }} · {{ rt.size || '-' }}</p>
            <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed">{{ rt.description }}</p>

            <div class="flex flex-wrap gap-1.5 py-3">
              <span v-for="f in rt.facilities?.slice(0, 4)" :key="f.id" class="text-xs bg-slate-50 border border-slate-100 text-slate-600 px-2 py-1 rounded-lg">{{ f.name }}</span>
            </div>

            <router-link :to="`/rooms/${rt.id}`" class="text-[13px] font-bold text-cyan-500 hover:text-cyan-600 w-max underline underline-offset-4 decoration-cyan-200 hover:decoration-cyan-400 transition-all mb-4 mt-1">
              Rincian selengkapnya ↗
            </router-link>

            <div class="flex items-end justify-between gap-3 pt-4 mt-auto border-t border-slate-100">
              <div class="min-w-0">
                <p class="text-xs text-slate-500 mb-0.5">Mulai dari</p>
                <p class="text-base sm:text-lg font-bold text-slate-900 truncate">{{ formatCurrency(rt.base_price) }}</p>
                <p class="text-xs text-slate-500">/ malam</p>
              </div>
              <router-link
                :to="{ path: `/booking/${rt.id}`, query: hasDates ? { check_in: searchForm.check_in, check_out: searchForm.check_out } : {} }"
                :class="[
                  'shrink-0 px-4 sm:px-5 py-2.5 rounded-full text-white text-sm font-semibold transition-colors text-center',
                  (rt.available_rooms_count || 0) > 0
                    ? 'bg-cyan-400 hover:bg-cyan-500'
                    : 'bg-slate-300 cursor-not-allowed pointer-events-none'
                ]"
              >
                {{ (rt.available_rooms_count || 0) > 0 ? 'Pesan sekarang' : 'Penuh' }}
              </router-link>
            </div>
          </div>
        </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Star, Menu, X } from 'lucide-vue-next'
import LogoIcon from '../../components/LogoIcon.vue'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
const roomTypes = ref([])
const mobileMenuOpen = ref(false)

const today = new Date().toISOString().split('T')[0]
const searchForm = ref({ check_in: '', check_out: '' })

const hasDates = computed(() => searchForm.value.check_in && searchForm.value.check_out)

const roomImages = [
  'https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
]

onMounted(() => {
  if (route.query.check_in) searchForm.value.check_in = route.query.check_in
  if (route.query.check_out) searchForm.value.check_out = route.query.check_out
  fetchRooms()
})

watch(() => route.query, () => {
  searchForm.value.check_in = route.query.check_in || ''
  searchForm.value.check_out = route.query.check_out || ''
  fetchRooms()
})

async function fetchRooms() {
  try {
    const params = {}
    if (hasDates.value) {
      params.check_in = searchForm.value.check_in
      params.check_out = searchForm.value.check_out
    }
    const res = await api.get('/guest/room-types', { params })
    roomTypes.value = res.data
  } catch (error) {
    console.error(error)
  }
}

function applySearch() {
  if (!hasDates.value) return
  router.replace({
    path: '/rooms',
    query: { check_in: searchForm.value.check_in, check_out: searchForm.value.check_out },
  })
}

function clearDates() {
  searchForm.value = { check_in: '', check_out: '' }
  router.replace({ path: '/rooms' })
}

function getRoomImage(rt, idx = 0) {
  if (rt.images && rt.images.length > idx) return '/storage/' + rt.images[idx]
  return roomImages[(rt.id + idx) % 3]
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}
</script>
