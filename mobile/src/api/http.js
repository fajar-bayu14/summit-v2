const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'http://summit.test/api/v1'

export class ApiError extends Error {
  constructor(message, status = 0, errors = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

export async function request(path, options = {}) {
  const { headers, body, ...rest } = options
  const token = getToken()
  const authHeaders = token ? { Authorization: `Bearer ${token}` } : {}

  const isFormData = typeof FormData !== 'undefined' && body instanceof FormData

  const defaultHeaders = isFormData
    ? { Accept: 'application/json' }
    : { 'Content-Type': 'application/json', Accept: 'application/json' }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...rest,
    headers: {
      ...defaultHeaders,
      ...authHeaders,
      ...headers
    },
    body: isFormData
      ? body
      : body === undefined
        ? undefined
        : JSON.stringify(body)
  })

  const payload = await response.json().catch(() => ({}))
  const message = extractMessage(payload, response.status)

  if (!response.ok) {
    throw new ApiError(message, response.status, payload.errors)
  }

  return payload
}

function extractMessage(payload, status) {
  if (payload.message) return payload.message

  const { errors } = payload
  if (errors) {
    const values = Array.isArray(errors) ? errors : Object.values(errors)
    const first = values[0]
    if (first) return Array.isArray(first) ? first[0] : first
  }

  return `Request failed (${status})`
}

export function getToken() {
  return localStorage.getItem('summit_token') || ''
}

export function setToken(token) {
  if (token) localStorage.setItem('summit_token', token)
}

export function clearToken() {
  localStorage.removeItem('summit_token')
}
