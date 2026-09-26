<template>
  <div class="space-y-6">
    <PageHeader title="Data Tamu" description="Cari dan tinjau profil tamu beserta riwayat reservasi.">
      <template #actions>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Cari nama, email, atau telepon..."
          class="input-field w-full sm:!w-64"
          aria-label="Cari tamu"
          @input="onSearch"
        />
      </template>
    </PageHeader>

     <div v-if="loading && !loaded" class="card space-y-3" aria-hidden="true">
       <div v-for="i in 4" :key="`guest-skeleton-${i}`" class="skeleton skeleton-table-row"></div>
     </div>

     <AlertBox v-else-if="loadError" variant="error" title="Gagal memuat data tamu">
       <div class="flex flex-wrap items-center gap-3 mt-1">
         <span>{{ loadError }}</span>
         <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
       </div>
     </AlertBox>

     <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">Nama</th>
            <th scope="col">Email</th>
            <th scope="col">Telepon</th>
            <th scope="col">Reservasi</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="g in guests" :key="g.id">
            <td class="font-semibold">{{ g.name }}</td>
            <td>{{ g.email || '-' }}</td>
            <td>{{ g.phone || '-' }}</td>
            <td>{{ g.reservations_count }} kali</td>
            <td>
              <BaseButton variant="ghost" size="sm" class="min-h-10" @click="openProfile(g)">Profil</BaseButton>
            </td>
          </tr>
          <tr v-if="!guests.length">
            <td colspan="5" class="!p-0">
              <EmptyState
                :title="searchQuery ? 'Tidak ada hasil' : 'Tidak ada tamu'"
                :description="
                  searchQuery
                    ? 'Coba kata kunci lain.'
                    : 'Data tamu akan muncul di sini setelah ada reservasi.'
                "
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav
       v-if="!loadError && pagination && pagination.last_page > 1"
      class="flex flex-wrap justify-center gap-2"
      aria-label="Navigasi halaman"
    >
      <button
        v-for="p in visiblePages"
        :key="p"
        type="button"
        @click="page = p; fetchData()"
        :class="[
          'btn btn-ghost btn-sm min-h-10 min-w-10',
          p === page ? '!bg-primary !text-white hover:!bg-primary-hover' : '',
        ]"
        :aria-current="p === page ? 'page' : undefined"
      >
        {{ p }}
      </button>
    </nav>

    <BaseModal
      v-model="profileOpen"
      title="Profil Tamu"
      :description="profile?.name"
      size="lg"
      @close="profile = null"
    >
      <template v-if="profile">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-body mb-5">
          <div>
            <span class="text-ink-secondary">Nama:</span>
            <strong>{{ profile.name }}</strong>
          </div>
          <div>
            <span class="text-ink-secondary">Email:</span> {{ profile.email || '-' }}
          </div>
          <div>
            <span class="text-ink-secondary">Telepon:</span> {{ profile.phone || '-' }}
          </div>
          <div>
            <span class="text-ink-secondary">Total Reservasi:</span>
            {{ profile.reservations?.length || 0 }} kali
          </div>
        </div>

        <div class="border-t border-border-subtle pt-4">
          <h4 class="font-bold text-sm mb-3">Riwayat Reservasi</h4>
          <div v-if="!profile.reservations?.length" class="py-3">
            <EmptyState title="Belum ada reservasi" description="Tamu ini belum pernah memesan." />
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="r in profile.reservations"
              :key="r.id"
              class="bg-surface-muted rounded-xl p-3"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-mono text-sm text-primary font-semibold">{{ r.reservation_code }}</p>
                  <p class="text-xs text-ink-secondary mt-0.5">
                    Kamar {{ r.room?.room_number }} — {{ r.room?.room_type?.name }}
                  </p>
                  <p class="text-xs text-ink-muted">
                    {{ formatDate(r.check_in_date) }} → {{ formatDate(r.check_out_date) }}
                  </p>
                  <p class="text-sm font-semibold mt-1">{{ formatCurrency(r.total_price) }}</p>
                </div>
                <StatusBadge :status="r.status" :label="statusLabel(r.status)" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="profileOpen = false">Tutup</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import { pageWindow } from '../../utils/pagination'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import AlertBox from '../../components/ui/AlertBox.vue'

const auth = useAuthStore()
const toast = useToast()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const guests = ref([])
const pagination = ref(null)
const page = ref(1)
const visiblePages = computed(() => pageWindow(page.value, pagination.value?.last_page))
const searchQuery = ref('')
const profile = ref(null)
const profileOpen = ref(false)
const loading = ref(false)
const loaded = ref(false)
const loadError = ref('')
let searchTimer = null
let listSeq = 0

onMounted(fetchData)

onUnmounted(() => {
  clearTimeout(searchTimer)
  listSeq += 1
})

function onSearch() {
  page.value = 1
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchData, 350)
}

async function fetchData() {
  const seq = ++listSeq
  loading.value = true
  loadError.value = ''
  try {
    const params = { page: page.value }
    if (searchQuery.value.trim()) params.q = searchQuery.value.trim()
    const res = await api.get(`${prefix}/guests`, { params })
    if (seq !== listSeq) return
    guests.value = res.data.data || res.data
    pagination.value = res.data.data ? res.data : null
    loaded.value = true
  } catch (error) {
    if (seq === listSeq) loadError.value = error.response?.data?.message || 'Data tamu tidak dapat dimuat.'
  } finally {
    if (seq === listSeq) loading.value = false
  }
}

async function openProfile(g) {
  try {
    const res = await api.get(`${prefix}/guests/${g.id}`)
    profile.value = res.data
    profileOpen.value = true
  } catch {
    toast.error('Gagal memuat profil tamu.')
  }
}

function formatDate(d) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '-'
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(v || 0)
}

function statusLabel(s) {
  const l = {
    pending: 'Tertunda',
    confirmed: 'Dikonfirmasi',
    checked_in: 'Check-In',
    checked_out: 'Check-Out',
    cancelled: 'Dibatalkan',
  }
  return l[s] || s
}
</script>
