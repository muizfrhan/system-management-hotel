<template>
  <div class="text-sm">
    <dl class="space-y-2">
      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">Harga per malam</dt>
        <dd class="font-semibold text-slate-900 text-right">{{ formatCurrency(pricePerNight) }}</dd>
      </div>
      <div class="flex justify-between gap-3">
        <dt class="text-slate-500">{{ nights }} malam</dt>
        <dd class="font-semibold text-slate-900 text-right">{{ formatCurrency(pricePerNight * nights) }}</dd>
      </div>
      <div class="flex justify-between gap-3 border-t border-slate-100 pt-3">
        <dt class="font-semibold text-slate-900">Total estimasi</dt>
        <dd class="font-bold text-primary text-right text-base">{{ formatCurrency(pricePerNight * nights) }}</dd>
      </div>
    </dl>
    <p class="text-xs text-slate-400 leading-relaxed mt-3">
      <slot name="note">
        Total dihitung dari harga kamar × jumlah malam, sesuai perhitungan sistem. Biaya tambahan (jika ada) muncul setelah reservasi diproses.
      </slot>
    </p>
  </div>
</template>

<script setup>
import { formatCurrency } from '../../utils/dates'

defineProps({
  pricePerNight: { type: [Number, String], default: 0 },
  nights: { type: Number, default: 0 },
})
</script>
