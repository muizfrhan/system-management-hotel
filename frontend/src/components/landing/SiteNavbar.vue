<template>
  <header ref="headerEl" class="fixed inset-x-0 top-0 z-50">
    <div v-if="showUtilityBar" class="bg-luxury-ink text-white">
      <div class="mx-auto flex h-9 max-w-public items-center justify-between px-5 text-[0.62rem] font-semibold uppercase tracking-[0.17em] sm:px-8 lg:px-12">
        <p class="text-white/65">Reservasi online</p>
        <div class="flex items-center gap-5 sm:gap-8">
          <a v-if="phone" :href="`tel:${phone}`" class="hidden text-white/70 transition-colors hover:text-white sm:inline">{{ phone }}</a>
          <router-link to="/track" class="inline-flex min-h-9 items-center text-white/85 transition-colors hover:text-white"><span class="sm:hidden">Lacak</span><span class="hidden sm:inline">Lacak Reservasi</span></router-link>
        </div>
      </div>
    </div>

    <nav
      :class="[
        'border-b backdrop-blur-xl transition-[background-color,border-color,box-shadow,color] duration-300',
        isOverlay
          ? 'border-white/15 bg-luxury-ink/35 text-white shadow-none'
          : 'border-luxury-stone/35 bg-white/95 text-luxury-ink shadow-[0_12px_35px_-30px_rgba(25,30,27,.65)]',
        { 'is-overlay': isOverlay },
      ]"
      aria-label="Navigasi utama"
    >
      <div class="mx-auto flex h-[4.75rem] max-w-public items-center justify-between px-5 sm:px-8 lg:px-12">
        <router-link :to="{ path: '/', hash: '#top' }" class="flex min-w-0 items-center gap-3" :aria-label="`${hotelName} — Beranda`">
          <img v-if="logoUrl && !logoFailed" :src="logoUrl" alt="" class="h-9 w-9 shrink-0 object-contain" @error="logoFailed = true" />
          <span v-else class="shrink-0"><LogoIcon :size="34" tone="gold" /></span>
          <span class="truncate font-display text-xl tracking-[-0.02em] sm:text-2xl">{{ hotelName }}</span>
        </router-link>

        <div class="hidden items-center gap-7 lg:flex xl:gap-9">
          <router-link :to="{ path: '/', hash: '#top' }" :class="linkClass('/')">Beranda</router-link>
          <router-link to="/rooms" :class="linkClass('/rooms')">Kamar</router-link>
          <router-link v-if="hasFacilities" :to="{ path: '/', hash: '#fasilitas' }" class="nav-link">Fasilitas</router-link>
          <router-link :to="{ path: '/', hash: '#about' }" class="nav-link">Tentang</router-link>
          <router-link v-if="hasLocation" :to="{ path: '/', hash: '#location' }" class="nav-link">Lokasi</router-link>
          <router-link to="/track" :class="linkClass('/track')">Lacak Reservasi</router-link>
        </div>

        <router-link
          to="/rooms"
          :class="[
            'btn hidden min-h-11 rounded-full px-6 lg:inline-flex',
            isOverlay ? 'bg-white text-luxury-ink hover:bg-luxury-cream' : 'bg-luxury-ink text-white hover:bg-luxury-charcoal',
          ]"
        >
          Pesan Kamar
        </router-link>

        <button
          ref="menuButton"
          type="button"
          :class="[
            'icon-btn -mr-2 h-11 w-11 lg:!hidden',
            isOverlay ? 'text-white hover:bg-white/10' : 'text-luxury-ink hover:bg-luxury-sand/50',
          ]"
          :aria-expanded="mobileMenuOpen"
          aria-controls="mobile-navigation"
          :aria-label="mobileMenuOpen ? 'Tutup menu' : 'Buka menu'"
          @click="toggleMobileMenu"
        >
          <X v-if="mobileMenuOpen" class="h-6 w-6" aria-hidden="true" />
          <Menu v-else class="h-6 w-6" aria-hidden="true" />
        </button>
      </div>
    </nav>

    <Transition name="dropdown">
      <div
        v-if="mobileMenuOpen"
        id="mobile-navigation"
        ref="menuPanel"
        class="max-h-[calc(100dvh-4.75rem)] overflow-y-auto overscroll-contain border-b border-luxury-stone/35 bg-white px-5 py-4 text-luxury-ink shadow-[0_24px_50px_-30px_rgba(25,30,27,.65)] lg:hidden"
      >
        <nav class="mx-auto flex max-w-public flex-col" aria-label="Navigasi seluler">
          <router-link :to="{ path: '/', hash: '#top' }" class="mobile-link" @click="closeMobileMenu">Beranda</router-link>
          <router-link to="/rooms" class="mobile-link" @click="closeMobileMenu">Kamar</router-link>
          <router-link v-if="hasFacilities" :to="{ path: '/', hash: '#fasilitas' }" class="mobile-link" @click="closeMobileMenu">Fasilitas</router-link>
          <router-link :to="{ path: '/', hash: '#about' }" class="mobile-link" @click="closeMobileMenu">Tentang</router-link>
          <router-link v-if="hasLocation" :to="{ path: '/', hash: '#location' }" class="mobile-link" @click="closeMobileMenu">Lokasi</router-link>
          <router-link to="/track" class="mobile-link" @click="closeMobileMenu">Lacak Reservasi</router-link>
          <router-link to="/rooms" class="btn mt-3 flex min-h-12 items-center justify-center rounded-full bg-luxury-ink px-5 text-sm font-bold text-white" @click="closeMobileMenu">Pesan Kamar</router-link>
        </nav>
      </div>
    </Transition>
  </header>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Menu, X } from 'lucide-vue-next'
