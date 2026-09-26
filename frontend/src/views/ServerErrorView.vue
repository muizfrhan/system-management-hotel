<template>
  <PublicLayout>
    <section class="public-container public-container-narrow public-inner-page flex min-h-[70vh] items-center justify-center text-center">
    <div class="w-full max-w-lg text-center" aria-labelledby="server-error-title">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-card bg-danger-soft text-danger" aria-hidden="true">
        <WifiOff class="h-6 w-6" />
      </div>
      <p class="mt-6 text-sm font-bold tracking-[0.2em] text-danger" aria-hidden="true">500</p>
      <h1 id="server-error-title" class="mt-2 text-2xl font-bold tracking-tight text-ink sm:text-3xl">
        Layanan sedang tidak tersedia
      </h1>
      <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-ink-secondary sm:text-base">
        Permintaan tidak dapat diproses. Periksa koneksi Anda, lalu coba kembali beberapa saat lagi.
      </p>
      <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
        <button type="button" class="btn btn-primary btn-lg" @click="retry">
          Coba Lagi
        </button>
        <router-link to="/" class="btn btn-outline btn-lg">
          Beranda
        </router-link>
      </div>
    </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { WifiOff } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import PublicLayout from '../components/layout/PublicLayout.vue'

const router = useRouter()

function retry() {
  router.go(0)
}

onMounted(() => {
  document.title = 'Layanan Tidak Tersedia'
})
</script>
