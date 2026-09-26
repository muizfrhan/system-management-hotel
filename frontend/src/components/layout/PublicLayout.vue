<template>
  <div id="top" data-surface="public" class="hotel-landing min-h-dvh bg-luxury-cream">
    <a href="#main-content" class="sr-only z-[100] bg-luxury-ink px-4 py-3 text-sm font-bold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
      Lewati ke konten utama
    </a>

    <SiteNavbar />

    <main id="main-content" tabindex="-1" class="min-h-[60vh] focus:outline-none">
      <slot />
    </main>

    <SiteFooter :class="footerClass" />
  </div>
</template>

<script setup>
import SiteNavbar from '../landing/SiteNavbar.vue'
import SiteFooter from '../landing/SiteFooter.vue'

defineProps({
  footerClass: { type: String, default: '' },
})
</script>
