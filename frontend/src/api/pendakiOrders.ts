import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  OrderFilterParams,
  OrderListResponse,
  OrderDetailResponse,
  PesananDetail,
} from '@/types/pendakiOrder'

export const pendakiOrdersApi = {
  /**
   * Fetch climber's booking orders list (paginated, filterable by status)
   */
  async getMyOrders(params?: OrderFilterParams): Promise<OrderListResponse> {
    const response = await apiClient.get<OrderListResponse>('/orders', { params })
    return response.data
  },

  /**
   * Get single order detail with manifest, tickets, and payment by invoice
   */
  async getOrderByInvoice(invoice: string): Promise<OrderDetailResponse> {
    const response = await apiClient.get<OrderDetailResponse>(`/orders/${encodeURIComponent(invoice)}`)
    return response.data
  },

  /**
   * Cancel a pending booking order
   */
  async cancelOrder(invoice: string): Promise<ApiResponse<PesananDetail>> {
    const response = await apiClient.post<ApiResponse<PesananDetail>>(
      `/orders/${encodeURIComponent(invoice)}/cancel`
    )
    return response.data
  },
}

export const getMyOrders = pendakiOrdersApi.getMyOrders
export const getOrderByInvoice = pendakiOrdersApi.getOrderByInvoice
export const cancelOrder = pendakiOrdersApi.cancelOrder
