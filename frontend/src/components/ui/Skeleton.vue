<template>
  <div
    :class="['skeleton', shapeClass]"
    :style="style"
    aria-hidden="true"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  /** text | title | avatar | card | image | table | kpi | line */
  variant: { type: String, default: 'text' },
  width: { type: String, default: '' },
  height: { type: String, default: '' },
  rounded: { type: String, default: '' },
})

const shapeClass = computed(() => {
  const map = {
    text: 'skeleton-text',
    title: 'skeleton-title',
    avatar: 'skeleton-avatar',
    card: 'skeleton-card',
    image: 'skeleton-image',
    table: 'skeleton-table-row',
    kpi: 'skeleton-kpi',
    line: '',
  }
  return map[props.variant] || 'skeleton-text'
})

const style = computed(() => {
  const s = {}
  if (props.width) s.width = props.width
  if (props.height) s.height = props.height
  if (props.rounded) s.borderRadius = props.rounded
  return s
})
</script>
