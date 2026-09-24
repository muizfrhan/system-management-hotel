<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-white selection:bg-cyan-100 selection:text-cyan-900">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-cyan-400/5 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-blue-400/5 rounded-full blur-[100px] translate-y-1/2 -translate-x-1/3"></div>

    <div class="w-full max-w-md px-6 relative z-10">
      <div class="text-center mb-10">
        <div class="flex items-center justify-center gap-2 mb-6">
          <LogoIcon :size="40" />
          <span class="text-3xl font-bold tracking-tight text-slate-900">Lokanata</span>
        </div>
        <p class="text-sm text-slate-500 tracking-wide uppercase font-medium">Portal Manajemen Hotel</p>
      </div>

      <div class="bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] border border-slate-100 p-8">
        <h2 class="text-xl font-bold text-slate-900 mb-1">Selamat Datang Kembali</h2>
        <p class="text-sm text-slate-500 mb-6">Silakan masukkan kredensial Anda untuk melanjutkan</p>

        <div v-if="auth.error" class="mb-5 p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-600 flex items-start gap-3">
          <AlertCircle class="w-5 h-5 flex-shrink-0 text-red-500" />
          <span>{{ auth.error }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-900">Email</label>
            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-100 transition">
              <Mail class="w-5 h-5 text-slate-400 shrink-0" />
              <input v-model="email" type="email" class="bg-transparent border-none outline-none w-full text-sm text-slate-700" placeholder="admin@lokanata.com" required />
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-900">Password</label>
            <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus-within:border-cyan-400 focus-within:ring-2 focus-within:ring-cyan-100 transition">
              <Lock class="w-5 h-5 text-slate-400 shrink-0" />
              <input v-model="password" type="password" class="bg-transparent border-none outline-none w-full text-sm text-slate-700" placeholder="••••••••" required />
            </div>
          </div>
          <button type="submit" :disabled="auth.loading" class="w-full py-3.5 rounded-full bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-base disabled:opacity-50 disabled:cursor-not-allowed mt-2">
            <span v-if="auth.loading" class="flex items-center justify-center gap-2">
              <Loader2 class="w-5 h-5 animate-spin" /> Memproses...
            </span>
            <span v-else class="flex items-center justify-center gap-2">
              Masuk Akses <ArrowRight class="w-4 h-4" />
            </span>
          </button>
        </form>

        <div class="mt-8 pt-5 border-t border-slate-100">
          <p class="text-xs text-center text-slate-500 flex items-center justify-center gap-2">
            <Info class="w-4 h-4" /> Demo kredensial: <strong class="text-slate-700">admin@lokanata.com / password</strong>
          </p>
        </div>
      </div>

      <p class="text-center mt-8 text-xs text-slate-400">
        Copyright &copy; {{ new Date().getFullYear() }} | Developed by Cybha.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { Mail, Lock, ArrowRight, Loader2, AlertCircle, Info } from 'lucide-vue-next'
import LogoIcon from '../../components/LogoIcon.vue'

const auth = useAuthStore()
const router = useRouter()
const email = ref('')
const password = ref('')

async function handleLogin() {
  try {
    const res = await auth.login(email.value, password.value)
    if (res.user?.role === 'receptionist') router.push('/receptionist/booking-online')
    else if (res.user?.role === 'housekeeper') router.push('/housekeeper/housekeeping')
    else router.push('/admin/dashboard')
  } catch {}
}
</script>
