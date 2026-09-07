import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  LogbookEntry,
  VerifyLogbookPayload,
  LogbookFilterParams,
} from '@/types/logbook'

export interface MitraLogbooksResponse extends ApiResponse<LaravelPaginatedData<LogbookEntry> | LogbookEntry[]> {}

export const mitraLogbooksApi = {
  /**
   * Mengambil daftar submission validasi logbook summit mitra
   */
  async getLogbooks(params?: LogbookFilterParams): Promise<MitraLogbooksResponse> {
    const response = await apiClient.get<MitraLogbooksResponse>('/mitra/logbooks', {
      params,
    })
    return response.data
  },

  /**
   * Memvalidasi bukti summit (Setujui / Tolak)
   */
  async verifyLogbook(
    id: number,
    payload: VerifyLogbookPayload
  ): Promise<ApiResponse<LogbookEntry>> {
    const response = await apiClient.post<ApiResponse<LogbookEntry>>(
      `/mitra/logbooks/${id}/verify`,
      payload
    )
    return response.data
  },
}

export default mitraLogbooksApi
