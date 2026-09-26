<template>
  <PublicLayout>
    <section class="public-container public-inner-page">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Kamar' }]" />
      <PublicPageHeader
        eyebrow="Pilihan Kamar"
        title="Pilih Kamar Anda"
        description="Lihat detail tipe kamar, fasilitas, harga, dan ketersediaan untuk tanggal pilihan Anda."
      />

      <!-- Date availability filter -->
      <div class="public-panel mb-6 p-4 sm:p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 items-end">
          <div class="space-y-1.5">
            <label for="list-checkin" class="text-sm font-semibold text-slate-900">Check-in</label>
            <input id="list-checkin" v-model="searchForm.check_in" type="date" :min="today" class="input-field rounded-2xl" />
          </div>
          <div class="space-y-1.5">
            <label for="list-checkout" class="text-sm font-semibold text-slate-900">Check-out</label>
            <input id="list-checkout" v-model="searchForm.check_out" type="date" :min="searchForm.check_in || today" class="input-field rounded-2xl" />
          </div>
          <button type="button" class="btn btn-primary rounded-2xl w-full py-3.5" :disabled="!hasDates || fetching" :aria-busy="fetching || undefined" @click="applySearch">
            <LoadingSpinner v-if="fetching" size="sm" />
            <span>{{ fetching ? 'Mengecek...' : 'Cek Ketersediaan' }}</span>
          </button>
        </div>
        <p v-if="hasDates" class="text-xs text-slate-500 mt-3 text-center">
          Menampilkan ketersediaan untuk <strong class="text-slate-700">{{ formatDate(searchForm.check_in) }}</strong> → <strong class="text-slate-700">{{ formatDate(searchForm.check_out) }}</strong>
          <button type="button" class="ml-2 inline-flex items-center min-h-10 py-1.5 text-primary underline underline-offset-2" @click="clearDates">Hapus filter</button>
        </p>
      </div>

      <!-- Client-side filters -->
      <div class="max-w-3xl mx-auto mb-10 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="relative">
          <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" aria-hidden="true" />
          <label for="list-search" class="sr-only">Cari kamar</label>
          <input id="list-search" v-model="searchQuery" type="search" placeholder="Cari tipe kamar..." class="input-field rounded-2xl pl-9" />
        </div>
        <div>
          <label for="list-guests" class="sr-only">Jumlah tamu</label>
          <select id="list-guests" v-model.number="guestFilter" class="select-field rounded-2xl">
            <option :value="0">Semua kapasitas</option>
            <option v-for="n in maxCapacity" :key="'g-' + n" :value="n">{{ n }}+ tamu</option>
          </select>
        </div>
        <div>
          <label for="list-sort" class="sr-only">Urutkan</label>
          <select id="list-sort" v-model="sortBy" class="select-field rounded-2xl">
            <option value="default">Urutan default</option>
            <option value="price-asc">Harga terendah</option>
            <option value="price-desc">Harga tertinggi</option>
            <option value="name">Nama (A–Z)</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="fetching && roomTypes.length === 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8" aria-hidden="true">
        <div v-for="n in 6" :key="'sk-' + n" class="border border-slate-100 rounded-3xl p-4">
          <div class="skeleton h-52 rounded-2xl mb-4"></div>
          <div class="skeleton h-5 w-2/3 mb-3"></div>
          <div class="skeleton h-4 w-1/2 mb-3"></div>
          <div class="skeleton h-4 w-full mb-2"></div>
          <div class="skeleton h-4 w-3/4"></div>
        </div>
      </div>

      <!-- Error -->
      <AlertBox v-else-if="fetchError" variant="error" title="Gagal memuat kamar">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-1">
          <span>Periksa koneksi Anda lalu coba lagi.</span>
          <button type="button" class="btn btn-outline rounded-full px-5 w-max" @click="fetchRooms">
            Coba Lagi
          </button>
        </div>
      </AlertBox>

      <!-- Empty -->
      <EmptyState
        v-else-if="filteredRooms.length === 0"
        title="Tidak ada kamar yang cocok"
        :description="roomTypes.length === 0 ? 'Tipe kamar akan tampil di sini setelah tersedia.' : 'Coba ubah kata kunci atau filter Anda.'"
        :icon="BedDouble"
      >
        <template v-if="hasActiveFilters" #action>
          <button type="button" class="btn btn-primary rounded-full px-6" @click="resetFilters">Reset Filter</button>
        </template>
      </EmptyState>

      <!-- List -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
        <article v-for="rt in filteredRooms" :key="rt.id" class="public-room-card group flex flex-col h-full">
          <router-link :to="{ path: `/rooms/${rt.id}`, query: forwardedQuery }" class="relative h-48 sm:h-56 md:h-64 w-full rounded-2xl sm:rounded-3xl overflow-hidden mb-4 shrink-0 block" tabindex="-1" aria-hidden="true">
            <RoomImage :src="roomImageUrl(rt)" :alt="rt.name" class="h-full" />
            <span class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold text-slate-700">
              {{ rt.capacity }} Tamu
            </span>
          </router-link>

          <div class="space-y-2 flex-1 flex flex-col">
            <div class="flex items-center gap-2 text-sm">
              <span
                class="badge"
                :class="(rt.available_rooms_count || 0) > 0 ? 'badge-available' : 'badge-cancelled'"
              >
                <span v-if="(rt.available_rooms_count || 0) > 0" class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                <XCircle v-else class="w-3 h-3" aria-hidden="true" />
                {{ (rt.available_rooms_count || 0) > 0 ? `${rt.available_rooms_count} tersedia` : 'Tidak tersedia' }}
              </span>
            </div>

            <h2 class="text-xl font-bold text-slate-900">
              <router-link :to="{ path: `/rooms/${rt.id}`, query: forwardedQuery }" class="hover:text-primary transition-colors">{{ rt.name }}</router-link>
            </h2>
            <p class="text-sm text-slate-500 mb-1">{{ rt.bed_type || '-' }} · {{ rt.size || '-' }}</p>
            <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed">{{ rt.description }}</p>

            <div class="flex flex-wrap gap-1.5 py-3">
              <span v-for="f in (rt.facilities || []).slice(0, 4)" :key="f.id" class="inline-flex items-center gap-1.5 text-xs bg-slate-50 border border-slate-100 text-slate-600 px-2 py-1 rounded-lg">
                <FacilityIcon :name="f" class="h-3.5 w-3.5 shrink-0" />
                {{ f.name }}
              </span>
            </div>

            <router-link
              :to="{ path: `/rooms/${rt.id}`, query: forwardedQuery }"
              class="text-[13px] font-bold text-primary hover:text-primary-hover w-max min-h-10 inline-flex items-center underline underline-offset-4 decoration-primary/25 hover:decoration-primary transition-[color,decoration-color] duration-150 mb-4 mt-1"
            >
              Rincian selengkapnya ↗
            </router-link>

            <div class="flex items-end justify-between gap-3 pt-4 mt-auto border-t border-slate-100">
              <div class="min-w-0">
                <p class="text-xs text-slate-500 mb-0.5">Mulai dari</p>
                <p class="text-base sm:text-lg font-bold text-slate-900 truncate">{{ formatCurrency(rt.base_price) }}</p>
                <p class="text-xs text-slate-500">/ malam</p>
              </div>
              <router-link
                v-if="(rt.available_rooms_count || 0) > 0"
                :to="{ path: `/booking/${rt.id}`, query: forwardedQuery }"
                class="btn btn-primary rounded-full px-5 text-sm shrink-0"
              >
                Pesan sekarang
              </router-link>
              <button
                v-else
                type="button"
                class="btn btn-outline rounded-full px-5 text-sm shrink-0"
                disabled
                aria-disabled="true"
              >
                Penuh
              </button>
            </div>
          </div>
        </article>
      </div>

      <p v-if="!fetching && !fetchError && filteredRooms.length > 0" class="text-center text-xs text-slate-400 mt-8">
        Menampilkan {{ filteredRooms.length }} dari {{ roomTypes.length }} tipe kamar
      </p>
    </section>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { BedDouble, Search, XCircle } from 'lucide-vue-next'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'
