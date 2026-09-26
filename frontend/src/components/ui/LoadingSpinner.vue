<template>
  <span
    :class="['inline-block animate-spin rounded-full border-2 border-current border-t-transparent', sizeClass]"
    role="status"
    aria-label="Memuat"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
})

const sizeClass = computed(
  () =>
    ({
      sm: 'w-3.5 h-3.5',
      md: 'w-5 h-5',
      lg: 'w-8 h-8',
    })[props.size]
)
</script>
