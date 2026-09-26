<template>
  <PublicLayout>
    <section class="public-container public-container-narrow public-inner-page flex min-h-[70vh] items-center justify-center text-center">
    <div class="w-full max-w-lg text-center" aria-labelledby="forbidden-title">
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-card bg-danger-soft text-danger" aria-hidden="true">
        <ShieldX class="h-6 w-6" />
      </div>
      <p class="mt-6 text-sm font-bold tracking-[0.2em] text-danger" aria-hidden="true">403</p>
      <h1 id="forbidden-title" class="mt-2 text-2xl font-bold tracking-tight text-ink sm:text-3xl">
        Akses ditolak
      </h1>
      <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-ink-secondary sm:text-base">
        Akun Anda tidak memiliki izin untuk membuka halaman tersebut.
      </p>
      <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
        <router-link :to="primaryDestination" class="btn btn-primary btn-lg">
          {{ primaryLabel }}
        </router-link>
        <router-link to="/" class="btn btn-outline btn-lg">
          Beranda
        </router-link>
      </div>
    </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { ShieldX } from 'lucide-vue-next'
import PublicLayout from '../components/layout/PublicLayout.vue'
import { useAuthStore } from '../stores/authStore'

const roleHomeRoutes = {
  admin: '/admin/dashboard',
  receptionist: '/receptionist/dashboard',
  housekeeper: '/housekeeper/housekeeping',
}

const auth = useAuthStore()
const primaryDestination = computed(() => {
  if (!auth.isAuthenticated) return '/login'
  if (auth.userRole === 'admin') return roleHomeRoutes.admin
  if (auth.userRole === 'receptionist') return roleHomeRoutes.receptionist
  if (auth.userRole === 'housekeeper') return roleHomeRoutes.housekeeper
  return '/'
})
const primaryLabel = computed(() => (auth.isAuthenticated ? 'Ke dasbor' : 'Masuk'))

onMounted(() => {
  document.title = 'Akses Ditolak'
})
</script>
