import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { LoginCredentials, User } from '@/types/auth'

export const authApi = {
  /**
   * Submit login credentials to backend Laravel
   */
  async login(credentials: LoginCredentials): Promise<ApiResponse<User>> {
    const response = await apiClient.post<ApiResponse<User>>('/auth/login', {
      email: credentials.email,
      password: credentials.password,
    })
    return response.data
  },

  /**
   * Fetch current authenticated user profile
   */
  async getProfile(): Promise<ApiResponse<User>> {
    const response = await apiClient.get<ApiResponse<User>>('/profile')
    return response.data
  },

  /**
   * Logout user and revoke token
   */
  async logout(): Promise<ApiResponse<null>> {
    const response = await apiClient.post<ApiResponse<null>>('/auth/logout')
    return response.data
  },
}

export default authApi
