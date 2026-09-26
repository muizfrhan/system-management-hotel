<template>
  <!-- Overlay penutup untuk mobile -->
  <Transition name="fade">
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 bg-slate-900/40 z-40 lg:hidden"
      aria-hidden="true"
      @click="$emit('close-mobile')"
    ></div>
  </Transition>

  <aside
    ref="asideEl"
    :class="[
      'flex flex-col bg-surface border-r border-border-subtle z-50 relative',
      'transition-[transform,width] duration-200 ease-out',
      'fixed inset-y-0 left-0 max-lg:shadow-2xl w-[min(16rem,85vw)]',
      isMobileOpen ? 'translate-x-0' : '-translate-x-full',
      'lg:translate-x-0 lg:static lg:z-20 lg:shadow-none',
      effectiveCollapsed ? 'lg:w-20 lg:items-center' : 'lg:w-64',
    ]"
    :aria-hidden="!isMobileOpen && isMobile ? 'true' : undefined"
    :inert="!isMobileOpen && isMobile ? true : undefined"
  >
    <div
      :class="[
        'py-5 border-b border-border-subtle flex items-center h-[76px]',
        'transition-[padding,justify-content] duration-200 ease-out',
        effectiveCollapsed ? 'justify-center px-0' : 'justify-between px-6',
      ]"
    >
      <div
        v-if="!effectiveCollapsed"
        class="flex items-center gap-3 whitespace-nowrap overflow-hidden transition-[opacity] duration-200 ease-out"
      >
        <LogoIcon :size="28" />
        <div>
          <h1 class="text-lg font-bold text-ink tracking-tight">Lokanata</h1>
          <p class="text-[9px] mt-0.5 uppercase font-extrabold tracking-[0.2em] text-ink-muted">
            Hotel Admin
          </p>
        </div>
      </div>

      <button
        type="button"
        @click="$emit('toggle')"
        class="hidden lg:flex text-ink-muted hover:text-primary hover:bg-surface-muted p-2.5 min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors shrink-0"
        :title="effectiveCollapsed ? 'Perluas menu' : 'Ciutkan menu'"
        :aria-label="effectiveCollapsed ? 'Perluas menu' : 'Ciutkan menu'"
      >
        <ChevronRight v-if="effectiveCollapsed" class="w-5 h-5" aria-hidden="true" />
        <ChevronLeft v-else class="w-5 h-5" aria-hidden="true" />
      </button>
    </div>

    <nav
      class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden w-full custom-scrollbar"
      aria-label="Navigasi utama"
    >
      <template v-for="section in filteredMenu" :key="section.title">
        <p v-if="!effectiveCollapsed" class="sidebar-section-label first:pt-0 whitespace-nowrap">
          {{ section.title }}
        </p>
        <div v-else class="w-full h-px bg-border-subtle my-4 first:hidden" aria-hidden="true"></div>

        <router-link
          v-for="item in section.items"
          :key="item.path"
          :to="item.path"
          :title="effectiveCollapsed ? item.label : undefined"
          :class="[
            'sidebar-link group relative min-h-11',
            effectiveCollapsed ? 'justify-center px-0 w-12 h-12 mx-auto rounded-xl' : 'px-4 w-full',
          ]"
          active-class="active"
        >
          <component
            :is="item.icon"
            :class="[
              'shrink-0 opacity-60 group-hover:opacity-100 group-[.active]:opacity-100 transition-[opacity,transform] duration-150 ease-out',
              effectiveCollapsed ? 'w-5 h-5' : 'w-4 h-4 mr-3',
            ]"
            aria-hidden="true"
          />
          <span v-if="!effectiveCollapsed" class="whitespace-nowrap font-medium">{{ item.label }}</span>
        </router-link>
      </template>
    </nav>

    <div
      :class="[
        'p-4 border-t border-border-subtle w-full flex flex-col',
        effectiveCollapsed ? 'items-center px-2' : '',
      ]"
    >
      <div :class="['flex items-center gap-3', effectiveCollapsed ? 'justify-center p-0 mb-2' : 'px-3 py-2']">
        <div
          class="w-9 h-9 shrink-0 rounded-full bg-linear-to-br from-primary-light to-info flex items-center justify-center text-sm font-bold text-white"
          aria-hidden="true"
        >
          {{ user?.name?.charAt(0)?.toUpperCase() }}
        </div>
        <div v-if="!effectiveCollapsed" class="flex-1 min-w-0">
          <p class="text-sm font-bold text-ink truncate">{{ user?.name }}</p>
          <p class="text-[11px] capitalize font-semibold text-ink-secondary">{{ roleLabel }}</p>
        </div>
      </div>
      <button
        type="button"
        @click="$emit('logout')"
        :title="effectiveCollapsed ? 'Keluar' : undefined"
        :aria-label="effectiveCollapsed ? 'Keluar' : undefined"
        :class="[
          'sidebar-link group mt-1 min-h-11 !text-danger hover:!bg-danger-soft',
          effectiveCollapsed ? 'justify-center px-0 w-12 h-12 mx-auto rounded-xl' : 'w-full px-4',
        ]"
      >
        <LogOut
          :class="[
            'shrink-0 opacity-70 group-hover:opacity-100',
            effectiveCollapsed ? 'w-5 h-5' : 'w-4 h-4 mr-3',
          ]"
          aria-hidden="true"
        />
        <span v-if="!effectiveCollapsed" class="font-medium">Keluar</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  LayoutDashboard, Tags, DoorOpen, Sparkles, Globe,
  ClipboardList, KeyRound, CreditCard, Users, Paintbrush, UserCircle,
  BarChart3, Settings, LogOut, ChevronLeft, ChevronRight,
} from 'lucide-vue-next'
import LogoIcon from '../LogoIcon.vue'

