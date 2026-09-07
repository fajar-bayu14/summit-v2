import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  Cart,
  CreateCartPayload,
  AddCartItemPayload,
  UpdateCartItemPayload,
} from '@/types/pendakiCart'

export const pendakiCartApi = {
  /**
   * Fetch active shopping cart for authenticated climber
   */
  async getCart(): Promise<ApiResponse<Cart | null>> {
    const response = await apiClient.get<ApiResponse<Cart | null>>('/cart')
    return response.data
  },

  /**
   * Initialize or update active cart context (basecamp, jalur, dates)
   */
  async createCart(payload: CreateCartPayload): Promise<ApiResponse<Cart>> {
    const response = await apiClient.post<ApiResponse<Cart>>('/cart', payload)
    return response.data
  },

  /**
   * Add a product line item to the active cart
   */
  async addItem(payload: AddCartItemPayload): Promise<ApiResponse<Cart>> {
    const response = await apiClient.post<ApiResponse<Cart>>('/cart/items', payload)
    return response.data
  },

  /**
   * Update quantity or rental period of a specific cart item
   */
  async updateItem(itemId: number, payload: UpdateCartItemPayload): Promise<ApiResponse<Cart>> {
    const response = await apiClient.patch<ApiResponse<Cart>>(`/cart/items/${itemId}`, payload)
    return response.data
  },

  /**
   * Remove a single item from the cart
   */
  async removeItem(itemId: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>(`/cart/items/${itemId}`)
    return response.data
  },

  /**
   * Clear all items from the active cart
   */
  async clearCart(): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>('/cart')
    return response.data
  },
}
