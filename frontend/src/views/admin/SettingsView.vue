<template>
  <div class="space-y-6">
    <h1 class="page-title">Pengaturan Hotel</h1>
    <form @submit.prevent="saveSetting" class="card max-w-2xl space-y-4">
      <div><label class="label">Nama Hotel</label><input v-model="form.hotel_name" class="input-field" /></div>
      <div><label class="label">Tagline</label><input v-model="form.tagline" class="input-field" /></div>
      <div><label class="label">Alamat</label><textarea v-model="form.address" class="input-field" rows="2"></textarea></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><label class="label">Telepon</label><input v-model="form.phone" class="input-field" /></div>
        <div><label class="label">Email</label><input v-model="form.email" type="email" class="input-field" /></div>
      </div>
      <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <p v-if="saved" class="text-sm text-emerald-600 font-semibold bg-emerald-50 px-3 py-1.5 rounded-lg flex items-center gap-2">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
          Berhasil Disimpan!
        </p>
        <p v-else></p>
        <button type="submit" class="btn-primary shadow-lg shadow-primary/20 hover:-translate-y-0.5 transition-transform w-full sm:w-auto">Simpan Pengaturan</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
const form = ref({ hotel_name: '', tagline: '', address: '', phone: '', email: '' })
const saved = ref(false)
onMounted(async () => { const res = await api.get('/admin/settings'); if (res.data) form.value = res.data })
async function saveSetting() { await api.put('/admin/settings', form.value); saved.value = true; setTimeout(() => saved.value = false, 3000) }
</script>
