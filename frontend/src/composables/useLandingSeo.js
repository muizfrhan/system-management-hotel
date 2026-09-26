import { onBeforeUnmount, watch } from 'vue'

function upsertMeta(selector, attribute, content) {
  let element = document.head.querySelector(selector)
  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, selector.includes('property=') ? 'property' : 'name')
    const [key, value] = selector.match(/(?:property|name)="([^"]+)"/).slice(1)
    element.setAttribute(key, value)
    document.head.appendChild(element)
  }
  element.setAttribute('content', content)
}

function removeMeta(selector) {
  document.head.querySelector(selector)?.remove()
}

function canonicalUrl() {
  const url = new URL(import.meta.env.BASE_URL || '/', window.location.origin)
  url.search = ''
  url.hash = ''
  return url.href
}

function mediaUrl(src) {
  if (!src) return null
  if (/^https:\/\//i.test(src)) return src
  return new URL(src, window.location.origin).href
}

export function useLandingSeo(setting, roomTypes, heroImage) {
  const update = () => {
    const hotelName = setting.value?.hotel_name || 'Lokanata Hotel'
    const description = `Pesan kamar di ${hotelName}, lihat detail tipe kamar, cek ketersediaan, dan lacak status reservasi secara online.`
    const url = canonicalUrl()
    const image = mediaUrl(heroImage?.value)

    document.title = `${hotelName} — Pesan Kamar Online`
    upsertMeta('meta[name="description"]', 'name', description)
    upsertMeta('meta[property="og:site_name"]', 'property', hotelName)
    upsertMeta('meta[property="og:title"]', 'property', `${hotelName} — Pesan Kamar Online`)
    upsertMeta('meta[property="og:description"]', 'property', description)
    upsertMeta('meta[property="og:type"]', 'property', 'website')
    upsertMeta('meta[property="og:url"]', 'property', url)
    upsertMeta('meta[name="twitter:title"]', 'name', `${hotelName} — Pesan Kamar Online`)
    upsertMeta('meta[name="twitter:description"]', 'name', description)

    if (image) {
      upsertMeta('meta[property="og:image"]', 'property', image)
      upsertMeta('meta[property="og:image:alt"]', 'property', `Kamar di ${hotelName}`)
      upsertMeta('meta[name="twitter:image"]', 'name', image)
    } else {
      removeMeta('meta[property="og:image"]')
      removeMeta('meta[property="og:image:alt"]')
      removeMeta('meta[name="twitter:image"]')
    }

    let canonical = document.head.querySelector('link[rel="canonical"]')
    if (!canonical) {
      canonical = document.createElement('link')
      canonical.setAttribute('rel', 'canonical')
      document.head.appendChild(canonical)
    }
    canonical.setAttribute('href', url)

    let script = document.getElementById('hotel-structured-data')
    if (!setting.value) {
      script?.remove()
      return
    }

    const schema = {
      '@context': 'https://schema.org',
      '@type': 'Hotel',
      name: setting.value.hotel_name,
      description,
      url,
    }

    if (setting.value.address) {
      schema.address = {
        '@type': 'PostalAddress',
        streetAddress: setting.value.address,
      }
    }
    if (setting.value.phone) schema.telephone = setting.value.phone
    if (setting.value.email) schema.email = setting.value.email

    if (!script) {
      script = document.createElement('script')
      script.type = 'application/ld+json'
      script.id = 'hotel-structured-data'
      document.head.appendChild(script)
    }
    script.textContent = JSON.stringify(schema)
  }

  watch([setting, roomTypes, heroImage], update, { deep: true, immediate: true })

  onBeforeUnmount(() => {
    document.getElementById('hotel-structured-data')?.remove()
  })
}
