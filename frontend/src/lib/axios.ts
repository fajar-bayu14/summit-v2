import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://summit.test/api/v1'

export const apiClient = axios.create({
  baseURL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// Request Interceptor: Attach Bearer Token & Auto Handle FormData Content-Type
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Let the browser set the multipart/form-data header with the correct boundary
    if (config.data instanceof FormData && config.headers) {
      delete config.headers['Content-Type']
    }

    return config
  },
  (error) => Promise.reject(error)
)

// Response Interceptor: Handle Global Errors & 401 Unauthorized
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorResponse>) => {
    if (error.response?.status === 401) {
      // Clear invalid token & session data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('active_basecamp_id')

      // Avoid redirect loops if already on /login
      if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
        const redirect = encodeURIComponent(window.location.pathname + window.location.search)
        window.location.href = `/login?redirect=${redirect}`
      }
    }
    return Promise.reject(error)
  }
)

/**
 * Utility helper to extract user-friendly error messages from API responses
 */
export function getApiErrorMessage(error: unknown, fallback = 'Terjadi kesalahan sistem.'): string {
  if (!error) return fallback
  
  const axiosErr = error as AxiosError<ApiErrorResponse>
  if (axiosErr.response?.data?.message) {
    return axiosErr.response.data.message
  }
  
  if (axiosErr.response?.status === 403) {
    return 'Anda tidak memiliki hak akses untuk melakukan aksi ini (403 Forbidden).'
  }

  if (axiosErr.response?.status === 404) {
    return 'Data atau endpoint yang diminta tidak ditemukan (404 Not Found).'
  }

  if (axiosErr.response?.status === 500) {
    return 'Terjadi gangguan internal pada server (500 Internal Server Error).'
  }

  if (axiosErr.code === 'ERR_NETWORK' || axiosErr.message === 'Network Error') {
    return 'Gagal terhubung ke server backend. Pastikan server Laravel aktif.'
  }

  if (axiosErr.message) {
    return axiosErr.message
  }

  return fallback
}

export default apiClient
