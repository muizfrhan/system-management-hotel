<template>
  <section
    id="about"
    ref="sectionEl"
    :class="['scroll-mt-28 bg-luxury-cream py-24 sm:py-32 lg:py-40 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto grid max-w-public gap-14 px-5 sm:px-8 lg:grid-cols-12 lg:items-center lg:gap-8 lg:px-12">
      <div class="relative lg:col-span-7 lg:pr-16">
        <HotelVisual
          :src="image"
          :alt="imageRoomName ? `Kamar ${imageRoomName} di ${hotelName}` : `Interior ${hotelName}`"
          class="aspect-[4/5] w-full sm:aspect-[5/4] lg:aspect-[4/5]"
          @error="emit('error', $event)"
        />
        <div class="absolute -bottom-5 left-4 right-4 bg-luxury-ink px-5 py-4 text-white sm:-bottom-7 sm:left-auto sm:right-8 sm:max-w-[24rem] sm:px-7 sm:py-5">
          <p class="max-w-[16rem] truncate text-[0.62rem] font-bold uppercase tracking-[0.24em] text-luxury-gold-light">{{ hotelName }}</p>
          <p class="mt-1 max-w-[14rem] font-display text-xl leading-tight sm:text-2xl">Ruang, sebelum check-in.</p>
        </div>
      </div>

      <div class="lg:col-span-5 lg:-ml-10 lg:pt-16">
        <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Tentang {{ hotelName }}</p>
        <h2 class="mt-5 max-w-xl font-display text-[clamp(2.7rem,5.4vw,5.2rem)] leading-[0.98] tracking-[-0.045em] text-luxury-ink text-balance">
          Lebih dari sekadar tempat bermalam.
        </h2>
        <p class="mt-7 max-w-lg text-base leading-8 text-hotel-muted">
          Setiap tipe kamar di {{ hotelName }} memiliki karakter, kapasitas, dan fasilitasnya sendiri. Nilai setiap detail sebelum memilih ruang yang paling sesuai untuk perjalanan Anda.
        </p>
        <p v-if="tagline" class="mt-4 max-w-lg font-display text-2xl italic leading-9 text-luxury-ink/80">
          {{ tagline }}
        </p>

        <dl class="mt-10 border-t border-luxury-stone/50">
          <div class="grid gap-1 border-b border-luxury-stone/50 py-4 sm:grid-cols-[10rem_1fr] sm:gap-5">
            <dt class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-hotel-muted">Alamat</dt>
            <dd class="wrap-break-word text-sm leading-6 text-luxury-ink">{{ address || 'Alamat hotel belum tersedia.' }}</dd>
          </div>
          <div class="grid gap-1 border-b border-luxury-stone/50 py-4 sm:grid-cols-[10rem_1fr] sm:gap-5">
            <dt class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-hotel-muted">Booking</dt>
            <dd class="text-sm leading-6 text-luxury-ink">Cek ketersediaan dan pesan secara online.</dd>
          </div>
          <div class="grid gap-1 border-b border-luxury-stone/50 py-4 sm:grid-cols-[10rem_1fr] sm:gap-5">
            <dt class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-hotel-muted">Reservasi</dt>
            <dd class="text-sm leading-6 text-luxury-ink">Lacak status pemesanan dengan kode reservasi.</dd>
          </div>
        </dl>
      </div>
    </div>
  </section>
</template>

<script setup>
import HotelVisual from './HotelVisual.vue'
import { useReveal } from '../../composables/useReveal'

defineProps({
  hotelName: { type: String, required: true },
  tagline: { type: String, default: '' },
  address: { type: String, default: '' },
  image: { type: String, default: null },
  imageRoomName: { type: String, default: '' },
})

const emit = defineEmits(['error'])
const { el: sectionEl, visible } = useReveal()
</script>
