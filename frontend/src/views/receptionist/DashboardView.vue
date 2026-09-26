<template>
  <div class="space-y-6">
    <PageHeader
      title="Dasbor Front Desk"
      :description="`Operasi hari ini · ${formatDateLong(data.today)}`"
    >
      <template #actions>
        <BaseButton variant="outline" size="sm" :loading="loading" @click="fetchData">
          Segarkan
        </BaseButton>
        <BaseButton size="sm" @click="$router.push('/receptionist/check-in-out')">
          Check In/Out
        </BaseButton>
      </template>
    </PageHeader>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <StatCard label="Kamar Tersedia" :value="data.rooms?.available ?? 0" color="var(--color-success)" />
      <StatCard label="Terisi" :value="data.rooms?.occupied ?? 0" color="var(--color-info)" />
      <StatCard label="Dipesan" :value="data.rooms?.reserved ?? 0" color="var(--color-primary)" />
      <StatCard label="Check-in Datang" :value="data.arrivals?.length ?? 0" color="var(--color-primary)" />
      <StatCard label="Menunggu Konfirmasi" :value="data.pending_count ?? 0" color="var(--color-warning)" />
    </div>

    <div v-if="alerts.length" class="space-y-2">
      <AlertBox
        v-for="a in alerts"
        :key="a.key"
        :variant="a.variant"
        :title="a.title"
      >
        {{ a.message }}
        <router-link
          v-if="a.link"
          :to="a.link.to"
          class="font-semibold underline mt-1 inline-block py-1 -my-1 min-h-10"
        >
          {{ a.link.label }}
        </router-link>
      </AlertBox>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <section class="card" aria-labelledby="arrivals-heading">
        <div class="flex items-center justify-between mb-4">
          <h3 id="arrivals-heading" class="text-base font-bold text-ink">
            Check-in (Datang)
          </h3>
          <BaseBadge tone="primary" :label="String(data.arrivals?.length ?? 0)" />
        </div>
        <div v-if="!data.arrivals?.length" class="py-4">
          <EmptyState title="Tidak ada kedatangan" description="Belum ada reservasi menunggu check-in." />
        </div>
        <ul v-else class="divide-y divide-border-subtle">
          <li v-for="r in data.arrivals" :key="r.id" class="py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-ink truncate">{{ r.guest?.name }}</p>
              <p class="text-xs text-ink-secondary">
                {{ r.reservation_code }} · Kamar {{ r.room?.room_number }}
              </p>
              <p class="text-xs" :class="isOverdue(r.check_in_date) ? 'text-danger font-semibold' : 'text-ink-muted'">
                {{ isOverdue(r.check_in_date) ? 'Terlambat · ' : '' }}{{ formatDate(r.check_in_date) }}
              </p>
            </div>
            <StatusBadge
              :status="paymentStatus(r)"
              class="shrink-0"
            />
          </li>
        </ul>
        <div v-if="data.arrivals?.length" class="pt-3">
          <BaseButton variant="outline" size="sm" block @click="$router.push('/receptionist/check-in-out')">
            Proses Check-in
          </BaseButton>
        </div>
      </section>

      <section class="card" aria-labelledby="departures-heading">
        <div class="flex items-center justify-between mb-4">
          <h3 id="departures-heading" class="text-base font-bold text-ink">
            Check-out (Pulang)
          </h3>
          <BaseBadge tone="primary" :label="`${data.today_departures_count ?? 0} hari ini`" />
        </div>
        <div v-if="!departuresDue.length" class="py-4">
          <EmptyState title="Tidak ada check-out" description="Belum ada tamu jatuh tempo keluar." />
        </div>
        <ul v-else class="divide-y divide-border-subtle">
          <li v-for="r in departuresDue" :key="r.id" class="py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-ink truncate">{{ r.guest?.name }}</p>
              <p class="text-xs text-ink-secondary">
                {{ r.reservation_code }} · Kamar {{ r.room?.room_number }}
              </p>
              <p class="text-xs" :class="isOverdue(r.check_out_date) ? 'text-danger font-semibold' : 'text-ink-muted'">
                {{ isOverdue(r.check_out_date) ? 'Terlambat · ' : '' }}{{ formatDate(r.check_out_date) }}
              </p>
            </div>
            <StatusBadge :status="paymentStatus(r)" class="shrink-0" />
          </li>
        </ul>
        <div v-if="departuresDue.length" class="pt-3">
          <BaseButton variant="outline" size="sm" block @click="$router.push('/receptionist/check-in-out')">
            Proses Check-out
          </BaseButton>
        </div>
      </section>

      <section class="card" aria-labelledby="rooms-heading">
        <div class="flex items-center justify-between mb-4">
          <h3 id="rooms-heading" class="text-base font-bold text-ink">Ketersediaan Kamar</h3>
          <router-link
            to="/receptionist/rooms"
            class="text-xs font-semibold text-primary hover:underline"
          >
            Lihat semua
          </router-link>
        </div>
        <ul class="space-y-3">
          <li
            v-for="s in roomStatusList"
            :key="s.value"
            class="flex items-center justify-between gap-3"
          >
            <StatusBadge :status="s.value" :label="s.label" />
            <span class="text-lg font-bold text-ink">{{ data.rooms?.[s.value] ?? 0 }}</span>
          </li>
        </ul>
        <div class="mt-4 pt-3 border-t border-border-subtle flex items-center justify-between text-sm">
          <span class="text-ink-secondary">Total</span>
          <span class="font-bold text-ink">{{ data.rooms?.total ?? 0 }} kamar</span>
        </div>
      </section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="card" aria-labelledby="pending-heading">
        <div class="flex items-center justify-between mb-4">
          <h3 id="pending-heading" class="text-base font-bold text-ink">Booking Menunggu</h3>
          <router-link
            to="/receptionist/booking-online"
            class="text-xs font-semibold text-primary hover:underline min-h-10 inline-flex items-center"
          >
            Kelola
          </router-link>
        </div>
        <div v-if="!data.pending_reservations?.length" class="py-4">
          <EmptyState title="Tidak ada booking tertunda" description="Semua booking sudah diproses." />
        </div>
        <ul v-else class="divide-y divide-border-subtle">
          <li v-for="r in data.pending_reservations" :key="r.id" class="py-3">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-semibold text-ink truncate">{{ r.guest?.name }}</p>
                <p class="text-xs text-ink-secondary">
                  {{ r.reservation_code }} · Kamar {{ r.room?.room_number }} ·
                  {{ formatDate(r.check_in_date) }}
                </p>
              </div>
              <StatusBadge status="pending" />
            </div>
          </li>
        </ul>
      </section>

      <section class="card" aria-labelledby="activity-heading">
        <div class="flex items-center justify-between mb-4">
          <h3 id="activity-heading" class="text-base font-bold text-ink">Aktivitas Terbaru</h3>
        </div>
        <div v-if="!data.recent_activity?.length" class="py-4">
          <EmptyState title="Belum ada aktivitas" description="Reservasi baru akan muncul di sini." />
        </div>
        <ul v-else class="divide-y divide-border-subtle">
          <li v-for="r in data.recent_activity" :key="r.id" class="py-3 flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold text-ink truncate">{{ r.guest?.name }}</p>
              <p class="text-xs text-ink-secondary">
                {{ r.reservation_code }} · Kamar {{ r.room?.room_number }}
              </p>
            </div>
            <StatusBadge :status="r.status" :label="statusLabel(r.status)" />
          </li>
        </ul>
      </section>
    </div>

    <section class="card" aria-labelledby="quick-actions-heading">
      <h3 id="quick-actions-heading" class="text-base font-bold text-ink mb-4">Aksi Cepat</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <BaseButton variant="outline" @click="$router.push('/receptionist/check-in-out')">
          Check In/Out
        </BaseButton>
        <BaseButton variant="outline" @click="$router.push('/receptionist/reservations')">
          Reservasi
        </BaseButton>
        <BaseButton variant="outline" @click="$router.push('/receptionist/rooms')">
          Ketersediaan
        </BaseButton>
        <BaseButton variant="outline" @click="$router.push('/receptionist/payments')">
          Pembayaran
        </BaseButton>
      </div>
      <p class="text-xs text-ink-muted mt-3">
        Walk-in: gunakan tombol "+ Reservasi Manual" di halaman Reservasi.
      </p>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import StatCard from '../../components/ui/StatCard.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import { localToday } from '../../utils/dates'
