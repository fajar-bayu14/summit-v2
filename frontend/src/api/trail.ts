import apiClient from '@/lib/axios'
import { normalizePaginatedResponse } from '@/lib/normalizer'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type {
  CreateJalurPayload,
  JalurPendakian,
  TrailFilterParams,
  TrailStatus,
  UpdateJalurPayload,
} from '@/types/mountain'

export const trailApi = {
  /**
   * Fetch paginated list of trails with server-side filters
   */
  async getTrails(params: TrailFilterParams = {}): Promise<NormalizedPaginatedResult<JalurPendakian>> {
    const queryParams: Record<string, any> = {}
    if (params.gunung_id) queryParams.gunung_id = params.gunung_id
    if (params.status) queryParams.status = params.status
    if (params.tingkat_kesulitan) queryParams.tingkat_kesulitan = params.tingkat_kesulitan
    if (params.search) queryParams.search = params.search
    if (params.page) queryParams.page = params.page
    if (params.per_page) queryParams.per_page = params.per_page

    const response = await apiClient.get<ApiResponse<any>>('/admin/trails', {
      params: queryParams,
    })

    return normalizePaginatedResponse<JalurPendakian>(response.data)
  },

  /**
   * Fetch single trail details
   */
  async getTrailDetail(id: number): Promise<ApiResponse<JalurPendakian>> {
    const response = await apiClient.get<ApiResponse<JalurPendakian>>(`/admin/trails/${id}`)
    return response.data
  },

  /**
   * Create new trail
   */
  async createTrail(payload: CreateJalurPayload): Promise<ApiResponse<JalurPendakian>> {
    const response = await apiClient.post<ApiResponse<JalurPendakian>>('/admin/trails', payload)
    return response.data
  },

  /**
   * Update existing trail
   */
  async updateTrail(id: number, payload: UpdateJalurPayload): Promise<ApiResponse<JalurPendakian>> {
    const response = await apiClient.put<ApiResponse<JalurPendakian>>(`/admin/trails/${id}`, payload)
    return response.data
  },

  /**
   * Quick toggle trail open/close status
   */
  async updateTrailStatus(id: number, status: TrailStatus): Promise<ApiResponse<JalurPendakian>> {
    const response = await apiClient.patch<ApiResponse<JalurPendakian>>(`/admin/trails/${id}/status`, {
      status,
    })
    return response.data
  },

  /**
   * Delete trail
   */
  async deleteTrail(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>(`/admin/trails/${id}`)
    return response.data
  },
}

export default trailApi
