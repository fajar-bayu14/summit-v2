import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type { LogbookEntry, BadgeItem } from '@/types/pendakiLogbook'

export const pendakiLogbookApi = {
  /**
   * Upload summit proof photo and submit digital logbook for an order
   */
  async submitLogbook(invoice: string, formData: FormData): Promise<ApiResponse<LogbookEntry>> {
    const response = await apiClient.post<ApiResponse<LogbookEntry>>(
      `/orders/${encodeURIComponent(invoice)}/logbook`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )
    return response.data
  },

  /**
   * Get digital logbook details by invoice
   */
  async getLogbook(invoice: string): Promise<ApiResponse<LogbookEntry>> {
    const response = await apiClient.get<ApiResponse<LogbookEntry>>(
      `/orders/${encodeURIComponent(invoice)}/logbook`
    )
    return response.data
  },

  /**
   * Get list of climbing achievement badges for the climber
   */
  async getBadges(): Promise<ApiResponse<BadgeItem[]>> {
    const response = await apiClient.get<ApiResponse<BadgeItem[]>>('/pendaki/badges')
    return response.data
  },

  /**
   * Download or stream climbing certificate
   */
  async downloadCertificate(invoice: string): Promise<Blob> {
    const response = await apiClient.get(`/orders/${encodeURIComponent(invoice)}/certificate`, {
      responseType: 'blob',
    })
    return response.data
  },
}

export const submitLogbook = pendakiLogbookApi.submitLogbook
export const getLogbook = pendakiLogbookApi.getLogbook
export const getBadges = pendakiLogbookApi.getBadges
export const downloadCertificate = pendakiLogbookApi.downloadCertificate
