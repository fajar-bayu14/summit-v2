import api from '@/lib/axios'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type { Mitra, CreateMitraPayload, UpdateMitraPayload, MitraFilterParams } from '@/types/partner'
import { normalizePaginatedResponse } from '@/lib/normalizer'

export async function getPartners(params?: MitraFilterParams): Promise<NormalizedPaginatedResult<Mitra>> {
  const response = await api.get<ApiResponse<any>>('/admin/partners', { params })
  return normalizePaginatedResponse<Mitra>(response.data)
}

export async function getPartnerById(id: number): Promise<Mitra> {
  const response = await api.get<ApiResponse<Mitra>>(`/admin/partners/${id}`)
  return response.data.data!
}

export async function createPartner(payload: CreateMitraPayload): Promise<Mitra> {
  const response = await api.post<ApiResponse<Mitra>>('/admin/partners', payload)
  return response.data.data!
}

export async function updatePartner(id: number, payload: UpdateMitraPayload): Promise<Mitra> {
  const response = await api.put<ApiResponse<Mitra>>(`/admin/partners/${id}`, payload)
  return response.data.data!
}

export async function deletePartner(id: number): Promise<void> {
  await api.delete<ApiResponse<null>>(`/admin/partners/${id}`)
}
