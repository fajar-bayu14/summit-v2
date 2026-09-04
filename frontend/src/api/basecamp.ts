import api from '@/lib/axios'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type { Basecamp, CreateBasecampPayload, UpdateBasecampPayload, BasecampFilterParams } from '@/types/basecamp'
import { normalizePaginatedResponse } from '@/lib/normalizer'

export async function getBasecamps(params?: BasecampFilterParams): Promise<NormalizedPaginatedResult<Basecamp>> {
  const response = await api.get<ApiResponse<any>>('/admin/basecamps', { params })
  return normalizePaginatedResponse<Basecamp>(response.data)
}

export async function getBasecampById(id: number): Promise<Basecamp> {
  const response = await api.get<ApiResponse<Basecamp>>(`/admin/basecamps/${id}`)
  return response.data.data!
}

export async function createBasecamp(payload: CreateBasecampPayload): Promise<Basecamp> {
  const response = await api.post<ApiResponse<Basecamp>>('/admin/basecamps', payload)
  return response.data.data!
}

export async function updateBasecamp(id: number, payload: UpdateBasecampPayload): Promise<Basecamp> {
  const response = await api.put<ApiResponse<Basecamp>>(`/admin/basecamps/${id}`, payload)
  return response.data.data!
}

export async function deleteBasecamp(id: number): Promise<void> {
  await api.delete<ApiResponse<null>>(`/admin/basecamps/${id}`)
}
