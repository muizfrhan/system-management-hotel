<template>
  <div class="space-y-6">
    <PageHeader title="Booking Online" description="Konfirmasi atau tolak permintaan booking dari website.">
      <template #actions>
        <BaseBadge v-if="bookings.length" tone="warning" :label="`${bookings.length} menunggu`" />
        <BaseButton variant="outline" size="sm" :loading="loading" @click="fetchData">Segarkan</BaseButton>
      </template>
    </PageHeader>

     <AlertBox v-if="loadError" variant="error" title="Gagal memuat booking online">
       <div class="flex flex-wrap items-center gap-3 mt-1">
         <span>{{ loadError }}</span>
         <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
       </div>
     </AlertBox>

     <div v-else-if="loading && !bookings.length" class="card space-y-3">
      <div v-for="i in 3" :key="i" class="skeleton skeleton-kpi"></div>
    </div>

    <div v-else-if="!bookings.length" class="card">
      <EmptyState title="Tidak ada booking tertunda" description="Semua permintaan booking sudah diproses." />
    </div>

    <div v-else class="space-y-4">
      <article v-for="b in bookings" :key="b.id" class="card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <p class="font-bold text-primary font-mono">{{ b.reservation_code }}</p>
            <StatusBadge status="pending" />
          </div>
          <p class="text-sm mt-1">{{ b.guest?.name }} · {{ b.guest?.email }}</p>
          <p class="text-xs text-ink-secondary">
            {{ b.room?.room_type?.name }} · Kamar {{ b.room?.room_number }} ·
            {{ formatDate(b.check_in_date) }} → {{ formatDate(b.check_out_date) }}
          </p>
          <p class="text-sm font-semibold mt-1">{{ formatCurrency(b.total_price) }}</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
           <BaseButton variant="success" size="sm" class="min-h-10" :disabled="busy" @click="askConfirm(b)">Konfirmasi</BaseButton>
           <BaseButton variant="danger" size="sm" class="min-h-10" :disabled="busy" @click="askReject(b)">Tolak</BaseButton>
        </div>
      </article>
    </div>

    <ConfirmModal
      :visible="confirmOpen"
      :title="pendingAction === 'confirm' ? 'Konfirmasi Booking' : 'Tolak Booking'"
      :message="confirmMessage"
      :confirm-text="pendingAction === 'confirm' ? 'Ya, Konfirmasi' : 'Ya, Tolak'"
       :danger="pendingAction === 'reject'"
       :loading="busy"
       @confirm="runAction"
      @cancel="confirmOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import ConfirmModal from '../../components/ui/ConfirmModal.vue'

const auth = useAuthStore()
const toast = useToast()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const bookings = ref([])
const loading = ref(false)
const loadError = ref('')
const confirmOpen = ref(false)
const pendingAction = ref('confirm')
const pendingTarget = ref(null)
const busy = ref(false)

const confirmMessage = computed(() => {
  const b = pendingTarget.value
  if (!b) return 'Apakah Anda yakin?'
  const name = b.guest?.name || 'tamu'
  if (pendingAction.value === 'confirm') {
    return `Konfirmasi booking ${b.reservation_code} dari ${name}? Status menjadi Dikonfirmasi.`
  }
  return `Tolak booking ${b.reservation_code} dari ${name}? Reservasi akan dibatalkan.`
})

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.get(`${prefix}/booking-online`)
    bookings.value = res.data || []
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Booking online tidak dapat dimuat.'
  } finally {
    loading.value = false
  }
}

function askConfirm(b) {
  pendingTarget.value = b
  pendingAction.value = 'confirm'
  confirmOpen.value = true
}

function askReject(b) {
  pendingTarget.value = b
  pendingAction.value = 'reject'
  confirmOpen.value = true
}

async function runAction() {
  if (!pendingTarget.value || busy.value) return
  busy.value = true
  const b = pendingTarget.value
  try {
    const res = await api.put(`${prefix}/booking-online/${b.id}/${pendingAction.value === 'confirm' ? 'confirm' : 'reject'}`)
    confirmOpen.value = false
    toast.success(res.data?.message || (pendingAction.value === 'confirm' ? 'Booking dikonfirmasi.' : 'Booking ditolak.'))
    await fetchData()
  } catch (err) {
    confirmOpen.value = false
    toast.error(err.response?.data?.message || 'Gagal memproses booking.')
  } finally {
    busy.value = false
    pendingTarget.value = null
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
</script>
