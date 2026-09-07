import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  GunungItem,
  MountainFilterParams,
} from '@/types/pendakiMountain'

export const pendakiMountainsApi = {
  /**
   * Fetch list of mountains with trails and basecamps
   */
  async getMountains(params?: MountainFilterParams): Promise<ApiResponse<GunungItem[]>> {
    const response = await apiClient.get<ApiResponse<GunungItem[]>>('/mountains', {
      params,
    })
    return response.data
  },

  /**
   * Fetch single mountain detail with full trails and basecamp information
   */
  async getMountainById(id: number | string): Promise<ApiResponse<GunungItem>> {
    const response = await apiClient.get<ApiResponse<GunungItem>>(`/mountains/${id}`)
    return response.data
  },
}
