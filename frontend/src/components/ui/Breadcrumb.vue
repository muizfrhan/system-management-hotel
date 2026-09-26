<template>
  <nav class="public-breadcrumb" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2">
      <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-2">
        <router-link v-if="item.to && index < items.length - 1" :to="item.to" class="public-breadcrumb-link">
          {{ item.label }}
        </router-link>
        <span v-else :aria-current="index === items.length - 1 ? 'page' : undefined" class="public-breadcrumb-current">
          {{ item.label }}
        </span>
        <ChevronRight v-if="index < items.length - 1" class="h-3.5 w-3.5 text-current opacity-50" aria-hidden="true" />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { ChevronRight } from 'lucide-vue-next'

defineProps({
  items: { type: Array, required: true },
})
</script>