const props = defineProps({
  user: { type: Object, default: null },
  userRole: { type: String, default: '' },
  isCollapsed: { type: Boolean, default: false },
  isMobileOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['logout', 'toggle', 'close-mobile'])

const isMobile = ref(false)
const asideEl = ref(null)
const effectiveCollapsed = computed(() => props.isCollapsed && !isMobile.value)

function updateViewport() {
  isMobile.value = window.innerWidth < 1024
}

function onKeydown(e) {
  if (e.key === 'Escape' && props.isMobileOpen) {
    emit('close-mobile')
    document.querySelector('[aria-label="Buka menu navigasi"]')?.focus()
  }
}

onMounted(() => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
  document.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateViewport)
  document.removeEventListener('keydown', onKeydown)
})

// focus first link when drawer opens
watch(
  () => props.isMobileOpen,
  async (open) => {
    if (open) {
      await nextTick()
      asideEl.value?.querySelector('a, button')?.focus()
    }
  }
)

const roleLabel = computed(() => {
  const labels = { admin: 'Administrator', receptionist: 'Resepsionis', housekeeper: 'Tata Graha' }
  return labels[props.userRole] || props.userRole
})

const menu = [
  {
    title: 'Utama',
    items: [
      { label: 'Dasbor', icon: LayoutDashboard, roles: ['admin', 'receptionist'], adminPath: '/admin/dashboard', receptionistPath: '/receptionist/dashboard' },
    ],
  },
  {
    title: 'Kamar',
    items: [
      { path: '/admin/room-types', label: 'Tipe Kamar', icon: Tags, roles: ['admin'] },
      { label: 'Ketersediaan', icon: DoorOpen, roles: ['admin', 'receptionist'], adminPath: '/admin/rooms', receptionistPath: '/receptionist/rooms' },
      { path: '/admin/facilities', label: 'Fasilitas', icon: Sparkles, roles: ['admin'] },
    ],
  },
  {
    title: 'Operasional',
    items: [
      { label: 'Booking Online', icon: Globe, roles: ['admin', 'receptionist'], adminPath: '/admin/booking-online', receptionistPath: '/receptionist/booking-online' },
      { label: 'Reservasi', icon: ClipboardList, roles: ['admin', 'receptionist'], adminPath: '/admin/reservations', receptionistPath: '/receptionist/reservations' },
      { label: 'Check In/Out', icon: KeyRound, roles: ['admin', 'receptionist'], adminPath: '/admin/check-in-out', receptionistPath: '/receptionist/check-in-out' },
    ],
  },
  {
    title: 'Keuangan',
    items: [
      { label: 'Pembayaran', icon: CreditCard, roles: ['admin', 'receptionist'], adminPath: '/admin/payments', receptionistPath: '/receptionist/payments' },
    ],
  },
  {
    title: 'Manajemen',
    items: [
      { label: 'Tamu', icon: Users, roles: ['admin', 'receptionist'], adminPath: '/admin/guests', receptionistPath: '/receptionist/guests' },
      { label: 'Tata Graha', icon: Paintbrush, roles: ['admin', 'housekeeper'], adminPath: '/admin/housekeeping', housekeeperPath: '/housekeeper/housekeeping' },
      { path: '/admin/staff', label: 'Staf', icon: UserCircle, roles: ['admin'] },
      { path: '/admin/reports', label: 'Laporan', icon: BarChart3, roles: ['admin'] },
      { path: '/admin/settings', label: 'Pengaturan', icon: Settings, roles: ['admin'] },
    ],
  },
]

const filteredMenu = computed(() => {
  return menu
    .map((section) => ({
      ...section,
      items: section.items
        .filter((item) => item.roles.includes(props.userRole))
        .map((item) => {
          if (item.path) return item
          let path = item.adminPath
          if (props.userRole === 'receptionist' && item.receptionistPath) path = item.receptionistPath
          if (props.userRole === 'housekeeper' && item.housekeeperPath) path = item.housekeeperPath
          return { ...item, path }
        }),
    }))
    .filter((section) => section.items.length > 0)
})
</script>
