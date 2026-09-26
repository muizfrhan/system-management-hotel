<template>
  <section class="relative isolate flex min-h-[44rem] items-end overflow-hidden bg-luxury-ink lg:min-h-[min(58rem,100svh)]" :aria-busy="loading">
    <span v-if="loading" class="sr-only" role="status">Memuat informasi hotel.</span>
    <div class="absolute inset-0 overflow-hidden">
      <HotelVisual
        :src="image"
        :alt="imageRoomName ? `Kamar ${imageRoomName} di ${hotelName}` : `Tampilan ${hotelName}`"
        eager
        class="hotel-hero-media h-full w-full"
        img-class="object-center"
        @error="emit('error', $event)"
      />
    </div>
    <div v-if="!imageOnly" class="absolute inset-0 bg-[linear-gradient(90deg,rgba(15,18,16,.82)_0%,rgba(15,18,16,.5)_48%,rgba(15,18,16,.22)_100%)]" aria-hidden="true"></div>
    <div v-if="!imageOnly" class="absolute inset-0 bg-[linear-gradient(0deg,rgba(15,18,16,.72)_0%,transparent_52%,rgba(15,18,16,.45)_100%)]" aria-hidden="true"></div>

    <div v-if="!imageOnly" class="relative z-10 mx-auto w-full max-w-public px-5 pb-36 pt-40 sm:px-8 sm:pb-44 sm:pt-44 lg:px-12 lg:pb-48">
      <div class="hotel-hero-copy max-w-5xl text-white">
        <div class="mb-6 flex items-center gap-4 sm:mb-8">
          <span class="h-px w-10 bg-luxury-gold sm:w-14" aria-hidden="true"></span>
          <p class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/80 sm:text-xs">
            {{ hotelName }}
          </p>
        </div>
        <h1 class="max-w-4xl font-display text-[clamp(3.4rem,10vw,8.2rem)] font-normal leading-[0.88] tracking-[-0.055em] text-balance">
          Ruang untuk <br />
          <span class="italic text-luxury-sand">kembali.</span>
        </h1>
        <p class="mt-7 max-w-xl text-sm leading-7 text-white/75 sm:mt-9 sm:text-base sm:leading-8">
          Jelajahi pilihan kamar, lihat detail fasilitas, lalu pesan penginapan Anda secara online.
        </p>
        <p v-if="tagline" class="mt-4 text-xs uppercase tracking-[0.2em] text-luxury-gold-light">
          {{ tagline }}
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:items-center">
          <router-link to="/rooms" class="btn btn-lg w-full rounded-full border-white bg-white px-7 text-luxury-ink hover:border-luxury-cream hover:bg-luxury-cream sm:w-auto">
            Pesan Kamar
          </router-link>
          <router-link :to="{ path: '/', hash: '#kamar' }" class="btn btn-lg w-full rounded-full border border-white/35 bg-white/5 px-7 text-white backdrop-blur-sm hover:border-white hover:bg-white hover:text-luxury-ink sm:w-auto">
            Jelajahi Kamar
          </router-link>
        </div>
      </div>

      <div class="mt-12 flex flex-col gap-5 border-t border-white/20 pt-5 text-xs text-white/70 sm:mt-16 sm:flex-row sm:items-center sm:justify-between">
        <p v-if="address" class="max-w-xl leading-5">{{ address }}</p>
        <p v-else class="max-w-xl leading-5">Pilih tanggal untuk melihat ketersediaan kamar.</p>
        <p v-if="image && imageRoomName" class="shrink-0 uppercase tracking-[0.18em] text-white/70">Ruang pilihan · {{ imageRoomName }}</p>
      </div>
    </div>

    <router-link v-if="!imageOnly" :to="{ path: '/', hash: '#booking' }" class="absolute bottom-8 right-5 z-10 hidden min-h-11 items-center gap-3 text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-white/70 transition-colors hover:text-white sm:flex lg:right-12">
      Cari kamar
      <span class="relative block h-10 w-px overflow-hidden bg-white/30" aria-hidden="true">
        <span class="absolute inset-x-0 top-0 h-4 bg-white"></span>
      </span>
    </router-link>
  </section>
</template>

<script setup>
import HotelVisual from './HotelVisual.vue'

defineProps({
  hotelName: { type: String, required: true },
  tagline: { type: String, default: '' },
  address: { type: String, default: '' },
  image: { type: String, default: null },
  imageRoomName: { type: String, default: '' },
  imageOnly: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['error'])
</script>

<style scoped>
.hotel-hero-media {
  animation: hotel-media-settle 1100ms var(--ease-out) both;
}

.hotel-hero-copy > * {
  animation: hotel-copy-reveal 720ms var(--ease-out) both;
}

.hotel-hero-copy > :nth-child(2) {
  animation-delay: 80ms;
}

.hotel-hero-copy > :nth-child(3) {
  animation-delay: 140ms;
}

.hotel-hero-copy > :nth-child(4) {
  animation-delay: 200ms;
}

.hotel-hero-copy > :nth-child(5) {
  animation-delay: 260ms;
}

@keyframes hotel-media-settle {
  from {
    opacity: 0.72;
    transform: scale(1.035);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes hotel-copy-reveal {
  from {
    opacity: 0;
    transform: translateY(18px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hotel-hero-media,
  .hotel-hero-copy > * {
    animation: none;
  }
}
</style>
