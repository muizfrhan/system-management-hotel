<template>
  <div class="space-y-6">
    <h1 class="page-title">Laporan</h1>
    <div class="card">
      <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 sm:items-end mb-6">
        <div class="flex-1"><label class="label">Dari</label><input v-model="from" type="date" class="input-field" /></div>
        <div class="flex-1"><label class="label">Sampai</label><input v-model="to" type="date" class="input-field" /></div>
        <button @click="fetchData" class="btn-primary sm:shrink-0">Filter</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-blue-50"><p class="text-xs font-semibold text-[var(--text-secondary)] uppercase">Total Reservasi</p><p class="text-3xl font-bold text-blue-700">{{ report.total_reservations }}</p></div>
        <div class="stat-card bg-green-50"><p class="text-xs font-semibold text-[var(--text-secondary)] uppercase">Dikonfirmasi</p><p class="text-3xl font-bold text-green-700">{{ report.confirmed_reservations }}</p></div>
        <div class="stat-card bg-red-50"><p class="text-xs font-semibold text-[var(--text-secondary)] uppercase">Dibatalkan</p><p class="text-3xl font-bold text-red-700">{{ report.cancelled_reservations }}</p></div>
        <div class="stat-card bg-[var(--accent)]/10"><p class="text-xs font-semibold text-[var(--text-secondary)] uppercase">Total Pendapatan</p><p class="text-2xl font-bold text-[var(--primary)]">{{ formatCurrency(report.total_revenue) }}</p></div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
          <TrendingUp class="w-5 h-5 text-cyan-500" />
          Pendapatan Harian
        </h3>
        <div style="height: 300px">
          <Bar v-if="dailyChartData" :data="dailyChartData" :options="barOptions" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm">Tidak ada data untuk periode ini</div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
          <PieChart class="w-5 h-5 text-violet-500" />
          Status Reservasi
        </h3>
        <div style="height: 300px" class="flex items-center justify-center">
          <Doughnut v-if="statusChartData" :data="statusChartData" :options="doughnutOptions" />
          <div v-else class="text-slate-400 text-sm">Tidak ada data</div>
        </div>
      </div>
    </div>

    <div v-if="report.recent_payments?.length" class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
      <div class="px-4 sm:px-7 py-5 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
          <CreditCard class="w-5 h-5 text-emerald-500" />
          Riwayat Pembayaran
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-slate-50/80">
              <th class="text-left px-4 sm:px-7 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Reservasi</th>
              <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tamu</th>
              <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Metode</th>
              <th class="text-left px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
              <th class="text-right px-4 sm:px-7 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jumlah</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="p in report.recent_payments" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 sm:px-7 py-3.5 font-mono font-bold text-sm text-cyan-600">{{ p.reservation?.reservation_code }}</td>
              <td class="px-4 py-3.5 text-sm font-medium text-slate-700">{{ p.reservation?.guest?.name }}</td>
              <td class="px-4 py-3.5 text-sm text-slate-600">{{ p.payment_method }}</td>
              <td class="px-4 py-3.5 text-sm text-slate-600">{{ formatDate(p.paid_at) }}</td>
              <td class="px-4 sm:px-7 py-3.5 text-sm font-bold text-emerald-600 text-right">{{ formatCurrency(p.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { TrendingUp, PieChart, CreditCard } from 'lucide-vue-next'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement,
  Title, Tooltip, Legend
} from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'
import api from '../../services/api'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend)

const from = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const to = ref(new Date().toISOString().split('T')[0])
const report = ref({})

onMounted(() => fetchData())
async function fetchData() {
  const res = await api.get('/admin/reports', { params: { from: from.value, to: to.value } })
  report.value = res.data
}

const dailyChartData = computed(() => {
  const d = report.value.daily_chart
  if (!d?.length) return null
  return {
    labels: d.map(i => i.label),
    datasets: [
      {
        label: 'Pendapatan (Rp)',
        data: d.map(i => i.revenue),
        backgroundColor: 'rgba(6, 182, 212, 0.2)',
        borderColor: 'rgb(6, 182, 212)',
        borderWidth: 2,
        borderRadius: 6,
        hoverBackgroundColor: 'rgba(6, 182, 212, 0.4)',
      },
      {
        label: 'Reservasi',
        data: d.map(i => i.reservations),
        backgroundColor: 'rgba(99, 102, 241, 0.2)',
        borderColor: 'rgb(99, 102, 241)',
        borderWidth: 2,
        borderRadius: 6,
        hoverBackgroundColor: 'rgba(99, 102, 241, 0.4)',
      },
    ],
  }
})

const statusChartData = computed(() => {
  const s = report.value.status_breakdown
  if (!s) return null
  const total = s.pending + s.confirmed + s.cancelled
  if (total === 0) return null
  return {
    labels: ['Tertunda', 'Dikonfirmasi', 'Dibatalkan'],
    datasets: [{
      data: [s.pending, s.confirmed, s.cancelled],
      backgroundColor: [
        'rgba(245, 158, 11, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(239, 68, 68, 0.8)',
      ],
      borderColor: '#fff',
      borderWidth: 3,
      hoverOffset: 8,
    }],
  }
})

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { size: 12, weight: '600' } } },
    tooltip: {
      backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 8,
      titleFont: { size: 13, weight: '700' }, bodyFont: { size: 12 },
      callbacks: { label: (c) => c.dataset.label.includes('Rp') ? `Rp ${c.parsed.y.toLocaleString('id-ID')}` : `${c.parsed.y} reservasi` },
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, maxRotation: 45 } },
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 } } },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10, padding: 16, font: { size: 11, weight: '600' } } },
    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 8, bodyFont: { size: 12 } },
  },
  cutout: '65%',
}

function formatCurrency(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v || 0) }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }
</script>
