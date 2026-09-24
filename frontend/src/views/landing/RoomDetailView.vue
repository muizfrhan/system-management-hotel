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

    <section class="max-w-3xl mx-auto px-4 sm:px-6 md:px-8 pt-24 md:pt-28 pb-12 relative z-10">
      <div v-if="detail" class="space-y-8">
        <div class="relative h-[240px] sm:h-[320px] md:h-[400px] w-full rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden bg-slate-100">
          <img :src="getRoomImage(detail.room_type)" :alt="detail.room_type?.name" class="w-full h-full object-cover" />
          <div class="absolute top-4 left-4 sm:top-6 sm:left-6 bg-white/90 backdrop-blur-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold text-slate-700">
            {{ detail.room_type?.capacity }} Tamu
          </div>
        </div>

        <div class="space-y-6">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">{{ detail.room_type?.name }}</h1>
            <p class="text-base md:text-lg text-slate-600 leading-relaxed">{{ detail.room_type?.description }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Kapasitas</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.capacity }} tamu</p>
            </div>
            <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Kasur</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.bed_type || '-' }}</p>
            </div>
            <div class="bg-slate-50/80 border border-slate-100 rounded-2xl p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Ukuran</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.size || '-' }}</p>
            </div>
          </div>

          <div v-if="categorizedFacilities && Object.keys(categorizedFacilities).length > 0">
            <h4 class="font-bold text-slate-900 mb-6 text-xl tracking-tight">Fasilitas Lengkap</h4>
            <div class="space-y-6">
              <div v-for="(facilities, category) in categorizedFacilities" :key="category">
                <h5 class="font-bold text-slate-800 mb-3 text-base capitalize">{{ category }}</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div v-for="f in facilities" :key="f.id" class="flex items-center gap-2">
                    <CheckCircle2 class="w-5 h-5 text-cyan-500 shrink-0" stroke-width="2.5" />
                    <span class="text-sm font-medium text-slate-600">{{ f.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-100">
            <div>
              <p class="text-xs text-slate-500 mb-0.5">Mulai dari</p>
              <p class="text-2xl sm:text-3xl font-bold text-slate-900">{{ formatCurrency(detail.room_type?.base_price) }}</p>
              <p class="text-sm text-slate-500 mt-1">/ malam · <span class="text-emerald-600 font-semibold">{{ detail.available_rooms }} tersedia</span></p>
            </div>
            <router-link :to="`/booking/${$route.params.id}`" class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-center">
              Pesan Sekarang
            </router-link>
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
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { CheckCircle2 } from 'lucide-vue-next'
import LogoIcon from '../../components/LogoIcon.vue'
import api from '../../services/api'

const route = useRoute()
const detail = ref(null)

const roomImages = [
  'https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
]

onMounted(async () => {
  const res = await api.get(`/guest/room-types/${route.params.id}`)
  detail.value = res.data
})

const categorizedFacilities = computed(() => {
  if (!detail.value || !detail.value.room_type) return {}
  const rt = detail.value.room_type
  const groups = {}
  
  if (rt.facilities) {
    rt.facilities.forEach(f => {
      const cat = f.category || 'Lainnya'
      if (!groups[cat]) groups[cat] = []
      groups[cat].push(f)
    })
  }

  if (rt.custom_facilities) {
    for (const [cat, text] of Object.entries(rt.custom_facilities)) {
      if (!text || !text.trim()) continue;
      if (!groups[cat]) groups[cat] = []
      
      const items = text.split(',').map(s => s.trim()).filter(s => s)
      items.forEach((item, idx) => {
        groups[cat].push({ id: `custom-${cat}-${idx}`, name: item })
      })
    }
  }

  return groups
})

function getRoomImage(rt) {
  if (rt?.images && rt.images.length > 0) return '/storage/' + rt.images[0]
  return roomImages[((rt?.id || 0)) % 3]
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}
</script>
