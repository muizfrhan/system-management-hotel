<template>
  <div class="stat-card" :style="borderStyle">
    <div class="flex items-center justify-between gap-2">
      <p class="text-caption font-semibold uppercase tracking-wide text-ink-secondary">{{ label }}</p>
      <div v-if="$slots.icon" class="text-ink-muted">
        <slot name="icon" />
      </div>
    </div>
    <p class="text-h3 sm:text-h2 font-bold wrap-break-word tabular-nums" :style="{ color: valueColor }">{{ displayValue }}</p>
    <p v-if="$slots.footer" class="text-caption text-ink-secondary">
      <slot name="footer" />
    </p>
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

const valueColor = computed(() => props.color || 'var(--color-ink)')

const borderStyle = computed(() => {
  if (!props.borderColor) return {}
  return { borderLeft: `4px solid ${props.borderColor}` }
})

const displayValue = computed(() => {
  if (props.currency) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(props.value || 0)
  }
  return props.value
})
</script>