import PublicPageHeader from '../../components/ui/PublicPageHeader.vue'
import RoomImage from '../../components/landing/RoomImage.vue'
import FacilityIcon from '../../components/icons/FacilityIcon.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue'
import api from '../../services/api'
import { useLandingData } from '../../composables/useLandingData'
import { roomImageUrl } from '../../utils/roomImages'
import { localToday, nightsBetween, formatDateID } from '../../utils/dates'

const route = useRoute()
const router = useRouter()
const { setting, load } = useLandingData()

const roomTypes = ref([])
const fetching = ref(false)
const fetchError = ref(false)
let fetchSeq = 0

const today = localToday()
const searchForm = ref({ check_in: '', check_out: '' })
const searchQuery = ref('')
const guestFilter = ref(0)
const sortBy = ref('default')

const hasDates = computed(
  () => !!(searchForm.value.check_in && searchForm.value.check_out) && nightsBetween(searchForm.value.check_in, searchForm.value.check_out) > 0
)

const forwardedQuery = computed(() => {
  const q = {}
  if (hasDates.value) {
    q.check_in = searchForm.value.check_in
    q.check_out = searchForm.value.check_out
  }
  if (route.query.guests) q.guests = route.query.guests
  return q
})

const maxCapacity = computed(() => Math.max(0, ...roomTypes.value.map((r) => r.capacity || 0)))