import AlertBox from '../../components/ui/AlertBox.vue'

const toast = useToast()
const loading = ref(false)
const data = ref({
  today: null,
  arrivals: [],
  departures: [],
  today_departures_count: 0,
  rooms: {},
  pending_count: 0,
  pending_reservations: [],
  recent_activity: [],
})

const roomStatusList = [
  { value: 'available', label: 'Tersedia' },
  { value: 'occupied', label: 'Terisi' },
  { value: 'reserved', label: 'Dipesan' },
  { value: 'cleaning', label: 'Perlu Dibersihkan' },
  { value: 'maintenance', label: 'Pemeliharaan' },
]

const departuresDue = computed(() =>
  (data.value.departures || []).filter(
    (r) => r.check_out_date && r.check_out_date.slice(0, 10) <= (data.value.today || '')
  )
)

const alerts = computed(() => {
  const list = []
  const overdueIn = (data.value.arrivals || []).filter((r) => isOverdue(r.check_in_date))
  const overdueOut = departuresDue.value.filter((r) => isOverdue(r.check_out_date))
  const unpaidDepartures = departuresDue.value.filter((r) => paymentStatus(r) === 'unpaid')

  if (overdueIn.length) {
    list.push({
      key: 'overdue-in',
      variant: 'warning',
      title: 'Check-in terlambat',
      message: `${overdueIn.length} tamu melewati tanggal check-in namun belum masuk.`,
      link: { to: '/receptionist/check-in-out', label: 'Proses sekarang' },
    })
  }
  if (overdueOut.length) {
    list.push({
      key: 'overdue-out',
      variant: 'warning',
      title: 'Check-out terlambat',
      message: `${overdueOut.length} tamu melewati tanggal check-out masih tercatat menginap.`,
      link: { to: '/receptionist/check-in-out', label: 'Proses sekarang' },
    })
  }
  if (unpaidDepartures.length) {
    list.push({
      key: 'unpaid',
      variant: 'info',
      title: 'Pembayaran belum lunas',
      message: `${unpaidDepartures.length} tamu check-out belum memiliki pembayaran tercatat.`,
      link: { to: '/receptionist/payments', label: 'Lihat pembayaran' },
    })
  }
  if (data.value.pending_count > 0) {
    list.push({
      key: 'pending',
      variant: 'info',
      title: 'Booking menunggu konfirmasi',
      message: `${data.value.pending_count} booking perlu dikonfirmasi atau ditolak.`,
      link: { to: '/receptionist/booking-online', label: 'Buka booking online' },
    })
  }
  return list
})

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  try {
    const res = await api.get('/receptionist/dashboard')
    data.value = res.data
  } catch {
    toast.error('Gagal memuat dasbor.')
  } finally {
    loading.value = false
  }
}

function paymentStatus(r) {
  const paid = (r.payments || []).filter((p) => p.status === 'paid')
    .reduce((s, p) => s + parseFloat(p.amount || 0), 0)
  const charges = (r.charges || []).reduce((s, c) => s + parseFloat(c.total_price || 0), 0)
  const total = parseFloat(r.total_price || 0) + charges
  return paid >= total && total > 0 ? 'paid' : 'unpaid'
}

function isOverdue(dateStr) {
  if (!dateStr) return false
  const d = dateStr.slice(0, 10)
  const today = data.value.today || localToday()
  return d < today
}

function formatDate(d) {
  return d
    ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '-'
}

function formatDateLong(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
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
