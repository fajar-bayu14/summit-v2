import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  ProdukCatalogItem,
  ProductFilterParams,
} from '@/types/pendakiProduct'

export const pendakiProductsApi = {
  /**
   * Fetch active products list (can filter by basecamp_id & kategori)
   */
  async getProducts(params?: ProductFilterParams): Promise<ApiResponse<ProdukCatalogItem[]>> {
    const response = await apiClient.get<ApiResponse<ProdukCatalogItem[]>>('/products', {
      params,
    })
    return response.data
  },

  /**
   * Fetch detailed product by ID (including ticket quotas / opentrip schedules)
   */
  async getProductById(id: number | string): Promise<ApiResponse<ProdukCatalogItem>> {
    const response = await apiClient.get<ApiResponse<ProdukCatalogItem>>(`/products/${id}`)
    return response.data
  },
}
