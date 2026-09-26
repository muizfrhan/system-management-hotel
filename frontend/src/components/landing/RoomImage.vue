<template>
  <div class="relative overflow-hidden bg-slate-100">
    <HotelVisual
      :src="src"
      :alt="alt"
      :img-class="`h-full w-full object-cover ${imgClass}`"
      :eager="eager"
      class="h-full w-full"
      @error="emit('error', $event)"
    />
    <slot />
  </div>
</template>

<script setup>
import HotelVisual from './HotelVisual.vue'

defineProps({
  src: { type: String, default: null },
  alt: { type: String, default: '' },
  imgClass: { type: String, default: 'group-hover:scale-105' },
  eager: { type: Boolean, default: false },
})

const emit = defineEmits(['error'])
</script>
