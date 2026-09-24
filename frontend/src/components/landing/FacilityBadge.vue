<template>
  <span class="text-[10px] uppercase font-bold tracking-widest px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-500 hover:border-accent hover:text-accent hover:bg-accent/5 transition-all cursor-default">
    {{ name }}
  </span>
</template>

<script setup>
defineProps({
  name: { type: String, required: true },
})
</script>
