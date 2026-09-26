<template>
  <nav aria-label="Langkah pemesanan" class="mb-6">
    <ol class="flex items-center gap-1.5 sm:gap-2">
      <li
        v-for="(step, index) in steps"
        :key="step.id"
        class="flex items-center gap-1.5 sm:gap-2 min-w-0"
        :aria-current="step.id === current ? 'step' : undefined"
      >
        <component
          :is="step.id < current && clickableBack ? 'button' : 'span'"
          :type="step.id < current && clickableBack ? 'button' : undefined"
          :class="[
            'flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 min-h-10 rounded-full text-xs font-bold transition-colors',
            step.id === current
              ? 'bg-primary text-white'
              : step.id < current
                ? 'bg-primary-soft text-primary hover:bg-primary/15 cursor-pointer'
                : 'bg-slate-100 text-slate-400',
          ]"
          :aria-label="step.id < current && clickableBack ? `Kembali ke langkah ${step.id}: ${step.label}` : undefined"
          @click="step.id < current && clickableBack && $emit('go', step.id)"
        >
          <Check v-if="step.id < current" class="w-3.5 h-3.5" aria-hidden="true" />
          <span v-else>{{ String(step.id).padStart(2, '0') }}</span>
          <span class="hidden sm:inline">{{ step.label }}</span>
        </component>
        <span
          v-if="index < steps.length - 1"
          class="h-px w-3 sm:w-6 bg-slate-200 shrink-0"
          aria-hidden="true"
        ></span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { Check } from 'lucide-vue-next'

defineProps({
  steps: { type: Array, required: true },
  current: { type: Number, required: true },
  clickableBack: { type: Boolean, default: true },
})

defineEmits(['go'])
</script>
