<template>
  <!-- Overlay penutup untuk mobile -->
  <div
    v-if="isMobileOpen"
    class="fixed inset-0 bg-slate-900/40 z-40 lg:hidden"
    @click="$emit('close-mobile')"
  ></div>

  <aside
    :class="[
      'flex flex-col bg-white border-r border-slate-100 z-50 relative transition-all duration-300 ease-in-out',
      // Mobile: off-canvas drawer
      'fixed inset-y-0 left-0 max-lg:shadow-2xl',
      isMobileOpen ? 'translate-x-0' : '-translate-x-full',
      // Desktop: static sidebar
      'lg:translate-x-0 lg:static lg:z-20 lg:shadow-none',
      isCollapsed ? 'w-64 lg:w-20 lg:items-center' : 'w-64'
    ]"
  >
    <div :class="['py-5 border-b border-slate-100 flex items-center transition-all h-[76px]', isCollapsed ? 'justify-center px-0' : 'justify-between px-6']">
      <div v-if="!isCollapsed" class="flex items-center gap-3 whitespace-nowrap overflow-hidden transition-all duration-300">
        <LogoIcon :size="28" />
        <div>
          <h1 class="text-lg font-bold text-slate-900 tracking-tight">Lokanata</h1>
          <p class="text-[9px] mt-0.5 uppercase font-[800] tracking-[0.2em] text-slate-400">Hotel Admin</p>
        </div>
      </div>
      
      <!-- Toggle Button inline with Logo -->
      <button 
        @click="$emit('toggle')" 
        class="text-slate-400 hover:text-cyan-600 hover:bg-slate-50 p-1.5 rounded-lg transition-colors shrink-0"
        :title="isCollapsed ? 'Expand Menu' : 'Collapse Menu'"
      >
        <ChevronRight v-if="isCollapsed" class="w-5 h-5" />
        <ChevronLeft v-else class="w-5 h-5" />
      </button>
    </div>
    
    <nav class="flex-1 px-3 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden w-full custom-scrollbar">
      <template v-for="section in filteredMenu" :key="section.title">
        <p v-if="!isCollapsed" class="px-3 pt-6 pb-2 text-[10px] uppercase tracking-[0.15em] font-bold text-slate-400 first:pt-0 whitespace-nowrap">{{ section.title }}</p>
        <div v-else class="w-full h-px bg-slate-100 my-4 first:hidden"></div>

        <router-link
          v-for="item in section.items"
          :key="item.path"
          :to="item.path"
          :title="isCollapsed ? item.label : ''"
          :class="[
            'sidebar-link group relative', 
            isCollapsed ? 'justify-center px-0 w-12 h-12 mx-auto rounded-xl' : 'px-4 w-full'
          ]"
          active-class="active"
        >
          <component :is="item.icon" :class="['shrink-0 opacity-60 group-hover:opacity-100 group-[.active]:opacity-100 transition-all', isCollapsed ? 'w-5 h-5' : 'w-4 h-4 mr-3']" />
          <span v-if="!isCollapsed" class="whitespace-nowrap font-medium">{{ item.label }}</span>
        </router-link>
      </template>
    </nav>
    
    <div :class="['p-4 border-t border-slate-100 w-full flex flex-col', isCollapsed ? 'items-center px-2' : '']">
      <div :class="['flex items-center gap-3', isCollapsed ? 'justify-center p-0 mb-2' : 'px-3 py-2']">
        <div class="w-9 h-9 shrink-0 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-sm font-bold text-white shadow-md shadow-cyan-400/20">
          {{ user?.name?.charAt(0)?.toUpperCase() }}
        </div>
        <div v-if="!isCollapsed" class="flex-1 min-w-0">
          <p class="text-sm font-bold text-slate-900 truncate">{{ user?.name }}</p>
          <p class="text-[11px] capitalize font-semibold text-slate-500">{{ roleLabel }}</p>
        </div>
      </div>
      <button @click="$emit('logout')" :title="isCollapsed ? 'Keluar' : ''" :class="['sidebar-link group mt-1 !text-red-500 hover:!bg-red-50', isCollapsed ? 'justify-center px-0 w-12 h-12 mx-auto rounded-xl' : 'w-full px-4']">
        <LogOut :class="['shrink-0 opacity-70 group-hover:opacity-100', isCollapsed ? 'w-5 h-5' : 'w-4 h-4 mr-3']" />
        <span v-if="!isCollapsed" class="font-medium">Keluar</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import {
  LayoutDashboard, Tags, DoorOpen, Sparkles, Globe, 
  ClipboardList, KeyRound, CreditCard, Users, Paintbrush, UserCircle, 
  BarChart3, Settings, LogOut, ChevronLeft, ChevronRight
} from 'lucide-vue-next'
import LogoIcon from '../LogoIcon.vue'

const props = defineProps({
  user: { type: Object, default: null },
  userRole: { type: String, default: '' },
  isCollapsed: { type: Boolean, default: false },
  isMobileOpen: { type: Boolean, default: false },
})

defineEmits(['logout', 'toggle', 'close-mobile'])

const roleLabel = computed(() => {
  const labels = { admin: 'Administrator', receptionist: 'Resepsionis', housekeeper: 'Tata Graha' }
  return labels[props.userRole] || props.userRole
})

const menu = [
  {
    title: 'Utama',
    items: [
      { path: '/admin/dashboard', label: 'Dasbor', icon: LayoutDashboard, roles: ['admin'] },
    ],
  },
  {
    title: 'Kamar',
    items: [
      { path: '/admin/room-types', label: 'Tipe Kamar', icon: Tags, roles: ['admin'] },
      { path: '/admin/rooms', label: 'Daftar Kamar', icon: DoorOpen, roles: ['admin'] },
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
    .map(section => ({
      ...section,
      items: section.items
        .filter(item => item.roles.includes(props.userRole))
        .map(item => {
          if (item.path) return item
          let path = item.adminPath
          if (props.userRole === 'receptionist' && item.receptionistPath) path = item.receptionistPath
          if (props.userRole === 'housekeeper' && item.housekeeperPath) path = item.housekeeperPath
          return { ...item, path }
        }),
    }))
    .filter(section => section.items.length > 0)
})
</script>

<style scoped>
.sidebar-link {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  border-radius: 0.75rem;
  transition: all 0.2s;
}

.sidebar-link:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.sidebar-link.active {
  background-color: #ecfeff;
  color: #06b6d4;
  font-weight: 600;
}

.sidebar-link.active :deep(svg) {
  color: #06b6d4 !important;
  opacity: 1 !important;
}
</style>
