<template>
  <button
    :type="type"
    :class="['btn', `btn-${variant}`, sizeClass, { 'btn-block': block }]"
    :disabled="disabled || loading"
    :aria-disabled="loading || undefined"
    :aria-busy="loading || undefined"
    v-bind="$attrs"
  >
    <LoadingSpinner v-if="loading" size="sm" class="shrink-0" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  type: { type: String, default: 'button' },
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline', 'ghost', 'danger', 'success'].includes(v),
  },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  block: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const sizeClass = computed(() => (props.size === 'md' ? '' : `btn-${props.size}`))
</script>
