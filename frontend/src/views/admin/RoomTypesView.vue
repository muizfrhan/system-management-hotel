<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="page-title">Tipe Kamar</h1>
      <button @click="showForm = true; editing = null; resetForm()" class="btn-primary">+ Tambah Tipe Kamar</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="rt in roomTypes" :key="rt.id" class="card !p-0 overflow-hidden group">
        <div class="h-40 bg-slate-100 relative overflow-hidden">
          <img v-if="getFirstImage(rt)" :src="getFirstImage(rt)" :alt="rt.name" class="w-full h-full object-cover" />
          <div v-else class="flex items-center justify-center h-full text-slate-300">
            <ImageIcon class="w-12 h-12" />
          </div>
          <div v-if="(rt.images || []).length > 1" class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full backdrop-blur-sm">
            +{{ (rt.images || []).length - 1 }} foto
          </div>
        </div>
        <div class="p-5">
          <div class="flex items-start justify-between mb-2">
            <h3 class="text-lg font-bold">{{ rt.name }}</h3>
            <span :class="rt.is_active ? 'badge-available' : 'badge-maintenance'" class="badge">{{ rt.is_active ? 'Aktif' : 'Nonaktif' }}</span>
          </div>
          <p class="text-2xl font-bold text-[var(--primary)] mb-3">{{ formatCurrency(rt.base_price) }}<span class="text-sm font-normal text-[var(--text-secondary)]">/malam</span></p>
          <div class="flex flex-wrap gap-1.5 mb-3">
            <span v-for="f in rt.facilities" :key="f.id" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">{{ f.name }}</span>
          </div>
          <p class="text-xs text-[var(--text-secondary)]">{{ rt.capacity }} tamu · {{ rt.bed_type || '-' }} · {{ rt.size || '-' }}</p>
          <p class="text-xs text-[var(--text-secondary)] mt-1">{{ rt.rooms?.length || 0 }} kamar terdaftar</p>
          <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
            <button @click="editItem(rt)" class="btn-ghost text-xs !px-3 !py-1.5 font-semibold">Edit</button>
            <button @click="deleteItem(rt.id)" class="btn-ghost text-xs !px-3 !py-1.5 text-red-500 hover:!bg-red-50 font-semibold">Hapus</button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
      <div class="modal-content p-6 animate-slide-up max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold mb-4">{{ editing ? 'Edit' : 'Tambah' }} Tipe Kamar</h3>
        <div v-if="formError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-600 flex items-start gap-3">
          <span class="shrink-0 mt-0.5">⚠️</span>
          <div>
            <p class="font-semibold">{{ formError }}</p>
            <ul v-if="formErrors.length" class="mt-1 list-disc list-inside text-xs text-red-500">
              <li v-for="(e, i) in formErrors" :key="i">{{ e }}</li>
            </ul>
          </div>
        </div>
        <form @submit.prevent="saveItem" class="space-y-4">
          <div><label class="label">Nama</label><input v-model="form.name" class="input-field" required /></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="label">Harga/Malam</label><input v-model="form.base_price" type="number" class="input-field" required /></div>
            <div><label class="label">Kapasitas</label><input v-model="form.capacity" type="number" class="input-field" required /></div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label class="label">Ukuran</label><input v-model="form.size" class="input-field" placeholder="32 m²" /></div>
            <div><label class="label">Tipe Kasur</label><input v-model="form.bed_type" class="input-field" placeholder="King" /></div>
          </div>
          <div><label class="label">Deskripsi</label><textarea v-model="form.description" class="input-field" rows="3"></textarea></div>

          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-4">
            <h4 class="font-bold text-sm text-slate-800 border-b border-slate-200 pb-2">Fasilitas Tersedia</h4>
            <div v-if="Object.keys(facilitiesByCategory).length > 0">
              <label class="label mb-2 block">Pilih dari Master Data (Bisa pilih lebih dari satu)</label>
              <div class="space-y-4 max-h-56 overflow-y-auto pr-2">
                <div v-for="(facList, catName) in facilitiesByCategory" :key="catName">
                  <p class="text-[11px] font-bold text-slate-500 mb-2 uppercase tracking-wider">{{ catName }}</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label v-for="f in facList" :key="f.id" class="flex items-center gap-2 cursor-pointer border border-slate-200 bg-white px-3 py-2 rounded-lg hover:border-cyan-400 transition-colors">
                      <input type="checkbox" :value="f.id" v-model="form.selectedFacilities" class="rounded text-cyan-500 focus:ring-cyan-500 border-slate-300 w-4 h-4" />
                      <span class="text-xs font-medium text-slate-700">{{ f.name }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-200">
              <label class="label mb-2 block">Ketikan Custom Fasilitas (Opsional)</label>
              <p class="text-xs text-slate-500 mb-3">Jika fasilitas belum ada di atas, Anda bisa mengetiknya di sini. Pisahkan dengan koma (,). Contoh: handuk, pengering rambut.</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="cat in ['Kamar Mandi', 'Kamar Tidur', 'Makanan dan Minuman', 'Internet', 'Lainnya']" :key="cat">
                  <label class="text-xs font-semibold text-slate-600">{{ cat }}</label>
                  <textarea v-model="form.customFacilities[cat]" rows="2" class="input-field mt-1 text-xs" :placeholder="'Contoh tambahan ' + cat.toLowerCase() + '...'"></textarea>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="label">Foto Kamar (maks. 3)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-2">
              <div v-for="(img, idx) in existingImages" :key="'exist-'+idx" class="relative h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                <img :src="storageUrl + '/' + img" class="w-full h-full object-cover" />
                <button type="button" @click="removeExistingImage(idx)" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition-colors">✕</button>
              </div>
              <div v-for="(file, idx) in newPhotoPreviews" :key="'new-'+idx" class="relative h-28 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                <img :src="file" class="w-full h-full object-cover" />
                <button type="button" @click="removeNewPhoto(idx)" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition-colors">✕</button>
              </div>
              <label v-if="totalImages < 3" class="h-28 rounded-xl border-2 border-dashed border-slate-200 hover:border-cyan-400 bg-slate-50 hover:bg-cyan-50 flex flex-col items-center justify-center cursor-pointer transition-colors">
                <PlusCircle class="w-6 h-6 text-slate-400 mb-1" />
                <span class="text-xs text-slate-500">Tambah Foto</span>
                <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" multiple class="hidden" @change="onFileSelect" />
              </label>
            </div>
            <p class="text-xs text-slate-400 mt-2">{{ totalImages }}/3 foto · Format: JPG, PNG, WebP, AVIF · Maks 2MB</p>
          </div>

          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded" />
            <label for="is_active" class="text-sm">Aktif</label>
          </div>
          <div class="flex gap-2 justify-end pt-2">
            <button type="button" @click="showForm = false" class="btn-ghost">Batal</button>
            <button type="submit" :disabled="saving" class="btn-primary">
              <span v-if="saving">Menyimpan...</span>
              <span v-else>{{ editing ? 'Simpan' : 'Tambah' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Image as ImageIcon, PlusCircle } from 'lucide-vue-next'
import api from '../../services/api'

const storageUrl = '/storage'
const roomTypes = ref([])
const showForm = ref(false)
const editing = ref(null)
const saving = ref(false)
const formError = ref('')
const formErrors = ref([])
const form = ref({ name: '', base_price: '', capacity: '', size: '', bed_type: '', description: '', is_active: true, selectedFacilities: [], customFacilities: {} })

const existingImages = ref([])
const newPhotos = ref([])
const newPhotoPreviews = ref([])
const facilities = ref([])

const facilitiesByCategory = computed(() => {
  const groups = {}
  facilities.value.forEach(f => {
    const cat = f.category || 'Lainnya'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(f)
  })
  return groups
})

const totalImages = computed(() => existingImages.value.length + newPhotos.value.length)

onMounted(() => {
  fetchData()
  fetchFacilities()
})

async function fetchFacilities() {
  const res = await api.get('/admin/facilities')
  facilities.value = res.data
}

function getFirstImage(rt) {
  if (rt.images && rt.images.length > 0) {
    return storageUrl + '/' + rt.images[0]
  }
  return null
}

async function fetchData() {
  const res = await api.get('/admin/room-types')
  roomTypes.value = res.data
}

function resetForm() {
  form.value = { name: '', base_price: '', capacity: '', size: '', bed_type: '', description: '', is_active: true, selectedFacilities: [], customFacilities: {} }
  existingImages.value = []
  newPhotos.value = []
  newPhotoPreviews.value = []
  formError.value = ''
  formErrors.value = []
}

function editItem(rt) {
  editing.value = rt.id
  form.value = { name: rt.name, base_price: rt.base_price, capacity: rt.capacity, size: rt.size, bed_type: rt.bed_type, description: rt.description, is_active: rt.is_active, selectedFacilities: rt.facilities ? rt.facilities.map(f => f.id) : [], customFacilities: rt.custom_facilities || {} }
  existingImages.value = [...(rt.images || [])]
  newPhotos.value = []
  newPhotoPreviews.value = []
  formError.value = ''
  formErrors.value = []
  showForm.value = true
}

function onFileSelect(e) {
  const files = Array.from(e.target.files)
  const remaining = 3 - totalImages.value
  const toAdd = files.slice(0, remaining)
  toAdd.forEach(f => {
    newPhotos.value.push(f)
    const reader = new FileReader()
    reader.onload = (ev) => newPhotoPreviews.value.push(ev.target.result)
    reader.readAsDataURL(f)
  })
  e.target.value = ''
}

function removeExistingImage(idx) {
  existingImages.value.splice(idx, 1)
}

function removeNewPhoto(idx) {
  newPhotos.value.splice(idx, 1)
  newPhotoPreviews.value.splice(idx, 1)
}

async function saveItem() {
  saving.value = true
  formError.value = ''
  formErrors.value = []
  try {
    const fd = new FormData()
    fd.append('name', form.value.name)
    fd.append('base_price', form.value.base_price)
    fd.append('capacity', form.value.capacity)
    if (form.value.size) fd.append('size', form.value.size)
    if (form.value.bed_type) fd.append('bed_type', form.value.bed_type)
    if (form.value.description) fd.append('description', form.value.description)
    fd.append('is_active', form.value.is_active ? '1' : '0')

    form.value.selectedFacilities.forEach(fId => {
      fd.append('facilities[]', fId)
    })

    if (Object.keys(form.value.customFacilities).length > 0) {
      fd.append('custom_facilities', JSON.stringify(form.value.customFacilities))
    }

    if (editing.value) {
      fd.append('keep_images', JSON.stringify(existingImages.value))
    }

    newPhotos.value.forEach(f => fd.append('photos[]', f))

    if (editing.value) {
      fd.append('_method', 'PUT')
      await api.post(`/admin/room-types/${editing.value}`, fd, { headers: { 'Content-Type': undefined } })
    } else {
      await api.post('/admin/room-types', fd, { headers: { 'Content-Type': undefined } })
    }
    showForm.value = false
    fetchData()
  } catch (err) {
    const data = err.response?.data
    formError.value = data?.message || 'Gagal menyimpan data'
    if (data?.errors) {
      formErrors.value = Object.values(data.errors).flat()
    }
  } finally {
    saving.value = false
  }
}

async function deleteItem(id) {
  if (!confirm('Hapus tipe kamar ini?')) return
  await api.delete(`/admin/room-types/${id}`)
  fetchData()
}

function formatCurrency(val) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}
</script>
