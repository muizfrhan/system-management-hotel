<template>
  <div class="w-full" aria-hidden="true">
    <div class="skeleton skeleton-text mb-3" style="width: 30%" />
    <div v-for="i in rows" :key="i" class="skeleton skeleton-table-row" />
  </div>
</template>

<script setup>
defineProps({
  rows: { type: Number, default: 5 },
})
</script>
