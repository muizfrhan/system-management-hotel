<template>
  <component
    :is="tag"
    :class="['card', { 'card-hover': hover }]"
  >
    <slot />
  </component>
</template>

<script setup>
defineProps({
  tag: { type: String, default: 'div' },
  hover: { type: Boolean, default: false },
})
</script>
