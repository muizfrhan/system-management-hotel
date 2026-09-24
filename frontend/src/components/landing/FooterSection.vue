<template>
  <footer class="bg-slate-900 border-t border-slate-800 text-slate-400 py-16 px-6">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center text-slate-900 shadow-xl shadow-accent/20">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/><path d="M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/><path d="M10 9h.01M14 9h.01M10 13h.01M14 13h.01"/></svg>
        </div>
        <div class="text-left">
          <h4 class="text-2xl font-bold tracking-tight text-white">{{ hotelName || 'Lokanata' }}</h4>
          <p class="text-xs tracking-[0.2em] font-medium text-accent uppercase">Premium Hotel & Suite</p>
        </div>
      </div>
      
      <div class="flex flex-col md:flex-row gap-8 items-center md:items-start text-sm font-medium">
        <div class="flex items-center gap-2 hover:text-white transition cursor-pointer">
          <MapPin class="w-4 h-4 text-accent" />
          <span>{{ address || 'Pusat Kota' }}</span>
        </div>
        <div class="flex items-center gap-2 hover:text-white transition cursor-pointer">
          <Phone class="w-4 h-4 text-accent" />
          <span>{{ phone || '+62 0000 0000' }}</span>
        </div>
        <div class="flex items-center gap-2 hover:text-white transition cursor-pointer">
          <Mail class="w-4 h-4 text-accent" />
          <span>{{ email || 'info@lokanata.com' }}</span>
        </div>
      </div>
    </div>
    <div class="max-w-7xl mx-auto mt-12 pt-8 border-t border-slate-800/50 flex flex-col md:flex-row justify-between items-center text-xs">
      <p>Copyright &copy; {{ new Date().getFullYear() }} | Developed by Cybha.</p>
      <div class="flex items-center gap-6 mt-4 md:mt-0">
        <a href="#" class="hover:text-white transition">Privacy Policy</a>
        <a href="#" class="hover:text-white transition">Terms of Service</a>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { MapPin, Phone, Mail } from 'lucide-vue-next'

defineProps({
  hotelName: { type: String, default: 'Lokanata Hotel' },
  address: { type: String, default: 'Pusat Kota' },
  phone: { type: String, default: '+62 0000 0000' },
  email: { type: String, default: 'info@lokanata.com' },
})
</script>
