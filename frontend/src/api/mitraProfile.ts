import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { User } from '@/types/auth'
import type { MitraProfile, UpdateMitraProfilePayload, ChangePasswordPayload } from '@/types/profile'

export const mitraProfileApi = {
  /**
   * Fetch authenticated user profile with attached mitra/basecamp info
   */
  async getProfile(): Promise<ApiResponse<User>> {
    const response = await apiClient.get<ApiResponse<User>>('/profile')
    return response.data
  },

  /**
   * Update partner (Mitra) profile details and bank accounts
   */
  async updateProfile(payload: UpdateMitraProfilePayload): Promise<ApiResponse<MitraProfile>> {
    const response = await apiClient.put<ApiResponse<MitraProfile>>('/mitra/profile', payload)
    return response.data
  },

  /**
   * Change user account password
   */
  async updatePassword(payload: ChangePasswordPayload): Promise<ApiResponse<null>> {
    const response = await apiClient.put<ApiResponse<null>>('/profile/password', payload)
    return response.data
  },
}

export default mitraProfileApi
