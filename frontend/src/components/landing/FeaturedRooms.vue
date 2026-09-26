<template>
  <section
    id="kamar"
    ref="sectionEl"
    :class="['scroll-mt-28 bg-white py-24 sm:py-32 lg:py-40 reveal', { 'is-visible': visible }]"
    :aria-busy="loading"
  >
    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="grid gap-6 border-b border-luxury-stone/45 pb-9 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-8">
          <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Pilihan Kamar</p>
          <h2 class="mt-4 max-w-3xl font-display text-[clamp(2.8rem,5.5vw,5.5rem)] leading-[0.96] tracking-[-0.045em] text-luxury-ink text-balance">
            Setiap ruang punya karakternya.
          </h2>
          <p class="mt-5 max-w-2xl text-sm leading-7 text-hotel-muted sm:text-base">
            Bandingkan tipe kamar yang tersedia, termasuk kapasitas, fasilitas, dan harga per malam.
          </p>
        </div>
        <div class="flex items-end justify-between gap-5 lg:col-span-4 lg:justify-end">
          <p class="text-xs uppercase tracking-[0.16em] text-hotel-muted">
            {{ rooms.length }} pilihan ditampilkan
          </p>
          <router-link to="/rooms" class="group inline-flex min-h-11 items-center gap-2 text-sm font-bold text-luxury-ink">
            Lihat semua
            <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
          </router-link>
        </div>
      </div>

      <div v-if="loading" class="mt-12 grid gap-10 lg:grid-cols-12" role="status">
        <span class="sr-only">Memuat pilihan kamar.</span>
        <div class="lg:col-span-7">
          <div class="skeleton aspect-[16/11] w-full"></div>
          <div class="skeleton mt-6 h-12 w-2/3"></div>
          <div class="skeleton mt-4 h-4 w-1/2"></div>
        </div>
        <div class="grid gap-10 lg:col-span-5">
          <div v-for="index in 2" :key="index">
            <div class="skeleton aspect-[16/10] w-full"></div>
            <div class="skeleton mt-5 h-8 w-1/2"></div>
          </div>
        </div>
      </div>

      <AlertBox v-else-if="error" variant="error" title="Kamar belum dapat dimuat" class="mt-10">
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
          <span>Periksa koneksi Anda lalu coba kembali.</span>
          <button type="button" class="btn w-max rounded-full border border-luxury-stone px-5 text-luxury-ink" @click="$emit('retry')">Coba Lagi</button>
        </div>
      </AlertBox>

      <EmptyState
        v-else-if="rooms.length === 0"
        title="Belum ada tipe kamar"
        description="Pilihan kamar akan tampil di sini setelah tersedia."
        class="mt-10"
      >
        <template #action>
          <router-link to="/rooms" class="btn rounded-full bg-luxury-ink px-6 text-white">Buka Halaman Kamar</router-link>
        </template>
      </EmptyState>

      <div v-else class="mt-12 grid gap-16 lg:grid-cols-12 lg:gap-10">
        <div v-if="primaryRoom" class="lg:col-span-7">
          <FeaturedRoomCard :room="primaryRoom" :stay-query="stayQuery" featured />
        </div>
        <div v-if="secondaryRooms.length" class="space-y-14 lg:col-span-5 lg:space-y-16 lg:pl-6">
          <FeaturedRoomCard v-for="room in secondaryRooms" :key="room.id" :room="room" :stay-query="stayQuery" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowRight } from 'lucide-vue-next'
import FeaturedRoomCard from './FeaturedRoomCard.vue'
import AlertBox from '../ui/AlertBox.vue'
import EmptyState from '../ui/EmptyState.vue'
import { useReveal } from '../../composables/useReveal'

const props = defineProps({
  rooms: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: [Object, Error], default: null },
  stayQuery: { type: Object, default: () => ({}) },
})

defineEmits(['retry'])

const { el: sectionEl, visible } = useReveal()
const primaryRoom = computed(() => props.rooms[0] || null)
const secondaryRooms = computed(() => props.rooms.slice(1, 3))
</script>
