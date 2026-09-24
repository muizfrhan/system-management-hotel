<template>
  <div class="stat-card" :style="borderStyle">
    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--text-secondary)">{{ label }}</p>
    <p class="text-3xl font-bold" :style="{ color: valueColor }">{{ displayValue }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  color: { type: String, default: '' },
  currency: { type: Boolean, default: false },
  borderColor: { type: String, default: '' },
})

const valueColor = computed(() => props.color || 'var(--text-primary)')

const borderStyle = computed(() => {
  if (!props.borderColor) return {}
  return { borderLeft: `4px solid ${props.borderColor}` }
})

const displayValue = computed(() => {
  if (props.currency) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(props.value || 0)
  }
  return props.value
})
</script>
