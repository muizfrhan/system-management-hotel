<template>
  <div class="space-y-6">
    <PageHeader title="Pembayaran & Faktur" description="Riwayat pembayaran dan invoice tamu.">
      <template #actions>
        <BaseButton variant="outline" size="sm" :loading="loading" @click="fetchData">
          Segarkan
        </BaseButton>
      </template>
    </PageHeader>

    <div v-if="loading && !payments.length" class="card space-y-3">
      <div v-for="i in 5" :key="i" class="skeleton skeleton-table-row"></div>
    </div>

     <AlertBox v-else-if="loadError" variant="error" title="Gagal memuat pembayaran">
       <div class="flex flex-wrap items-center gap-3 mt-1">
         <span>{{ loadError }}</span>
         <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
       </div>
     </AlertBox>

     <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Reservasi</th>
            <th scope="col">Tamu</th>
            <th scope="col">Jumlah</th>
            <th scope="col">Metode</th>
            <th scope="col">Status</th>
            <th scope="col">Tanggal</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payments" :key="p.id">
            <td>#{{ p.id }}</td>
            <td class="font-mono text-primary">{{ p.reservation?.reservation_code || '-' }}</td>
            <td>{{ p.reservation?.guest?.name || '-' }}</td>
            <td class="font-semibold">{{ formatCurrency(p.amount) }}</td>
            <td>{{ p.payment_method || '-' }}</td>
            <td>
              <StatusBadge :status="p.status" :label="p.status === 'paid' ? 'Lunas' : 'Belum'" />
            </td>
            <td>{{ p.paid_at ? formatDate(p.paid_at) : '-' }}</td>
            <td>
              <div class="flex flex-wrap gap-1">
                <BaseButton variant="ghost" size="sm" class="min-h-10" @click="viewInvoice(p)">Detail</BaseButton>
                <BaseButton variant="ghost" size="sm" class="min-h-10" @click="printPdf(p)">PDF</BaseButton>
              </div>
            </td>
          </tr>
          <tr v-if="!payments.length">
            <td colspan="8" class="!p-0">
              <EmptyState
                title="Tidak ada pembayaran"
                description="Pembayaran yang tercatat akan muncul di sini."
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav
      v-if="pagination && pagination.last_page > 1"
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

    <BaseModal v-model="invoiceOpen" title="Invoice" size="md">
      <template v-if="invoice">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <p class="font-mono text-body text-primary">
              {{ invoice.reservation?.reservation_code }}
            </p>
            <p class="text-sm text-ink-secondary">Invoice pembayaran #{{ invoice.id }}</p>
          </div>
          <StatusBadge
            :status="invoice.totals?.settlement_status || 'unpaid'"
            :label="invoice.totals?.settlement_status === 'paid' ? 'Lunas' : 'Belum'"
          />
        </div>

        <div class="text-sm space-y-2 mb-4">
          <div class="flex justify-between gap-3">
            <span class="text-ink-secondary shrink-0">Tamu</span>
            <strong class="text-right">{{ invoice.reservation?.guest?.name }}</strong>
          </div>
          <div class="flex justify-between gap-3">
            <span class="text-ink-secondary shrink-0">Kamar</span>
            <span class="text-right">
              {{ invoice.reservation?.room?.room_number }} —
              {{ invoice.reservation?.room?.roomType?.name || invoice.reservation?.room?.room_type?.name }}
            </span>
          </div>
          <div class="flex justify-between gap-3">
            <span class="text-ink-secondary shrink-0">Metode</span>
            <span>{{ invoice.payment_method || '-' }}</span>
          </div>
          <div class="flex justify-between gap-3">
            <span class="text-ink-secondary shrink-0">Tanggal</span>
            <span>{{ invoice.paid_at ? formatDate(invoice.paid_at) : '-' }}</span>
          </div>
        </div>

        <div class="border-t border-border-subtle pt-3 text-sm space-y-2">
          <div class="flex justify-between">
            <span class="text-ink-secondary">Total Kamar</span>
            <span>{{ formatCurrency(invoice.totals?.room_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-ink-secondary">Biaya Tambahan</span>
            <span>{{ formatCurrency(invoice.totals?.charges_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-ink-secondary">Sudah Dibayar</span>
            <span>{{ formatCurrency(invoice.totals?.paid_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-semibold">Sisa Tagihan</span>
            <span :class="invoice.totals?.remaining > 0 ? 'text-danger' : 'text-success'">
              {{ formatCurrency(invoice.totals?.remaining) }}
            </span>
          </div>
          <div class="flex justify-between font-bold text-base bg-primary-soft/60 rounded-xl px-3 py-2 mt-2">
            <span>Total</span>
            <span class="text-primary">
              {{ formatCurrency(invoice.totals?.grand_total || invoice.amount) }}
            </span>
          </div>
        </div>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="invoiceOpen = false">Tutup</BaseButton>
        <BaseButton @click="printPdf(invoice)" :disabled="!invoice">Cetak PDF</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
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

const payments = ref([])
const pagination = ref(null)
const page = ref(1)
const visiblePages = computed(() => pageWindow(page.value, pagination.value?.last_page))
const loading = ref(false)
const loadError = ref('')
const invoice = ref(null)
const invoiceOpen = ref(false)

onMounted(fetchData)

async function fetchData() {
  loading.value = true
  loadError.value = ''
  try {
    const res = await api.get(`${prefix}/payments`, { params: { page: page.value } })
    payments.value = res.data.data || res.data
    pagination.value = res.data.data ? res.data : null
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Pembayaran tidak dapat dimuat.'
  } finally {
    loading.value = false
  }
}

async function viewInvoice(p) {
  try {
    const res = await api.get(`${prefix}/payments/${p.id}/invoice`)
    invoice.value = res.data
    invoiceOpen.value = true
  } catch {
    toast.error('Gagal memuat invoice.')
  }
}

function printPdf(p) {
  if (!p) return
  const url = `/api/v1${prefix}/payments/${p.id}/invoice/pdf`
  window.open(url, '_blank', 'noopener,noreferrer')
}

function formatCurrency(v) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(v || 0)
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>
