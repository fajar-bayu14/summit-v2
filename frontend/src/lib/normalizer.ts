import type { ApiResponse, LaravelPaginatedData, NormalizedPaginatedResult, PaginationMeta, PaginationLinks } from '@/types/api'

/**
 * Normalizes backend response which can be either a simple ApiResponse<T>,
 * an ApiResponse with paginated Laravel payload (e.g., data.data and data.meta),
 * or standard array data.
 */
export function normalizePaginatedResponse<T = any>(response: ApiResponse<LaravelPaginatedData<T> | T[]> | any): NormalizedPaginatedResult<T> {
  const payload = response?.data !== undefined ? response.data : response

  // If payload has nested data array (standard Laravel length-aware pagination)
  if (payload && typeof payload === 'object' && Array.isArray(payload.data)) {
    const items: T[] = payload.data
    const meta: PaginationMeta = payload.meta || {
      current_page: payload.current_page || 1,
      from: payload.from || (items.length > 0 ? 1 : null),
      last_page: payload.last_page || 1,
      per_page: payload.per_page || items.length || 15,
      to: payload.to || items.length,
      total: payload.total !== undefined ? payload.total : items.length,
    }

    const rawLinks = payload.links
    const links: PaginationLinks = Array.isArray(rawLinks)
      ? {
          first: rawLinks.find(l => l.label.includes('First') || l.label === '1')?.url || null,
          last: rawLinks.find(l => l.label.includes('Last'))?.url || null,
          prev: rawLinks.find(l => l.label.includes('Previous') || l.label === '&laquo; Previous')?.url || null,
          next: rawLinks.find(l => l.label.includes('Next') || l.label === 'Next &raquo;')?.url || null,
        }
      : rawLinks || {}

    return { items, meta, links }
  }

  // If payload already has normalized items array
  if (payload && typeof payload === 'object' && Array.isArray((payload as any).items)) {
    const items: T[] = (payload as any).items
    const meta: PaginationMeta = (payload as any).meta || {
      current_page: 1,
      from: items.length > 0 ? 1 : null,
      last_page: 1,
      per_page: items.length || 15,
      to: items.length,
      total: items.length,
    }
    return { items, meta, links: (payload as any).links || {} }
  }

  // If payload is already a raw array
  if (Array.isArray(payload)) {
    return {
      items: payload,
      meta: {
        current_page: 1,
        from: payload.length > 0 ? 1 : null,
        last_page: 1,
        per_page: payload.length,
        to: payload.length,
        total: payload.length,
      },
      links: {},
    }
  }

  // Fallback empty result
  return {
    items: [],
    meta: {
      current_page: 1,
      from: null,
      last_page: 1,
      per_page: 15,
      to: null,
      total: 0,
    },
    links: {},
  }
}

/**
 * Extracts a human-readable error message and structured field validation errors
 * from an API error response.
 */
export function extractApiError(err: any): { message: string; fieldErrors: Record<string, string> } {
  const resData = err?.response?.data
  let message = resData?.message || err?.message || 'Terjadi kesalahan pada sistem.'
  const fieldErrors: Record<string, string> = {}

  if (resData?.errors && typeof resData.errors === 'object') {
    for (const [key, value] of Object.entries(resData.errors)) {
      if (Array.isArray(value) && value.length > 0) {
        fieldErrors[key] = String(value[0])
      } else if (typeof value === 'string') {
        fieldErrors[key] = value
      }
    }

    // If message is generic validation error, provide first error detail
    const firstKey = Object.keys(fieldErrors)[0]
    if (firstKey && (!resData.message || resData.message === 'The given data was invalid.' || resData.message === 'Data yang dikirimkan tidak valid.')) {
      message = fieldErrors[firstKey]
    }
  }

  if (err?.code === 'ERR_NETWORK' || err?.message === 'Network Error' || !err?.response) {
    message = 'Gagal terhubung ke server (Network Error). Pastikan backend Laravel aktif.'
  }

  return { message, fieldErrors }
}
