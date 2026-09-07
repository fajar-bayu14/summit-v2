import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { BannerAdItem, BannerPosition } from '@/types/bannerAd'

export const publicAdsApi = {
  async getBanners(posisi?: BannerPosition): Promise<ApiResponse<BannerAdItem[]>> {
    const response = await apiClient.get<ApiResponse<BannerAdItem[]>>('/ads/banners', {
      params: posisi ? { posisi } : {}
    })
    return response.data
  },

  async recordClick(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.post<ApiResponse<null>>(`/ads/banners/${id}/click`)
    return response.data
  }
}
