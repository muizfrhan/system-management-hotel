export function toLocalDateStr(date = new Date()) {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

export function localToday() {
    return toLocalDateStr(new Date())
}

export function localDateAfter(days) {
    const date = new Date()
    date.setHours(12, 0, 0, 0)
    date.setDate(date.getDate() + Number(days || 0))
    return toLocalDateStr(date)
}

export function nightsBetween(checkIn, checkOut) {
    if (!checkIn || !checkOut) return 0
    const a = new Date(`${checkIn}T00:00:00`)
    const b = new Date(`${checkOut}T00:00:00`)
    if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime())) return 0
    const diff = Math.round((b - a) / 86400000)
    return diff > 0 ? diff : 0
}

export function formatDateID(value, options = { day: 'numeric', month: 'long', year: 'numeric' }) {
    if (!value) return '-'
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`)
    if (Number.isNaN(date.getTime())) return '-'
    return date.toLocaleDateString('id-ID', options)
}

export function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value) || 0)
}
