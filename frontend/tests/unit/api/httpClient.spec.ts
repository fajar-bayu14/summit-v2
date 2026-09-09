import { describe, it, expect, beforeEach, vi } from 'vitest'
import { apiClient, getApiErrorMessage } from '@/lib/axios'
import type { AxiosError, InternalAxiosRequestConfig } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

describe('HTTP Client & Session Interceptor (Task 0.1)', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.restoreAllMocks()
  })

  describe('Request Interceptor', () => {
    it('should inject Authorization Bearer token when auth_token exists in localStorage', async () => {
      localStorage.setItem('auth_token', 'test-jwt-token-12345')

      const config: InternalAxiosRequestConfig = {
        headers: {} as any,
      } as any

      const interceptors = (apiClient.interceptors.request as any).handlers
      const requestHandler = interceptors[0].fulfilled

      const updatedConfig = await requestHandler(config)

      expect(updatedConfig.headers.Authorization).toBe('Bearer test-jwt-token-12345')
    })

    it('should not attach Authorization header when auth_token is absent', async () => {
      const config: InternalAxiosRequestConfig = {
        headers: {} as any,
      } as any

      const interceptors = (apiClient.interceptors.request as any).handlers
      const requestHandler = interceptors[0].fulfilled

      const updatedConfig = await requestHandler(config)

      expect(updatedConfig.headers.Authorization).toBeUndefined()
    })

    it('should remove Content-Type header if data is FormData', async () => {
      const formData = new FormData()
      formData.append('name', 'Gunung Slamet')

      const config: InternalAxiosRequestConfig = {
        headers: {
          'Content-Type': 'application/json',
        } as any,
        data: formData,
      } as any

      const interceptors = (apiClient.interceptors.request as any).handlers
      const requestHandler = interceptors[0].fulfilled

      const updatedConfig = await requestHandler(config)

      expect(updatedConfig.headers['Content-Type']).toBeUndefined()
    })
  })

  describe('Response Interceptor', () => {
    it('should clear session tokens on 401 Unauthorized', async () => {
      localStorage.setItem('auth_token', 'expired-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Mitra' }))
      localStorage.setItem('active_basecamp_id', '10')

      const error: Partial<AxiosError<ApiErrorResponse>> = {
        response: {
          status: 401,
          statusText: 'Unauthorized',
          headers: {},
          config: {} as any,
          data: {
            status: 'error',
            message: 'Unauthenticated.',
          },
        },
      }

      const interceptors = (apiClient.interceptors.response as any).handlers
      const errorHandler = interceptors[0].rejected

      await expect(errorHandler(error)).rejects.toEqual(error)

      expect(localStorage.getItem('auth_token')).toBeNull()
      expect(localStorage.getItem('user_data')).toBeNull()
      expect(localStorage.getItem('active_basecamp_id')).toBeNull()
    })
  })

  describe('getApiErrorMessage helper', () => {
    it('should return API response message if provided by backend', () => {
      const error = {
        response: {
          data: {
            message: 'Email atau password salah.',
          },
        },
      }

      expect(getApiErrorMessage(error)).toBe('Email atau password salah.')
    })

    it('should return 403 forbidden message when status is 403', () => {
      const error = {
        response: {
          status: 403,
          data: {},
        },
      }

      expect(getApiErrorMessage(error)).toContain('403 Forbidden')
    })

    it('should return 404 not found message when status is 404', () => {
      const error = {
        response: {
          status: 404,
          data: {},
        },
      }

      expect(getApiErrorMessage(error)).toContain('404 Not Found')
    })

    it('should return network error message on ERR_NETWORK', () => {
      const error = {
        code: 'ERR_NETWORK',
        message: 'Network Error',
      }

      expect(getApiErrorMessage(error)).toContain('Gagal terhubung ke server backend')
    })

    it('should return custom fallback if error is empty or unhandled', () => {
      expect(getApiErrorMessage(null, 'Terjadi kendala')).toBe('Terjadi kendala')
    })
  })
})
