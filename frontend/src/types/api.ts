export interface ApiResponse<T = any> {
  status: 'success' | 'error'
  message: string
  data?: T
  token?: string
  errors?: Record<string, string[]>
}

export interface PaginationLinkItem {
  url: string | null
  label: string
  active: boolean
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  path?: string
  per_page: number
  to: number | null
  total: number
  links?: PaginationLinkItem[]
}

export interface PaginationLinks {
  first?: string | null
  last?: string | null
  prev?: string | null
  next?: string | null
}

export interface LaravelPaginatedData<T> {
  data: T[]
  current_page?: number
  from?: number | null
  last_page?: number
  per_page?: number
  to?: number | null
  total?: number
  meta?: PaginationMeta
  links?: PaginationLinks | PaginationLinkItem[]
}

export interface NormalizedPaginatedResult<T> {
  items: T[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface ApiErrorResponse {
  status: 'error'
  message: string
  error_code?: string
  errors?: Record<string, string[]>
  debug?: string | null
}

export interface ApiQueryParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  [key: string]: any
}

