<template>
  <PublicLayout>
    <HotelHero
        :hotel-name="hotelName"
        :tagline="setting?.tagline || ''"
        :address="setting?.address || ''"
        :image="heroImage"
        :image-only="heroImage === heroAsset"
        :image-room-name="heroImage === heroAsset ? '' : heroImage && heroRoom ? heroRoom.name : ''"
        :loading="loading"
        @error="markImageUnavailable"
      />

      <BookingSearch
        :room-types="roomTypes"
        :loading="loading"
        @search="handleSearch"
        @update:stay-query="stayQuery = $event"
      />

      <HotelIntro
        :hotel-name="hotelName"
        :tagline="setting?.tagline || ''"
        :address="setting?.address || ''"
        :image="introImage"
        :image-room-name="introImage === introAsset ? '' : introImage && introRoom ? introRoom.name : ''"
        @error="markImageUnavailable"
      />

      <FeaturedRooms
        :rooms="featuredRooms"
        :loading="loading"
        :error="loadError"
        :stay-query="stayQuery"
        @retry="reload"
      />

      <RoomAmenitiesSection v-if="facilityGroups.length" :rooms="roomTypes" />

      <RoomStories v-if="storyRooms.length" :rooms="storyRooms" :hotel-name="hotelName" :stay-query="stayQuery" />

      <HotelGallery v-if="galleryItems.length" :items="galleryItems" />

      <BookingJourney :stay-query="stayQuery" />

      <HotelLocation
        v-if="hasContactData"
        :hotel-name="hotelName"
        :address="setting?.address || ''"
        :phone="setting?.phone || ''"
        :email="setting?.email || ''"
      />

      <HotelFinalCta
        :hotel-name="hotelName"
        :image="heroImage"
        @error="markImageUnavailable"
      />
  </PublicLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import PublicLayout from '../../components/layout/PublicLayout.vue'
import HotelHero from '../../components/landing/HotelHero.vue'
import BookingSearch from '../../components/landing/BookingSearch.vue'
import HotelIntro from '../../components/landing/HotelIntro.vue'
import FeaturedRooms from '../../components/landing/FeaturedRooms.vue'
import RoomAmenitiesSection from '../../components/landing/RoomAmenitiesSection.vue'
import RoomStories from '../../components/landing/RoomStories.vue'
import HotelGallery from '../../components/landing/HotelGallery.vue'
import BookingJourney from '../../components/landing/BookingJourney.vue'
import HotelLocation from '../../components/landing/HotelLocation.vue'
import HotelFinalCta from '../../components/landing/HotelFinalCta.vue'
import { useLandingData } from '../../composables/useLandingData'
import { useLandingSeo } from '../../composables/useLandingSeo'
import { roomImageUrl, resolveRoomImages } from '../../utils/roomImages'
import { aggregateRoomFacilities } from '../../utils/roomFacilities'

const router = useRouter()
const { setting, roomTypes, loading, error: loadError, load } = useLandingData()
const stayQuery = ref({})
const failedImages = ref(new Set())
const heroAsset = '/hero.jpg'
const introAsset = '/tentang.jpg'

const hotelName = computed(() => setting.value?.hotel_name || 'Lokanata Hotel')
const featuredRooms = computed(() => roomTypes.value.slice(0, 3))
const roomsWithImages = computed(() => roomTypes.value.filter((room) => roomImageUrl(room)))
const heroRoom = computed(() => roomsWithImages.value[0] || null)
const heroImage = computed(() => {
  if (!failedImages.value.has(heroAsset)) return heroAsset
  const src = heroRoom.value ? roomImageUrl(heroRoom.value) : null
  return src && !failedImages.value.has(src) ? src : null
})
const introRoom = computed(() => roomsWithImages.value.find((room) => room.id !== heroRoom.value?.id) || null)
const introImage = computed(() => {
  if (!failedImages.value.has(introAsset)) return introAsset
  const src = introRoom.value ? roomImageUrl(introRoom.value) : null
  return src && !failedImages.value.has(src) ? src : null
})
const storyRooms = computed(() =>
  roomsWithImages.value.filter((room) => ![heroRoom.value?.id, introRoom.value?.id].includes(room.id)).slice(0, 2)
)
const galleryItems = computed(() =>
  roomTypes.value.flatMap((room) =>
    resolveRoomImages(room).map((src, index) => ({
      src,
      roomName: room.name,
      alt: `${room.name}${index > 0 ? ` — detail ${index + 1}` : ''} di ${hotelName.value}`,
    }))
  )
)
const facilityGroups = computed(() => aggregateRoomFacilities(roomTypes.value))
const hasContactData = computed(() => !!setting.value?.address)

useLandingSeo(setting, roomTypes, heroImage)

function markImageUnavailable(src) {
  if (!src) return
  failedImages.value = new Set([...failedImages.value, src])
}

function handleSearch(query) {
  router.push({ path: '/rooms', query }).catch(() => {})
}

function reload() {
  load(true).catch(() => {})
}

onMounted(() => {
  load().catch(() => {})
})
</script>
