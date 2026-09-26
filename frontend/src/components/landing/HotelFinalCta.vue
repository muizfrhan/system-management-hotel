<template>
  <section
    ref="sectionEl"
    :class="['relative isolate overflow-hidden bg-luxury-ink py-28 text-white sm:py-36 lg:py-44 reveal', { 'is-visible': visible }]"
  >
    <div v-if="image" class="absolute inset-0 -z-20 overflow-hidden">
      <HotelVisual :src="image" alt="" class="h-full w-full" @error="emit('error', $event)" />
    </div>
    <div class="absolute inset-0 -z-10 bg-luxury-ink/78"></div>
    <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(20,24,22,.88),rgba(20,24,22,.45))]"></div>
    <div class="absolute inset-y-0 right-[12%] -z-10 hidden w-px bg-white/10 lg:block" aria-hidden="true"></div>
    <div class="absolute inset-y-0 right-[7%] -z-10 hidden w-px bg-white/10 lg:block" aria-hidden="true"></div>

    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="max-w-4xl">
        <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold-light">Sampai jumpa di {{ hotelName }}</p>
        <h2 class="mt-5 font-display text-[clamp(3.2rem,7.5vw,7.2rem)] leading-[0.9] tracking-[-0.055em] text-balance">
          Penginapan berikutnya dimulai di sini.
        </h2>
        <p class="mt-7 max-w-xl text-sm leading-7 text-white/70 sm:text-base sm:leading-8">
          Pilih tanggal, bandingkan kamar, dan kirim permintaan reservasi Anda secara online.
        </p>
        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
          <router-link to="/rooms" class="btn btn-lg w-full rounded-full bg-luxury-cream px-7 text-luxury-ink hover:bg-white focus-visible:outline-luxury-gold-light sm:w-auto">Pesan Kamar</router-link>
          <router-link to="/track" class="btn btn-lg w-full rounded-full border border-white/35 bg-white/5 px-7 text-white hover:border-white hover:bg-white hover:text-luxury-ink focus-visible:outline-luxury-gold-light sm:w-auto">Lacak Reservasi</router-link>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import HotelVisual from './HotelVisual.vue'
import { useReveal } from '../../composables/useReveal'

defineProps({
  hotelName: { type: String, required: true },
  image: { type: String, default: null },
})

const emit = defineEmits(['error'])
const { el: sectionEl, visible } = useReveal()
</script>
