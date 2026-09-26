<template>
  <div class="space-y-6">
    <PageHeader
      title="Tata Graha"
      description="Kelola pembersihan dan kondisi kamar."
    >
      <template #actions>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Cari nomor / tipe kamar..."
          class="input-field w-full sm:!w-56"
          aria-label="Cari kamar"
        />
        <select v-model="filterStatus" class="select-field !w-auto" aria-label="Filter status">
          <option value="">Semua Status</option>
          <option value="cleaning">Perlu Dibersihkan</option>
          <option value="available">Bersih</option>
          <option value="occupied">Terisi</option>
          <option value="reserved">Dipesan</option>
          <option value="maintenance">Pemeliharaan</option>
        </select>
        <BaseButton variant="outline" size="sm" :loading="loading" @click="loadAll">
          Segarkan
        </BaseButton>
      </template>
    </PageHeader>

    <!-- TODAY'S TASKS -->
    <section v-if="showLoadedContent" aria-labelledby="today-tasks-heading">
      <h2 id="today-tasks-heading" class="sr-only">Tugas Hari Ini</h2>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard
          label="Perlu Dibersihkan"
          :value="counts.cleaning"
          color="var(--color-warning)"
          :border-color="counts.cleaning > 0 ? 'var(--color-warning)' : ''"
        />
        <StatCard
          label="Bersih"
          :value="counts.available"
          color="var(--color-success)"
        />
        <StatCard
          label="Terisi"
          :value="counts.occupied"
          color="var(--color-info)"
        />
        <StatCard
          label="Perhatian"
          :value="counts.maintenance + counts.reserved"
          color="var(--color-danger)"
        />
      </div>
    </section>

    <!-- ERROR -->
    <AlertBox v-if="loadError" variant="error" title="Gagal memuat tugas">
      <p>Tidak dapat memuat data housekeeping. Periksa koneksi lalu coba lagi.</p>
      <BaseButton size="sm" class="mt-2" :loading="loading" @click="loadAll">Coba Lagi</BaseButton>
    </AlertBox>

    <div v-if="showLoadedContent" class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
      <!-- TASK QUEUE -->
      <section class="xl:col-span-2 space-y-4" aria-labelledby="queue-heading">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <h2 id="queue-heading" class="text-base font-bold text-ink">
            Antrean Pembersihan
            <span v-if="!loading && queueRooms.length" class="ml-2">
              <span class="badge badge-cleaning">{{ queueRooms.length }}</span>
            </span>
          </h2>
        </div>

        <div v-if="loading && !loadedOnce" class="space-y-3">
          <div v-for="i in 4" :key="i" class="card !p-4">
            <div class="flex items-center justify-between gap-4">
              <div class="space-y-2 flex-1">
                <div class="skeleton skeleton-text" style="width: 6rem"></div>
                <div class="skeleton skeleton-text" style="width: 10rem"></div>
              </div>
              <div class="skeleton" style="height: 2.25rem; width: 8rem; border-radius: 0.5rem"></div>
            </div>
          </div>
        </div>

        <div v-else-if="!queueRooms.length" class="card">
          <EmptyState
            :icon="Sparkles"
            title="Semua kamar sudah bersih"
            description="Tidak ada kamar yang perlu dibersihkan saat ini."
          />
        </div>

        <ul v-else class="space-y-3" role="list">
          <li v-for="room in queueRooms" :key="room.id">
            <article class="card !p-4 sm:!p-5">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-lg font-extrabold text-ink tracking-tight">
                      Kamar {{ room.room_number }}
                    </p>
                    <span class="badge badge-cleaning">
                      <Sparkles class="w-3 h-3" aria-hidden="true" />
                      Perlu Dibersihkan
                    </span>
                  </div>
                  <p class="text-sm text-ink-secondary mt-1">
                    {{ room.room_type?.name || 'Tipe -' }}
                    <span v-if="room.floor"> · Lt.{{ room.floor }}</span>
                  </p>
                  <p class="text-xs text-ink-muted mt-0.5">
                    Diperbarui {{ formatRelative(room.updated_at) }}
                  </p>
                </div>
                <BaseButton
                  variant="success"
                  :loading="busyId === room.id"
                  :disabled="busyId !== null && busyId !== room.id"
                  class="w-full sm:w-auto shrink-0 !min-h-11"
                  @click="askComplete(room)"
                >
                  Tandai Selesai
                </BaseButton>
              </div>
            </article>
          </li>
        </ul>
      </section>

      <!-- ROOM STATUS -->
      <section class="space-y-4" aria-labelledby="status-heading">
        <h2 id="status-heading" class="text-base font-bold text-ink">Status Kamar</h2>

        <div v-if="loading && !loadedOnce" class="card space-y-3">
          <div v-for="i in 6" :key="i" class="skeleton skeleton-text" style="height: 2.5rem"></div>
        </div>

        <div v-else-if="!rooms.length" class="card">
          <EmptyState title="Tidak ada data kamar" description="Muat ulang untuk mencoba lagi." />
        </div>

        <div v-else class="card !p-0 overflow-hidden">
          <ul class="divide-y divide-border-subtle" role="list">
            <li
              v-for="room in overviewRooms"
              :key="room.id"
              class="flex items-center justify-between gap-3 px-4 py-3"
            >
              <div class="min-w-0">
                <p class="font-semibold text-ink text-sm">
                  Kamar {{ room.room_number }}
                </p>
                <p class="text-xs text-ink-secondary truncate">
                  {{ room.room_type?.name || '-' }}
                  <span v-if="room.floor"> · Lt.{{ room.floor }}</span>
                </p>
              </div>
              <RoomStatusBadge :status="room.status" />
            </li>
          </ul>
          <p
            v-if="filteredRooms.length < rooms.length"
            class="text-xs text-ink-muted px-4 py-3 border-t border-border-subtle"
          >
            Menampilkan {{ filteredRooms.length }} dari {{ rooms.length }} kamar
            <span v-if="searchQuery || filterStatus">(filter aktif)</span>.
          </p>
        </div>
      </section>
    </div>

    <ConfirmModal
      :visible="confirmOpen"
      title="Tandai Selesai?"
      :message="confirmMessage"
      confirm-text="Ya, Selesai"
      @confirm="completeCleaning"
      @cancel="confirmOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Sparkles } from 'lucide-vue-next'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import StatCard from '../../components/ui/StatCard.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'
