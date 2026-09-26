import { onMounted, onUnmounted, ref } from 'vue'

/**
 * Limited scroll reveal — hanya untuk section guest landing.
 * Element terlihat saat masuk viewport, lalu observer dilepas (sekali saja).
 * Hormati prefers-reduced-motion: langsung tampil.
 */
export function useReveal() {
  const el = ref(null)
  const visible = ref(false)
  let observer = null

  onMounted(() => {
    if (!el.value) return

    const reduced =
      window.matchMedia &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (reduced || !('IntersectionObserver' in window)) {
      visible.value = true
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            visible.value = true
            observer?.disconnect()
            observer = null
          }
        })
      },
      { threshold: 0.01, rootMargin: '0px 0px -8% 0px' }
    )
    observer.observe(el.value)
  })

  onUnmounted(() => {
    observer?.disconnect()
    observer = null
  })

  return { el, visible }
}
