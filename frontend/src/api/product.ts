import api from '@/lib/axios'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type { Product, ProductFilterParams } from '@/types/product'
import { normalizePaginatedResponse } from '@/lib/normalizer'

export async function getProducts(params?: ProductFilterParams): Promise<NormalizedPaginatedResult<Product>> {
  const response = await api.get<ApiResponse<any>>('/admin/products', { params })
  return normalizePaginatedResponse<Product>(response.data)
}

export async function getProductById(id: number): Promise<Product> {
  const response = await api.get<ApiResponse<Product>>(`/admin/products/${id}`)
  return response.data.data!
}
