const ICON_ALIASES = {
  wifi: 'wifi',
  'wi-fi': 'wifi',
  wireless: 'wifi',
  ac: 'ac',
  'air-conditioner': 'ac',
  'air-conditioner-unit': 'ac',
  'air-vent': 'ac',
  snowflake: 'ac',
  tv: 'tv',
  television: 'tv',
  'kolam-renang': 'kolam-renang',
  kolam: 'kolam-renang',
  pool: 'kolam-renang',
  'swimming-pool': 'kolam-renang',
  parkir: 'parkir',
  parking: 'parkir',
  'parking-lot': 'parkir',
  restoran: 'restoran',
  restaurant: 'restoran',
  'food-beverage': 'restoran',
  gym: 'gym',
  gymnasium: 'gym',
  'fitness-center': 'gym',
  dumbbell: 'gym',
  laundry: 'laundry',
  'washing-machine': 'laundry',
}

export const FACILITY_ICON_OPTIONS = [
  { value: 'wifi', label: 'WiFi' },
  { value: 'ac', label: 'AC (Air Conditioner)' },
  { value: 'tv', label: 'TV' },
  { value: 'kolam-renang', label: 'Kolam Renang' },
  { value: 'parkir', label: 'Parkir' },
  { value: 'restoran', label: 'Restoran' },
  { value: 'gym', label: 'Gym' },
  { value: 'laundry', label: 'Laundry' },
]

function normalize(value) {
  return String(value || '')
    .trim()
    .toLocaleLowerCase('id-ID')
    .replace(/[\s_]+/g, '-')
    .replace(/-{2,}/g, '-')
}

export function facilityIconKey(value) {
  if (value && typeof value === 'object') return facilityIconKey(value.icon || value.name || '')
  const normalized = normalize(value)
  if (!normalized) return 'default'
  return ICON_ALIASES[normalized] || 'default'
}
