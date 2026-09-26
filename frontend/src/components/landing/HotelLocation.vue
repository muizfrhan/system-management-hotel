<template>
  <section
    id="location"
    ref="sectionEl"
    :class="['scroll-mt-28 bg-luxury-sand/45 py-24 sm:py-32 lg:py-36 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto grid max-w-public gap-12 px-5 sm:px-8 lg:grid-cols-12 lg:items-center lg:gap-16 lg:px-12">
      <div class="lg:col-span-7">
        <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Lokasi</p>
        <h2 class="mt-4 max-w-3xl font-display text-[clamp(3rem,6vw,6rem)] leading-[0.92] tracking-[-0.05em] text-luxury-ink text-balance">
          Temukan kami.
        </h2>
        <p v-if="address" class="mt-7 max-w-2xl font-display text-2xl leading-10 text-luxury-ink/85 sm:text-3xl sm:leading-[1.35]">
          {{ address }}
        </p>
        <div class="mt-8 flex flex-col gap-4 border-t border-luxury-stone/55 pt-6 sm:flex-row sm:flex-wrap sm:gap-x-8">
          <a v-if="phone" :href="`tel:${phone}`" class="group inline-flex min-h-11 items-center gap-3 text-sm font-semibold text-luxury-ink">
            <Phone class="h-4 w-4 text-luxury-gold" aria-hidden="true" />
            {{ phone }}
          </a>
          <a v-if="email" :href="`mailto:${email}`" class="group inline-flex min-h-11 items-center gap-3 break-all text-sm font-semibold text-luxury-ink">
            <Mail class="h-4 w-4 shrink-0 text-luxury-gold" aria-hidden="true" />
            {{ email }}
          </a>
        </div>
      </div>

      <div class="relative min-h-[22rem] overflow-hidden bg-luxury-ink p-8 text-white sm:min-h-[28rem] sm:p-10 lg:col-span-5" aria-hidden="true">
        <div class="absolute inset-x-0 top-1/2 h-px bg-white/10"></div>
        <div class="absolute inset-y-0 left-1/2 w-px bg-white/10"></div>
        <div class="absolute left-8 top-8 h-24 w-24 rounded-full border border-luxury-gold/35"></div>
        <div class="absolute left-14 top-14 h-12 w-12 rounded-full border border-luxury-gold/25"></div>
        <MapPin class="absolute left-1/2 top-1/2 h-10 w-10 -translate-x-1/2 -translate-y-1/2 text-luxury-gold-light" />
        <div class="absolute bottom-8 left-8 right-8 flex items-end justify-between gap-4 sm:bottom-10 sm:left-10 sm:right-10">
          <div>
            <p class="text-[0.62rem] font-bold uppercase tracking-[0.24em] text-white/50">Hotel</p>
            <p class="mt-2 max-w-[16rem] font-display text-2xl leading-tight text-white">{{ hotelName }}</p>
          </div>
          <p class="font-display text-6xl text-white/10">L</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { Mail, MapPin, Phone } from 'lucide-vue-next'
import { useReveal } from '../../composables/useReveal'

defineProps({
  hotelName: { type: String, required: true },
  address: { type: String, default: '' },
  phone: { type: String, default: '' },
  email: { type: String, default: '' },
})

const { el: sectionEl, visible } = useReveal()
</script>
