<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Fasilitas</h1>
      <button @click="showForm = true; editing = null; form = { name: '', icon: '', category: 'Lainnya' }" class="btn-primary">+ Tambah</button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div v-for="f in facilities" :key="f.id" class="card !p-4 text-center">
        <div class="mb-3 flex justify-center text-primary-light">
          <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11.5 15.5 3-3"/><path d="m10.5 14.5-3 3"/><path d="m15.5 11.5 3-3"/><path d="m14.5 10.5-3 3"/><path d="M5 6v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z"/></svg>
        </div>
        <p class="font-semibold text-sm">{{ f.name }}</p>
        <p class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 mt-1 rounded-full w-max mx-auto">{{ f.category || 'Lainnya' }}</p>
        <div class="flex flex-col gap-1 justify-center mt-3 border-t border-slate-100 pt-3">
          <button @click="editing = f.id; form = { ...f }; showForm = true" class="btn-ghost text-xs !px-2 !py-1">Edit</button>
          <button @click="deleteItem(f.id)" class="btn-ghost text-xs !px-2 !py-1 text-red-500">Hapus</button>
        </div>
      </div>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content p-6 animate-slide-up">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Edit' : 'Tambah' }} Fasilitas</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div><label class="label">Nama</label><input v-model="form.name" class="input-field" required /></div>
          <div>
            <label class="label">Kategori</label>
            <select v-model="form.category" class="input-field" required>
              <option value="Kamar Mandi">Kamar Mandi</option>
              <option value="Kamar Tidur">Kamar Tidur</option>
              <option value="Makanan dan Minuman">Makanan dan Minuman</option>
              <option value="Internet">Internet</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div><label class="label">Icon (opsional)</label><input v-model="form.icon" class="input-field" /></div>
          <div class="flex gap-2 justify-end"><button type="button" @click="showForm = false" class="btn-ghost">Batal</button><button type="submit" class="btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
const facilities = ref([])
const showForm = ref(false)
const editing = ref(null)
const form = ref({ name: '', icon: '', category: 'Lainnya' })
onMounted(() => fetchData())
async function fetchData() { const res = await api.get('/admin/facilities'); facilities.value = res.data }
async function saveItem() {
  if (editing.value) await api.put(`/admin/facilities/${editing.value}`, form.value)
  else await api.post('/admin/facilities', form.value)
  showForm.value = false; fetchData()
}
async function deleteItem(id) { if (!confirm('Hapus?')) return; await api.delete(`/admin/facilities/${id}`); fetchData() }
</script>
