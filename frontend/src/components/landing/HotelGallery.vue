<template>
  <section
    ref="sectionEl"
    :class="['bg-luxury-cream py-24 sm:py-32 lg:py-40 reveal', { 'is-visible': visible }]"
  >
    <div class="mx-auto max-w-public px-5 sm:px-8 lg:px-12">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-[0.68rem] font-bold uppercase tracking-[0.26em] text-luxury-gold">Galeri</p>
          <h2 class="mt-4 font-display text-[clamp(2.8rem,5.5vw,5.5rem)] leading-[0.96] tracking-[-0.045em] text-luxury-ink">Ruang dalam perspektif lain.</h2>
        </div>
        <p class="max-w-sm text-sm leading-7 text-hotel-muted">Klik salah satu visual untuk melihat gambar lebih besar.</p>
      </div>

      <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-12 lg:auto-rows-[minmax(13rem,auto)]">
        <button
          v-for="(item, index) in visibleItems"
          :key="`${item.src}-${index}`"
          type="button"
          :class="['group relative overflow-hidden bg-luxury-stone/30 text-left', galleryClass(index)]"
          :aria-label="`Perbesar foto ${item.roomName} ${index + 1} dari ${visibleItems.length}`"
          @click="openLightbox(index)"
        >
          <RoomImage :src="item.src" :alt="item.alt" class="h-full w-full" img-class="object-center group-hover:scale-[1.025]" />
          <span class="absolute inset-0 bg-gradient-to-t from-luxury-ink/65 via-transparent to-transparent opacity-80 transition-opacity group-hover:opacity-100"></span>
          <span class="absolute bottom-3 left-3 right-3 flex items-end justify-between gap-3 text-white sm:bottom-5 sm:left-5 sm:right-5">
            <span class="text-[0.62rem] font-bold uppercase tracking-[0.18em]">{{ item.roomName }}</span>
            <Maximize2 class="h-4 w-4 shrink-0" aria-hidden="true" />
          </span>
        </button>
      </div>
    </div>

    <BaseModal v-model="lightboxOpen" :title="selectedItem?.roomName || 'Galeri kamar'" size="xl">
      <div v-if="selectedItem" class="relative">
        <RoomImage :src="selectedItem.src" :alt="selectedItem.alt" class="aspect-[16/10] w-full bg-luxury-ink" img-class="object-contain" />
        <div class="mt-4 flex items-center justify-between gap-4">
          <p class="text-xs uppercase tracking-[0.16em] text-hotel-muted" aria-live="polite">
            {{ selectedIndex + 1 }} / {{ items.length }} · {{ selectedItem.roomName }}
          </p>
          <div v-if="items.length > 1" class="flex items-center gap-2">
            <button type="button" class="icon-btn h-11 w-11 border border-luxury-stone text-luxury-ink" aria-label="Foto sebelumnya" @click="showPrevious">
              <ChevronLeft class="h-5 w-5" aria-hidden="true" />
            </button>
            <button type="button" class="icon-btn h-11 w-11 border border-luxury-stone text-luxury-ink" aria-label="Foto berikutnya" @click="showNext">
              <ChevronRight class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </BaseModal>
  </section>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, Maximize2 } from 'lucide-vue-next'
import BaseModal from '../ui/BaseModal.vue'
import RoomImage from './RoomImage.vue'
import { useReveal } from '../../composables/useReveal'

const props = defineProps({
  items: { type: Array, default: () => [] },
})

const { el: sectionEl, visible } = useReveal()
const lightboxOpen = ref(false)
const selectedIndex = ref(0)
const visibleItems = computed(() => props.items.slice(0, 6))
const selectedItem = computed(() => props.items[selectedIndex.value] || null)

function galleryClass(index) {
  const total = visibleItems.value.length
  if (total === 1) return 'col-span-2 aspect-[4/3] lg:aspect-[16/7]'
  if (total === 2) return 'col-span-1 aspect-square lg:col-span-6 lg:aspect-[4/3]'
  if (total === 4) {
    if (index === 0) return 'col-span-2 aspect-[4/3] lg:col-span-6 lg:row-span-2 lg:aspect-auto'
    if (index === 3) return 'col-span-2 aspect-[4/3] lg:col-span-12 lg:aspect-[16/7]'
    return 'col-span-1 aspect-square lg:col-span-6 lg:aspect-auto'
  }
  if (total === 5) {
    if (index === 0) return 'col-span-2 aspect-[4/3] lg:col-span-7 lg:row-span-2 lg:aspect-auto'
    if (index < 3) return 'col-span-1 aspect-square lg:col-span-5 lg:aspect-[4/3]'
    return 'col-span-1 aspect-square lg:col-span-6 lg:aspect-[4/3]'
  }
  if (index === 0) return 'col-span-2 aspect-[4/3] lg:col-span-7 lg:row-span-2 lg:aspect-auto'
  if (index < 3) return 'col-span-1 aspect-square lg:col-span-5 lg:aspect-[4/3]'
  return 'col-span-1 aspect-square lg:col-span-4 lg:aspect-square'
}

function openLightbox(index) {
  selectedIndex.value = index
  lightboxOpen.value = true
}

function showNext() {
  selectedIndex.value = (selectedIndex.value + 1) % props.items.length
}

function showPrevious() {
  selectedIndex.value = (selectedIndex.value - 1 + props.items.length) % props.items.length
}

function onKeydown(event) {
  if (!lightboxOpen.value || props.items.length < 2) return
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    showNext()
  }
  if (event.key === 'ArrowLeft') {
    event.preventDefault()
    showPrevious()
  }
}

watch(lightboxOpen, (open) => {
  if (open) document.addEventListener('keydown', onKeydown)
  else document.removeEventListener('keydown', onKeydown)
})

onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>
