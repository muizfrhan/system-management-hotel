<template>
  <PublicLayout :footer-class="step === 3 ? 'pb-24 sm:pb-0' : ''">
    <section class="public-container public-inner-page" :class="step === 3 ? 'pb-28 sm:pb-16' : 'pb-16'">
      <Breadcrumb :items="[{ label: 'Beranda', to: '/' }, { label: 'Kamar', to: '/rooms' }, { label: roomType ? roomType.name : 'Detail Kamar' }, { label: 'Booking' }]" />
      <PublicPageHeader
        eyebrow="Reservasi"
        :title="`Pesan ${roomType ? roomType.name : 'Kamar'}`"
        description="Lengkapi informasi menginap Anda untuk melanjutkan ke pembayaran dan konfirmasi reservasi."
        :back-to="{ path: `/rooms/${$route.params.roomTypeId}`, query: backQuery }"
        back-label="Kembali ke detail kamar"
      />
      <BookingProgress :steps="steps" :current="step" @go="goToStep" />

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 lg:gap-8 items-start">
        <!-- Left: wizard -->
        <div class="public-panel lg:col-span-3 order-2 lg:order-1 p-5 sm:p-8">
          <!-- Room error blocks entire flow -->
          <AlertBox v-if="roomError && !roomType" variant="error" title="Gagal memuat kamar">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-1">
              <span>Periksa koneksi Anda lalu coba lagi.</span>
              <button type="button" class="btn btn-outline rounded-full px-5 w-max" @click="fetchRoom">Coba Lagi</button>
            </div>
          </AlertBox>

          <template v-else>
            <!-- Submit error -->
            <AlertBox v-if="submitError" variant="error" class="mb-5" :title="submitError.title" dismissible @dismiss="clearSubmitError">
              <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <span>{{ submitError.message }}</span>
                <button
                  v-if="submitError.action === 'dates'"
                  type="button"
                  class="btn btn-outline rounded-full px-5 w-max shrink-0"
                  @click="goToStep(1)"
                >
                  Ubah Tanggal
                </button>
                <button
                  v-else-if="submitError.action === 'retry'"
                  type="button"
                   class="btn btn-outline rounded-full px-5 w-max shrink-0"
                   :disabled="submitting"
                   @click="submitBooking"
                >
                  Coba Lagi
                </button>
              </div>
            </AlertBox>

            <!-- ============ STEP 1: Dates & Guests ============ -->
            <div v-if="step === 1">
              <h2 ref="stepHeading" tabindex="-1" class="text-xl sm:text-2xl font-bold text-slate-900 mb-1 focus:outline-none">
                Kapan Anda menginap?
              </h2>
              <p class="text-sm text-slate-500 mb-6">Pilih tanggal check-in, check-out, dan jumlah tamu.</p>

              <div class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <BaseInput
                    v-model="stay.check_in"
                    type="date"
                    label="Check-in"
                    required
                    :min="today"
                    :error="fieldErrors.check_in"
                    @update:model-value="onStayChange"
                  />
                  <BaseInput
                    v-model="stay.check_out"
                    type="date"
                    label="Check-out"
                    required
                    :min="stay.check_in || today"
                    :error="fieldErrors.check_out"
                    @update:model-value="onStayChange"
                  />
                </div>

                <BaseInput
                  v-model.number="stay.guests"
                  type="number"
                  label="Jumlah Tamu"
                  required
                  min="1"
                  :max="roomType?.capacity || 20"
                  :hint="roomType ? `Maksimal ${roomType.capacity} tamu untuk tipe kamar ini` : ''"
                  :error="fieldErrors.guests"
                  @update:model-value="onStayChange"
                />

                <!-- Availability status -->
                <div aria-live="polite">
                  <div
                    v-if="availabilityLoading"
                    class="flex items-center gap-2.5 text-sm text-slate-500 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3"
                  >
                    <LoadingSpinner size="sm" />
                    <span>Memeriksa ketersediaan kamar...</span>
                  </div>
                  <div
                    v-else-if="availabilityError"
                    class="flex flex-col sm:flex-row sm:items-center gap-3 text-sm bg-danger-soft border border-danger/20 text-danger rounded-2xl px-4 py-3"
                  >
                    <span>Gagal memeriksa ketersediaan. Periksa koneksi Anda.</span>
                    <button type="button" class="btn btn-outline rounded-full px-4 text-xs w-max shrink-0" @click="checkAvailability">
                      Cek Ulang
                    </button>
                  </div>
                  <div
                    v-else-if="availability !== null && stay.check_in && stay.check_out"
                    class="flex items-center gap-2.5 text-sm rounded-2xl px-4 py-3 border"
                    :class="availability > 0 ? 'bg-success-soft border-success/20 text-success' : 'bg-danger-soft border-danger/20 text-danger'"
                  >
                    <CheckCircle2 v-if="availability > 0" class="w-4 h-4 shrink-0" aria-hidden="true" />
                    <XCircle v-else class="w-4 h-4 shrink-0" aria-hidden="true" />
                    <span v-if="availability > 0">
                      Tersedia {{ availability }} kamar untuk tanggal yang dipilih.
                    </span>
                    <span v-else>
                      Kamar tidak tersedia pada tanggal tersebut. Silakan pilih tanggal lain.
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-8 flex justify-end">
                <button type="button" class="btn btn-primary rounded-full btn-lg px-8" :disabled="!step1Valid" @click="goToStep(2)">
                  Lanjut
                  <ArrowRight class="w-4 h-4" aria-hidden="true" />
                </button>
              </div>
            </div>

            <!-- ============ STEP 2: Guest Information ============ -->
            <div v-else-if="step === 2">
              <h2 ref="stepHeading" tabindex="-1" class="text-xl sm:text-2xl font-bold text-slate-900 mb-1 focus:outline-none">
                Data diri Anda
              </h2>
              <p class="text-sm text-slate-500 mb-6">Kami hubungi Anda melalui email atau telepon untuk konfirmasi.</p>

              <div class="space-y-5">
                <BaseInput
                  v-model="guest.name"
                  label="Nama Lengkap"
                  required
                  autocomplete="name"
                  placeholder="Nama sesuai identitas"
                  :error="fieldErrors.name"
                  @update:model-value="clearFieldError('name')"
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <BaseInput
                    v-model="guest.email"
                    type="email"
                    label="Email"
                    required
                    autocomplete="email"
                    placeholder="nama@email.com"
                    :error="fieldErrors.email"
                    @update:model-value="clearFieldError('email')"
                  />
                  <BaseInput
                    v-model="guest.phone"
                    type="tel"
                    label="Telepon"
                    required
                    autocomplete="tel"
                    placeholder="08xxxxxxxxxx"
                    :error="fieldErrors.phone"
                    @update:model-value="clearFieldError('phone')"
                  />
                </div>

                <BaseTextarea
                  v-model="guest.special_requests"
                  label="Permintaan Khusus (opsional)"
                  rows="3"
                  maxlength="2000"
                  placeholder="Misal: lantai atas, ranjang tambahan, tiba larut malam..."
                  :error="fieldErrors.requests"
                  hint="Maksimal 2.000 karakter."
                  @update:model-value="clearFieldError('requests')"
                />
              </div>

              <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
                <button type="button" class="btn btn-outline rounded-full px-6" @click="goToStep(1)">
                  <ArrowLeft class="w-4 h-4" aria-hidden="true" />
                  Kembali
                </button>
                <button type="button" class="btn btn-primary rounded-full btn-lg px-8" @click="nextFromGuest">
                  Tinjau Pemesanan
                  <ArrowRight class="w-4 h-4" aria-hidden="true" />
                </button>
              </div>
            </div>

            <!-- ============ STEP 3: Review ============ -->
            <div v-else>
              <h2 ref="stepHeading" tabindex="-1" class="text-xl sm:text-2xl font-bold text-slate-900 mb-1 focus:outline-none">
                Tinjau pemesanan Anda
              </h2>
              <p class="text-sm text-slate-500 mb-6">Pastikan semua informasi benar sebelum mengonfirmasi.</p>

              <div class="space-y-5">
                <!-- Stay -->
                <div class="bg-slate-50 rounded-2xl p-4 sm:p-5">
                  <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Menginap</h3>
                    <button type="button" class="text-xs font-bold text-primary hover:text-primary-hover underline underline-offset-2 py-1.5 min-h-10 inline-flex items-center" @click="goToStep(1)">
                      Ubah
                    </button>
                  </div>
                  <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Check-in</dt>
                      <dd class="font-semibold text-slate-900">{{ formatDateID(stay.check_in) }}</dd>
                    </div>
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Check-out</dt>
                      <dd class="font-semibold text-slate-900">{{ formatDateID(stay.check_out) }}</dd>
                    </div>
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Durasi</dt>
                      <dd class="font-semibold text-slate-900">{{ nights }} malam</dd>
                    </div>
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Jumlah Tamu</dt>
                      <dd class="font-semibold text-slate-900">{{ stay.guests }} orang</dd>
                    </div>
                  </dl>
                </div>

                <!-- Guest info -->
                <div class="bg-slate-50 rounded-2xl p-4 sm:p-5">
                  <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Data Tamu</h3>
                    <button type="button" class="text-xs font-bold text-primary hover:text-primary-hover underline underline-offset-2 py-1.5 min-h-10 inline-flex items-center" @click="goToStep(2)">
                      Ubah
                    </button>
                  </div>
                  <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Nama</dt>
                      <dd class="font-semibold text-slate-900">{{ guest.name }}</dd>
                    </div>
                    <div>
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Telepon</dt>
                      <dd class="font-semibold text-slate-900">{{ guest.phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Email</dt>
                      <dd class="font-semibold text-slate-900 break-all">{{ guest.email }}</dd>
                    </div>
                    <div v-if="guest.special_requests" class="sm:col-span-2">
                      <dt class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-0.5">Permintaan Khusus</dt>
                      <dd class="text-slate-700">{{ guest.special_requests }}</dd>
                    </div>
                  </dl>
                </div>

                <!-- Room (mobile: show since aside hidden below lg? aside is order-1 on mobile so room visible top) -->
                <!-- Price -->
                <div class="bg-primary-soft rounded-2xl p-4 sm:p-5">
                  <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Harga</h3>
                  <PriceBreakdown v-if="roomType" :price-per-night="roomType.base_price" :nights="nights" />
                </div>

                <p class="text-xs text-slate-400 leading-relaxed">
                  Dengan mengonfirmasi, pemesanan Anda akan dibuat berstatus <strong class="text-slate-500">Menunggu Konfirmasi</strong>.
                  Simpan kode reservasi yang muncul setelah ini untuk melacak statusnya.
                </p>
              </div>

              <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
                <button type="button" class="btn btn-outline rounded-full px-6" @click="goToStep(2)">
                  <ArrowLeft class="w-4 h-4" aria-hidden="true" />
                  Kembali
                </button>
                <button
                  type="button"
                  class="btn btn-primary rounded-full btn-lg px-8 hidden sm:inline-flex"
                  :disabled="submitting || roomLoading"
                  :aria-busy="submitting || roomLoading || undefined"
                  @click="roomError ? fetchRoom() : submitBooking()"
                >
                  <LoadingSpinner v-if="submitting || roomLoading" size="sm" />
                  <span>{{ roomError ? 'Muat ulang kamar' : submitting ? 'Memproses pemesanan...' : 'Konfirmasi Reservasi' }}</span>
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Right: room summary -->
        <aside class="public-panel lg:col-span-2 order-1 lg:order-2 lg:sticky lg:top-24 overflow-hidden" aria-label="Ringkasan kamar">
          <RoomSummaryCard
            :room-type="roomType"
            :loading="roomLoading"
            :error="roomError && !!roomType"
            :nights="nights"
            @retry="fetchRoom"
          />
        </aside>
      </div>
    </section>

    <!-- Mobile sticky confirm (step 3) -->
    <div
      v-if="step === 3 && roomType"
      class="public-sticky-action sm:hidden fixed bottom-0 left-0 right-0 z-40 px-4 py-3"
    >
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-xs text-slate-500">Total estimasi</p>
          <p class="text-lg font-bold text-slate-900 truncate">{{ formatCurrency(nights * Number(roomType.base_price || 0)) }}</p>
        </div>
        <button type="button" class="btn btn-primary rounded-full px-6 shrink-0" :disabled="submitting || roomLoading" :aria-busy="submitting || roomLoading || undefined" @click="roomError ? fetchRoom() : submitBooking()">
          <LoadingSpinner v-if="submitting || roomLoading" size="sm" />
          <span>{{ roomError ? 'Muat ulang' : submitting ? 'Memproses...' : 'Konfirmasi' }}</span>
        </button>
      </div>
    </div>

  </PublicLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, ArrowRight, CheckCircle2, XCircle } from 'lucide-vue-next'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import Breadcrumb from '../../components/ui/Breadcrumb.vue'
import PublicPageHeader from '../../components/ui/PublicPageHeader.vue'
import BookingProgress from '../../components/booking/BookingProgress.vue'
import RoomSummaryCard from '../../components/booking/RoomSummaryCard.vue'
import PriceBreakdown from '../../components/booking/PriceBreakdown.vue'
import AlertBox from '../../components/ui/AlertBox.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseTextarea from '../../components/ui/BaseTextarea.vue'
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue'
import api from '../../services/api'
import bookingApi from '../../services/bookingApi'
import { useLandingData } from '../../composables/useLandingData'
import { useToast } from '../../composables/useToast'
import { localToday, nightsBetween, formatDateID, formatCurrency } from '../../utils/dates'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { setting, load } = useLandingData()

const steps = [
  { id: 1, label: 'Tanggal' },
  { id: 2, label: 'Data Tamu' },
  { id: 3, label: 'Tinjauan' },
]

const step = ref(1)
const stepHeading = ref(null)

const today = localToday()
const roomType = ref(null)
const roomLoading = ref(true)
const roomError = ref(false)

const stay = reactive({
  check_in: '',
  check_out: '',
  guests: 2,
})

const guest = reactive({
  name: '',
  email: '',
  phone: '',
  special_requests: '',
})

const fieldErrors = reactive({
  check_in: '',
  check_out: '',
  guests: '',
  name: '',
  email: '',
  phone: '',
  requests: '',
})

const availability = ref(null)
const availabilityLoading = ref(false)
const availabilityError = ref(false)
let availabilitySeq = 0
let roomSeq = 0
let bookingIdempotencyKey = ''
let bookingKeyFingerprint = ''
let availabilityTimer = null

const submitting = ref(false)
const submitError = ref(null)

const nights = computed(() => nightsBetween(stay.check_in, stay.check_out))

const backQuery = computed(() => {
  const q = {}
  if (stay.check_in) q.check_in = stay.check_in
  if (stay.check_out) q.check_out = stay.check_out
  if (stay.guests) q.guests = stay.guests
  return q
})

const step1Valid = computed(() => {
  if (!stay.check_in || !stay.check_out) return false
  if (nights.value <= 0) return false
  if (stay.check_in < today) return false
  const g = Number(stay.guests)
  if (!Number.isInteger(g) || g < 1) return false
  if (roomType.value && g > roomType.value.capacity) return false
  if (availabilityError.value) return false
  if (availability.value === null) return false
  return availability.value > 0
})

function clearSubmitError() {
  submitError.value = null
}

function clearFieldError(key) {
  fieldErrors[key] = ''
}

function goToStep(n) {
  if (n < 1 || n > 3) return
  if (n > 1 && !step1Valid.value) n = 1
  if (n > 2 && !validateGuestFields()) n = 2
  step.value = n
  clearSubmitError()
  nextTick(() => stepHeading.value?.focus())
}

async function fetchRoom() {
  const seq = ++roomSeq
  availabilitySeq += 1
  const availabilityAtStart = availabilitySeq
  roomLoading.value = true
  roomError.value = false
  try {
    const params = {}
    if (stay.check_in && stay.check_out && nights.value > 0) {
      params.check_in = stay.check_in
      params.check_out = stay.check_out
    }
    const res = await api.get(`/guest/room-types/${route.params.roomTypeId}`, { params, timeout: 15000 })
    if (seq !== roomSeq) return
    roomType.value = res.data.room_type
    if (availabilitySeq === availabilityAtStart) {
      availability.value = typeof res.data.available_rooms === 'number' ? res.data.available_rooms : null
    }
    if (roomType.value && Number(stay.guests) > roomType.value.capacity) {
      stay.guests = roomType.value.capacity
    }
    document.title = `Pesan ${roomType.value?.name || 'Kamar'} — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
  } catch {
    if (seq !== roomSeq) return
    roomError.value = true
    availability.value = null
  } finally {
    if (seq === roomSeq) roomLoading.value = false
  }
}

async function checkAvailability() {
  if (!stay.check_in || !stay.check_out || nights.value <= 0) {
    availability.value = null
    availabilityLoading.value = false
    availabilityError.value = false
    return
  }
  const seq = ++availabilitySeq
  availabilityLoading.value = true
  availabilityError.value = false
  try {
    const res = await api.get(`/guest/room-types/${route.params.roomTypeId}`, {
      params: { check_in: stay.check_in, check_out: stay.check_out },
      timeout: 15000,
    })
    if (seq !== availabilitySeq) return
    availability.value = typeof res.data.available_rooms === 'number' ? res.data.available_rooms : null
  } catch {
    if (seq !== availabilitySeq) return
    availability.value = null
    availabilityError.value = true
  } finally {
    if (seq === availabilitySeq) availabilityLoading.value = false
  }
}

function onStayChange() {
  availabilitySeq += 1
  availability.value = null
  availabilityError.value = false
  fieldErrors.check_in = ''
  fieldErrors.check_out = ''
  fieldErrors.guests = ''

  if (stay.check_out && stay.check_in && stay.check_out <= stay.check_in) {
    stay.check_out = ''
  }

  // persist non-sensitive stay data to URL (refresh-safe)
  const query = { ...route.query }
  if (stay.check_in) query.check_in = stay.check_in
  else delete query.check_in
  if (stay.check_out) query.check_out = stay.check_out
  else delete query.check_out
  if (stay.guests) query.guests = String(stay.guests)
  else delete query.guests
  router.replace({ path: route.path, query }).catch(() => {})

  if (availabilityTimer) clearTimeout(availabilityTimer)
  if (stay.check_in && stay.check_out && nights.value > 0) {
    availabilityTimer = setTimeout(checkAvailability, 350)
  }
}

function validateStayFields() {
  let ok = true
  fieldErrors.check_in = ''
  fieldErrors.check_out = ''
  fieldErrors.guests = ''

  if (!stay.check_in) {
    fieldErrors.check_in = 'Pilih tanggal check-in.'
    ok = false
  } else if (stay.check_in < today) {
    fieldErrors.check_in = 'Tanggal check-in tidak boleh lampau.'
    ok = false
  }

  if (!stay.check_out) {
    fieldErrors.check_out = 'Pilih tanggal check-out.'
    ok = false
  } else if (stay.check_in && stay.check_out <= stay.check_in) {
    fieldErrors.check_out = 'Check-out harus setelah check-in.'
    ok = false
  }

  const g = Number(stay.guests)
  if (!Number.isInteger(g) || g < 1) {
    fieldErrors.guests = 'Jumlah tamu minimal 1 orang.'
    ok = false
  } else if (roomType.value && g > roomType.value.capacity) {
    fieldErrors.guests = `Maksimal ${roomType.value.capacity} tamu untuk tipe kamar ini.`
    ok = false
  }

  return ok
}

function validateGuestFields() {
  let ok = true
  fieldErrors.name = ''
  fieldErrors.email = ''
  fieldErrors.phone = ''
  fieldErrors.requests = ''

  if (!guest.name.trim()) {
    fieldErrors.name = 'Nama lengkap wajib diisi.'
    ok = false
  }

  const email = guest.email.trim()
  if (!email) {
    fieldErrors.email = 'Email wajib diisi.'
    ok = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    fieldErrors.email = 'Format email tidak valid.'
    ok = false
  }

  const phone = guest.phone.trim()
  if (!phone) {
    fieldErrors.phone = 'Nomor telepon wajib diisi.'
    ok = false
  } else if (!/^[0-9+\-\s()]{6,20}$/.test(phone)) {
    fieldErrors.phone = 'Nomor telepon tidak valid (6–20 karakter, angka dan + - saja).'
    ok = false
  }

  if (guest.special_requests.length > 2000) {
    fieldErrors.requests = 'Permintaan khusus maksimal 2.000 karakter.'
    ok = false
  }

  return ok
}

function nextFromGuest() {
  if (!validateGuestFields()) return
  goToStep(3)
}

function mapServerError(err) {
  const status = err.response?.status
  const data = err.response?.data

  if (!err.response) {
    if (err.code === 'ECONNABORTED') {
      return {
        title: 'Koneksi terlalu lambat',
        message: 'Permintaan memakan waktu terlalu lama. Pemesanan mungkin belum terkirim — silakan coba lagi.',
        action: 'retry',
      }
    }
    return {
      title: 'Koneksi bermasalah',
      message: 'Tidak dapat terhubung ke server. Silakan periksa koneksi Anda lalu coba lagi.',
      action: 'retry',
    }
  }

  if (data?.errors) {
    const map = {
      check_in_date: 'check_in',
      check_out_date: 'check_out',
      number_of_guests: 'guests',
      name: 'name',
      email: 'email',
      phone: 'phone',
      special_requests: 'requests',
      room_type_id: null,
    }
    for (const [serverKey, messages] of Object.entries(data.errors)) {
      const localKey = map[serverKey]
      if (localKey && Array.isArray(messages) && messages.length) {
        fieldErrors[localKey] = messages[0]
      }
    }
  }

  const message = data?.message || 'Pemesanan tidak dapat diproses. Silakan periksa data Anda lalu coba lagi.'

  if (/tidak ada kamar tersedia/i.test(message)) {
    return {
      title: 'Kamar tidak tersedia',
      message: 'Kamar ini baru saja dipesan oleh tamu lain untuk tanggal tersebut. Silakan pilih tanggal atau tipe kamar lain.',
      action: 'dates',
    }
  }

  if (/melebihi kapasitas/i.test(message)) {
    return { title: 'Jumlah tamu melebihi kapasitas', message, action: 'dates' }
  }

  if (status === 429) {
    return {
      title: 'Terlalu banyak permintaan',
      message: 'Anda terlalu sering mencoba. Tunggu sebentar lalu coba lagi.',
      action: 'retry',
    }
  }

  if (status === 422) {
    return { title: 'Data belum valid', message, action: null }
  }

  return { title: 'Pemesanan gagal', message: 'Pemesanan tidak dapat diproses. Silakan coba lagi.', action: 'retry' }
}

async function submitBooking() {
  if (submitting.value) return
  if (!step1Valid.value) {
    goToStep(1)
    return
  }
  if (!validateGuestFields()) {
    goToStep(2)
    return
  }

  submitting.value = true
  submitError.value = null
  try {
    const payload = {
      room_type_id: Number(route.params.roomTypeId),
      name: guest.name.trim(),
      email: guest.email.trim(),
      phone: guest.phone.trim(),
      check_in_date: stay.check_in,
      check_out_date: stay.check_out,
      number_of_guests: Number(stay.guests),
      special_requests: guest.special_requests.trim() || null,
    }
    const fingerprint = JSON.stringify(payload)
    const storageKey = `booking-intent:${route.params.roomTypeId}:${stay.check_in}:${stay.check_out}:${stay.guests}`
    if (bookingKeyFingerprint !== fingerprint) {
      bookingIdempotencyKey = readBookingKey(storageKey) || createIdempotencyKey()
      bookingKeyFingerprint = fingerprint
      writeBookingKey(storageKey, bookingIdempotencyKey)
    }
    const res = await bookingApi.submitGuest(payload, {
      timeout: 30000,
      headers: { 'Idempotency-Key': bookingIdempotencyKey },
    })
    toast.success('Pemesanan berhasil dibuat.')
    clearBookingKey(`booking-intent:${route.params.roomTypeId}:${stay.check_in}:${stay.check_out}:${stay.guests}`)
    await router.replace(`/booking/success/${res.data.reservation_code}`).catch(() => {})
  } catch (err) {
    if (err.response?.status === 409) {
      clearBookingKey(`booking-intent:${route.params.roomTypeId}:${stay.check_in}:${stay.check_out}:${stay.guests}`)
      bookingIdempotencyKey = ''
      bookingKeyFingerprint = ''
    }
    submitError.value = mapServerError(err)
    // refresh availability after a conflict
    if (submitError.value?.action === 'dates') checkAvailability()
  } finally {
    submitting.value = false
  }
}

function createIdempotencyKey() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  return `booking-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function readBookingKey(key) {
  try {
    return sessionStorage.getItem(key)
  } catch {
    return null
  }
}

function writeBookingKey(key, value) {
  try {
    sessionStorage.setItem(key, value)
  } catch {}
}

function clearBookingKey(key) {
  try {
    sessionStorage.removeItem(key)
  } catch {}
}

onMounted(() => {
  if (route.query.check_in) stay.check_in = String(route.query.check_in)
  if (route.query.check_out) stay.check_out = String(route.query.check_out)
  if (route.query.guests) stay.guests = parseInt(route.query.guests, 10) || 2

  // clamp prefilled dates to today+
  if (stay.check_in && stay.check_in < today) stay.check_in = today

  fetchRoom()
  load()
    .catch(() => {})
    .finally(() => {
      document.title = `Pesan Kamar — ${setting.value?.hotel_name || 'Lokanata Hotel'}`
    })

})

onUnmounted(() => {
  clearTimeout(availabilityTimer)
  availabilitySeq += 1
  roomSeq += 1
})

watch(
  () => roomType.value?.capacity,
  (cap) => {
    if (cap && Number(stay.guests) > cap) stay.guests = cap
  }
)
</script>
