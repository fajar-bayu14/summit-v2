import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { BasecampMitraSummary } from '@/types/pendakiMountain'

export const pendakiBasecampsApi = {
  /**
   * Fetch single basecamp detail by ID (including mitra, jalur, and mountain info)
   */
  async getBasecampById(id: number | string): Promise<ApiResponse<BasecampMitraSummary>> {
    const response = await apiClient.get<ApiResponse<BasecampMitraSummary>>(`/basecamps/${id}`)
    return response.data
  },
}
