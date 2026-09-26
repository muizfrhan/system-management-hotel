<template>
  <Teleport to="body">
    <div class="toast-viewport" aria-live="polite" aria-atomic="false">
      <TransitionGroup name="toast" tag="div" class="toast-stack">
        <div v-for="t in toasts" :key="t.id" :class="['toast', `toast-${t.type}`]" role="status">
          <component
            :is="iconMap[t.type]"
            class="w-5 h-5 shrink-0 mt-0.5"
            :class="iconColorMap[t.type]"
            aria-hidden="true"
          />
          <p class="min-w-0 flex-1 pt-0.5">{{ t.message }}</p>
          <button
            type="button"
            class="toast-dismiss"
            aria-label="Tutup notifikasi"
            @click="dismiss(t.id)"
          >
            <X class="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast'

const { toasts, dismiss } = useToast()

const iconMap = {
  success: CheckCircle2,
  error: AlertCircle,
  warning: AlertTriangle,
  info: Info,
}

const iconColorMap = {
  success: 'text-success',
  error: 'text-danger',
  warning: 'text-warning',
  info: 'text-info',
}
</script>

<style scoped>
.toast-enter-active {
  transition:
    opacity var(--duration-standard, 180ms) var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1)),
    transform var(--duration-standard, 180ms) var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
}

.toast-leave-active {
  transition:
    opacity var(--duration-micro, 120ms) var(--ease-in, cubic-bezier(0.7, 0, 0.84, 0)),
    transform var(--duration-micro, 120ms) var(--ease-in, cubic-bezier(0.7, 0, 0.84, 0));
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(16px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

.toast-move {
  transition: transform var(--duration-standard, 180ms) var(--ease-out, cubic-bezier(0.16, 1, 0.3, 1));
}
</style>
