<template>
  <section
    ref="sectionEl"
    :class="['overflow-hidden bg-luxury-ink py-24 text-white sm:py-32 lg:py-40 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="grid gap-8 border-b border-white/15 pb-10 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-8">
          <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold-light">Ruang Terpilih</p>
          <h2 class="mt-4 max-w-3xl font-display text-[clamp(2.8rem,5.5vw,5.5rem)] leading-[0.96] tracking-[-0.045em] text-balance">
            Lihat ruang sebelum Anda datang.
          </h2>
        </div>
        <p class="max-w-md text-sm leading-7 text-white/60 lg:col-span-4">
          Bandingkan karakter setiap tipe kamar sebelum menentukan ruang untuk perjalanan Anda.
        </p>
      </div>

      <article v-for="(room, index) in rooms" :key="room.id" class="grid gap-8 border-b border-white/15 py-12 lg:grid-cols-12 lg:items-center lg:gap-16 lg:py-16">
        <div :class="['lg:col-span-7', index % 2 ? 'lg:order-2' : '']">
          <HotelVisual
            :src="roomImageUrl(room)"
            :alt="`Kamar ${room.name} di ${hotelName}`"
            class="aspect-[4/3] w-full"
            img-class="object-center"
          />
        </div>
        <div :class="['lg:col-span-5', index % 2 ? 'lg:order-1' : '']">
          <p class="text-xs uppercase tracking-[0.2em] text-luxury-gold-light">{{ String(index + 1).padStart(2, '0') }} / {{ room.name }}</p>
          <h3 class="mt-4 font-display text-4xl leading-none tracking-[-0.035em] sm:text-5xl">{{ room.name }}</h3>
          <p v-if="room.description" class="mt-5 max-w-lg text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
            {{ room.description }}
          </p>
          <dl class="mt-7 flex flex-wrap gap-x-8 gap-y-3 text-xs uppercase tracking-[0.14em] text-white/50">
            <div v-if="room.capacity" class="flex gap-2"><dt>Kapasitas</dt><dd class="text-white">{{ room.capacity }} tamu</dd></div>
            <div v-if="room.bed_type" class="flex gap-2"><dt>Kasur</dt><dd class="text-white">{{ room.bed_type }}</dd></div>
            <div v-if="room.size" class="flex gap-2"><dt>Ukuran</dt><dd class="text-white">{{ room.size }}</dd></div>
          </dl>
          <router-link :to="{ path: `/rooms/${room.id}`, query: stayQuery }" class="group mt-8 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-white">
            Lihat detail kamar
            <ArrowUpRight class="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true" />
          </router-link>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { ArrowUpRight } from 'lucide-vue-next'
import HotelVisual from './HotelVisual.vue'
import { useReveal } from '../../composables/useReveal'
import { roomImageUrl } from '../../utils/roomImages'

defineProps({
  rooms: { type: Array, default: () => [] },
  hotelName: { type: String, required: true },
  stayQuery: { type: Object, default: () => ({}) },
})

const { el: sectionEl, visible } = useReveal()
</script>
