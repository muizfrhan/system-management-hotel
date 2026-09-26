<template>
  <PublicLayout footer-class="pb-24 sm:pb-0">
    <section class="public-container public-inner-page">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Kamar', to: '/rooms' }, { label: detail?.room_type?.name || 'Detail Kamar' }]" />
      <PublicPageHeader
        eyebrow="Detail Kamar"
        :title="detail?.room_type?.name || 'Detail Kamar'"
        :description="detail?.room_type?.description || 'Lihat informasi kamar, fasilitas, dan ketersediaan sebelum melanjutkan pemesanan.'"
      />
      <!-- Loading -->
      <div v-if="fetching" class="space-y-8" aria-hidden="true">
        <div class="skeleton h-[240px] sm:h-[320px] md:h-[400px] w-full rounded-[1.5rem] md:rounded-[2.5rem]"></div>
        <div class="skeleton h-8 w-2/3"></div>
        <div class="skeleton h-4 w-full"></div>
        <div class="skeleton h-4 w-5/6"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="skeleton h-20 rounded-2xl"></div>
          <div class="skeleton h-20 rounded-2xl"></div>
          <div class="skeleton h-20 rounded-2xl"></div>
        </div>
      </div>

      <!-- Error -->
      <AlertBox v-else-if="fetchError" variant="error" :title="notFound ? 'Kamar tidak ditemukan' : 'Gagal memuat detail kamar'">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-1">
          <span>{{ notFound ? 'Tipe kamar ini mungkin sudah tidak tersedia.' : 'Periksa koneksi Anda lalu coba lagi.' }}</span>
          <button v-if="!notFound" type="button" class="btn btn-outline rounded-full px-5 w-max" @click="fetchDetail">
            Coba Lagi
          </button>
          <router-link v-else to="/rooms" class="btn btn-outline rounded-full px-5 w-max">Lihat Semua Kamar</router-link>
        </div>
      </AlertBox>

      <!-- Content -->
      <div v-else-if="detail" class="grid gap-8 lg:grid-cols-12 lg:gap-10">
        <!-- Gallery -->
        <div class="relative h-[240px] sm:h-[320px] md:h-[400px] w-full rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden bg-slate-100 lg:col-span-7">
          <button
            type="button"
            class="absolute inset-0 h-full w-full cursor-zoom-in"
            :aria-label="`Perbesar foto ${detail.room_type?.name || 'kamar'}`"
            @click="openLightbox"
          >
            <Transition name="slide-fade" mode="out-in">
              <div :key="activeImg" class="absolute inset-0">
                <RoomImage
                  :src="images[activeImg] || null"
                  :alt="detail.room_type?.name || ''"
                  img-class=""
                  eager
                  class="h-full"
                />
              </div>
            </Transition>
          </button>
          <div class="absolute top-4 left-4 sm:top-6 sm:left-6 bg-white/90 backdrop-blur-sm px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold text-slate-700 pointer-events-none">
            {{ detail.room_type?.capacity }} Tamu
          </div>
          <span
            class="absolute top-4 right-4 sm:top-6 sm:right-6 hidden items-center gap-2 rounded-full bg-black/55 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm sm:inline-flex"
          >
            <Maximize2 class="h-4 w-4" aria-hidden="true" />
            Perbesar
          </span>

          <div v-if="images.length > 1" class="absolute bottom-0 left-0 right-0 h-1 bg-white/25">
            <div
              :key="slideProgressKey"
              class="h-full origin-left bg-white"
              :style="{ animation: `slide-progress ${slideInterval}ms linear forwards` }"
            ></div>
          </div>
        </div>

        <div class="space-y-6 lg:col-span-5">
          <div>
            <p class="public-eyebrow">Ruang Anda</p>
            <h2 class="mt-3 font-display text-3xl leading-tight text-luxury-ink sm:text-4xl">{{ detail.room_type?.name }}</h2>
            <p class="mt-3 text-sm leading-7 text-hotel-muted">{{ detail.room_type?.description }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="public-subpanel p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Kapasitas</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.capacity }} tamu</p>
            </div>
            <div class="public-subpanel p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Kasur</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.bed_type || '-' }}</p>
            </div>
            <div class="public-subpanel p-4 text-center">
              <p class="text-sm font-semibold text-slate-900">Ukuran</p>
              <p class="text-sm text-slate-500 mt-1">{{ detail.room_type?.size || '-' }}</p>
            </div>
          </div>

          <div v-if="Object.keys(categorizedFacilities).length > 0">
             <h2 class="mb-6 font-display text-3xl leading-none tracking-[-0.02em] text-luxury-ink">Fasilitas Lengkap</h2>
            <div class="space-y-6">
              <div v-for="(facilities, category) in categorizedFacilities" :key="category">
                <h3 class="font-bold text-slate-800 mb-3 text-base capitalize">{{ category }}</h3>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <li v-for="f in facilities" :key="f.id" class="flex items-center gap-2">
                    <FacilityIcon :name="f" class="w-5 h-5 shrink-0" />
                    <span class="text-sm font-medium text-slate-600">{{ f.name }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Desktop / tablet inline price bar -->
           <div class="public-panel hidden sm:flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5">
            <div>
              <p class="text-xs text-slate-500 mb-0.5">Mulai dari</p>
              <p class="text-2xl sm:text-3xl font-bold text-slate-900">{{ formatCurrency(detail.room_type?.base_price) }}</p>
              <p class="text-sm text-slate-500 mt-1">
                / malam ·
                <span :class="(detail.available_rooms || 0) > 0 ? 'text-success font-semibold' : 'text-danger font-semibold'">
                  {{ (detail.available_rooms || 0) > 0 ? `${detail.available_rooms} tersedia` : 'Tidak tersedia' }}
                </span>
              </p>
            </div>
            <router-link
              v-if="(detail.available_rooms || 0) > 0"
              :to="{ path: `/booking/${$route.params.id}`, query: forwardedQuery }"
              class="btn btn-primary rounded-full px-8 py-3.5 text-base"
            >
              Pesan Sekarang
            </router-link>
            <button v-else type="button" class="btn btn-outline rounded-full px-8 py-3.5 text-base" disabled aria-disabled="true">
              Tidak Tersedia
            </button>
          </div>
        </div>
      </div>

      <AlertBox v-else variant="info" title="Detail kamar belum tersedia" class="mt-8">
        Kembali ke daftar kamar untuk memilih tipe lainnya.
      </AlertBox>
    </section>

    <!-- Sticky mobile booking bar -->
    <div
      v-if="detail && !fetching && !fetchError"
      class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 px-4 py-3 shadow-[0_-10px_30px_rgba(0,0,0,0.06)]"
    >
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500">Mulai dari</p>
          <p class="text-base leading-tight font-bold text-slate-900">{{ formatCurrency(detail.room_type?.base_price) }}<span class="text-xs font-medium text-slate-500">/malam</span></p>
        </div>
        <router-link
          v-if="(detail.available_rooms || 0) > 0"
          :to="{ path: `/booking/${$route.params.id}`, query: forwardedQuery }"
          class="btn btn-primary rounded-full px-6 shrink-0"
        >
          Pesan Sekarang
        </router-link>
        <button v-else type="button" class="btn btn-outline rounded-full px-6 shrink-0" disabled aria-disabled="true">
          Penuh
        </button>
      </div>
    </div>

    </PublicLayout>

    <RoomGalleryLightbox
      v-model="lightboxOpen"
      :items="lightboxItems"
      :start-index="activeImg"
      :title="detail?.room_type?.name || 'Detail Kamar'"
    />
  </template>


<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Maximize2 } from 'lucide-vue-next'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'
import PublicPageHeader from '../../components/ui/PublicPageHeader.vue'
import RoomImage from '../../components/landing/RoomImage.vue'
import RoomGalleryLightbox from '../../components/landing/RoomGalleryLightbox.vue'
import FacilityIcon from '../../components/icons/FacilityIcon.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import api from '../../services/api'
import { useLandingData } from '../../composables/useLandingData'
import { useAutoSlide } from '../../composables/useAutoSlide'
import { resolveRoomImages } from '../../utils/roomImages'

const route = useRoute()
const { setting, load } = useLandingData()

const detail = ref(null)
const fetching = ref(true)
const fetchError = ref(false)
const notFound = ref(false)
const lightboxOpen = ref(false)
let fetchSeq = 0

const images = computed(() => resolveRoomImages(detail.value?.room_type))

const {
  index: activeImg,
  progressKey: slideProgressKey,
  interval: slideInterval,
  goTo: goToImg,
} = useAutoSlide(images, { interval: 5000 })

const lightboxItems = computed(() =>
  images.value.map((src) => ({ src, alt: detail.value?.room_type?.name || 'Kamar' }))
)

function openLightbox() {
  lightboxOpen.value = true
}

const forwardedQuery = computed(() => {
  const q = {}
  if (route.query.check_in) q.check_in = route.query.check_in
  if (route.query.check_out) q.check_out = route.query.check_out
  if (route.query.guests) q.guests = route.query.guests
  return q
})

const categorizedFacilities = computed(() => {
  const rt = detail.value?.room_type
  if (!rt) return {}
  const groups = {}

  if (rt.facilities) {
    rt.facilities.forEach((f) => {
      const cat = f.category || 'Lainnya'
      if (!groups[cat]) groups[cat] = []
      groups[cat].push(f)
    })
  }

  if (rt.custom_facilities) {
    for (const [cat, text] of Object.entries(rt.custom_facilities)) {
      if (typeof text !== 'string' || !text.trim()) continue
      if (!groups[cat]) groups[cat] = []
      text
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
        .forEach((item, idx) => groups[cat].push({ id: `custom-${cat}-${idx}`, name: item }))
    }
  }

  return groups
})

async function fetchDetail() {
  const seq = ++fetchSeq
  fetching.value = true
  fetchError.value = false
  notFound.value = false
  goToImg(0)
  try {
    const params = {}
    if (route.query.check_in) params.check_in = route.query.check_in
    if (route.query.check_out) params.check_out = route.query.check_out
    const res = await api.get(`/guest/room-types/${route.params.id}`, { params, timeout: 15000 })
    if (seq !== fetchSeq) return
    detail.value = res.data
    const name = detail.value?.room_type?.name || 'Kamar'
    document.title = `${name} — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
  } catch (e) {
    if (seq !== fetchSeq) return
    fetchError.value = true
    notFound.value = e.response?.status === 404
  } finally {
    if (seq === fetchSeq) fetching.value = false
  }
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}

onUnmounted(() => {
  fetchSeq += 1
})

onMounted(() => {
  fetchDetail()
  load()
    .catch(() => {})
    .finally(() => {
      if (detail.value) {
        const name = detail.value.room_type?.name || 'Kamar'
        document.title = `${name} — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
      }
    })
})

watch(
  () => route.params.id,
  () => fetchDetail()
)
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 450ms ease-out, transform 450ms ease-out;
}

.slide-fade-enter-from {
  opacity: 0;
  transform: scale(1.03);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: scale(0.99);
}

@keyframes slide-progress {
  from {
    transform: scaleX(0);
  }

  to {
    transform: scaleX(1);
  }
}
</style>
