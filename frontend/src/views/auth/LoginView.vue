<template>
  <PublicLayout>
    <section class="public-container public-container-narrow public-inner-page relative flex min-h-[70vh] items-center justify-center overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-luxury-gold/5 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-luxury-sand/40 rounded-full blur-[100px] translate-y-1/2 -translate-x-1/3"></div>

    <div class="w-full max-w-md px-6 relative z-10">
      <div class="text-center mb-10">
        <div class="flex items-center justify-center gap-2 mb-6">
          <LogoIcon :size="40" tone="gold" />
          <span class="text-3xl font-bold tracking-tight text-slate-900">Lokanata</span>
        </div>
        <p class="text-sm text-slate-500 tracking-wide uppercase font-medium">Portal Manajemen Hotel</p>
      </div>

      <div class="public-panel p-5 sm:p-8">
        <h1 class="mb-1 font-display text-3xl leading-tight text-luxury-ink sm:text-4xl">Selamat Datang Kembali</h1>
        <p class="text-sm text-slate-500 mb-6">Silakan masukkan kredensial Anda untuk melanjutkan</p>

        <div v-if="displayError" class="mb-5 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600 flex items-start gap-3" role="alert">
          <AlertCircle class="w-5 h-5 flex-shrink-0 text-red-500" aria-hidden="true" />
          <span>{{ displayError }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-2">
            <label for="email" class="text-sm font-semibold text-slate-900">Email</label>
            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-100 transition">
              <Mail class="w-5 h-5 text-slate-400 shrink-0" />
              <input id="email" v-model="email" name="email" type="email" autocomplete="email" class="bg-transparent border-none outline-none w-full text-base sm:text-sm text-slate-700" placeholder="nama@email.com" required />
            </div>
          </div>
          <div class="space-y-2">
            <label for="password" class="text-sm font-semibold text-slate-900">Password</label>
            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-100 transition">
              <Lock class="w-5 h-5 text-slate-400 shrink-0" />
              <input id="password" v-model="password" name="password" type="password" autocomplete="current-password" class="bg-transparent border-none outline-none w-full text-base sm:text-sm text-slate-700" placeholder="••••••••" required />
            </div>
          </div>
           <BaseButton type="submit" block :loading="auth.loading" :disabled="auth.loading" class="mt-2 !min-h-12 !rounded-full !text-base">
             <span v-if="!auth.loading" class="flex items-center justify-center gap-2">
               Masuk Akses <ArrowRight class="w-4 h-4" />
             </span>
             <span v-else>Memproses...</span>
           </BaseButton>
        </form>

      </div>

      <p class="text-center mt-8 text-xs text-slate-400">
        Copyright &copy; {{ new Date().getFullYear() }} | Developed by Cybha.
      </p>
    </div>
    </section>
  </PublicLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { Mail, Lock, ArrowRight, AlertCircle } from 'lucide-vue-next'
import LogoIcon from '../../components/LogoIcon.vue'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import BaseButton from '../../components/ui/BaseButton.vue'

const roleHomeRoutes = {
  admin: '/admin/dashboard',
  receptionist: '/receptionist/dashboard',
  housekeeper: '/housekeeper/housekeeping',
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const email = ref('')
const password = ref('')

const displayError = computed(() => {
  if (auth.error) return auth.error
  if (route.query.reason === 'session-expired') return 'Sesi Anda telah berakhir. Silakan masuk kembali.'
  return ''
})

async function handleLogin() {
  try {
    await auth.login(email.value, password.value)
    await router.replace(getSafeRedirect(route.query.redirect) || getRoleHome(auth.userRole))
  } catch {}
}

function getRoleHome(role) {
  if (role === 'admin') return roleHomeRoutes.admin
  if (role === 'receptionist') return roleHomeRoutes.receptionist
  if (role === 'housekeeper') return roleHomeRoutes.housekeeper
  return '/403'
}

function getSafeRedirect(value) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//') || value.includes('\\')) return null

  const target = router.resolve(value)
  if (!target.matched.length) return null
  if (target.matched.some((record) => record.meta.errorPage || record.meta.guest)) return null

  const requiresAuth = target.matched.some((record) => record.meta.requiresAuth)
  if (!requiresAuth) return target.fullPath

  const allowedRoles = [...new Set(target.matched.flatMap((record) => record.meta.roles || []))]
  return allowedRoles.includes(auth.userRole) ? target.fullPath : null
}
</script>
