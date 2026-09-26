<template>
  <BaseModal v-model="openProxy" :title="title" size="xl">
    <div v-if="total > 0" class="relative" @mouseenter="pause" @mouseleave="resume" @focusin="pause" @focusout="resume">
      <div class="relative overflow-hidden rounded-2xl bg-luxury-ink">
        <Transition name="gallery-fade" mode="out-in">
          <HotelVisual
            :key="current.src + '-' + currentIndex"
            :src="current.src"
            :alt="current.alt"
            class="aspect-[16/10] w-full"
            img-class="object-contain"
          />
        </Transition>

        <button
          type="button"
          class="icon-btn absolute left-2 top-1/2 h-10 w-10 -translate-y-1/2 rounded-full bg-white/85 text-luxury-ink shadow-lg hover:bg-white sm:left-4 sm:h-11 sm:w-11"
          aria-label="Foto sebelumnya"
          @click="showPrevious"
        >
          <ChevronLeft class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="icon-btn absolute right-2 top-1/2 h-10 w-10 -translate-y-1/2 rounded-full bg-white/85 text-luxury-ink shadow-lg hover:bg-white sm:right-4 sm:h-11 sm:w-11"
          aria-label="Foto berikutnya"
          @click="showNext"
        >
          <ChevronRight class="h-5 w-5" aria-hidden="true" />
        </button>

        <span
          v-if="total > 1"
          class="absolute bottom-3 left-3 rounded-full bg-black/55 px-2.5 py-1 text-[0.68rem] font-semibold tracking-[0.14em] text-white backdrop-blur-sm sm:bottom-4 sm:left-4"
        >
          {{ currentIndex + 1 }} / {{ total }}
        </span>
      </div>

      <div v-if="total > 1" class="mt-4 flex items-center gap-4">
        <ul class="flex min-w-0 flex-1 items-center gap-2 overflow-x-auto pb-1">
          <li v-for="(item, index) in items" :key="`${item.src}-${index}`" class="shrink-0">
            <button
              type="button"
              class="block h-14 w-20 overflow-hidden rounded-lg border-2 transition-colors sm:h-16 sm:w-24"
              :class="index === currentIndex ? 'border-luxury-gold' : 'border-transparent hover:border-luxury-stone'"
              :aria-label="`Tampilkan foto ${index + 1}`"
              :aria-current="index === currentIndex"
              @click="goTo(index)"
            >
              <HotelVisual :src="item.src" :alt="item.alt" class="h-full w-full" img-class="object-cover" />
            </button>
          </li>
        </ul>

        <button
          type="button"
          class="icon-btn h-11 w-11 shrink-0 rounded-full border border-luxury-stone text-luxury-ink"
          :aria-label="isPlaying ? 'J slideshow' : 'Putar slideshow'"
          @click="toggle"
        >
          <Pause v-if="isPlaying" class="h-5 w-5" aria-hidden="true" />
          <Play v-else class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <div v-if="total > 1" class="mt-3 h-1 w-full overflow-hidden rounded-full bg-luxury-stone/40">
        <div
          :key="progressKey"
          class="h-full origin-left rounded-full bg-luxury-gold"
          :style="{ animation: `gallery-progress ${interval}ms linear forwards`, animationPlayState: isPlaying ? 'running' : 'paused' }"
        ></div>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-vue-next'
import BaseModal from '../ui/BaseModal.vue'
import HotelVisual from './HotelVisual.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  items: { type: Array, default: () => [] },
  startIndex: { type: Number, default: 0 },
  title: { type: String, default: 'Galeri kamar' },
  interval: { type: Number, default: 4500 },
})

const emit = defineEmits(['update:modelValue'])

const currentIndex = ref(0)
const isPlaying = ref(true)
const progressKey = ref(0)
let timer = null

const total = computed(() => props.items.length)
const current = computed(() => props.items[currentIndex.value] || { src: null, alt: '' })

const openProxy = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

function clampIndex(index) {
  if (!total.value) return 0
  return ((index % total.value) + total.value) % total.value
}

function goTo(index) {
  currentIndex.value = clampIndex(index)
  restart()
}

function showNext() {
  currentIndex.value = clampIndex(currentIndex.value + 1)
  restart()
}

function showPrevious() {
  currentIndex.value = clampIndex(currentIndex.value - 1)
  restart()
}

function toggle() {
  isPlaying.value = !isPlaying.value
  restart()
}

function pause() {
  isPlaying.value = false
  stop()
}

function resume() {
  isPlaying.value = true
  restart()
}

function stop() {
  if (timer) clearInterval(timer)
  timer = null
}

function start() {
  stop()
  if (!props.modelValue || total.value < 2 || !isPlaying.value || prefersReducedMotion()) return
  timer = setInterval(() => {
    currentIndex.value = clampIndex(currentIndex.value + 1)
    progressKey.value += 1
  }, props.interval)
}

function restart() {
  progressKey.value += 1
  start()
}

function onKeydown(event) {
  if (!props.modelValue || total.value < 2) return
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    showNext()
  }
  if (event.key === 'ArrowLeft') {
    event.preventDefault()
    showPrevious()
  }
}

watch(
  () => props.modelValue,
  (open, wasOpen) => {
    if (open && !wasOpen) {
      currentIndex.value = clampIndex(props.startIndex)
      isPlaying.value = true
      restart()
      document.addEventListener('keydown', onKeydown)
    } else if (!open && wasOpen) {
      document.removeEventListener('keydown', onKeydown)
      stop()
    }
  }
)

watch(
  () => props.items.length,
  () => {
    currentIndex.value = clampIndex(currentIndex.value)
    if (props.modelValue) restart()
  }
)

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  stop()
})
</script>

<style scoped>
.gallery-fade-enter-active,
.gallery-fade-leave-active {
  transition: opacity 220ms ease-out;
}

.gallery-fade-enter-from,
.gallery-fade-leave-to {
  opacity: 0;
}

@keyframes gallery-progress {
  from {
    transform: scaleX(0);
  }

  to {
    transform: scaleX(1);
  }
}
</style>
