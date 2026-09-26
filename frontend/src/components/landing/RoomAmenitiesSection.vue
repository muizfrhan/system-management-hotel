<template>
  <section
    id="fasilitas"
    ref="sectionEl"
    :class="['scroll-mt-28 bg-luxury-sand/45 py-24 sm:py-32 lg:py-36 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-4">
          <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Detail Kamar</p>
          <h2 class="mt-4 font-display text-[clamp(2.8rem,5vw,5rem)] leading-[0.98] tracking-[-0.045em] text-luxury-ink text-balance">
            Detail sebelum Anda memilih.
          </h2>
          <p class="mt-6 max-w-md text-sm leading-7 text-hotel-muted">
            Fasilitas di bawah mengikuti setiap tipe kamar. Periksa detail yang paling sesuai sebelum Anda memesan.
          </p>
        </div>

        <div class="border-t border-luxury-stone/55 lg:col-span-8">
          <div v-for="(facility, index) in groups" :key="facility.name" class="grid gap-3 border-b border-luxury-stone/55 py-6 sm:grid-cols-[3.5rem_1fr_1.2fr] sm:items-baseline sm:gap-6">
            <div class="flex items-center gap-3 sm:block">
              <p class="font-display text-2xl text-luxury-gold">{{ String(index + 1).padStart(2, '0') }}</p>
              <FacilityIcon :name="facility" class="hidden h-7 w-7 shrink-0 sm:mt-2 sm:block" />
            </div>
            <h3 class="font-display text-2xl text-luxury-ink sm:text-3xl">{{ facility.name }}</h3>
            <p class="text-xs leading-6 text-hotel-muted">
              <span class="font-bold uppercase tracking-[0.15em] text-luxury-ink/70">Tersedia pada:</span>
              {{ facility.roomNames.join(', ') || 'Tipe kamar tertentu' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useReveal } from '../../composables/useReveal'
import { aggregateRoomFacilities } from '../../utils/roomFacilities'
import FacilityIcon from '../icons/FacilityIcon.vue'

const props = defineProps({
  rooms: { type: Array, default: () => [] },
})

const { el: sectionEl, visible } = useReveal()
const groups = computed(() => aggregateRoomFacilities(props.rooms))
</script>
