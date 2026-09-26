<template>
  <span :class="['badge', toneClass, { 'badge-lg': size === 'lg' }]">
    <slot>{{ label || status }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: '' },
  label: { type: String, default: '' },
  tone: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (v) => ['md', 'lg'].includes(v) },
})

const toneClass = computed(() => {
  if (props.tone) return `badge-${props.tone}`
  if (props.status) return `badge-${props.status}`
  return 'badge-neutral'
})
</script>
