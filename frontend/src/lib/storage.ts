/**
 * Standard utility to resolve backend storage file URLs safely.
 * Handles relative paths, legacy absolute URLs from local test domains (e.g. be-summit.test),
 * and dynamic VITE_API_BASE_URL environments.
 */
export function getStorageUrl(path: string | null | undefined, fallback = '/images/hero-summit.jpg'): string {
  if (!path || !path.trim()) {
    return fallback
  }

  const trimmed = path.trim()
  const backendBase = (import.meta.env.VITE_API_BASE_URL?.replace('/api/v1', '') || 'http://127.0.0.1:8000').replace(/\/$/, '')

  // If path is a blob or data URI (from FileReader or URL.createObjectURL)
  if (trimmed.startsWith('blob:') || trimmed.startsWith('data:')) {
    return trimmed
  }

  // If path is relative
  if (!trimmed.startsWith('http://') && !trimmed.startsWith('https://')) {
    const cleanPath = trimmed.startsWith('/') ? trimmed.slice(1) : trimmed
    const storagePath = cleanPath.startsWith('storage/') ? cleanPath : `storage/${cleanPath}`
    return `${backendBase}/${storagePath}`
  }

  // If path is an absolute URL
  try {
    const url = new URL(trimmed)
    if (url.pathname.includes('/storage/')) {
      const storageIdx = url.pathname.indexOf('/storage/')
      const relativeStorage = url.pathname.slice(storageIdx)
      return `${backendBase}${relativeStorage}`
    }
    return trimmed
  } catch {
    return trimmed
  }
}

export default getStorageUrl
