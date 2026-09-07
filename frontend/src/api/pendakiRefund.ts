import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { RefundItem, StoreRefundPayload, DisputeRefundPayload } from '@/types/pendakiRefund'

export const pendakiRefundApi = {
  async requestRefund(invoice: string, payload: StoreRefundPayload): Promise<ApiResponse<RefundItem>> {
    const response = await apiClient.post<ApiResponse<RefundItem>>(
      `/orders/${encodeURIComponent(invoice)}/refund-request`,
      payload
    )
    return response.data
  },

  async disputeRefund(refundId: number, payload: DisputeRefundPayload): Promise<ApiResponse<RefundItem>> {
    const response = await apiClient.post<ApiResponse<RefundItem>>(
      `/refunds/${refundId}/dispute`,
      payload
    )
    return response.data
  }
}
