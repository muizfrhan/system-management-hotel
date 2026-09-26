<template>
  <div class="space-y-6">
    <PageHeader title="Manajemen Staf" description="Kelola akun staf dan hak akses peran.">
      <template #actions>
        <BaseButton
          @click="
            showForm = true;
            editing = null;
            form = { name: '', email: '', password: '', role: 'receptionist' };
          "
        >
          + Tambah Staf
        </BaseButton>
      </template>
    </PageHeader>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">Nama</th>
            <th scope="col">Email</th>
            <th scope="col">Role</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in staff" :key="s.id">
            <td class="font-semibold">{{ s.name }}</td>
            <td>{{ s.email }}</td>
            <td>
              <BaseBadge tone="primary">{{ roleLabel(s.role) }}</BaseBadge>
            </td>
            <td>
              <div class="flex flex-wrap gap-1">
                <BaseButton
                  variant="ghost"
                  size="sm"
                  class="min-h-10"
                  @click="
                    editing = s.id;
                    form = { ...s, password: '' };
                    showForm = true;
                  "
                >
                  Edit
                </BaseButton>
                <BaseButton
                  variant="ghost"
                  size="sm"
                  class="min-h-10 !text-danger hover:!bg-danger-soft"
                   :loading="deletingId === s.id"
                    :disabled="deletingId === s.id"
                    @click="askDelete(s.id)"
                 >
                   Hapus
                </BaseButton>
              </div>
            </td>
          </tr>
          <tr v-if="!staff.length">
            <td colspan="4" class="!p-0">
              <EmptyState
                title="Belum ada staf"
                description="Tambahkan akun staf untuk mulai mengelola tim."
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BaseModal v-model="showForm" :title="editing ? 'Edit Staf' : 'Tambah Staf'" size="sm">
      <form @submit.prevent="saveItem" class="space-y-4">
        <BaseInput v-model="form.name" label="Nama" required />
        <BaseInput v-model="form.email" label="Email" type="email" required />
        <BaseInput
          v-model="form.password"
          label="Password"
          :hint="editing ? 'Kosongkan jika tidak ingin mengubah. Minimal 12 karakter.' : 'Minimal 12 karakter'"
          type="password"
          autocomplete="new-password"
          minlength="12"
          maxlength="1024"
          :required="!editing"
        />
        <BaseSelect v-model="form.role" label="Role" required>
          <option value="admin">Administrator</option>
          <option value="receptionist">Resepsionis</option>
          <option value="housekeeper">Tata Graha</option>
        </BaseSelect>
        <div class="flex gap-2 justify-end pt-2 border-t border-border-subtle">
          <BaseButton variant="ghost" type="button" @click="showForm = false">Batal</BaseButton>
          <BaseButton type="submit" :loading="saving" :disabled="saving">Simpan</BaseButton>
        </div>
      </form>
    </BaseModal>

    <ConfirmModal
      :visible="deleteTarget !== null"
      title="Hapus staf?"
      message="Akun staf akan kehilangan akses ke sistem."
      confirm-text="Hapus Staf"
      danger
      :loading="deletingId !== null"
      @cancel="deleteTarget = null"
      @confirm="deleteItem"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseSelect from '../../components/ui/BaseSelect.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'

const toast = useToast()
const staff = ref([])
const showForm = ref(false)
const editing = ref(null)
const form = ref({ name: '', email: '', password: '', role: 'receptionist' })
const saving = ref(false)
const deletingId = ref(null)
const deleteTarget = ref(null)

onMounted(() => fetchData())

async function fetchData() {
  try {
    const res = await api.get('/admin/staff')
    staff.value = res.data.data || res.data
  } catch (error) {
    toast.error(error.response?.data?.message || 'Data staf tidak dapat dimuat.')
  }
}

async function saveItem() {
  if (saving.value) return
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
    if (editing.value) await api.put(`/admin/staff/${editing.value}`, payload)
    else await api.post('/admin/staff', payload)
    showForm.value = false
    await fetchData()
    toast.success('Data staf disimpan.')
  } catch (err) {
    if (err.response?.status === 422) {
      const errMsgs = Object.values(err.response.data.errors).flat().join(', ')
      toast.error('Gagal menyimpan: ' + errMsgs)
    } else {
      toast.error(err.response?.data?.message || 'Terjadi kesalahan sistem.')
    }
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
    await api.delete(`/admin/staff/${id}`)
    await fetchData()
    toast.success('Staf dihapus.')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Staf gagal dihapus.')
  } finally {
    deletingId.value = null
    deleteTarget.value = null
  }
}

function roleLabel(r) {
  const l = { admin: 'Administrator', receptionist: 'Resepsionis', housekeeper: 'Tata Graha' }
  return l[r] || r
}
</script>
