<template>
  <span :class="['badge', badgeClass]" :title="displayLabel">
    <component :is="icon" class="w-3 h-3 shrink-0" aria-hidden="true" />
    {{ displayLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import {
  Sparkles,
  CheckCircle2,
  BedDouble,
  Bookmark,
  Wrench,
} from 'lucide-vue-next'

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
})

const meta = {
  cleaning: { label: 'Perlu Dibersihkan', icon: Sparkles, badge: 'badge-cleaning' },
  available: { label: 'Bersih', icon: CheckCircle2, badge: 'badge-available' },
  occupied: { label: 'Terisi', icon: BedDouble, badge: 'badge-occupied' },
  reserved: { label: 'Dipesan', icon: Bookmark, badge: 'badge-reserved' },
  maintenance: { label: 'Pemeliharaan', icon: Wrench, badge: 'badge-maintenance' },
}

const entry = computed(() => meta[props.status] || null)
const displayLabel = computed(() => props.label || entry.value?.label || props.status)
const badgeClass = computed(() => entry.value?.badge || 'badge-neutral')
const icon = computed(() => entry.value?.icon || Sparkles)
</script>
