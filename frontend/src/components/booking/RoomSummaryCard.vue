<template>
  <div>
    <template v-if="loading">
      <div class="p-5 sm:p-6 space-y-4" aria-hidden="true">
        <div class="skeleton h-40 rounded-2xl"></div>
        <div class="skeleton h-5 w-2/3"></div>
        <div class="skeleton h-4 w-1/2"></div>
        <div class="skeleton h-10 w-full"></div>
      </div>
    </template>

    <div v-else-if="error" class="p-5 sm:p-6">
      <AlertBox variant="error" title="Gagal memuat kamar">
        <button type="button" class="btn btn-outline rounded-full px-5 mt-2" @click="$emit('retry')">
          Coba Lagi
        </button>
      </AlertBox>
    </div>

    <template v-else-if="roomType">
      <RoomImage :src="roomImageUrl(roomType)" :alt="roomType.name" img-class="" class="h-40 sm:h-48" />
      <div class="p-5 sm:p-6 space-y-4">
        <div>
          <p class="text-xs font-bold uppercase tracking-widest text-primary mb-1">Kamar dipilih</p>
          <h2 class="text-lg font-bold text-slate-900">{{ roomType.name }}</h2>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ roomType.bed_type || '-' }} · {{ roomType.size || '-' }} · Maks {{ roomType.capacity }} tamu
          </p>
          <p v-if="roomType.description" class="text-sm text-slate-500 mt-2 line-clamp-2 leading-relaxed">
            {{ roomType.description }}
          </p>
        </div>

        <div v-if="nights > 0" class="border-t border-slate-100 pt-4">
          <PriceBreakdown :price-per-night="roomType.base_price" :nights="nights" />
        </div>
        <div v-else class="border-t border-slate-100 pt-4 flex items-baseline justify-between gap-3">
          <span class="text-sm text-slate-500">Mulai dari</span>
          <span class="text-lg font-bold text-slate-900">
            {{ formatCurrency(roomType.base_price) }}<span class="text-xs font-medium text-slate-500">/malam</span>
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import RoomImage from '../landing/RoomImage.vue'
import AlertBox from '../ui/AlertBox.vue'
import PriceBreakdown from './PriceBreakdown.vue'
import { roomImageUrl } from '../../utils/roomImages'
import { formatCurrency } from '../../utils/dates'

defineProps({
  roomType: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  nights: { type: Number, default: 0 },
})

defineEmits(['retry'])
</script>
