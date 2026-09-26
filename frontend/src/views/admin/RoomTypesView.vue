<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Tipe Kamar</h1>
      <BaseButton type="button" @click="openCreate">+ Tambah Tipe Kamar</BaseButton>
    </div>

    <AlertBox v-if="fetchError" variant="error" title="Gagal memuat tipe kamar">
      <div class="flex flex-wrap items-center gap-3 mt-1">
        <span>Periksa koneksi Anda lalu coba lagi.</span>
        <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
      </div>
    </AlertBox>

    <div v-else-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" aria-hidden="true">
      <div v-for="n in 3" :key="`room-type-skeleton-${n}`" class="card !p-0 overflow-hidden">
        <div class="skeleton h-40 rounded-none"></div>
        <div class="p-5 space-y-3">
          <div class="skeleton h-6 w-2/3"></div>
          <div class="skeleton h-8 w-1/2"></div>
          <div class="skeleton h-4 w-full"></div>
        </div>
      </div>
    </div>

    <div v-else-if="roomTypes.length === 0" class="card">
      <EmptyState title="Belum ada tipe kamar" description="Tambahkan tipe kamar pertama untuk mulai menerima reservasi.">
        <template #action>
          <BaseButton type="button" @click="openCreate">+ Tambah Tipe Kamar</BaseButton>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article v-for="rt in roomTypes" :key="rt.id" class="card !p-0 overflow-hidden group">
        <div class="h-40 bg-slate-100 relative overflow-hidden">
          <img v-if="getFirstImage(rt)" :src="getFirstImage(rt)" :alt="rt.name" class="w-full h-full object-cover" />
          <div v-else class="flex items-center justify-center h-full text-slate-300">
            <ImageIcon class="w-12 h-12" aria-hidden="true" />
          </div>
          <div v-if="(rt.images || []).length > 1" class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full backdrop-blur-sm">
            +{{ (rt.images || []).length - 1 }} foto
          </div>
        </div>
        <div class="p-5">
          <div class="flex items-start justify-between gap-3 mb-2">
            <h3 class="text-lg font-bold">{{ rt.name }}</h3>
            <span :class="rt.is_active ? 'badge-available' : 'badge-maintenance'" class="badge">{{ rt.is_active ? 'Aktif' : 'Nonaktif' }}</span>
          </div>
          <p class="text-2xl font-bold text-[var(--primary)] mb-3">{{ formatCurrency(rt.base_price) }}<span class="text-sm font-normal text-[var(--text-secondary)]">/malam</span></p>
          <div class="flex flex-wrap gap-1.5 mb-3">
            <span v-for="facility in rt.facilities" :key="facility.id" class="inline-flex items-center gap-1.5 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">
              <FacilityIcon :name="facility" class="h-3.5 w-3.5 shrink-0" />
              {{ facility.name }}
            </span>
          </div>
          <p class="text-xs text-[var(--text-secondary)]">{{ rt.capacity }} tamu · {{ rt.bed_type || '-' }} · {{ rt.size || '-' }}</p>
          <p class="text-xs text-[var(--text-secondary)] mt-1">{{ rt.rooms?.length || 0 }} kamar terdaftar</p>
          <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
            <BaseButton type="button" variant="ghost" size="sm" class="flex-1" @click="openEdit(rt)">Edit</BaseButton>
            <BaseButton type="button" variant="ghost" size="sm" class="flex-1 !text-danger hover:!bg-danger-soft" :loading="deletingId === rt.id" :disabled="deletingId === rt.id" @click="askDelete(rt.id)">Hapus</BaseButton>
          </div>
        </div>
      </article>
    </div>

    <BaseModal v-model="showForm" :title="editing ? 'Edit Tipe Kamar' : 'Tambah Tipe Kamar'" size="lg">
      <AlertBox v-if="formError" variant="error" class="mb-4" :title="formError">
        <ul v-if="formErrors.length" class="mt-1 list-disc list-inside text-xs">
          <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
        </ul>
      </AlertBox>
      <form @submit.prevent="saveItem" class="space-y-4">
        <BaseInput v-model="form.name" label="Nama" required />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="form.base_price" label="Harga/Malam" type="number" min="0" step="0.01" required />
          <BaseInput v-model="form.capacity" label="Kapasitas" type="number" min="1" step="1" required />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BaseInput v-model="form.size" label="Ukuran" placeholder="32 m²" />
          <BaseInput v-model="form.bed_type" label="Tipe Kasur" placeholder="King" />
        </div>
        <BaseTextarea v-model="form.description" label="Deskripsi" :rows="3" />

        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-4">
          <h4 class="font-bold text-sm text-slate-800 border-b border-slate-200 pb-2">Fasilitas Tersedia</h4>
          <div v-if="facilitiesByCategory.length">
            <p class="label mb-2 block">Pilih dari Master Data (Bisa pilih lebih dari satu)</p>
            <div class="space-y-4 max-h-56 overflow-y-auto pr-2">
              <div v-for="group in facilitiesByCategory" :key="group.category">
                <p class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ group.category }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <label v-for="facility in group.items" :key="facility.id" class="flex items-center gap-2 cursor-pointer border border-slate-200 bg-white px-3 py-2 rounded-lg hover:border-cyan-400 transition-colors">
                    <input type="checkbox" :value="facility.id" v-model="form.selectedFacilities" class="rounded text-cyan-500 focus:ring-cyan-500 border-slate-300 w-4 h-4" />
                    <FacilityIcon :name="facility" class="h-4 w-4 shrink-0" />
                    <span class="text-xs font-medium text-slate-700">{{ facility.name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200">
            <p class="label mb-2 block">Ketikan Custom Fasilitas (Opsional)</p>
            <p class="text-xs text-slate-500 mb-3">Pisahkan dengan koma (,). Contoh: handuk, pengering rambut.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="category in customCategories" :key="category">
                <BaseTextarea v-model="form.customFacilities[category]" :label="category" :rows="2" class="text-xs" :placeholder="`Contoh tambahan ${category.toLowerCase()}...`" />
              </div>
            </div>
          </div>
        </div>

        <div>
          <p class="label">Foto Kamar (maks. 3)</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-2">
            <div v-for="(image, index) in existingImages" :key="`existing-${image}`" class="relative h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
              <img :src="existingImageUrl(image)" alt="Foto tipe kamar" class="w-full h-full object-cover" />
              <BaseButton type="button" variant="danger" size="sm" class="absolute top-1.5 right-1.5 !min-h-9 !min-w-9 !p-0 rounded-full" aria-label="Hapus foto" @click="removeExistingImage(index)">×</BaseButton>
            </div>
            <div v-for="photo in newPhotos" :key="photo.id" class="relative h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
              <img :src="photo.preview" alt="Pratinjau foto" class="w-full h-full object-cover" />
              <BaseButton type="button" variant="danger" size="sm" class="absolute top-1.5 right-1.5 !min-h-9 !min-w-9 !p-0 rounded-full" aria-label="Hapus foto baru" @click="removeNewPhoto(photo.id)">×</BaseButton>
            </div>
            <label v-if="totalImages < 3" class="h-28 rounded-xl border-2 border-dashed border-slate-200 hover:border-cyan-400 bg-slate-50 hover:bg-cyan-50 flex flex-col items-center justify-center cursor-pointer transition-colors focus-within:ring-2 focus-within:ring-primary focus-within:ring-offset-2">
              <PlusCircle class="w-6 h-6 text-slate-400 mb-1" aria-hidden="true" />
              <span class="text-xs text-slate-500">Tambah Foto</span>
              <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" multiple class="sr-only peer" @change="onFileSelect" />
            </label>
          </div>
          <p class="text-xs text-slate-400 mt-2">{{ totalImages }}/3 foto · Format: JPG, PNG, WebP, AVIF · Maks 2MB</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded" />
          <span>Aktif</span>
        </label>
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton type="button" variant="ghost" @click="showForm = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">{{ editing ? 'Simpan' : 'Tambah' }}</BaseButton>
        </div>
      </form>
    </BaseModal>

    <ConfirmModal
      :visible="deleteTarget !== null"
      title="Hapus tipe kamar?"
      message="Tipe kamar hanya dapat dihapus jika tidak memiliki kamar terdaftar."
      confirm-text="Hapus Tipe Kamar"
      danger
      :loading="deletingId !== null"
      @cancel="deleteTarget = null"
      @confirm="deleteItem"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Image as ImageIcon, PlusCircle } from 'lucide-vue-next'
import api from '../../services/api'
import AlertBox from '../../components/ui/AlertBox.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import BaseTextarea from '../../components/ui/BaseTextarea.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import FacilityIcon from '../../components/icons/FacilityIcon.vue'
import { publicAssetUrl } from '../../utils/publicAssets'
import { roomImageUrl } from '../../utils/roomImages'

const MAX_PHOTO_BYTES = 2 * 1024 * 1024
const ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif']
const roomTypes = ref([])
const facilities = ref([])
const showForm = ref(false)
const editing = ref(null)
const saving = ref(false)
const loading = ref(false)
const deletingId = ref(null)
const deleteTarget = ref(null)
const fetchError = ref('')
let listSeq = 0
const formError = ref('')
const formErrors = ref([])
const form = ref(emptyForm())
const existingImages = ref([])
const newPhotos = ref([])

const customCategories = ['Kamar Mandi', 'Kamar Tidur', 'Makanan dan Minuman', 'Internet', 'Lainnya']

const facilitiesByCategory = computed(() => {
  const groups = {}
  facilities.value.forEach((facility) => {
    const category = facility.category || 'Lainnya'
    if (!groups[category]) groups[category] = []
    groups[category].push(facility)
  })
  return Object.entries(groups).map(([category, items]) => ({ category, items }))
})

const totalImages = computed(() => existingImages.value.length + newPhotos.value.length)

onUnmounted(() => {
  listSeq += 1
  newPhotos.value.forEach((photo) => URL.revokeObjectURL(photo.preview))
})

onMounted(() => {
  fetchData()
  fetchFacilities()
})

function emptyForm() {
  return {
    name: '',
    base_price: '',
    capacity: '',
    size: '',
    bed_type: '',
    description: '',
    is_active: true,
    selectedFacilities: [],
    customFacilities: {},
  }
}

function resetForm() {
  form.value = emptyForm()
  existingImages.value = []
  newPhotos.value.forEach((photo) => URL.revokeObjectURL(photo.preview))
  newPhotos.value = []
  formError.value = ''
  formErrors.value = []
}

function openCreate() {
  editing.value = null
  resetForm()
  showForm.value = true
}

function openEdit(roomType) {
  editing.value = roomType.id
  form.value = {
    name: roomType.name || '',
    base_price: roomType.base_price ?? '',
    capacity: roomType.capacity ?? '',
    size: roomType.size || '',
    bed_type: roomType.bed_type || '',
    description: roomType.description || '',
    is_active: !!roomType.is_active,
    selectedFacilities: (roomType.facilities || []).map((facility) => facility.id),
    customFacilities: { ...(roomType.custom_facilities || {}) },
  }
  existingImages.value = [...(roomType.images || [])]
  newPhotos.value.forEach((photo) => URL.revokeObjectURL(photo.preview))
  newPhotos.value = []
  formError.value = ''
  formErrors.value = []
  showForm.value = true
}

function getFirstImage(roomType) {
  return roomImageUrl(roomType)
}

function existingImageUrl(image) {
  return publicAssetUrl(image)
}

async function fetchData() {
  const seq = ++listSeq
  loading.value = true
  fetchError.value = ''
  try {
    const res = await api.get('/admin/room-types')
    if (seq !== listSeq) return
    roomTypes.value = res.data || []
  } catch (error) {
    if (seq === listSeq) fetchError.value = error.response?.data?.message || 'Tipe kamar tidak dapat dimuat.'
  } finally {
    if (seq === listSeq) loading.value = false
  }
}

async function fetchFacilities() {
  try {
    const res = await api.get('/admin/facilities')
    facilities.value = res.data || []
  } catch {
    facilities.value = []
  }
}

function onFileSelect(event) {
  const files = Array.from(event.target.files)
  const remaining = 3 - totalImages.value
  const accepted = []

  for (const file of files) {
    if (accepted.length >= remaining) break

    if (!ALLOWED_PHOTO_TYPES.includes(file.type)) {
      formError.value = `Format foto ${file.name} tidak didukung.`
      formErrors.value = ['Gunakan JPG, PNG, GIF, WebP, atau AVIF.']
      continue
    }

    if (file.size > MAX_PHOTO_BYTES) {
      formError.value = `Ukuran foto ${file.name} melebihi 2MB.`
      formErrors.value = ['Kompres atau perkecil foto sebelum diunggah.']
      continue
    }

    accepted.push(file)
  }

  accepted.forEach((file) => {
    newPhotos.value.push({ id: `${file.name}-${file.lastModified}-${globalThis.crypto?.randomUUID?.() || Math.random()}`, file, preview: URL.createObjectURL(file) })
  })
  event.target.value = ''
}

function removeExistingImage(index) {
  existingImages.value.splice(index, 1)
}

function removeNewPhoto(id) {
  const index = newPhotos.value.findIndex((photo) => photo.id === id)
  if (index === -1) return
  URL.revokeObjectURL(newPhotos.value[index].preview)
  newPhotos.value.splice(index, 1)
}

async function saveItem() {
  if (saving.value) return
  saving.value = true
  formError.value = ''
  formErrors.value = []
  try {
    const data = new FormData()
    data.append('name', form.value.name)
    data.append('base_price', form.value.base_price)
    data.append('capacity', form.value.capacity)
    if (form.value.size) data.append('size', form.value.size)
    if (form.value.bed_type) data.append('bed_type', form.value.bed_type)
    if (form.value.description) data.append('description', form.value.description)
    data.append('is_active', form.value.is_active ? '1' : '0')
    form.value.selectedFacilities.forEach((facilityId) => data.append('facilities[]', facilityId))
    if (Object.keys(form.value.customFacilities).length) data.append('custom_facilities', JSON.stringify(form.value.customFacilities))
    if (editing.value) data.append('keep_images', JSON.stringify(existingImages.value))
    newPhotos.value.forEach((photo) => data.append('photos[]', photo.file))

    if (editing.value) {
      data.append('_method', 'PUT')
      await api.post(`/admin/room-types/${editing.value}`, data, { headers: { 'Content-Type': undefined } })
    } else {
      await api.post('/admin/room-types', data, { headers: { 'Content-Type': undefined } })
    }
    showForm.value = false
    resetForm()
    await fetchData()
  } catch (error) {
    const response = error.response?.data
    formError.value = response?.message || 'Tipe kamar gagal disimpan.'
    formErrors.value = Object.values(response?.errors || {}).flat()
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
    await api.delete(`/admin/room-types/${id}`)
    await fetchData()
  } catch (error) {
    fetchError.value = error.response?.data?.message || 'Tipe kamar gagal dihapus.'
  } finally {
    deletingId.value = null
    deleteTarget.value = null
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}
</script>
