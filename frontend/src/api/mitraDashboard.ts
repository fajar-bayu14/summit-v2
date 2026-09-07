import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { MitraAnalyticsSummary, EmergencyTrailPayload } from '@/types/dashboard'
import type { BasecampJalur } from '@/types/basecamp'

export const mitraDashboardApi = {
  /**
   * Fetch aggregate analytics metrics for mitra
   */
  async getSummary(basecampId?: number | null): Promise<ApiResponse<MitraAnalyticsSummary>> {
    const params: Record<string, any> = {}
    if (basecampId) {
      params.basecamp_id = basecampId
    }
    const response = await apiClient.get<ApiResponse<MitraAnalyticsSummary>>('/mitra/analytics/summary', { params })
    return response.data
  },

  /**
   * Perform quick emergency closure or reopening of a trail
   */
  async emergencyCloseTrail(trailId: number, payload: EmergencyTrailPayload): Promise<ApiResponse<BasecampJalur>> {
    const response = await apiClient.post<ApiResponse<BasecampJalur>>(`/mitra/trails/${trailId}/emergency-close`, payload)
    return response.data
  },

  /**
   * Fetch today's arrivals / pending check-in orders
   */
  async getTodayOrders(basecampId?: number | null): Promise<ApiResponse<any>> {
    const params: Record<string, any> = {
      per_page: 10,
    }
    if (basecampId) {
      params.basecamp_id = basecampId
    }
    const response = await apiClient.get<ApiResponse<any>>('/mitra/orders', { params })
    return response.data
  },
}

export default mitraDashboardApi
