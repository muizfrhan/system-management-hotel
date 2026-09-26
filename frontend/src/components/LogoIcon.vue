<template>
  <svg :width="size" :height="size" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 8C14 14 18 24 18 24" :stroke="primaryColor" stroke-width="4" stroke-linecap="round" />
    <path d="M24 8C18 14 14 24 14 24" :stroke="secondaryColor" stroke-width="4" stroke-linecap="round" />
  </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: { type: [Number, String], default: 32 },
  tone: { type: String, default: 'brand', validator: (value) => ['brand', 'gold'].includes(value) },
})

const primaryColor = computed(() => (props.tone === 'gold' ? '#d8b77f' : '#2DD4BF'))
const secondaryColor = computed(() => (props.tone === 'gold' ? '#9b7a48' : '#38BDF8'))
</script>
