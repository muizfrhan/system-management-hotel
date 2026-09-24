<template>
  <section class="relative h-[80vh] min-h-[600px] flex items-center justify-center overflow-hidden">
    <!-- Premium background image -->
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1542314831-c6a4d14d8376?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat object-cover"></div>
    
    <!-- Sophisticated dark gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-900/60 to-slate-900/90 backdrop-blur-[2px]"></div>
    
    <div class="max-w-5xl mx-auto px-6 text-center relative z-10 animate-slide-up">
      <div class="flex items-center justify-center gap-4 mb-8">
        <div class="h-[1px] w-12 bg-accent opacity-50"></div>
        <p class="font-bold text-xs tracking-[0.3em] uppercase text-accent">{{ tagline }}</p>
        <div class="h-[1px] w-12 bg-accent opacity-50"></div>
      </div>
      
      <h2 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight tracking-tight text-white drop-shadow-2xl">
        {{ titlePrefix }}<br>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-accent-light via-accent to-yellow-600">
          {{ hotelName }}
        </span>
      </h2>
      
      <p class="text-lg md:text-xl mb-12 max-w-2xl mx-auto text-slate-300 font-light leading-relaxed">
        {{ description }}
      </p>
      
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <slot></slot>
      </div>
    </div>

    <!-- Scroll down indicator -->
    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 flex flex-col items-center gap-2 text-white/50 animate-bounce">
      <span class="text-[10px] uppercase tracking-widest">Jelajahi</span>
      <ChevronDown class="w-4 h-4" />
    </div>
  </section>
</template>

<script setup>
import { ChevronDown } from 'lucide-vue-next'

defineProps({
  tagline: { type: String, default: 'Pengalaman Menginap Terbaik' },
  titlePrefix: { type: String, default: 'Selamat Datang di' },
  hotelName: { type: String, default: 'Lokanata Hotel' },
  description: { type: String, default: 'Nikmati kenyamanan dan kemewahan dengan pilihan kamar terbaik untuk setiap kebutuhan menginap Anda.' },
})
</script>
