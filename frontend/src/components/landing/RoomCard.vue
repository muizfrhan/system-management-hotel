<template>
  <div class="card group" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);">
    <div class="h-56 relative bg-slate-100 overflow-hidden">
      <!-- Fallback pattern if no image -->
      <div v-if="!roomType.image" class="absolute inset-0 bg-slate-900 flex items-center justify-center">
        <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMiIgZmlsbD0iI2ZmZiIvPjwvc3ZnPg==')]"></div>
        <BedDouble class="w-16 h-16 text-accent opacity-50 relative z-10 group-hover:scale-110 transition-transform duration-500" />
      </div>
      <!-- Real image -->
      <img v-else :src="roomType.image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" :alt="roomType.name" />
      
      <!-- Gradient overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
      
      <!-- Floating badge -->
      <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between">
        <h4 class="text-2xl font-bold text-white tracking-tight">{{ roomType.name }}</h4>
        <div class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full border border-white/30 text-white text-xs font-semibold flex items-center gap-1">
          <Users class="w-3.5 h-3.5" /> {{ roomType.capacity }} Org
        </div>
      </div>
    </div>
    <div class="p-6 bg-white">
      <p class="text-sm text-slate-500 mb-5 line-clamp-2 leading-relaxed">{{ roomType.description }}</p>
      
      <div class="flex flex-wrap gap-2 mb-6">
        <FacilityBadge v-for="f in roomType.facilities" :key="f.id" :name="f.name" />
      </div>

      <div class="flex items-center justify-between pt-6 border-t border-slate-100">
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Mulai Dari</p>
          <p class="text-2xl font-bold text-slate-800">
            {{ formatCurrency(roomType.base_price) }}<span class="text-sm font-medium text-slate-500">/mlm</span>
          </p>
        </div>
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { BedDouble, Users } from 'lucide-vue-next'
import FacilityBadge from './FacilityBadge.vue'

defineProps({
  roomType: { type: Object, required: true },
})

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0)
}
</script>
