export function roomFacilities(roomType) {
  const items = []
  const seen = new Set()

  for (const facility of roomType?.facilities || []) {
    const name = String(facility?.name || '').trim()
    if (!name) continue
    const key = name.toLocaleLowerCase('id-ID')
    if (seen.has(key)) continue
    seen.add(key)
    items.push({ name, icon: facility?.icon || '' })
  }

  for (const value of Object.values(roomType?.custom_facilities || {})) {
    for (const entry of String(value || '').split(',')) {
      const name = entry.trim()
      if (!name) continue
      const key = name.toLocaleLowerCase('id-ID')
      if (seen.has(key)) continue
      seen.add(key)
      items.push({ name, icon: '' })
    }
  }

  return items
}

export function aggregateRoomFacilities(roomTypes) {
  const groups = new Map()
  for (const roomType of roomTypes || []) {
    const roomName = String(roomType?.name || '').trim()
    for (const facility of roomFacilities(roomType)) {
      const key = facility.name.toLocaleLowerCase('id-ID')
      const existing = groups.get(key)
      if (existing) {
        if (!existing.icon && facility.icon) existing.icon = facility.icon
        if (roomName && !existing.roomNames.includes(roomName)) existing.roomNames.push(roomName)
      } else {
        groups.set(key, { name: facility.name, icon: facility.icon, roomNames: roomName ? [roomName] : [] })
      }
    }
  }
  return [...groups.values()].sort((a, b) => a.name.localeCompare(b.name, 'id-ID'))
}
