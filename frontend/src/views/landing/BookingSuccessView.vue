<template>
  <main class="min-h-screen bg-white flex items-center justify-center px-4 selection:bg-cyan-100 selection:text-cyan-900">
    <div class="text-center max-w-lg mx-auto bg-white p-6 sm:p-10 md:p-12 rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] border border-slate-100">
      <div class="w-20 h-20 sm:w-24 sm:h-24 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6 sm:mb-8">
        <CheckCircle class="w-10 h-10 sm:w-12 sm:h-12 text-emerald-500" />
      </div>
      <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3 tracking-tight">Pemesanan Berhasil!</h2>
      <p class="text-slate-500 mb-6 text-sm sm:text-base">Kode reservasi Anda:</p>

      <div class="bg-slate-50 border border-slate-200 px-4 sm:px-8 py-4 sm:py-5 rounded-2xl inline-block mb-6 sm:mb-8 max-w-full">
        <p class="text-2xl sm:text-3xl md:text-4xl font-mono font-bold text-slate-900 tracking-wider break-all">{{ $route.params.code }}</p>
      </div>

      <p class="text-sm text-slate-500 mb-10 max-w-sm mx-auto leading-relaxed">
        Silakan simpan kode reservasi ini. Tim kami akan mengkonfirmasi pemesanan Anda dalam waktu singkat.
        Anda bisa melacak statusnya kapan saja.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <router-link to="/track" class="w-full sm:w-auto inline-flex items-center justify-center py-3.5 px-8 rounded-full border-2 border-slate-200 text-slate-700 font-semibold hover:border-slate-300 transition-colors text-base">
          Lacak Reservasi
        </router-link>
        <router-link to="/" class="w-full sm:w-auto inline-flex items-center justify-center py-3.5 px-8 rounded-full bg-cyan-400 text-white font-semibold hover:bg-cyan-500 transition-colors text-base">
          Kembali ke Beranda
        </router-link>
      </div>
    </div>
  </main>
</template>

<script setup>
import { CheckCircle } from 'lucide-vue-next'
</script>
