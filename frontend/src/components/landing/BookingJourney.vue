<template>
  <section
    id="alur-booking"
    ref="sectionEl"
    :class="['bg-white py-24 sm:py-32 lg:py-36 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
        <div class="lg:col-span-4">
          <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Perjalanan Anda</p>
          <h2 class="mt-4 font-display text-[clamp(2.8rem,5vw,5rem)] leading-[0.98] tracking-[-0.045em] text-luxury-ink text-balance">
            Perjalanan pemesanan yang jelas.
          </h2>
          <p class="mt-6 max-w-md text-sm leading-7 text-hotel-muted">
            Dari mencari kamar hingga memantau status reservasi, setiap langkah tetap terhubung dalam satu alur.
          </p>
        </div>

        <div class="border-t border-luxury-stone/50 lg:col-span-8">
          <div v-for="(step, index) in steps" :key="step.title" class="grid gap-5 border-b border-luxury-stone/50 py-7 sm:grid-cols-[5rem_1fr_auto] sm:items-center sm:gap-7">
            <p class="font-display text-3xl text-luxury-gold">{{ String(index + 1).padStart(2, '0') }}</p>
            <div>
              <h3 class="font-display text-3xl leading-none text-luxury-ink sm:text-4xl">{{ step.title }}</h3>
              <p class="mt-3 max-w-xl text-sm leading-7 text-hotel-muted">{{ step.description }}</p>
            </div>
            <router-link :to="step.to" class="group inline-flex min-h-11 items-center gap-2 text-sm font-bold text-luxury-ink">
              {{ step.cta }}
              <ArrowUpRight class="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true" />
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowUpRight } from 'lucide-vue-next'
import { useReveal } from '../../composables/useReveal'

const props = defineProps({
  stayQuery: { type: Object, default: () => ({}) },
})

const steps = computed(() => [
  {
    title: 'Cari sesuai tanggal',
    description: 'Masukkan check-in, check-out, jumlah tamu, dan tipe kamar yang diinginkan.',
    cta: 'Cari kamar',
    to: { path: '/rooms', query: { ...props.stayQuery } },
  },
  {
    title: 'Pilih dan pesan',
    description: 'Tinjau detail kamar, isi data tamu, lalu kirim permintaan reservasi.',
    cta: 'Lihat pilihan',
    to: { path: '/rooms', query: { ...props.stayQuery } },
  },
  {
    title: 'Lacak reservasi',
    description: 'Gunakan kode reservasi untuk melihat status pemesanan yang sudah dibuat.',
    cta: 'Lacak',
    to: '/track',
  },
])

const { el: sectionEl, visible } = useReveal()
</script>
