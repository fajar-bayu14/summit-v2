import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  CheckoutPayload,
  CheckoutResponse,
  OrderDetailResponse,
  OrderListResponse,
  Pesanan,
} from '@/types/pendakiCheckout'

export const pendakiCheckoutApi = {
  /**
   * Checkout the active cart into a pending order and generate Xendit invoice
   */
  async checkout(payload: CheckoutPayload): Promise<CheckoutResponse> {
    const response = await apiClient.post<CheckoutResponse>('/orders/checkout', payload)
    return response.data
  },

  /**
   * Get single booking order details by invoice number
   */
  async getOrderDetail(invoice: string): Promise<OrderDetailResponse> {
    const response = await apiClient.get<OrderDetailResponse>(`/orders/${encodeURIComponent(invoice)}`)
    return response.data
  },

  /**
   * Cancel a pending order
   */
  async cancelOrder(invoice: string): Promise<ApiResponse<Pesanan>> {
    const response = await apiClient.post<ApiResponse<Pesanan>>(
      `/orders/${encodeURIComponent(invoice)}/cancel`
    )
    return response.data
  },

  /**
   * List climber's booking orders (paginated)
   */
  async listOrders(params?: { status?: string; page?: number }): Promise<OrderListResponse> {
    const response = await apiClient.get<OrderListResponse>('/orders', { params })
    return response.data
  },
}

export const checkoutOrder = pendakiCheckoutApi.checkout
export const getOrderDetail = pendakiCheckoutApi.getOrderDetail
export const cancelOrder = pendakiCheckoutApi.cancelOrder
export const listOrders = pendakiCheckoutApi.listOrders
