<template>
  <div
    class="relative isolate overflow-hidden bg-luxury-ink"
    :role="(!src || failed) && alt ? 'img' : undefined"
    :aria-label="(!src || failed) && alt ? alt : undefined"
  >
    <img
      v-if="src && !failed"
      :src="src"
      :alt="alt"
      :class="['h-full w-full object-cover', imgClass]"
      width="1600"
      height="1100"
      :loading="eager ? 'eager' : 'lazy'"
      :fetchpriority="eager ? 'high' : 'auto'"
      decoding="async"
      sizes="(min-width: 1280px) 90vw, (min-width: 768px) 100vw, 100vw"
      @error="handleError"
    />
    <svg v-else viewBox="0 0 1600 1100" preserveAspectRatio="xMidYMid slice" class="h-full w-full" aria-hidden="true">
      <rect width="1600" height="1100" fill="#c8b9a5" />
      <path d="M0 0H785V760H0Z" fill="#d9cebd" />
      <path d="M785 0H1600V1100H785Z" fill="#a99d8d" />
      <path d="M0 756C180 688 326 678 482 756V1100H0Z" fill="#706c63" />
      <path d="M210 760V352C210 216 300 126 436 126s226 90 226 226v408Z" fill="#eee8de" />
      <path d="M290 760V382c0-93 59-152 146-152s146 59 146 152v378Z" fill="#6b7772" />
      <path d="M0 0h1600v98H0z" fill="#363c39" />
      <path d="M112 98h38v660h-38zM660 98h38v660h-38zM902 98h38v660h-38zM1450 98h38v660h-38z" fill="#3e4541" />
      <path d="M1050 160h390v36h-390zM1050 282h390v36h-390zM1050 404h390v36h-390z" fill="#d6cbb9" opacity=".72" />
      <rect x="1048" y="536" width="394" height="222" fill="#444c48" />
      <path d="M1016 758h458v36h-458z" fill="#e2d8c8" />
      <rect x="116" y="792" width="720" height="156" rx="10" fill="#d7cdbd" />
      <rect x="152" y="744" width="250" height="94" rx="18" fill="#eee7db" />
      <rect x="520" y="744" width="250" height="94" rx="18" fill="#eee7db" />
      <rect x="620" y="836" width="136" height="112" fill="#8f6d45" />
      <circle cx="1300" cy="645" r="68" fill="#b99462" />
      <path d="M1300 574v142M1229 645h142" stroke="#e4c69a" stroke-width="8" opacity=".7" />
      <path d="M0 1080h1600v20H0z" fill="#242a27" />
    </svg>
    <slot />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  alt: { type: String, default: '' },
  imgClass: { type: String, default: 'object-center' },
  eager: { type: Boolean, default: false },
})

const emit = defineEmits(['error'])
const failed = ref(false)

function handleError() {
  failed.value = true
  emit('error', props.src)
}

watch(
  () => props.src,
  () => {
    failed.value = false
  }
)
</script>
