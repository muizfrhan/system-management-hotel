<template>
  <div class="skeleton-card skeleton" :style="style" aria-hidden="true">
    <div v-if="image" class="skeleton skeleton-image mb-3" />
    <div class="skeleton skeleton-title mb-2" />
    <div class="skeleton skeleton-text mb-2" />
    <div class="skeleton skeleton-text" style="width: 70%" />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  image: { type: Boolean, default: false },
  height: { type: String, default: '' },
})

const style = computed(() => {
  const s = { padding: '1rem', height: 'auto', minHeight: '8rem' }
  if (props.height) s.minHeight = props.height
  return s
})
</script>
