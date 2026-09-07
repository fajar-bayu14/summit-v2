import { apiClient } from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  KuotaHarian,
  BatchQuotaPayload,
  UpdateSingleQuotaPayload,
  QuotaFilterParams,
} from '@/types/quota'

export const mitraQuotasApi = {
  /**
   * Mengambil daftar kuota harian untuk produk tiket tertentu dalam rentang tanggal
   */
  async getProductQuotas(
    productId: number,
    params?: QuotaFilterParams
  ): Promise<ApiResponse<KuotaHarian[]>> {
    const response = await apiClient.get<ApiResponse<KuotaHarian[]>>(
      `/mitra/products/${productId}/quotas`,
      { params }
    )
    return response.data
  },

  /**
   * Mengatur kuota tiket secara massal untuk rentang tanggal tertentu
   */
  async batchSetQuotas(
    productId: number,
    payload: BatchQuotaPayload
  ): Promise<ApiResponse<null>> {
    const response = await apiClient.post<ApiResponse<null>>(
      `/mitra/products/${productId}/quotas/batch`,
      payload
    )
    return response.data
  },

  /**
   * Mengubah jumlah kuota total pada satu tanggal spesifik
   */
  async updateSingleQuota(
    quotaId: number,
    payload: UpdateSingleQuotaPayload
  ): Promise<ApiResponse<KuotaHarian>> {
    const response = await apiClient.put<ApiResponse<KuotaHarian>>(
      `/mitra/quotas/${quotaId}`,
      payload
    )
    return response.data
  },
}

export default mitraQuotasApi
