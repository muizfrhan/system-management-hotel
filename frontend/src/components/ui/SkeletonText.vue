<template>
  <div aria-hidden="true">
    <span
      v-for="i in lines"
      :key="i"
      class="skeleton"
      :class="variant === 'title' ? 'skeleton-title' : 'skeleton-text'"
      :style="{ display: 'block', marginBottom: '0.5rem', width: i === lines ? lastWidth : '100%' }"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  lines: { type: Number, default: 3 },
  variant: { type: String, default: 'text', validator: (v) => ['text', 'title'].includes(v) },
})

const lastWidth = computed(() => (props.lines > 1 ? '60%' : '100%'))
</script>
