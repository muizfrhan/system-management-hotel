<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
  >
    <template v-if="iconKey === 'wifi'">
      <g stroke="#2563eb" stroke-width="2" stroke-linecap="round">
        <path d="M3.6 9.4a10.4 10.4 0 0 1 16.8 0" />
        <path d="M7 13.1a7 7 0 0 1 10 0" />
        <path d="M10.1 16.6a3.4 3.4 0 0 1 3.8 0" />
      </g>
      <circle cx="12" cy="19.4" r="1.4" fill="#2563eb" />
    </template>

    <template v-else-if="iconKey === 'ac'">
      <rect x="2.5" y="5.4" width="19" height="9.6" rx="2.5" fill="#e5e7eb" />
      <rect x="2.5" y="11.9" width="19" height="3.1" rx="1.55" fill="#9ca3af" />
      <g stroke="#4b5563" stroke-width="1.5" stroke-linecap="round">
        <path d="M12 7.2v3.8" />
        <path d="M10.3 8.1 13.7 10.1" />
        <path d="M13.7 8.1 10.3 10.1" />
      </g>
    </template>

    <template v-else-if="iconKey === 'tv'">
      <path d="M8.8 7.4 12 3.9l3.2 3.5" stroke="#4b5563" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      <rect x="2.6" y="7.2" width="18.8" height="12" rx="2.4" fill="#e5e7eb" />
      <rect x="4.6" y="9.2" width="14.8" height="7.6" rx="1.4" fill="#4b5563" />
      <rect x="7.6" y="19.2" width="8.8" height="2" rx="1" fill="#9ca3af" />
    </template>

    <template v-else-if="iconKey === 'kolam-renang'">
      <rect x="2.5" y="3.2" width="19" height="17.6" rx="2.6" fill="#dbeafe" />
      <rect x="2.5" y="3.2" width="19" height="17.6" rx="2.6" stroke="#93c5fd" stroke-width="1.6" />
      <g stroke="#2563eb" stroke-width="1.6" stroke-linecap="round">
        <path d="M4.8 8.6c1.2-1 2.4-1 3.6 0s2.4 1 3.6 0 2.4-1 3.6 0 2.4 1 3.6 0" />
        <path d="M4.8 12c1.2-1 2.4-1 3.6 0s2.4 1 3.6 0 2.4-1 3.6 0 2.4 1 3.6 0" />
      </g>
      <rect x="2.5" y="14.6" width="7.2" height="2.6" rx="1.3" fill="#ffffff" stroke="#9ca3af" stroke-width="1.2" />
      <circle cx="15.4" cy="15.1" r="1.7" fill="#2563eb" />
      <path d="M12 19.5c1.5-1.9 3-2.8 4.6-3" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" />
    </template>

    <template v-else-if="iconKey === 'parkir'">
      <rect x="3" y="2.5" width="18" height="12.6" rx="2.4" fill="#e5e7eb" />
      <path d="M9.6 12.4V6.1h3.2a2.7 2.7 0 0 1 0 5.4H9.6" stroke="#4b5563" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M6.6 18.4 8 15.1h8l1.4 3.3" fill="#dbeafe" stroke="#2563eb" stroke-width="1.4" stroke-linejoin="round" />
      <rect x="3.6" y="18.2" width="16.8" height="3.4" rx="1.7" fill="#2563eb" />
      <circle cx="7.6" cy="21.8" r="1.5" fill="#4b5563" />
      <circle cx="16.4" cy="21.8" r="1.5" fill="#4b5563" />
    </template>

    <template v-else-if="iconKey === 'restoran'">
      <circle cx="12" cy="12" r="8.4" fill="#f9fafb" stroke="#9ca3af" stroke-width="1.6" />
      <g stroke="#4b5563" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M7.2 4.6v3.1M9.2 4.6v3.1M11.2 4.6v3.1" />
        <path d="M7.2 7.7h4c.4 1 .3 2-.3 2.7-.5.6-1.1.9-1.7.9s-1.2-.3-1.7-.9c-.6-.7-.7-1.7-.3-2.7Z" fill="#4b5563" stroke="none" />
        <path d="M9.2 11.3v8.1" />
        <path d="M15.6 4.6v14.8" />
        <path d="M15.6 4.6c1.6.7 2.5 2.3 2.5 4.1 0 1.6-.9 2.5-2.5 2.5Z" fill="#4b5563" stroke="none" />
      </g>
    </template>

    <template v-else-if="iconKey === 'gym'">
      <rect x="2.4" y="10.4" width="19.2" height="3.2" rx="1.6" fill="#9ca3af" />
      <rect x="4.4" y="7.2" width="3.6" height="9.6" rx="1.6" fill="#4b5563" />
      <rect x="7.6" y="9" width="3.2" height="6" rx="1.3" fill="#6b7280" />
      <rect x="13.2" y="9" width="3.2" height="6" rx="1.3" fill="#6b7280" />
      <rect x="16" y="7.2" width="3.6" height="9.6" rx="1.6" fill="#4b5563" />
    </template>

    <template v-else-if="iconKey === 'laundry'">
      <rect x="4" y="2.5" width="16" height="19" rx="2.6" fill="#ffffff" stroke="#9ca3af" stroke-width="1.6" />
      <path d="M4 7.1h16" stroke="#9ca3af" stroke-width="1.4" />
      <circle cx="7.4" cy="4.9" r="1.1" fill="#4b5563" />
      <rect x="9.8" y="4.2" width="3.6" height="1.4" rx="0.7" fill="#9ca3af" />
      <circle cx="12" cy="14.6" r="4.8" fill="#2563eb" />
      <circle cx="12" cy="14.6" r="2.6" fill="#dbeafe" />
    </template>

    <template v-else>
      <rect x="3.2" y="3.2" width="17.6" height="17.6" rx="4.5" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.6" />
      <circle cx="12" cy="12" r="3" fill="#94a3b8" />
    </template>
  </svg>
</template>

<script setup>
import { computed } from 'vue'
import { facilityIconKey } from '../../utils/facilityIcons'

const props = defineProps({
  name: { type: [String, Object], default: '' },
  size: { type: [Number, String], default: 24 },
})

const iconKey = computed(() => facilityIconKey(props.name))
</script>
