<template>
  <header class="bg-white/80 backdrop-blur-xl border-b border-slate-100 flex items-center justify-between sticky top-0 z-30 px-4 md:px-8 py-4">
    <div class="flex items-center gap-3 min-w-0">
      <button
        class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-cyan-600 hover:bg-slate-50 rounded-xl transition-colors shrink-0"
        aria-label="Buka menu"
        @click="$emit('toggle-sidebar')"
      >
        <Menu class="w-5 h-5" />
      </button>
      <div class="w-8 h-8 rounded-xl bg-cyan-50 items-center justify-center text-cyan-500 hidden sm:flex">
        <LayoutDashboard v-if="pageTitle === 'Dasbor'" class="w-4 h-4" />
        <LayoutList v-else class="w-4 h-4" />
      </div>
      <h2 class="text-lg md:text-xl font-bold tracking-tight text-slate-900 truncate">{{ pageTitle }}</h2>
    </div>
    <div class="flex items-center gap-2 md:gap-4">
      <!-- Calendar / Quick Booking Dropdown -->
      <div class="relative" ref="bookingContainer">
        <button @click="showBooking = !showBooking" class="hidden md:flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-100 hover:border-cyan-200 hover:bg-cyan-50/50 rounded-full text-slate-600 text-sm font-medium transition-colors cursor-pointer focus:outline-none">
          <Calendar class="w-4 h-4 text-cyan-500" />
          {{ formattedDate }}
        </button>

        <!-- Booking Widget from Image -->
        <div v-if="showBooking" class="absolute right-0 mt-3 p-4 sm:p-6 bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] border border-slate-100 z-50 w-[calc(100vw-2rem)] sm:w-[580px] transform origin-top-right transition-all">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="flex-1">
              <label class="block text-sm font-bold text-slate-800 mb-2">Check-in</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Calendar class="h-5 w-5 text-slate-400" />
                </div>
                <input type="date" class="w-full pl-11 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-700 text-sm font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all outline-none cursor-pointer relative [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:cursor-pointer" />
              </div>
            </div>
            <div class="flex-1">
              <label class="block text-sm font-bold text-slate-800 mb-2">Check-out</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Calendar class="h-5 w-5 text-slate-400" />
                </div>
                <input type="date" class="w-full pl-11 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-700 text-sm font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all outline-none cursor-pointer relative [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:cursor-pointer" />
              </div>
            </div>
            <div class="flex-1">
              <label class="block text-sm font-bold text-slate-800 mb-2">Jumlah Tamu</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Users class="h-5 w-5 text-slate-400" />
                </div>
                <input type="number" value="2" min="1" class="w-full pl-11 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-700 text-sm font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all outline-none" />
              </div>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 sm:w-3/5">
              <span class="text-sm font-bold text-slate-800 mr-1">Tipe:</span>
              <button class="px-4 py-2 border border-slate-200 bg-white rounded-full text-sm font-medium text-slate-600 hover:border-cyan-500 hover:text-cyan-600 transition-colors">Deluxe</button>
              <button class="px-4 py-2 border border-slate-200 bg-white rounded-full text-sm font-medium text-slate-600 hover:border-cyan-500 hover:text-cyan-600 transition-colors">Superior</button>
              <button class="px-4 py-2 border border-slate-200 bg-white rounded-full text-sm font-medium text-slate-600 hover:border-cyan-500 hover:text-cyan-600 transition-colors mt-2">Standard</button>
            </div>
            
            <button @click="showBooking = false" class="w-full sm:w-auto bg-[#0b1426] hover:bg-[#132342] text-white px-7 py-3 rounded-2xl font-bold flex items-center justify-center gap-2 transition-colors shadow-lg shadow-blue-900/20">
              <span class="text-[15px]">Cek Ketersediaan</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
      
      <!-- Notifications Dropdown -->
      <div class="relative" ref="notificationContainer">
        <button @click="showDropdown = !showDropdown" class="relative p-2.5 text-slate-400 hover:text-cyan-500 hover:bg-cyan-50 rounded-xl transition-colors">
          <Bell class="w-5 h-5" />
          <span v-if="notifications.length > 0" class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
        </button>

        <div v-if="showDropdown" class="absolute right-0 mt-3 w-[calc(100vw-2rem)] sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden transform origin-top-right transition-all z-50">
          <div class="bg-slate-50 px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Notifikasi</h3>
            <span class="text-xs font-bold bg-cyan-100 text-cyan-700 px-2 py-0.5 rounded-full">{{ notifications.length }} Baru</span>
          </div>
          
          <div class="max-h-[360px] overflow-y-auto">
            <div v-if="notifications.length === 0" class="p-8 text-center flex flex-col items-center justify-center">
              <CheckCircle class="w-10 h-10 text-emerald-400/50 mb-3" />
              <p class="text-sm font-medium text-slate-500">Tidak ada notifikasi baru</p>
              <p class="text-xs text-slate-400 mt-1">Semua tugas operasional telah beres.</p>
            </div>
            
            <router-link 
              v-for="notif in notifications" 
              :key="notif.id" 
              :to="'/' + auth.userRole + notif.link"
              @click="showDropdown = false"
              class="block p-4 border-b border-slate-50 hover:bg-slate-50/80 transition-colors cursor-pointer group"
            >
              <div class="flex gap-3">
                <div class="mt-0.5">
                  <div v-if="notif.type === 'warning'" class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center"><AlertCircle class="w-4 h-4 text-amber-600" /></div>
                  <div v-else-if="notif.type === 'error'" class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center"><AlertTriangle class="w-4 h-4 text-red-600" /></div>
                  <div v-else class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center"><Info class="w-4 h-4 text-blue-600" /></div>
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-800 group-hover:text-cyan-600 transition-colors">{{ notif.title }}</p>
                  <p class="text-xs text-slate-500 mt-1 leading-snug">{{ notif.message }}</p>
                  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-2">{{ notif.time }}</p>
                </div>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Menu, Calendar, Bell, LayoutDashboard, LayoutList, AlertCircle, AlertTriangle, Info, CheckCircle, Users, ArrowRight } from 'lucide-vue-next'
import { useAuthStore } from '../../stores/authStore'
import api from '../../services/api'

defineProps({
  pageTitle: { type: String, default: 'Dasbor' },
})

defineEmits(['toggle-sidebar'])

const auth = useAuthStore()
const notifications = ref([])
const showDropdown = ref(false)
const showBooking = ref(false)
const notificationContainer = ref(null)
const bookingContainer = ref(null)
let pollInterval = null

const formattedDate = computed(() => {
  return new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
})

async function fetchNotifications() {
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data
  } catch (err) {
    // silently fail for bg fetching
  }
}

function handleClickOutside(event) {
  if (notificationContainer.value && !notificationContainer.value.contains(event.target)) {
    showDropdown.value = false
  }
  if (bookingContainer.value && !bookingContainer.value.contains(event.target)) {
    showBooking.value = false
  }
}

onMounted(() => {
  fetchNotifications()
  pollInterval = setInterval(fetchNotifications, 15000) // Poll every 15 seconds
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>
