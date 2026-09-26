<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="onOverlayClick">
        <div
          ref="panelEl"
          :class="['modal-content', sizeClass]"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : undefined"
          :aria-label="title ? undefined : 'Dialog'"
          :aria-describedby="description ? descriptionId : undefined"
          tabindex="-1"
        >
          <div v-if="title || $slots.header || showClose" class="modal-header">
            <div class="min-w-0">
              <slot name="header">
                <h3 v-if="title" :id="titleId" class="modal-title">{{ title }}</h3>
                <p v-if="description" :id="descriptionId" class="modal-desc">{{ description }}</p>
              </slot>
            </div>
            <button
              v-if="showClose"
              type="button"
              class="modal-close"
              aria-label="Tutup dialog"
              @click="close"
            >
              <X class="w-4 h-4" aria-hidden="true" />
            </button>
          </div>

          <div class="modal-body">
            <slot />
          </div>

          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch, useId } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v) },
  closeOnOverlay: { type: Boolean, default: true },
  showClose: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'close'])

const uid = useId()
const titleId = computed(() => `modal-title-${uid}`)
const descriptionId = computed(() => `modal-desc-${uid}`)
const panelEl = ref(null)
let previousFocus = null

const sizeClass = computed(() => `modal-${props.size}`)

const modalToken = {}
const modalState = (() => {
  if (typeof window === 'undefined') return { stack: [], overflow: '' }
  window.__lokanataModalState ||= { stack: [], overflow: '' }
  return window.__lokanataModalState
})()

function lockScroll() {
  if (modalState.stack.length === 0) modalState.overflow = document.body.style.overflow
  modalState.stack.push(modalToken)
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  const index = modalState.stack.indexOf(modalToken)
  if (index !== -1) modalState.stack.splice(index, 1)
  if (modalState.stack.length === 0) document.body.style.overflow = modalState.overflow
}

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function onOverlayClick() {
  if (props.closeOnOverlay) close()
}

function onKeydown(e) {
  if (!props.modelValue || modalState.stack[modalState.stack.length - 1] !== modalToken) return

  if (e.key === 'Escape') {
    e.stopPropagation()
    close()
    return
  }

  if (e.key !== 'Tab') return

  const focusable = [...(panelEl.value?.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  ) || [])].filter((element) => !element.hasAttribute('hidden'))

  if (!focusable.length) {
    e.preventDefault()
    panelEl.value?.focus()
    return
  }

  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  const active = document.activeElement

  if (e.shiftKey && (active === first || active === panelEl.value)) {
    e.preventDefault()
    last.focus()
  } else if (!e.shiftKey && (active === last || active === panelEl.value)) {
    e.preventDefault()
    first.focus()
  }
}

watch(
  () => props.modelValue,
  async (open, wasOpen) => {
    if (open && !wasOpen) {
      previousFocus = document.activeElement
      document.addEventListener('keydown', onKeydown)
      lockScroll()
      await nextTick()
      panelEl.value?.focus()
    } else if (!open && wasOpen) {
      document.removeEventListener('keydown', onKeydown)
      unlockScroll()
      await nextTick()
      previousFocus?.focus?.()
      previousFocus = null
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  if (props.modelValue) unlockScroll()
  previousFocus?.focus?.()
  previousFocus = null
})
</script>
