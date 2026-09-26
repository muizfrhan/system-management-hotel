<template>
  <article class="group">
    <router-link
      :to="roomLink"
      class="relative block overflow-hidden bg-luxury-stone/30"
      :class="featured ? 'aspect-[5/4] sm:aspect-[16/11]' : 'aspect-[16/10]'"
      tabindex="-1"
      aria-hidden="true"
    >
      <RoomImage :src="roomImageUrl(room)" :alt="room.name" class="h-full w-full" />
      <div class="absolute inset-0 bg-gradient-to-t from-luxury-ink/55 via-transparent to-transparent" aria-hidden="true"></div>
      <span v-if="room.capacity" class="absolute bottom-4 left-4 bg-white/95 px-3 py-1.5 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-luxury-ink sm:bottom-5 sm:left-5">
        {{ room.capacity }} tamu
      </span>
    </router-link>

    <div class="pt-5 sm:pt-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h3 :class="['wrap-break-word font-display leading-none tracking-[-0.025em] text-luxury-ink', featured ? 'text-4xl sm:text-5xl' : 'text-3xl sm:text-4xl']">
            <router-link :to="roomLink" class="transition-colors hover:text-luxury-gold">
              {{ room.name }}
            </router-link>
          </h3>
          <p v-if="metadata.length" class="mt-2 text-xs uppercase tracking-[0.13em] text-hotel-muted">
            {{ metadata.join(' · ') }}
          </p>
        </div>
        <p v-if="hasPrice" class="shrink-0 sm:text-right">
          <span class="block text-[0.62rem] font-bold uppercase tracking-[0.18em] text-hotel-muted">Mulai dari</span>
          <span class="mt-1 block text-lg font-bold text-luxury-ink">{{ formatCurrency(room.base_price) }}</span>
          <span class="text-xs text-hotel-muted">/ malam</span>
        </p>
        <p v-else class="shrink-0 text-xs font-semibold text-hotel-muted sm:text-right">Harga belum tersedia</p>
      </div>

      <p v-if="room.description" class="mt-4 max-w-2xl text-sm leading-7 text-hotel-muted line-clamp-2">
        {{ room.description }}
      </p>

      <ul v-if="facilities.length" class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-medium text-luxury-ink/80" aria-label="Fasilitas utama">
        <li v-for="facility in facilities.slice(0, 3)" :key="facility.name" class="inline-flex items-center gap-1.5">
          <FacilityIcon :name="facility" class="h-4 w-4 shrink-0" />
          {{ facility.name }}
        </li>
      </ul>

      <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-luxury-stone/40 pt-5">
        <router-link :to="roomLink" class="btn rounded-full bg-luxury-ink px-5 text-white hover:bg-luxury-charcoal">
          Cek Kamar
          <ArrowUpRight class="h-4 w-4" aria-hidden="true" />
        </router-link>
        <span class="text-xs text-hotel-muted">Detail dan ketersediaan tampil di halaman kamar.</span>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowUpRight } from 'lucide-vue-next'
import RoomImage from './RoomImage.vue'
import FacilityIcon from '../icons/FacilityIcon.vue'
import { formatCurrency } from '../../utils/dates'
import { roomImageUrl } from '../../utils/roomImages'
import { roomFacilities } from '../../utils/roomFacilities'

const props = defineProps({
  room: { type: Object, required: true },
  stayQuery: { type: Object, default: () => ({}) },
  featured: { type: Boolean, default: false },
})

const metadata = computed(() => [props.room.bed_type, props.room.size].filter(Boolean))
const facilities = computed(() => roomFacilities(props.room))
const hasPrice = computed(() => Number(props.room.base_price) > 0)
const roomLink = computed(() => ({ path: `/rooms/${props.room.id}`, query: { ...props.stayQuery } }))
</script>
