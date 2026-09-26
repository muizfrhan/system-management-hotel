<template>
  <div :class="['alert', `alert-${variant}`]" :role="role">
    <component :is="icon" v-if="icon" class="w-5 h-5 shrink-0 mt-0.5" aria-hidden="true" />
    <div class="min-w-0 flex-1">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div :class="{ 'mt-0.5': title }">
        <slot />
      </div>
    </div>
    <button
      v-if="dismissible"
      type="button"
      class="toast-dismiss -mt-0.5 -mr-1"
      aria-label="Tutup pesan"
      @click="$emit('dismiss')"
    >
      <X class="w-4 h-4" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next'

const props = defineProps({
  variant: { type: String, default: 'info', validator: (v) => ['success', 'error', 'warning', 'info'].includes(v) },
  title: { type: String, default: '' },
  dismissible: { type: Boolean, default: false },
})

defineEmits(['dismiss'])

const iconMap = {
  success: CheckCircle2,
  error: AlertCircle,
  warning: AlertTriangle,
  info: Info,
}

const icon = computed(() => iconMap[props.variant])
const role = computed(() => (props.variant === 'error' ? 'alert' : 'status'))
</script>
