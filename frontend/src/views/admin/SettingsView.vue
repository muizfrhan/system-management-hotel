<template>
  <div class="space-y-6">
     <h1 class="page-title">Pengaturan Hotel</h1>
     <AlertBox v-if="loadError" variant="error" class="max-w-2xl" :title="loadError" />
      <form @submit.prevent="saveSetting" :aria-busy="loading || saving" class="card max-w-2xl space-y-4">
      <div><label class="label" for="hotel-name">Nama Hotel</label><input id="hotel-name" v-model="form.hotel_name" class="input-field" /></div>
      <div><label class="label" for="hotel-tagline">Tagline</label><input id="hotel-tagline" v-model="form.tagline" class="input-field" /></div>
      <div><label class="label" for="hotel-address">Alamat</label><textarea id="hotel-address" v-model="form.address" class="textarea-field" rows="2"></textarea></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="label" for="hotel-phone">Telepon</label><input id="hotel-phone" v-model="form.phone" class="input-field" /></div>
        <div><label class="label" for="hotel-email">Email</label><input id="hotel-email" v-model="form.email" type="email" class="input-field" /></div>
      </div>
       <AlertBox v-if="saveError" variant="error" class="mt-4" :title="saveError" />
       <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <p v-if="saved" class="text-sm text-emerald-600 font-semibold bg-emerald-50 px-3 py-1.5 rounded-lg flex items-center gap-2">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
          Berhasil Disimpan!
        </p>
        <p v-else></p>
         <BaseButton type="submit" :loading="saving" :disabled="loading || saving || !!loadError" class="w-full sm:w-auto">Simpan Pengaturan</BaseButton>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import BaseButton from '../../components/ui/BaseButton.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import { useLandingData } from '../../composables/useLandingData'

const { load } = useLandingData()
const form = ref({ hotel_name: '', tagline: '', address: '', phone: '', email: '' })
const saved = ref(false)
const saving = ref(false)
const loading = ref(false)
const loadError = ref('')
const saveError = ref('')

onMounted(async () => {
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.get('/admin/settings')
    if (res.data) form.value = res.data
  } catch {
    loadError.value = 'Pengaturan tidak dapat dimuat.'
  } finally {
    loading.value = false
  }
})

async function saveSetting() {
  if (saving.value || loading.value || loadError.value) return
  saving.value = true
  saveError.value = ''
  saved.value = false
  try {
    const payload = {
      hotel_name: form.value.hotel_name,
      tagline: form.value.tagline,
      address: form.value.address,
      phone: form.value.phone,
      email: form.value.email,
    }
    await api.put('/admin/settings', payload)
    await load(true).catch(() => {})
    saved.value = true
    setTimeout(() => saved.value = false, 3000)
  } catch (error) {
    saveError.value = error.response?.data?.message || 'Pengaturan gagal disimpan.'
  } finally {
    saving.value = false
  }
}
</script>
