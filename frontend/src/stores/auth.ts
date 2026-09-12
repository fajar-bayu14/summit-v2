import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import { pendakiKycApi } from '@/api/pendakiKyc'
import { useMitraStore } from './mitra'
import type { LoginCredentials, User, UserRole } from '@/types/auth'
import type { AxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

function getInitialUser(): User | null {
  try {
    const raw = localStorage.getItem('user_data')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<User | null>(getInitialUser())
  const isLoading = ref<boolean>(false)
  const error = ref<string | null>(null)
  const validationErrors = ref<Record<string, string[]> | null>(null)

  const isAuthenticated = computed<boolean>(() => !!token.value)
  const userRole = computed<UserRole | null>(() => user.value?.role || null)
  const isAdmin = computed<boolean>(() => user.value?.role === 'admin')
  const isMitra = computed<boolean>(() => user.value?.role === 'mitra')
  const isPendaki = computed<boolean>(() => user.value?.role === 'pendaki')

  // KYC Getters for Climbers
  const kycStatus = computed<string>(() => {
    const raw = user.value?.pendaki?.status_verifikasi
    if (!raw) return 'unverified'
    if (raw === 'disetujui' || raw === 'verified') return 'verified'
    if (raw === 'ditolak' || raw === 'rejected') return 'rejected'
    if (raw === 'pending') return 'pending'
    return raw
  })
  const isKycVerified = computed<boolean>(() => kycStatus.value === 'verified')
  const isKycPending = computed<boolean>(() => kycStatus.value === 'pending')
  const isKycRejected = computed<boolean>(() => kycStatus.value === 'rejected')
  const pendakiProfile = computed(() => user.value?.pendaki || null)

  function setUserData(userData: User) {
    user.value = userData
    localStorage.setItem('user_data', JSON.stringify(userData))
    syncMitraContext(userData)
  }

  function updatePendakiProfile(pendakiData: any) {
    if (user.value) {
      user.value = {
        ...user.value,
        pendaki: pendakiData,
      }
      localStorage.setItem('user_data', JSON.stringify(user.value))
    }
  }

  function syncMitraContext(userData: User) {
    if (userData.role === 'mitra') {
      const mitraStore = useMitraStore()
      if (userData.mitra?.basecamps && userData.mitra.basecamps.length > 0) {
        mitraStore.setBasecamps(userData.mitra.basecamps)
      } else {
        mitraStore.fetchBasecamps()
      }
    }
  }

  function clearErrors() {
    error.value = null
    validationErrors.value = null
  }

  async function login(credentials: LoginCredentials): Promise<User> {
    isLoading.value = true
    clearErrors()

    try {
      const response = await authApi.login(credentials)
      
      if (response.token && response.data) {
        token.value = response.token
        user.value = response.data

        localStorage.setItem('auth_token', response.token)
        localStorage.setItem('user_data', JSON.stringify(response.data))
        
        syncMitraContext(response.data)
        
        if (response.data.role === 'pendaki') {
          fetchKycStatus()
        }

        return response.data
      }
      throw new Error(response.message || 'Login failed')
    } catch (err) {
      const axiosErr = err as AxiosError<ApiErrorResponse>
      if (axiosErr.response?.data) {
        const resData = axiosErr.response.data
        error.value = resData.message || 'Gagal masuk. Silakan coba lagi.'
        if (resData.errors) {
          validationErrors.value = resData.errors
        }
      } else if (axiosErr.code === 'ERR_NETWORK' || axiosErr.message === 'Network Error' || !axiosErr.response) {
        error.value = 'Gagal terhubung ke server (Network Error). Pastikan backend aktif di http://127.0.0.1:8000.'
      } else if (axiosErr.message) {
        error.value = axiosErr.message
      } else {
        error.value = 'Terjadi kesalahan koneksi server.'
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchProfile(): Promise<User | null> {
    if (!token.value) return null
    try {
      const response = await authApi.getProfile()
      if (response.data) {
        user.value = response.data
        localStorage.setItem('user_data', JSON.stringify(response.data))
        syncMitraContext(response.data)
        return response.data
      }
      return null
    } catch {
      return null
    }
  }

  async function fetchKycStatus() {
    if (!token.value) return null
    try {
      const response = await pendakiKycApi.getKycStatus()
      if (response.data) {
        updatePendakiProfile(response.data)
        return response.data
      }
      return null
    } catch {
      return null
    }
  }

  async function logout(): Promise<void> {
    isLoading.value = true
    try {
      if (token.value) {
        await authApi.logout()
      }
    } catch {
      // Ignore network errors on logout
    } finally {
      token.value = null
      user.value = null
      clearErrors()
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      
      const mitraStore = useMitraStore()
      mitraStore.reset()
      
      isLoading.value = false
    }
  }

  return {
    token,
    user,
    isLoading,
    error,
    validationErrors,
    isAuthenticated,
    userRole,
    isAdmin,
    isMitra,
    isPendaki,
    kycStatus,
    isKycVerified,
    isKycPending,
    isKycRejected,
    pendakiProfile,
    setUserData,
    updatePendakiProfile,
    clearErrors,
    login,
    fetchProfile,
    fetchKycStatus,
    logout,
  }
})