import RoomStatusBadge from '../../components/housekeeping/RoomStatusBadge.vue'

const auth = useAuthStore()
const toast = useToast()
const prefix = auth.userRole === 'housekeeper' ? '/housekeeper' : '/admin'

const rooms = ref([])
const loading = ref(false)
const loadedOnce = ref(false)
const loadError = ref(false)
const searchQuery = ref('')
const filterStatus = ref('')
const busyId = ref(null)
const confirmOpen = ref(false)
const completeTarget = ref(null)

const showLoadedContent = computed(() => {
  return !loadError.value && (loading.value || loadedOnce.value)
})

const counts = computed(() => {
  const c = { cleaning: 0, available: 0, occupied: 0, reserved: 0, maintenance: 0 }
  for (const r of rooms.value) {
    if (c[r.status] !== undefined) c[r.status]++
  }
  return c
})

const queueRooms = computed(() => {
  const q = rooms.value.filter((r) => r.status === 'cleaning')
  const term = searchQuery.value.trim().toLowerCase()
  if (!term) return q
  return q.filter(
    (r) =>
      String(r.room_number || '').toLowerCase().includes(term) ||
      (r.room_type?.name || '').toLowerCase().includes(term)
  )
})

const filteredRooms = computed(() => {
  const term = searchQuery.value.trim().toLowerCase()
  return rooms.value.filter((r) => {
    if (filterStatus.value && r.status !== filterStatus.value) return false
    if (!term) return true
    return (
      String(r.room_number || '').toLowerCase().includes(term) ||
      (r.room_type?.name || '').toLowerCase().includes(term)
    )
  })
})

const overviewRooms = computed(() => filteredRooms.value)

const confirmMessage = computed(() => {
  const r = completeTarget.value
  if (!r) return 'Apakah Anda yakin?'
  return `Kamar ${r.room_number} akan ditandai bersih dan siap digunakan.`
})

onMounted(loadAll)

async function loadAll() {
  loading.value = true
  try {
    const res = await api.get(`${prefix}/rooms`)
    rooms.value = Array.isArray(res.data) ? res.data : res.data.data || []
    loadError.value = false
    loadedOnce.value = true
  } catch {
    loadError.value = true
    if (!loadedOnce.value) toast.error('Gagal memuat data housekeeping.')
  } finally {
    loading.value = false
  }
}

function askComplete(room) {
  completeTarget.value = room
  confirmOpen.value = true
}

async function completeCleaning() {
  const room = completeTarget.value
  confirmOpen.value = false
  if (!room || busyId.value) return

  busyId.value = room.id
  try {
    const res = await api.patch(`${prefix}/housekeeping/${room.id}/done`)
    const updated = res.data?.room
    if (updated) {
      const idx = rooms.value.findIndex((r) => r.id === updated.id)
      if (idx !== -1) rooms.value[idx] = { ...rooms.value[idx], ...updated }
      else rooms.value.push(updated)
    } else {
      const idx = rooms.value.findIndex((r) => r.id === room.id)
      if (idx !== -1) rooms.value[idx] = { ...rooms.value[idx], status: 'available' }
    }
    toast.success(res.data?.message || `Kamar ${room.room_number} ditandai bersih.`)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal memperbarui status kamar.')
  } finally {
    busyId.value = null
    completeTarget.value = null
  }
}

function formatRelative(d) {
  if (!d) return '-'
  const diff = Date.now() - new Date(d).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'baru saja'
  if (mins < 60) return `${mins} menit lalu`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours} jam lalu`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days} hari lalu`
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>
