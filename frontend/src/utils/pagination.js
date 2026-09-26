export function pageWindow(current, last, span = 2) {
  const currentPage = Math.max(1, Number(current) || 1)
  const lastPage = Math.max(1, Number(last) || 1)
  const start = Math.max(1, Math.min(currentPage - span, lastPage - span * 2))
  const end = Math.min(lastPage, start + span * 2)
  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
}
