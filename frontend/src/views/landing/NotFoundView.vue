<template>
  <PublicLayout>
    <section class="public-container public-container-narrow public-inner-page text-center">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Halaman tidak ditemukan' }]" />
      <p class="font-display text-7xl leading-none text-luxury-gold/30 sm:text-8xl" aria-hidden="true">404</p>
      <h1 class="public-page-title mt-4">Halaman tidak ditemukan</h1>
      <p class="text-slate-600 mb-8 text-sm sm:text-base">
        Halaman yang Anda tuju tidak ada atau telah dipindahkan.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <router-link to="/" class="btn btn-primary rounded-full btn-lg px-8">Kembali ke Beranda</router-link>
        <router-link to="/rooms" class="btn btn-outline rounded-full btn-lg px-8">Lihat Kamar</router-link>
      </div>
    </section>

  </PublicLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'

onMounted(() => {
  document.title = 'Halaman Tidak Ditemukan'
})
</script>
