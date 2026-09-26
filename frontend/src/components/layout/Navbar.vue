<template>
  <header
    class="bg-surface/80 backdrop-blur-xl border-b border-border-subtle flex items-center justify-between sticky top-0 z-30 px-4 md:px-8 py-3.5"
  >
    <div class="flex items-center gap-3 min-w-0">
      <button
        type="button"
        class="lg:hidden p-2.5 min-h-11 min-w-11 flex items-center justify-center -ml-1 text-ink-secondary hover:text-primary hover:bg-surface-muted rounded-xl transition-colors shrink-0"
        aria-label="Buka menu navigasi"
        :aria-expanded="drawerOpen"
        @click="$emit('toggle-sidebar')"
      >
        <Menu class="w-5 h-5" aria-hidden="true" />
      </button>
      <div
        class="w-8 h-8 rounded-lg bg-primary-soft items-center justify-center text-primary hidden sm:flex"
        aria-hidden="true"
      >
        <LayoutDashboard v-if="pageTitle === 'Dasbor'" class="w-4 h-4" />
        <LayoutList v-else class="w-4 h-4" />
      </div>
      <h1 class="text-lg md:text-xl font-bold tracking-tight text-ink truncate">{{ pageTitle }}</h1>
    </div>

    <div class="flex items-center gap-2 md:gap-3">
      <!-- Calendar / Quick Booking Dropdown -->
      <div class="relative" ref="bookingContainer">
        <button
          type="button"
          :aria-expanded="showBooking"
          aria-haspopup="true"
          class="hidden md:flex items-center gap-2 px-4 py-2 max-w-[14rem] truncate bg-surface-muted border border-border-subtle hover:border-primary-soft hover:bg-primary-soft rounded-full text-ink-secondary text-sm font-medium transition-colors cursor-pointer focus-visible:outline-2 focus-visible:outline-primary focus-visible:outline-offset-2"
          @click="toggleBooking"
        >
          <Calendar class="w-4 h-4 text-primary" aria-hidden="true" />
          {{ formattedDate }}
        </button>

        <Transition name="dropdown">
          <div
            v-if="showBooking"
            class="dropdown-panel p-4 sm:p-6 w-[min(580px,calc(100vw-2rem))]"
          >
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="flex-1">
              <label class="label" for="qb-checkin">Check-in</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Calendar class="h-4 w-4 text-ink-muted" aria-hidden="true" />
                </div>
                <input
                  id="qb-checkin"
                  v-model="quickBooking.check_in"
                  type="date"
                  :min="today"
                  class="input-field !pl-10 cursor-pointer [&::-webkit-calendar-picker-indicator]:cursor-pointer"
                />
              </div>
            </div>
            <div class="flex-1">
              <label class="label" for="qb-checkout">Check-out</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Calendar class="h-4 w-4 text-ink-muted" aria-hidden="true" />
                </div>
                <input
                  id="qb-checkout"
                  v-model="quickBooking.check_out"
                  type="date"
                  :min="quickBooking.check_in || today"
                  class="input-field !pl-10 cursor-pointer [&::-webkit-calendar-picker-indicator]:cursor-pointer"
                />
              </div>
            </div>
            <div class="flex-1">
              <label class="label" for="qb-guests">Jumlah Tamu</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <Users class="h-4 w-4 text-ink-muted" aria-hidden="true" />
                </div>
                <input id="qb-guests" v-model.number="quickBooking.guests" type="number" min="1" max="20" class="input-field !pl-10" />
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 sm:w-3/5">
              <span class="text-sm font-bold text-ink mr-1">Tipe:</span>
              <button
                v-for="roomType in roomTypes"
                :key="roomType.id"
                type="button"
                 :aria-pressed="quickBooking.room_type_id === roomType.id"
                 :class="[
                   'px-4 py-2 border rounded-full text-sm font-medium transition-colors',
                  quickBooking.room_type_id === roomType.id
                    ? 'border-primary bg-primary-soft text-primary'
                    : 'border-border bg-surface text-ink-secondary hover:border-primary hover:text-primary',
                ]"
                @click="quickBooking.room_type_id = roomType.id"
              >
                {{ roomType.name }}
              </button>
              <span v-if="quickLoading" class="text-xs text-ink-muted">Memuat tipe kamar...</span>
            </div>

            <div class="sm:w-2/5">
              <p v-if="bookingError" class="mb-2 text-xs text-danger" role="alert">{{ bookingError }}</p>
              <button type="button" class="btn btn-primary w-full" :disabled="quickSubmitting || quickLoading" :aria-busy="quickSubmitting || undefined" @click="submitQuickBooking">
                <span>Cek Ketersediaan</span>
                <ArrowRight class="w-4 h-4" aria-hidden="true" />
              </button>
            </div>
          </div>
          </div>
        </Transition>
      </div>

      <!-- Notifications Dropdown -->
      <div class="relative" ref="notificationContainer">
        <button
          type="button"
          class="relative p-2.5 text-ink-muted hover:text-primary hover:bg-primary-soft rounded-xl transition-colors"
          :aria-expanded="showDropdown"
          aria-haspopup="true"
          aria-label="Notifikasi"
          @click="showDropdown = !showDropdown"
        >
          <Bell class="w-5 h-5" aria-hidden="true" />
          <span
            v-if="notifications.length > 0"
            class="absolute top-2 right-2 w-2 h-2 rounded-full bg-danger ring-2 ring-surface"
            aria-hidden="true"
          ></span>
          <span v-if="notifications.length > 0" class="sr-only">
            {{ notifications.length }} notifikasi baru
          </span>
        </button>

        <Transition name="dropdown">
          <div
            v-if="showDropdown"
            class="dropdown-panel w-[calc(100vw-2rem)] sm:w-80 overflow-hidden"
          >
          <div
            class="bg-surface-muted px-5 py-4 border-b border-border-subtle flex justify-between items-center"
          >
            <h2 class="font-bold text-ink">Notifikasi</h2>
            <span class="text-xs font-bold bg-primary-soft text-primary px-2 py-0.5 rounded-full">
              {{ notifications.length }} Baru
            </span>
          </div>

          <div class="max-h-[360px] overflow-y-auto">
            <div
              v-if="notifications.length === 0"
              class="empty-state !py-8"
            >
              <div class="empty-state-icon !w-12 !h-12">
                <CheckCircle class="w-6 h-6 text-success/60" aria-hidden="true" />
              </div>
              <p class="empty-state-title">Tidak ada notifikasi baru</p>
              <p class="empty-state-desc">Semua tugas operasional telah beres.</p>
            </div>

            <router-link
              v-for="notif in notifications"
              :key="notif.id"
              :to="'/' + auth.userRole + notif.link"
              @click="showDropdown = false"
              class="block p-4 border-b border-border-subtle hover:bg-surface-muted/80 transition-colors cursor-pointer group"
            >
              <div class="flex gap-3">
                <div class="mt-0.5">
                  <div
                    v-if="notif.type === 'warning'"
                    class="w-8 h-8 rounded-full bg-warning-soft flex items-center justify-center"
                  >
                    <AlertCircle class="w-4 h-4 text-warning" aria-hidden="true" />
                  </div>
                  <div
                    v-else-if="notif.type === 'error'"
                    class="w-8 h-8 rounded-full bg-danger-soft flex items-center justify-center"
                  >
                    <AlertTriangle class="w-4 h-4 text-danger" aria-hidden="true" />
                  </div>
                  <div v-else class="w-8 h-8 rounded-full bg-info-soft flex items-center justify-center">
                    <Info class="w-4 h-4 text-info" aria-hidden="true" />
                  </div>
                </div>
                <div>
                  <p class="text-sm font-bold text-ink group-hover:text-primary transition-colors">
                    {{ notif.title }}
                  </p>
                  <p class="text-xs text-ink-secondary mt-1 leading-snug">{{ notif.message }}</p>
                  <p class="text-[10px] font-bold text-ink-muted uppercase tracking-wider mt-2">
                    {{ notif.time }}
                  </p>
                </div>
              </div>
            </router-link>
          </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  Menu, Calendar, Bell, LayoutDashboard, LayoutList,
  AlertCircle, AlertTriangle, Info, CheckCircle, Users, ArrowRight,
} from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import api from '../../services/api'

