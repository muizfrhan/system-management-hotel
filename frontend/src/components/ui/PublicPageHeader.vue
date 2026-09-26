<template>
  <header :class="['public-page-header', align === 'center' ? 'text-center items-center' : 'text-left items-start']">
    <router-link v-if="backTo" :to="backTo" class="public-back-link">
      <ArrowLeft class="h-4 w-4" aria-hidden="true" />
      {{ backLabel }}
    </router-link>
    <p v-if="eyebrow" class="public-eyebrow">{{ eyebrow }}</p>
    <h1 class="public-page-title">{{ title }}</h1>
    <p v-if="description" class="public-page-description">{{ description }}</p>
    <div v-if="$slots.actions" class="public-page-actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
import { ArrowLeft } from 'lucide-vue-next'

defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  backTo: { type: [String, Object], default: null },
  backLabel: { type: String, default: 'Kembali' },
  align: { type: String, default: 'left', validator: (value) => ['left', 'center'].includes(value) },
})
</script>
