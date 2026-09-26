<template>
  <div class="empty-state">
    <div class="empty-state-icon">
      <slot name="icon">
        <component :is="icon" v-if="icon" class="w-7 h-7" aria-hidden="true" />
        <Inbox v-else class="w-7 h-7" aria-hidden="true" />
      </slot>
    </div>
    <p class="empty-state-title">{{ title }}</p>
    <p v-if="description" class="empty-state-desc">{{ description }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
import { Inbox } from 'lucide-vue-next'

defineProps({
  title: { type: String, default: 'Tidak ada data' },
  description: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
})
</script>
