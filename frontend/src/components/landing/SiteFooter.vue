<template>
  <footer id="kontak" class="scroll-mt-24 bg-luxury-charcoal text-white">
    <div class="mx-auto max-w-public px-5 py-16 sm:px-8 sm:py-20 lg:px-12">
      <div class="grid gap-12 border-b border-white/15 pb-14 sm:grid-cols-2 lg:grid-cols-12 lg:gap-10">
        <div class="sm:col-span-2 lg:col-span-5">
          <router-link :to="{ path: '/', hash: '#top' }" class="inline-flex max-w-full items-center gap-3" :aria-label="`${hotelName} — Beranda`">
            <img v-if="logoUrl && !logoFailed" :src="logoUrl" alt="" class="h-10 w-10 shrink-0 object-contain" @error="logoFailed = true" />
            <span v-else class="shrink-0"><LogoIcon :size="40" tone="gold" /></span>
            <span class="wrap-break-word font-display text-2xl tracking-[-0.025em] sm:text-3xl">{{ hotelName }}</span>
          </router-link>
          <p class="mt-6 max-w-md text-sm leading-7 text-white/65">
            {{ tagline || 'Lihat pilihan kamar, cek ketersediaan, dan pesan penginapan secara online.' }}
          </p>
        </div>

        <div class="lg:col-span-2">
          <h2 class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-luxury-gold-light">Jelajahi</h2>
          <ul class="mt-5 space-y-3">
            <li><router-link to="/" class="footer-link">Beranda</router-link></li>
            <li><router-link to="/rooms" class="footer-link">Kamar</router-link></li>
            <li><router-link :to="{ path: '/', hash: '#about' }" class="footer-link">Tentang</router-link></li>
            <li v-if="setting?.address"><router-link :to="{ path: '/', hash: '#location' }" class="footer-link">Lokasi</router-link></li>
          </ul>
        </div>

        <div class="lg:col-span-2">
          <h2 class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-luxury-gold-light">Reservasi</h2>
          <ul class="mt-5 space-y-3">
            <li><router-link to="/rooms" class="footer-link">Pesan Kamar</router-link></li>
            <li><router-link to="/track" class="footer-link">Lacak Reservasi</router-link></li>
            <li><router-link to="/login" class="footer-link">Portal Staf</router-link></li>
          </ul>
        </div>

        <div class="lg:col-span-3">
          <h2 class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-luxury-gold-light">Kontak</h2>
          <ul v-if="hasContact" class="mt-5 space-y-4">
            <li v-if="setting?.address" class="flex items-start gap-3 text-sm leading-6 text-white/65">
              <MapPin class="mt-1 h-4 w-4 shrink-0 text-luxury-gold-light" aria-hidden="true" />
              <span>{{ setting.address }}</span>
            </li>
            <li v-if="setting?.phone" class="flex items-center gap-3 text-sm text-white/65">
              <Phone class="h-4 w-4 shrink-0 text-luxury-gold-light" aria-hidden="true" />
              <a :href="`tel:${setting.phone}`" class="footer-link">{{ setting.phone }}</a>
            </li>
            <li v-if="setting?.email" class="flex items-center gap-3 text-sm text-white/65">
              <Mail class="h-4 w-4 shrink-0 text-luxury-gold-light" aria-hidden="true" />
              <a :href="`mailto:${setting.email}`" class="footer-link break-all">{{ setting.email }}</a>
            </li>
          </ul>
          <p v-else class="mt-5 text-sm leading-6 text-white/60">Informasi kontak belum tersedia.</p>
        </div>
      </div>

      <div class="flex flex-col gap-5 pt-7 text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ currentYear }} {{ hotelName }}. Dibuat oleh Muhamad Farhan Muizaddin.</p>
        <router-link to="/rooms" class="inline-flex min-h-11 items-center gap-2 font-semibold text-white/65 transition-colors hover:text-white">
          Mulai pemesanan
          <ArrowUpRight class="h-4 w-4" aria-hidden="true" />
        </router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowUpRight, Mail, MapPin, Phone } from 'lucide-vue-next'
import LogoIcon from '../LogoIcon.vue'
import { useLandingData } from '../../composables/useLandingData'
import { publicAssetUrl } from '../../utils/publicAssets'

const { setting, load } = useLandingData()
const logoFailed = ref(false)
const hotelName = computed(() => setting.value?.hotel_name || 'Lokanata Hotel')
const tagline = computed(() => setting.value?.tagline || '')
const logoUrl = computed(() => publicAssetUrl(setting.value?.logo))
const hasContact = computed(() => !!(setting.value?.address || setting.value?.phone || setting.value?.email))
const currentYear = new Date().getFullYear()

watch(
  () => logoUrl.value,
  () => {
    logoFailed.value = false
  }
)

onMounted(() => {
  load().catch(() => {})
})
</script>

<style scoped>
.footer-link {
  display: inline-flex;
  min-height: 2rem;
  align-items: center;
  color: rgb(255 255 255 / 0.58);
  transition: color var(--duration-standard) var(--ease-out);
}

.footer-link:hover {
  color: white;
}
</style>