const props = defineProps({
  pageTitle: { type: String, default: 'Dasbor' },
  drawerOpen: { type: Boolean, default: false },
})

defineEmits(['toggle-sidebar'])

const auth = useAuthStore()
const router = useRouter()
const notifications = ref([])
const showDropdown = ref(false)
const showBooking = ref(false)
const notificationContainer = ref(null)
const bookingContainer = ref(null)
const roomTypes = ref([])
const quickLoading = ref(false)
const quickSubmitting = ref(false)
const bookingError = ref(null)
const today = dateOffset(0)
const quickBooking = ref({
  check_in: dateOffset(1),
  check_out: dateOffset(2),
  guests: 2,
  room_type_id: null,
})
let pollInterval = null
let notificationsLoading = false

const formattedDate = computed(() => {
  if (quickBooking.value.check_in && quickBooking.value.check_out) {
    return `${formatShortDate(quickBooking.value.check_in)} — ${formatShortDate(quickBooking.value.check_out)}`
  }

  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

async function fetchNotifications() {
  if (notificationsLoading || document.hidden) return
  notificationsLoading = true
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data
  } catch {} finally {
    notificationsLoading = false
  }
}

async function toggleBooking() {
  showBooking.value = !showBooking.value
  bookingError.value = null

  if (!showBooking.value || roomTypes.value.length) return

  quickLoading.value = true
  try {
    const response = await api.get('/guest/room-types')
    roomTypes.value = response.data
  } catch {
    bookingError.value = 'Tipe kamar tidak dapat dimuat. Coba lagi.'
  } finally {
    quickLoading.value = false
  }
}

async function submitQuickBooking() {
  if (quickSubmitting.value || quickLoading.value) return
  bookingError.value = null
  const checkIn = quickBooking.value.check_in
  const checkOut = quickBooking.value.check_out
  const guests = Number(quickBooking.value.guests)

  if (!checkIn || !checkOut || checkOut <= checkIn) {
    bookingError.value = 'Pilih tanggal check-in dan check-out yang valid.'
    return
  }

  if (!Number.isInteger(guests) || guests < 1 || guests > 20) {
    bookingError.value = 'Jumlah tamu harus antara 1 hingga 20.'
    return
  }

  const query = { check_in: checkIn, check_out: checkOut, guests }
  if (quickBooking.value.room_type_id) query.type = quickBooking.value.room_type_id
  quickSubmitting.value = true
  showBooking.value = false
  try {
    await router.push({ path: '/rooms', query })
  } finally {
    quickSubmitting.value = false
  }
}

function dateOffset(days) {
  const date = new Date()
  date.setHours(12, 0, 0, 0)
  date.setDate(date.getDate() + days)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatShortDate(value) {
  return new Date(`${value}T12:00:00`).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
  })
}

function handleClickOutside(event) {
  if (notificationContainer.value && !notificationContainer.value.contains(event.target)) {
    showDropdown.value = false
  }
  if (bookingContainer.value && !bookingContainer.value.contains(event.target)) {
    showBooking.value = false
  }
}

function onKeydown(e) {
  if (e.key === 'Escape') {
    showDropdown.value = false
    showBooking.value = false
  }
}

onMounted(() => {
  fetchNotifications()
  pollInterval = setInterval(fetchNotifications, 15000)
  document.addEventListener('mousedown', handleClickOutside)
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
  document.removeEventListener('mousedown', handleClickOutside)
  document.removeEventListener('keydown', onKeydown)
})
</script>
