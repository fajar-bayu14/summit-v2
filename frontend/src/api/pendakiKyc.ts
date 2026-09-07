import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { User } from '@/types/auth'
import type { PendakiProfile, ChangePasswordPayload } from '@/types/pendakiKyc'

export const pendakiKycApi = {
  /**
   * Get currently authenticated user profile
   */
  async getProfile(): Promise<ApiResponse<User>> {
    const response = await apiClient.get<ApiResponse<User>>('/profile')
    return response.data
  },

  /**
   * Get climber KYC status & profile details
   */
  async getKycStatus(): Promise<ApiResponse<PendakiProfile | null>> {
    const response = await apiClient.get<ApiResponse<PendakiProfile | null>>('/kyc/status')
    return response.data
  },

  /**
   * Submit climber KYC identity document & information
   */
  async submitKyc(formData: FormData): Promise<ApiResponse<PendakiProfile>> {
    const response = await apiClient.post<ApiResponse<PendakiProfile>>('/kyc/submit', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },

  /**
   * Update password for authenticated climber
   */
  async updatePassword(payload: ChangePasswordPayload): Promise<ApiResponse<null>> {
    const response = await apiClient.put<ApiResponse<null>>('/profile/password', payload)
    return response.data
  },
}
