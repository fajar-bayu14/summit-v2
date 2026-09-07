import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  Produk,
  ProdukFilterParams,
  StoreProductPayload,
  UpdateProductPayload,
} from '@/types/product'

export const mitraProductsApi = {
  /**
   * Mengambil daftar produk mitra dengan pagination & filter kategori/pencarian
   */
  async getProducts(
    params?: ProdukFilterParams
  ): Promise<ApiResponse<LaravelPaginatedData<Produk> | Produk[]>> {
    const response = await apiClient.get<
      ApiResponse<LaravelPaginatedData<Produk> | Produk[]>
    >('/mitra/products', { params })
    return response.data
  },

  /**
   * Mengambil detail produk berdasarkan ID
   */
  async getProduct(id: number): Promise<ApiResponse<Produk>> {
    const response = await apiClient.get<ApiResponse<Produk>>(`/mitra/products/${id}`)
    return response.data
  },

  /**
   * Membuat produk baru (Ticket, Open Trip, Rental, Guide, Porter, dll.)
   */
  async createProduct(
    payload: StoreProductPayload
  ): Promise<ApiResponse<Produk>> {
    const response = await apiClient.post<ApiResponse<Produk>>(
      '/mitra/products',
      payload
    )
    return response.data
  },

  /**
   * Mengubah data produk yang sudah ada
   */
  async updateProduct(
    id: number,
    payload: UpdateProductPayload
  ): Promise<ApiResponse<Produk>> {
    const response = await apiClient.put<ApiResponse<Produk>>(
      `/mitra/products/${id}`,
      payload
    )
    return response.data
  },

  /**
   * Menghapus produk dari katalog mitra
   */
  async deleteProduct(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>(
      `/mitra/products/${id}`
    )
    return response.data
  },

  /**
   * Mengubah status aktif / non-aktif produk secara cepat (Toggle switch)
   */
  async toggleProductStatus(
    id: number,
    isActive: boolean
  ): Promise<ApiResponse<Produk>> {
    const response = await apiClient.patch<ApiResponse<Produk>>(
      `/mitra/products/${id}/toggle-status`,
      { is_active: isActive }
    )
    return response.data
  },

  /**
   * Penyesuaian stok fisik produk rental / merchandise
   */
  async updateProductStock(
    id: number,
    stock: number
  ): Promise<ApiResponse<Produk>> {
    const response = await apiClient.patch<ApiResponse<Produk>>(
      `/mitra/products/${id}/stock`,
      { stok: stock }
    )
    return response.data
  },
}

export default mitraProductsApi
