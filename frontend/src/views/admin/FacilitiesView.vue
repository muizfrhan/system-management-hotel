<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Fasilitas</h1>
      <BaseButton type="button" @click="openCreate">+ Tambah</BaseButton>
    </div>

    <AlertBox v-if="fetchError" variant="error" title="Gagal memuat fasilitas">
      <div class="flex flex-wrap items-center gap-3 mt-1">
        <span>Periksa koneksi Anda lalu coba lagi.</span>
        <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
      </div>
    </AlertBox>

    <div v-else-if="loading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" aria-hidden="true">
      <div v-for="n in 6" :key="`facility-skeleton-${n}`" class="card !p-4">
        <div class="skeleton mx-auto w-8 h-8 rounded-xl"></div>
        <div class="skeleton mx-auto mt-3 w-20 h-4"></div>
        <div class="skeleton mx-auto mt-2 w-14 h-3"></div>
        <div class="skeleton mt-4 w-full h-10"></div>
      </div>
    </div>

    <div v-else-if="facilities.length === 0" class="card">
      <EmptyState title="Belum ada fasilitas" description="Tambahkan fasilitas agar dapat ditampilkan pada tipe kamar.">
        <template #action>
          <BaseButton type="button" @click="openCreate">+ Tambah Fasilitas</BaseButton>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div v-for="f in facilities" :key="f.id" class="card !p-4 text-center">
        <div class="mb-3 flex justify-center">
          <FacilityIcon :name="f" class="w-9 h-9" />
        </div>
        <p class="font-semibold text-sm">{{ f.name }}</p>
        <p class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 mt-1 rounded-full w-max mx-auto">{{ f.category || 'Lainnya' }}</p>
        <div class="flex flex-col gap-1 justify-center mt-3 border-t border-slate-100 pt-3">
          <BaseButton type="button" variant="ghost" size="sm" class="w-full" @click="openEdit(f)">Edit</BaseButton>
          <BaseButton type="button" variant="ghost" size="sm" class="w-full !text-danger hover:!bg-danger-soft" :loading="deletingId === f.id" :disabled="deletingId === f.id" @click="askDelete(f.id)">Hapus</BaseButton>
        </div>
      </div>
    </div>

    <BaseModal v-model="showForm" :title="editing ? 'Edit Fasilitas' : 'Tambah Fasilitas'" size="sm">
      <AlertBox v-if="formError" variant="error" class="mb-4" :title="formError" />
      <form @submit.prevent="saveItem" class="space-y-4">
        <BaseInput v-model="form.name" label="Nama" required />
        <BaseSelect v-model="form.category" label="Kategori" required>
          <option value="Kamar Mandi">Kamar Mandi</option>
          <option value="Kamar Tidur">Kamar Tidur</option>
          <option value="Makanan dan Minuman">Makanan dan Minuman</option>
          <option value="Internet">Internet</option>
          <option value="Lainnya">Lainnya</option>
        </BaseSelect>
        <BaseSelect v-model="form.icon" label="Ikon">
          <option value="">Tanpa ikon khusus</option>
          <option v-for="option in FACILITY_ICON_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
        </BaseSelect>
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton type="button" variant="ghost" @click="showForm = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">Simpan</BaseButton>
        </div>
      </form>
    </BaseModal>

    <ConfirmModal
      :visible="deleteTarget !== null"
      title="Hapus fasilitas?"
      message="Fasilitas yang dihapus tidak dapat digunakan kembali pada tipe kamar."
      confirm-text="Hapus Fasilitas"
      danger
      :loading="deletingId !== null"
      @cancel="deleteTarget = null"
      @confirm="deleteItem"
    />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import api from '../../services/api'
import AlertBox from '../../components/ui/AlertBox.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import BaseSelect from '../../components/ui/BaseSelect.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import FacilityIcon from '../../components/icons/FacilityIcon.vue'
import { FACILITY_ICON_OPTIONS } from '../../utils/facilityIcons'

const facilities = ref([])
const showForm = ref(false)
const editing = ref(null)
const form = ref({ name: '', icon: '', category: 'Lainnya' })
const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const deleteTarget = ref(null)
const fetchError = ref('')
const formError = ref('')
let listSeq = 0

onMounted(fetchData)

onUnmounted(() => {
  listSeq += 1
})

function resetForm() {
  form.value = { name: '', icon: '', category: 'Lainnya' }
  formError.value = ''
}

function openCreate() {
  editing.value = null
  resetForm()
  showForm.value = true
}

function openEdit(facility) {
  editing.value = facility.id
  form.value = { name: facility.name || '', icon: facility.icon || '', category: facility.category || 'Lainnya' }
  formError.value = ''
  showForm.value = true
}

async function fetchData() {
  const seq = ++listSeq
  loading.value = true
  fetchError.value = ''
  try {
    const res = await api.get('/admin/facilities')
    if (seq !== listSeq) return
    facilities.value = res.data || []
  } catch {
    if (seq === listSeq) fetchError.value = 'Fasilitas tidak dapat dimuat.'
  } finally {
    if (seq === listSeq) loading.value = false
  }
}

async function saveItem() {
  if (saving.value) return
  saving.value = true
  formError.value = ''
  try {
    if (editing.value) await api.put(`/admin/facilities/${editing.value}`, form.value)
    else await api.post('/admin/facilities', form.value)
    showForm.value = false
    await fetchData()
  } catch (error) {
    const data = error.response?.data
    formError.value = data?.message || Object.values(data?.errors || {}).flat()[0] || 'Fasilitas gagal disimpan.'
  } finally {
    saving.value = false
  }
}

function askDelete(id) {
  if (deletingId.value) return
  deleteTarget.value = id
}

async function deleteItem() {
  const id = deleteTarget.value
  if (deletingId.value || id === null) return
  deletingId.value = id
  try {
    await api.delete(`/admin/facilities/${id}`)
    await fetchData()
  } catch (error) {
    fetchError.value = error.response?.data?.message || 'Fasilitas gagal dihapus.'
  } finally {
    deletingId.value = null
    deleteTarget.value = null
  }
}
</script>
