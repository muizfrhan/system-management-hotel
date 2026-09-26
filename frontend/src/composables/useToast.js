import { ref } from 'vue'

const toasts = ref([])
let nextId = 1

function dismiss(id) {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

function show(type, message, duration = 4000) {
  const id = nextId++
  toasts.value.push({ id, type, message })
  if (duration > 0) {
    setTimeout(() => dismiss(id), duration)
  }
  return id
}

export function useToast() {
  return {
    toasts,
    dismiss,
    show,
    success: (message, duration) => show('success', message, duration),
    error: (message, duration) => show('error', message, duration ?? 6000),
    warning: (message, duration) => show('warning', message, duration),
    info: (message, duration) => show('info', message, duration),
  }
}