const hasActiveFilters = computed(
  () => !!(searchQuery.value.trim() || guestFilter.value || route.query.type)
)

const filteredRooms = computed(() => {
  let list = [...roomTypes.value]

  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(
      (r) =>
        (r.name || '').toLowerCase().includes(q) ||
        (r.description || '').toLowerCase().includes(q) ||
        (r.bed_type || '').toLowerCase().includes(q)
    )
  }

  if (guestFilter.value) {
    list = list.filter((r) => (r.capacity || 0) >= guestFilter.value)
  }

  if (route.query.type) {
    list = list.filter((r) => r.id === Number(route.query.type))
  }

  if (sortBy.value === 'price-asc') list.sort((a, b) => Number(a.base_price) - Number(b.base_price))
  else if (sortBy.value === 'price-desc') list.sort((a, b) => Number(b.base_price) - Number(a.base_price))
  else if (sortBy.value === 'name') list.sort((a, b) => (a.name || '').localeCompare(b.name || ''))

  return list
})

async function fetchRooms() {
  const seq = ++fetchSeq
  fetching.value = true
  fetchError.value = false
  try {
    const params = {}
    if (hasDates.value) {
      params.check_in = searchForm.value.check_in
      params.check_out = searchForm.value.check_out
    }
    const res = await api.get('/guest/room-types', { params })
    if (seq !== fetchSeq) return
    roomTypes.value = res.data || []
  } catch {
    if (seq !== fetchSeq) return
    fetchError.value = true
  } finally {
    if (seq === fetchSeq) fetching.value = false
  }
}

function applySearch() {
  if (!hasDates.value) return
  router
    .replace({
      path: '/rooms',
      query: { ...route.query, check_in: searchForm.value.check_in, check_out: searchForm.value.check_out },
    })
    .catch(() => {})
}

function clearDates() {
  const query = { ...route.query }
  delete query.check_in
  delete query.check_out
  router.replace({ path: '/rooms', query }).catch(() => {})
}

function resetFilters() {
  searchQuery.value = ''
  guestFilter.value = 0
  sortBy.value = 'default'
  if (route.query.type) {
    const query = {}
    if (hasDates.value) {
      query.check_in = searchForm.value.check_in
      query.check_out = searchForm.value.check_out
    }
    router.replace({ path: '/rooms', query }).catch(() => {})
  }
}

function formatDate(d) {
  return formatDateID(d, { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}

watch(
  () => route.query,
  () => {
    searchForm.value.check_in = route.query.check_in || ''
    searchForm.value.check_out = route.query.check_out || ''
    guestFilter.value = Math.max(0, Number.parseInt(route.query.guests, 10) || 0)
    fetchRooms()
  }
)

onUnmounted(() => {
  fetchSeq += 1
})

onMounted(() => {
  if (route.query.check_in) searchForm.value.check_in = route.query.check_in
  if (route.query.check_out) searchForm.value.check_out = route.query.check_out
  guestFilter.value = Math.max(0, Number.parseInt(route.query.guests, 10) || 0)
  fetchRooms()
  load()
    .catch(() => {})
    .finally(() => {
      document.title = `Pilihan Kamar — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
    })
})
</script>
