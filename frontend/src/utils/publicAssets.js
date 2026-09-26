export function publicAssetUrl(path) {
  if (typeof path !== 'string' || !path || path.includes('..') || path.includes('\\')) return null
  if (path.startsWith('/storage/')) return path
  if (/^https:\/\//i.test(path)) return path
  if (path.startsWith('/') || path.startsWith('//')) return null
  return `/storage/${path}`
}
