<template>
  <BaseModal
    :model-value="visible"
    size="sm"
    :title="title"
    :show-close="false"
    @close="$emit('cancel')"
  >
    <p class="text-body text-ink-secondary">{{ message }}</p>
    <template #footer>
      <BaseButton variant="ghost" class="min-h-10" :disabled="loading" @click="$emit('cancel')">Batal</BaseButton>
      <BaseButton :variant="danger ? 'danger' : 'primary'" class="min-h-10" :loading="loading" :disabled="loading" @click="$emit('confirm')">
        {{ confirmText }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import BaseModal from '../ui/BaseModal.vue'
import BaseButton from '../ui/BaseButton.vue'

defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin?' },
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  danger: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

defineEmits(['confirm', 'cancel'])
</script>
