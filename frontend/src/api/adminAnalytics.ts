import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { AdminAnalyticsSummary } from '@/types/dashboard'

export const adminAnalyticsApi = {
  /**
   * Fetch aggregate operational and action queues summary for admin
   */
  async getSummary(): Promise<ApiResponse<AdminAnalyticsSummary>> {
    const response = await apiClient.get<ApiResponse<AdminAnalyticsSummary>>('/admin/analytics/summary')
    return response.data
  },
}

export default adminAnalyticsApi
