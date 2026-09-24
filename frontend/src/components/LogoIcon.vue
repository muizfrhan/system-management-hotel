<template>
  <svg :width="size" :height="size" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M8 8C14 14 18 24 18 24" stroke="#2DD4BF" stroke-width="4" stroke-linecap="round"/>
    <path d="M24 8C18 14 14 24 14 24" stroke="#38BDF8" stroke-width="4" stroke-linecap="round"/>
  </svg>
</template>

<script setup>
defineProps({
  size: { type: [Number, String], default: 32 }
})
</script>
