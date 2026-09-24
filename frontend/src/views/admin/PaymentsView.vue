<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3"><h1 class="page-title">Pembayaran & Faktur</h1></div>
    <div class="table-container">
      <table class="data-table">
        <thead><tr><th>ID</th><th>Reservasi</th><th>Tamu</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
          <tr v-for="p in payments" :key="p.id">
            <td>#{{ p.id }}</td>
            <td class="font-mono text-[var(--primary)]">{{ p.reservation?.reservation_code || '-' }}</td>
            <td>{{ p.reservation?.guest?.name || '-' }}</td>
            <td class="font-semibold">{{ formatCurrency(p.amount) }}</td>
            <td>{{ p.payment_method || '-' }}</td>
            <td><span :class="'badge badge-' + p.status">{{ p.status === 'paid' ? 'Lunas' : 'Belum' }}</span></td>
            <td>{{ p.paid_at ? formatDate(p.paid_at) : '-' }}</td>
            <td>
              <div class="flex gap-1">
                <button @click="viewInvoice(p)" class="btn-ghost text-xs !px-2 !py-1">Detail</button>
                <button @click="printPdf(p)" class="btn-ghost text-xs !px-2 !py-1 text-[var(--primary)]">PDF</button>
              </div>
            </td>
          </tr>
          <tr v-if="!payments.length">
            <td colspan="8" class="text-center py-8 text-[var(--text-secondary)]">Tidak ada pembayaran</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Invoice -->
    <div v-if="invoice" class="modal-overlay" @click.self="invoice = null">
      <div class="modal-content p-6 animate-slide-up" style="max-width: 36rem;">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold">Invoice</h3>
            <p class="text-sm font-mono text-[var(--primary)]">{{ invoice.reservation?.reservation_code }}</p>
          </div>
          <span :class="'badge badge-' + (invoice.status || 'paid')">{{ invoice.status === 'paid' ? 'Lunas' : 'Belum' }}</span>
        </div>

          <div class="text-sm space-y-2 mb-4">
          <div class="flex justify-between gap-3"><span class="text-[var(--text-secondary)] shrink-0">Tamu</span><strong class="text-right">{{ invoice.reservation?.guest?.name }}</strong></div>
          <div class="flex justify-between gap-3"><span class="text-[var(--text-secondary)] shrink-0">Kamar</span><span class="text-right">{{ invoice.reservation?.room?.room_number }} — {{ invoice.reservation?.room?.room_type?.name }}</span></div>
          <div class="flex justify-between gap-3"><span class="text-[var(--text-secondary)] shrink-0">Metode</span><span>{{ invoice.payment_method || '-' }}</span></div>
          <div class="flex justify-between gap-3"><span class="text-[var(--text-secondary)] shrink-0">Tanggal</span><span>{{ invoice.paid_at ? formatDate(invoice.paid_at) : '-' }}</span></div>
        </div>

        <div class="border-t border-gray-100 pt-3 text-sm space-y-2">
          <div class="flex justify-between">
            <span class="text-[var(--text-secondary)]">Total Kamar</span>
            <span>{{ formatCurrency(invoice.totals?.room_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[var(--text-secondary)]">Biaya Tambahan</span>
            <span>{{ formatCurrency(invoice.totals?.charges_total) }}</span>
          </div>
          <div class="flex justify-between font-bold text-base bg-[var(--primary)]/5 rounded-xl px-3 py-2 mt-2">
            <span>Total</span>
            <span class="text-[var(--primary)]">{{ formatCurrency(invoice.totals?.grand_total || invoice.amount) }}</span>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-5">
          <button @click="invoice = null" class="btn-ghost">Tutup</button>
          <button @click="printPdf(invoice)" class="btn-primary">Cetak PDF</button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'

const auth = useAuthStore()
const prefix = auth.userRole === 'receptionist' ? '/receptionist' : '/admin'

const payments = ref([])
const invoice = ref(null)

onMounted(async () => { const res = await api.get(`${prefix}/payments`); payments.value = res.data.data || res.data })

async function viewInvoice(p) {
  const res = await api.get(`${prefix}/payments/${p.id}/invoice`)
  invoice.value = res.data
}

function printPdf(p) {
  const url = `/api/v1${prefix}/payments/${p.id}/invoice/pdf`
  window.open(url, '_blank')
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0) }
function formatDate(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
</script>