import LogoIcon from '../LogoIcon.vue'
import { useLandingData } from '../../composables/useLandingData'
import { publicAssetUrl } from '../../utils/publicAssets'
import { aggregateRoomFacilities } from '../../utils/roomFacilities'

const route = useRoute()
const { setting, roomTypes, load } = useLandingData()
const mobileMenuOpen = ref(false)
const isScrolled = ref(false)
const logoFailed = ref(false)
const headerEl = ref(null)
const menuButton = ref(null)
const menuPanel = ref(null)

const hotelName = computed(() => setting.value?.hotel_name || 'Lokanata Hotel')
const phone = computed(() => setting.value?.phone || '')
const logoUrl = computed(() => publicAssetUrl(setting.value?.logo))
const hasFacilities = computed(() => aggregateRoomFacilities(roomTypes.value).length > 0)
const hasLocation = computed(() => !!setting.value?.address)
const showUtilityBar = computed(() => route.path === '/' && !isScrolled.value)
const isOverlay = computed(() => route.path === '/' && !isScrolled.value && !mobileMenuOpen.value)

function linkClass(path) {
  const active = route.path === path || route.path.startsWith(`${path}/`)
  return ['nav-link', active ? `font-bold ${isOverlay.value ? 'text-luxury-gold-light' : 'text-luxury-gold'}` : '']
}

function updateScrollState() {
  const nextScrolled = window.scrollY > 24
  isScrolled.value = nextScrolled
  if (nextScrolled && mobileMenuOpen.value) closeMobileMenu()
}

function onResize() {
  if (window.innerWidth >= 1024) closeMobileMenu()
}

function onPointerDown(event) {
  if (mobileMenuOpen.value && !headerEl.value?.contains(event.target)) closeMobileMenu()
}

async function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value
  if (mobileMenuOpen.value) {
    await nextTick()
    menuPanel.value?.querySelector('a')?.focus()
  }
}

function closeMobileMenu({ focusButton = false } = {}) {
  mobileMenuOpen.value = false
  if (focusButton) nextTick(() => menuButton.value?.focus())
}

function onKeydown(event) {
  if (!mobileMenuOpen.value || document.querySelector('[role="dialog"]')) return
  if (event.key === 'Escape') closeMobileMenu({ focusButton: true })
  if (event.key !== 'Tab') return

  const focusable = [...(menuPanel.value?.querySelectorAll('a[href]') || [])]
  if (!focusable.length) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

watch(
  () => logoUrl.value,
  () => {
    logoFailed.value = false
  }
)

watch(
  () => route.fullPath,
  () => closeMobileMenu()
)

onMounted(() => {
  updateScrollState()
  window.addEventListener('scroll', updateScrollState, { passive: true })
  window.addEventListener('resize', onResize)
  document.addEventListener('keydown', onKeydown)
  document.addEventListener('pointerdown', onPointerDown)
  load().catch(() => {})
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateScrollState)
  window.removeEventListener('resize', onResize)
  document.removeEventListener('keydown', onKeydown)
  document.removeEventListener('pointerdown', onPointerDown)
})
</script>

<style scoped>
.nav-link {
  display: inline-flex;
  min-height: 2.75rem;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  transition: color var(--duration-standard) var(--ease-out);
}

.nav-link:hover {
  color: var(--color-luxury-gold);
}

.is-overlay .nav-link:hover {
  color: var(--color-luxury-gold-light);
}

.mobile-link {
  display: flex;
  min-height: 3rem;
  align-items: center;
  border-bottom: 1px solid color-mix(in srgb, var(--color-luxury-stone) 45%, transparent);
  font-family: var(--font-display);
  font-size: 1.35rem;
  line-height: 1;
}
</style>
