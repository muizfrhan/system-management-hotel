<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="$emit('cancel')">
      <div class="modal-content p-6 animate-slide-up" style="max-width: 24rem;">
        <h3 class="text-lg font-bold mb-2" style="color: var(--text-primary)">{{ title }}</h3>
        <p class="text-sm mb-6" style="color: var(--text-secondary)">{{ message }}</p>
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
          <button @click="$emit('cancel')" class="btn-ghost">Batal</button>
          <button @click="$emit('confirm')" :class="danger ? 'btn-danger' : 'btn-primary'">{{ confirmText }}</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin?' },
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  danger: { type: Boolean, default: false },
})

defineEmits(['confirm', 'cancel'])
</script>
