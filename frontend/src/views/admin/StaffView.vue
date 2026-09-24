<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3"><h1 class="page-title">Manajemen Staf</h1><button @click="showForm = true; editing = null; form = { name: '', email: '', password: '', role: 'receptionist' }" class="btn-primary">+ Tambah Staf</button></div>
    <div class="table-container">
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Aksi</th></tr></thead>
        <tbody>
          <tr v-for="s in staff" :key="s.id">
            <td class="font-semibold">{{ s.name }}</td>
            <td>{{ s.email }}</td>
            <td><span class="badge bg-[var(--primary)]/10 text-[var(--primary)] capitalize">{{ roleLabel(s.role) }}</span></td>
            <td class="flex gap-2">
              <button @click="editing = s.id; form = { ...s, password: '' }; showForm = true" class="btn-ghost text-xs font-semibold">Edit</button>
              <button @click="deleteItem(s.id)" class="btn-ghost text-xs text-red-500 font-semibold">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content p-6 animate-slide-up">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Edit' : 'Tambah' }} Staf</h3>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div><label class="label">Nama</label><input v-model="form.name" class="input-field" required /></div>
          <div><label class="label">Email</label><input v-model="form.email" type="email" class="input-field" required /></div>
          <div><label class="label">Password {{ editing ? '(kosongkan jika tidak ubah)' : '' }}</label><input v-model="form.password" type="password" class="input-field" :required="!editing" /></div>
          <div><label class="label">Role</label>
            <select v-model="form.role" class="select-field" required>
              <option value="admin">Administrator</option><option value="receptionist">Resepsionis</option><option value="housekeeper">Tata Graha</option>
            </select>
          </div>
          <div class="flex gap-2 justify-end"><button type="button" @click="showForm = false" class="btn-ghost">Batal</button><button type="submit" class="btn-primary">Simpan</button></div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
const staff = ref([])
const showForm = ref(false)
const editing = ref(null)
const form = ref({ name: '', email: '', password: '', role: 'receptionist' })
onMounted(() => fetchData())
async function fetchData() { const res = await api.get('/admin/staff'); staff.value = res.data.data || res.data }
async function saveItem() {
  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
    if (editing.value) await api.put(`/admin/staff/${editing.value}`, payload)
    else await api.post('/admin/staff', payload)
    showForm.value = false
    fetchData()
  } catch (err) {
    if (err.response?.status === 422) {
      const errMsgs = Object.values(err.response.data.errors).flat().join('\n')
      alert('Gagal menyimpan:\n' + errMsgs)
    } else {
      alert(err.response?.data?.message || 'Terjadi kesalahan sistem.')
    }
  }
}
async function deleteItem(id) { if (!confirm('Hapus staf?')) return; await api.delete(`/admin/staff/${id}`); fetchData() }
function roleLabel(r) { const l = { admin: 'Administrator', receptionist: 'Resepsionis', housekeeper: 'Tata Graha' }; return l[r] || r }
</script>
