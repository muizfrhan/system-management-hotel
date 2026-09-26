<template>
  <div class="flex h-dvh overflow-hidden bg-background">
    <a
      href="#main-content"
      class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded-lg focus:text-sm focus:font-semibold"
    >
      Lewati ke konten utama
    </a>

    <Sidebar
      :user="auth.user"
      :user-role="auth.userRole"
      :is-collapsed="isSidebarCollapsed"
      :is-mobile-open="isMobileSidebarOpen"
      @logout="handleLogout"
      @toggle="isSidebarCollapsed = !isSidebarCollapsed"
      @close-mobile="isMobileSidebarOpen = false"
    />

    <div
       class="flex-1 overflow-y-auto flex flex-col min-w-0"
       :class="{ 'overflow-hidden': isMobileSidebarOpen }"
       :inert="isMobileSidebarOpen ? true : undefined"
    >
      <Navbar
        :page-title="currentPageTitle"
        :drawer-open="isMobileSidebarOpen"
        @toggle-sidebar="isMobileSidebarOpen = !isMobileSidebarOpen"
      />
      <main id="main-content" class="p-4 md:p-6 lg:p-8 max-w-[1600px] w-full mx-auto" tabindex="-1">
        <router-view v-slot="{ Component, route }">
          <Transition name="page" mode="out-in">
            <component :is="Component" :key="route.path" />
          </Transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import Sidebar from './Sidebar.vue'
import Navbar from './Navbar.vue'
import { useToast } from '../../composables/useToast'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const isSidebarCollapsed = ref(false)
const isMobileSidebarOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    isMobileSidebarOpen.value = false
  }
)

watch(isMobileSidebarOpen, (open) => {
  if (open) isSidebarCollapsed.value = false
})

const pageTitles = {
  dashboard: 'Dasbor',
  'room-types': 'Tipe Kamar',
  rooms: 'Daftar Kamar',
  facilities: 'Fasilitas',
  'booking-online': 'Booking Online',
  reservations: 'Reservasi',
  'check-in-out': 'Check In/Out',
  payments: 'Pembayaran & Faktur',
  guests: 'Data Tamu',
  housekeeping: 'Tata Graha',
  staff: 'Manajemen Staf',
  reports: 'Laporan',
  settings: 'Pengaturan',
}

const currentPageTitle = computed(() => {
  const segments = route.path.split('/')
  const lastSegment = segments[segments.length - 1]
  if (route.path.startsWith('/receptionist') && lastSegment === 'rooms') return 'Ketersediaan Kamar'
  if (route.path.startsWith('/receptionist') && lastSegment === 'dashboard') return 'Dasbor Front Desk'
  if (route.path.startsWith('/housekeeper') && lastSegment === 'housekeeping') return 'Tata Graha'
  return pageTitles[lastSegment] || 'Dasbor'
})

async function handleLogout() {
  try {
    await auth.logout()
  } catch {
    toast.error('Permintaan logout gagal, tetapi sesi lokal telah dihentikan.')
  } finally {
    await router.replace('/login')
  }
}
</script>
