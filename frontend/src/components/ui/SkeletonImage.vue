<template>
  <div class="skeleton skeleton-image" :style="style" aria-hidden="true">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  height: { type: String, default: '' },
  rounded: { type: String, default: '1rem' },
})

const style = computed(() => {
  const s = { borderRadius: props.rounded }
  if (props.height) s.height = props.height
  return s
})
</script>
