import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  RefundItem,
  MitraRejectRefundPayload,
  MitraRefundFilterParams,
} from '@/types/refund'

export interface MitraRefundsResponse extends ApiResponse<LaravelPaginatedData<RefundItem> | RefundItem[]> {}

export const mitraRefundsApi = {
  /**
   * Mengambil daftar permohonan refund mitra dengan filter status
   */
  async getRefunds(params?: MitraRefundFilterParams): Promise<MitraRefundsResponse> {
    const response = await apiClient.get<MitraRefundsResponse>('/mitra/refunds', {
      params,
    })
    return response.data
  },

  /**
   * Mengambil detail pengajuan refund berdasarkan ID
   */
  async getRefundById(id: number): Promise<ApiResponse<RefundItem>> {
    const response = await apiClient.get<ApiResponse<RefundItem>>(`/mitra/refunds/${id}`)
    return response.data
  },

  /**
   * Menyetujui permohonan refund pendaki (Tier-1 Review)
   */
  async approveRefund(id: number): Promise<ApiResponse<RefundItem>> {
    const response = await apiClient.post<ApiResponse<RefundItem>>(
      `/mitra/refunds/${id}/approve`
    )
    return response.data
  },

  /**
   * Menolak permohonan refund pendaki beserta alasan penolakan
   */
  async rejectRefund(
    id: number,
    payload: MitraRejectRefundPayload
  ): Promise<ApiResponse<RefundItem>> {
    const response = await apiClient.post<ApiResponse<RefundItem>>(
      `/mitra/refunds/${id}/reject`,
      payload
    )
    return response.data
  },
}

export default mitraRefundsApi
