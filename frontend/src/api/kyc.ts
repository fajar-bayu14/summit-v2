import apiClient from '@/lib/axios'
import { normalizePaginatedResponse } from '@/lib/normalizer'
import type { ApiResponse, NormalizedPaginatedResult } from '@/types/api'
import type { KycFilterParams, KycProfile, VerifyKycPayload } from '@/types/kyc'

export const kycApi = {
  /**
   * Fetch paginated list of KYC climber profiles with optional status filter
   */
  async getKycList(params: KycFilterParams = {}): Promise<NormalizedPaginatedResult<KycProfile>> {
    const queryParams: Record<string, any> = {}
    if (params.status) queryParams.status = params.status
    if (params.page) queryParams.page = params.page
    if (params.per_page) queryParams.per_page = params.per_page
    if (params.search) queryParams.search = params.search

    const response = await apiClient.get<ApiResponse<any>>('/admin/kyc', {
      params: queryParams,
    })

    return normalizePaginatedResponse<KycProfile>(response.data)
  },

  /**
   * Fetch single KYC climber profile detail
   */
  async getKycDetail(id: number): Promise<ApiResponse<KycProfile>> {
    const response = await apiClient.get<ApiResponse<KycProfile>>(`/admin/kyc/${id}`)
    return response.data
  },

  /**
   * Verify KYC (Approve or Reject with reason)
   */
  async verifyKyc(id: number, payload: VerifyKycPayload): Promise<ApiResponse<KycProfile>> {
    const response = await apiClient.post<ApiResponse<KycProfile>>(`/admin/kyc/${id}/verify`, payload)
    return response.data
  },

  /**
   * Fetch identity document file securely as a binary Blob
   */
  async downloadDocumentBlob(id: number): Promise<Blob> {
    const response = await apiClient.get(`/admin/kyc/${id}/download-document`, {
      responseType: 'blob',
    })
    return response.data
  },
}

export default kycApi
