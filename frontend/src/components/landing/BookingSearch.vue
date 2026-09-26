<template>
  <section id="booking" class="relative z-20 mx-auto -mt-20 w-[calc(100%-2rem)] max-w-public scroll-mt-32 sm:-mt-24 md:-mt-28">
    <form class="border border-luxury-stone/35 bg-white shadow-[0_24px_70px_-38px_rgba(28,31,29,.5)]" @submit.prevent="handleSubmit">
      <div class="flex flex-col gap-2 border-b border-luxury-stone/25 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
        <p class="text-[0.68rem] font-bold uppercase tracking-[0.24em] text-luxury-ink">Cari ketersediaan</p>
        <p id="booking-help" class="text-xs text-hotel-muted">Pilih tanggal untuk melihat kamar yang tersedia.</p>
      </div>

      <div class="grid gap-px bg-luxury-stone/20 sm:grid-cols-2 xl:grid-cols-12">
        <div class="bg-white px-5 py-4 sm:px-6 xl:col-span-3">
          <label for="landing-checkin" class="mb-2 block text-[0.65rem] font-bold uppercase tracking-[0.18em] text-hotel-muted">Check-in</label>
          <div class="flex items-center gap-3">
            <CalendarDays class="h-4 w-4 shrink-0 text-luxury-gold" aria-hidden="true" />
            <input id="landing-checkin" ref="checkInInput" v-model="checkIn" type="date" :min="today" :max="checkInMax" :aria-invalid="dateError" :aria-describedby="error ? 'booking-help booking-error' : 'booking-help'" class="min-h-11 min-w-0 flex-1 bg-transparent text-base text-luxury-ink focus-visible:ring-2 focus-visible:ring-luxury-gold/35 focus-visible:ring-offset-2 sm:text-sm" @change="handleCheckInChange" />
          </div>
        </div>

        <div class="bg-white px-5 py-4 sm:px-6 xl:col-span-3">
          <label for="landing-checkout" class="mb-2 block text-[0.65rem] font-bold uppercase tracking-[0.18em] text-hotel-muted">Check-out</label>
          <div class="flex items-center gap-3">
            <CalendarDays class="h-4 w-4 shrink-0 text-luxury-gold" aria-hidden="true" />
            <input id="landing-checkout" ref="checkOutInput" v-model="checkOut" type="date" :min="checkIn || today" :max="checkOutMax" :aria-invalid="dateError" :aria-describedby="error ? 'booking-help booking-error' : 'booking-help'" class="min-h-11 min-w-0 flex-1 bg-transparent text-base text-luxury-ink focus-visible:ring-2 focus-visible:ring-luxury-gold/35 focus-visible:ring-offset-2 sm:text-sm" />
          </div>
        </div>

        <div class="bg-white px-5 py-4 sm:px-6 xl:col-span-2">
          <label for="landing-guests" class="mb-2 block text-[0.65rem] font-bold uppercase tracking-[0.18em] text-hotel-muted">Tamu</label>
          <div class="flex items-center gap-3">
            <Users class="h-4 w-4 shrink-0 text-luxury-gold" aria-hidden="true" />
            <select id="landing-guests" ref="guestsInput" v-model.number="guests" :aria-invalid="guestError" aria-describedby="booking-help" class="min-h-11 w-full min-w-0 bg-transparent text-base text-luxury-ink focus-visible:ring-2 focus-visible:ring-luxury-gold/35 focus-visible:ring-offset-2 sm:text-sm">
              <option v-for="count in guestOptions" :key="count" :value="count">{{ count }} tamu</option>
            </select>
          </div>
        </div>

        <div class="bg-white px-5 py-4 sm:px-6 xl:col-span-2">
          <label for="landing-room-type" class="mb-2 block text-[0.65rem] font-bold uppercase tracking-[0.18em] text-hotel-muted">Tipe kamar</label>
          <select id="landing-room-type" v-model="selectedTypeId" :disabled="loading || roomTypes.length === 0" class="min-h-11 w-full min-w-0 bg-transparent text-base text-luxury-ink focus-visible:ring-2 focus-visible:ring-luxury-gold/35 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:text-ink-muted sm:text-sm">
            <option value="">Semua tipe</option>
            <option v-for="roomType in roomTypes" :key="roomType.id" :value="roomType.id">{{ roomType.name }}</option>
          </select>
        </div>

        <div class="flex items-center bg-luxury-ink px-5 py-4 sm:col-span-2 sm:px-6 xl:col-span-2">
          <button type="submit" class="btn group min-h-12 w-full items-center justify-center gap-2 rounded-full bg-luxury-cream px-5 text-sm font-bold text-luxury-ink transition-colors hover:bg-white focus-visible:outline-white">
            Cek Ketersediaan
            <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
          </button>
        </div>
      </div>

      <p v-if="error" id="booking-error" class="border-t border-danger/20 bg-danger-soft px-5 py-3 text-sm text-danger sm:px-7" role="alert">
        {{ error }}
      </p>
    </form>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ArrowRight, CalendarDays, Users } from 'lucide-vue-next'
import { localDateAfter, localToday } from '../../utils/dates'

const props = defineProps({
  roomTypes: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['search', 'update:stay-query'])
const today = localToday()
const checkInMax = localDateAfter(365)
const checkOutMax = localDateAfter(366)
const checkIn = ref('')
const checkOut = ref('')
const guests = ref(2)
const selectedTypeId = ref('')
const error = ref('')
const dateError = ref(false)
const guestError = ref(false)
const checkInInput = ref(null)
const checkOutInput = ref(null)
const guestsInput = ref(null)
const guestOptions = Array.from({ length: 20 }, (_, index) => index + 1)

const stayQuery = computed(() => {
  const query = {}
  if (checkIn.value && checkOut.value && checkOut.value > checkIn.value) {
    query.check_in = checkIn.value
    query.check_out = checkOut.value
  }
  if (guests.value) query.guests = guests.value
  return query
})

function handleCheckInChange() {
  dateError.value = false
  if (checkOut.value && checkIn.value && checkOut.value <= checkIn.value) checkOut.value = ''
}

function handleSubmit() {
  error.value = ''
  dateError.value = false
  guestError.value = false

  if (!checkIn.value || !checkOut.value) {
    error.value = 'Pilih tanggal check-in dan check-out.'
    dateError.value = true
    const focusTarget = checkIn.value ? checkOutInput.value : checkInInput.value
    focusTarget?.focus()
    return
  }
  if (checkOut.value <= checkIn.value) {
    error.value = 'Tanggal check-out harus setelah check-in.'
    dateError.value = true
    checkOutInput.value?.focus()
    return
  }
  if (checkIn.value > checkInMax || checkOut.value > checkOutMax) {
    error.value = 'Tanggal pemesanan hanya dapat dibuat hingga satu tahun ke depan.'
    dateError.value = true
    checkInInput.value?.focus()
    return
  }
  if (!Number.isInteger(guests.value) || guests.value < 1 || guests.value > 20) {
    error.value = 'Jumlah tamu harus antara 1 hingga 20.'
    guestError.value = true
    guestsInput.value?.focus()
    return
  }

  const query = { ...stayQuery.value }
  if (selectedTypeId.value) query.type = selectedTypeId.value
  emit('search', query)
}

watch(
  [checkIn, checkOut, guests],
  () => emit('update:stay-query', { ...stayQuery.value }),
  { immediate: true }
)
</script>
