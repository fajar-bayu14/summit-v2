import apiClient from '@/lib/axios'
import { normalizePaginatedResponse } from '@/lib/normalizer'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type { CreateGunungPayload, Gunung, UpdateGunungPayload } from '@/types/mountain'

export const mountainApi = {
  /**
   * Fetch all mountains (with associated trails and pagination normalizer)
   */
  async getMountains(params: { page?: number; per_page?: number } = {}): Promise<NormalizedPaginatedResult<Gunung>> {
    const response = await apiClient.get<ApiResponse<any>>('/mountains', { params })
    return normalizePaginatedResponse<Gunung>(response.data)
  },

  /**
   * Fetch raw list of all mountains (useful for dropdowns)
   */
  async getAllMountains(): Promise<Gunung[]> {
    const response = await apiClient.get<ApiResponse<any>>('/mountains', { params: { per_page: 100 } })
    const normalized = normalizePaginatedResponse<Gunung>(response.data)
    return normalized.items
  },

  /**
   * Fetch single mountain details
   */
  async getMountainDetail(id: number): Promise<ApiResponse<Gunung>> {
    const response = await apiClient.get<ApiResponse<Gunung>>(`/mountains/${id}`)
    return response.data
  },

  /**
   * Create new mountain with optional image file (multipart/form-data)
   */
  async createMountain(payload: CreateGunungPayload): Promise<ApiResponse<Gunung>> {
    const formData = new FormData()
    formData.append('nama_gunung', payload.nama_gunung)
    formData.append('deskripsi', payload.deskripsi)
    formData.append('tinggi_mdpl', String(payload.tinggi_mdpl))
    formData.append('lokasi', payload.lokasi)
    formData.append('status', payload.status)

    if (payload.foto instanceof File) {
      formData.append('foto', payload.foto)
    }

    const response = await apiClient.post<ApiResponse<Gunung>>('/admin/mountains', formData)
    return response.data
  },

  /**
   * Update existing mountain (Uses POST with _method=PUT for multipart/form-data file uploads)
   */
  async updateMountain(id: number, payload: UpdateGunungPayload): Promise<ApiResponse<Gunung>> {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    formData.append('nama_gunung', payload.nama_gunung)
    formData.append('deskripsi', payload.deskripsi)
    formData.append('tinggi_mdpl', String(payload.tinggi_mdpl))
    formData.append('lokasi', payload.lokasi)
    formData.append('status', payload.status)

    if (payload.foto instanceof File) {
      formData.append('foto', payload.foto)
    }

    const response = await apiClient.post<ApiResponse<Gunung>>(`/admin/mountains/${id}`, formData)
    return response.data
  },

  /**
   * Delete mountain
   */
  async deleteMountain(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>(`/admin/mountains/${id}`)
    return response.data
  },
}

export default mountainApi
