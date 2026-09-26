import { onMounted, onUnmounted, ref, watch } from 'vue'

/**
 * Auto slide untuk carousel foto — murni otomatis, tanpa kontrol pause.
 * - Maju tiap `interval` ms selama `count` > 1
 * - Hormati prefers-reduced-motion: tidak jalan otomatis
 * - Pause sementara kalau tab tidak aktif (hemat resource)
 * - Reset timer setiap `count` berubah
 */
export function useAutoSlide(count, options = {}) {
  const { interval = 4500, startIndex = 0 } = options

  const index = ref(startIndex)
  const progressKey = ref(0)

  let timer = null

  const total = () => {
    const source = typeof count === 'function' ? count() : count?.value ?? count
    if (Array.isArray(source)) return source.length
    return Number(source) || 0
  }

  const prefersReducedMotion = () =>
    typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

  function stop() {
    if (timer) clearInterval(timer)
    timer = null
  }

  function start() {
    stop()
    if (total() < 2 || prefersReducedMotion() || document.hidden) return
    timer = setInterval(() => {
      index.value = (index.value + 1) % total()
      progressKey.value += 1
    }, interval)
  }

  function goTo(target) {
    const n = total()
    if (!n) return
    index.value = ((target % n) + n) % n
    progressKey.value += 1
  }

  function onVisibility() {
    if (document.hidden) stop()
    else start()
  }

  watch(total, (n) => {
    if (n && index.value > n - 1) index.value = 0
    progressKey.value += 1
    start()
  })

  onMounted(() => {
    document.addEventListener('visibilitychange', onVisibility)
    start()
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibility)
    stop()
  })

  return { index, progressKey, interval, goTo, restart: start }
}
