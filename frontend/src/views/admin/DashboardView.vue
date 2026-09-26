<template>
  <div class="space-y-8">
    <AlertBox v-if="loadError" variant="error" title="Gagal memuat dashboard">
      <div class="flex flex-wrap items-center gap-3 mt-1">
        <span>{{ loadError }}</span>
        <BaseButton type="button" variant="outline" size="sm" :loading="loading" @click="fetchData">Coba Lagi</BaseButton>
      </div>
    </AlertBox>

    <div v-else-if="loading && !loaded" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4" aria-hidden="true">
      <div v-for="n in 6" :key="`dashboard-skeleton-${n}`" class="skeleton skeleton-kpi"></div>
    </div>

    <div v-else class="space-y-8">
      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
      <div v-for="card in roomStatCards" :key="card.label" class="group relative bg-white rounded-2xl border border-slate-100 p-5 hover:shadow-lg hover:shadow-slate-200/50 hover:-translate-y-0.5 transition-[box-shadow,transform,border-color] duration-200 ease-out overflow-hidden">
        <div class="absolute -right-3 -top-3 opacity-[0.06] group-hover:opacity-[0.1] transition-opacity">
          <component :is="card.icon" class="w-20 h-20" />
        </div>
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3', card.iconBg]">
          <component :is="card.icon" :class="['w-5 h-5', card.iconColor]" />
        </div>
        <p class="text-3xl font-extrabold text-slate-900 mb-1">{{ card.value }}</p>
        <p :class="['text-xs font-bold uppercase tracking-wider', card.labelColor]">{{ card.label }}</p>
      </div>
      </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="relative bg-linear-to-br from-emerald-50 to-emerald-100/60 rounded-2xl border border-emerald-200/50 p-6 overflow-hidden group hover:shadow-lg hover:shadow-emerald-100/50 hover:-translate-y-0.5 transition-[box-shadow,transform] duration-200 ease-out">
        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity">
          <LogIn class="w-24 h-24" />
        </div>
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <LogIn class="w-7 h-7 text-white" />
          </div>
          <div>
            <p class="text-xs font-bold text-emerald-600/80 uppercase tracking-wider">Check-in Hari Ini</p>
            <p class="text-4xl font-black text-emerald-700 -mt-0.5">{{ stats.today_check_in || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="relative bg-linear-to-br from-indigo-50 to-indigo-100/60 rounded-2xl border border-indigo-200/50 p-6 overflow-hidden group hover:shadow-lg hover:shadow-indigo-100/50 hover:-translate-y-0.5 transition-[box-shadow,transform] duration-200 ease-out">
        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity">
          <LogOut class="w-24 h-24" />
        </div>
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
            <LogOut class="w-7 h-7 text-white" />
          </div>
          <div>
            <p class="text-xs font-bold text-indigo-600/80 uppercase tracking-wider">Check-out Hari Ini</p>
            <p class="text-4xl font-black text-indigo-700 -mt-0.5">{{ stats.today_check_out || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="relative bg-linear-to-br from-amber-50 to-amber-100/60 rounded-2xl border border-amber-200/50 p-6 overflow-hidden group hover:shadow-lg hover:shadow-amber-100/50 hover:-translate-y-0.5 transition-[box-shadow,transform] duration-200 ease-out">
        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity">
          <Hourglass class="w-24 h-24" />
        </div>
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/30">
            <Hourglass class="w-7 h-7 text-white" />
          </div>
          <div>
            <p class="text-xs font-bold text-amber-600/80 uppercase tracking-wider">Booking Tertunda</p>
            <p class="text-4xl font-black text-amber-700 -mt-0.5">{{ stats.pending_bookings || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="relative bg-linear-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 overflow-hidden group hover:shadow-xl hover:shadow-slate-900/30 hover:-translate-y-0.5 transition-[box-shadow,transform] duration-200 ease-out">
        <div class="absolute -right-6 -bottom-6 opacity-5 group-hover:opacity-10 transition-opacity">
          <Wallet class="w-32 h-32" />
        </div>
        <div class="absolute top-0 right-0 w-32 h-32 bg-linear-to-bl from-cyan-500/10 to-transparent rounded-bl-full"></div>
        <div class="flex items-center gap-4 relative z-10">
          <div class="w-14 h-14 rounded-2xl bg-linear-to-br from-cyan-400 to-blue-500 flex items-center justify-center shadow-lg shadow-cyan-500/30">
            <Wallet class="w-7 h-7 text-white" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pendapatan Bulan Ini</p>
            <p class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-linear-to-r from-cyan-400 to-blue-400 -mt-0.5 wrap-break-word tabular-nums min-w-0 truncate">{{ formatCurrency(stats.monthly_revenue) }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
          <TrendingUp class="w-5 h-5 text-cyan-500" />
          Pendapatan 7 Hari Terakhir
        </h3>
        <div class="h-55 sm:h-70">
          <Bar v-if="dailyRevenueData" :data="dailyRevenueData" :options="barOptions" />
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <h3 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
          <PieChart class="w-5 h-5 text-violet-500" />
          Status Reservasi
        </h3>
        <div class="h-55 sm:h-70 flex items-center justify-center">
          <Doughnut v-if="statusChartData" :data="statusChartData" :options="doughnutOptions" />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
      <h3 class="text-base font-bold text-slate-800 mb-5 flex items-center gap-2">
        <BarChart3 class="w-5 h-5 text-indigo-500" />
        Tren Bulanan (6 Bulan)
      </h3>
      <div class="h-55 sm:h-70">
        <Line v-if="monthlyChartData" :data="monthlyChartData" :options="lineOptions" />
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm">
      <div class="flex items-center justify-between px-4 sm:px-7 py-5 border-b border-slate-100 flex-wrap gap-3">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-cyan-50 flex items-center justify-center">
            <ClipboardList class="w-5 h-5 text-cyan-500" />
          </div>
          Reservasi Terbaru
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-180">
          <thead>
            <tr class="bg-slate-50/80">
              <th class="text-left px-4 sm:px-7 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Kode</th>
              <th class="text-left px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tamu</th>
              <th class="text-left px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Kamar</th>
              <th class="text-left px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Check-in</th>
              <th class="text-left px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="text-right px-4 sm:px-7 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="r in stats.recent_reservations" :key="r.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 sm:px-7 py-4 font-mono font-bold text-sm text-cyan-600">{{ r.reservation_code }}</td>
              <td class="px-4 py-4 font-semibold text-sm text-slate-700">{{ r.guest?.name }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-slate-800">{{ r.room?.room_number }}</span>
                  <span class="text-slate-400 text-xs">— {{ r.room?.room_type?.name }}</span>
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-slate-600 font-medium">{{ formatDate(r.check_in_date) }}</td>
              <td class="px-4 py-4"><StatusBadge :status="r.status" :label="statusLabel(r.status)" /></td>
              <td class="px-4 sm:px-7 py-4 text-sm font-bold text-slate-800 text-right">{{ formatCurrency(r.total_price) }}</td>
            </tr>
            <tr v-if="!stats.recent_reservations?.length">
              <td colspan="6" class="text-center py-16">
                <div class="flex flex-col items-center justify-center text-slate-400">
                  <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-4">
                    <Inbox class="w-8 h-8 text-slate-300" />
                  </div>
                  <p class="font-semibold text-sm text-slate-500">Belum ada reservasi terbaru</p>
                  <p class="text-xs text-slate-400 mt-1">Data akan muncul ketika ada reservasi masuk</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  BedDouble, CircleCheckBig, Users, CalendarClock, SprayCan, Wrench,
  LogIn, LogOut, Hourglass, Wallet, ClipboardList, Inbox,
  TrendingUp, PieChart, BarChart3
} from 'lucide-vue-next'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement, PointElement, LineElement,
  ArcElement, Title, Tooltip, Legend, Filler
} from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import api from '../../services/api'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import BaseButton from '../../components/ui/BaseButton.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, PointElement, LineElement, ArcElement, Title, Tooltip, Legend, Filler)

const stats = ref({})
const loading = ref(false)
const loaded = ref(false)
const loadError = ref('')

onMounted(fetchData)

async function fetchData() {
  if (loading.value) return
  loading.value = true
  loadError.value = ''
  try {
    const response = await api.get('/admin/dashboard')
    stats.value = response.data || {}
    loaded.value = true
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Dashboard tidak dapat dimuat.'
  } finally {
    loading.value = false
  }
}

const roomStatCards = computed(() => [
  { label: 'Total Kamar', value: stats.value.rooms?.total || 0, icon: BedDouble, iconBg: 'bg-slate-100', iconColor: 'text-slate-600', labelColor: 'text-slate-500' },
  { label: 'Tersedia', value: stats.value.rooms?.available || 0, icon: CircleCheckBig, iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600', labelColor: 'text-emerald-600' },
  { label: 'Terisi', value: stats.value.rooms?.occupied || 0, icon: Users, iconBg: 'bg-blue-100', iconColor: 'text-blue-600', labelColor: 'text-blue-600' },
  { label: 'Dipesan', value: stats.value.rooms?.reserved || 0, icon: CalendarClock, iconBg: 'bg-amber-100', iconColor: 'text-amber-600', labelColor: 'text-amber-600' },
  { label: 'Perlu Dibersihkan', value: stats.value.rooms?.cleaning || 0, icon: SprayCan, iconBg: 'bg-orange-100', iconColor: 'text-orange-600', labelColor: 'text-orange-600' },
  { label: 'Pemeliharaan', value: stats.value.rooms?.maintenance || 0, icon: Wrench, iconBg: 'bg-red-100', iconColor: 'text-red-600', labelColor: 'text-red-600' },
])

const dailyRevenueData = computed(() => {
  const d = stats.value.daily_revenue
  if (!d?.length) return null
  return {
    labels: d.map(i => i.label),
    datasets: [
      {
        label: 'Pendapatan (Rp)',
        data: d.map(i => i.revenue),
        backgroundColor: 'rgba(6, 182, 212, 0.15)',
        borderColor: 'rgb(6, 182, 212)',
        borderWidth: 2,
        borderRadius: 8,
        hoverBackgroundColor: 'rgba(6, 182, 212, 0.35)',
      },
      {
        label: 'Reservasi',
        data: d.map(i => i.reservations),
        backgroundColor: 'rgba(99, 102, 241, 0.15)',
        borderColor: 'rgb(99, 102, 241)',
        borderWidth: 2,
        borderRadius: 8,
        hoverBackgroundColor: 'rgba(99, 102, 241, 0.35)',
      },
    ],
  }
})

const monthlyChartData = computed(() => {
  const d = stats.value.monthly_chart
  if (!d?.length) return null
  return {
    labels: d.map(i => i.label),
    datasets: [
      {
        label: 'Pendapatan (Rp)',
        data: d.map(i => i.revenue),
        borderColor: 'rgb(6, 182, 212)',
        backgroundColor: 'rgba(6, 182, 212, 0.08)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: 'rgb(6, 182, 212)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
      {
        label: 'Reservasi',
        data: d.map(i => i.reservations),
        borderColor: 'rgb(99, 102, 241)',
        backgroundColor: 'rgba(99, 102, 241, 0.08)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: 'rgb(99, 102, 241)',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
    ],
  }
})

const statusChartData = computed(() => {
  const s = stats.value.status_breakdown
  if (!s) return null
  return {
    labels: ['Tertunda', 'Dikonfirmasi', 'Check-In', 'Check-Out', 'Dibatalkan'],
    datasets: [{
      data: [s.pending, s.confirmed, s.checked_in, s.checked_out, s.cancelled],
      backgroundColor: [
        'rgba(245, 158, 11, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(59, 130, 246, 0.8)',
        'rgba(99, 102, 241, 0.8)',
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
    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 8, titleFont: { size: 13, weight: '700' }, bodyFont: { size: 12 }, callbacks: { label: (c) => c.dataset.label.includes('Rp') ? `Rp ${c.parsed.y.toLocaleString('id-ID')}` : `${c.parsed.y} reservasi` } }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '500' } } },
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 } } },
  },
}

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { size: 12, weight: '600' } } },
    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 8, titleFont: { size: 13, weight: '700' }, bodyFont: { size: 12 }, callbacks: { label: (c) => c.dataset.label.includes('Rp') ? `Rp ${c.parsed.y.toLocaleString('id-ID')}` : `${c.parsed.y} reservasi` } }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '500' } } },
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

function formatCurrency(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusLabel(status) {
  const labels = { pending: 'Tertunda', confirmed: 'Dikonfirmasi', checked_in: 'Check-In', checked_out: 'Check-Out', cancelled: 'Dibatalkan' }
  return labels[status] || status
}
</script>
