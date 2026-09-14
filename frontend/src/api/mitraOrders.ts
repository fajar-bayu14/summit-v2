import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  MitraPesanan,
  PesananFilterParams,
  ItemOperationalStatus,
  MitraOrderMeta,
} from '@/types/order'

export interface MitraOrdersResponse extends ApiResponse<LaravelPaginatedData<MitraPesanan> | MitraPesanan[]> {
  meta?: MitraOrderMeta & Record<string, any>
}

export const mitraOrdersApi = {
  /**
   * Mengambil daftar pesanan masuk mitra dengan filter status, tanggal, dan pencarian invoice/nama
   */
  async getOrders(params?: PesananFilterParams): Promise<MitraOrdersResponse> {
    const response = await apiClient.get<MitraOrdersResponse>('/mitra/orders', {
      params,
    })
    return response.data
  },

  /**
   * Mengambil detail lengkap pesanan pendaki termasuk manifes anggota & daftar item
   */
  async getOrderById(id: number): Promise<ApiResponse<MitraPesanan>> {
    const response = await apiClient.get<ApiResponse<MitraPesanan>>(
      `/mitra/orders/${id}`
    )
    return response.data
  },

  /**
   * Memperbarui status operasional satu item tertentu (alat sewa, porter, tiket)
   */
  async updateItemStatus(
    pesananId: number,
    itemId: number,
    status: ItemOperationalStatus
  ): Promise<ApiResponse<null>> {
    const response = await apiClient.patch<ApiResponse<null>>(
      `/mitra/orders/${pesananId}/items/${itemId}`,
      { status_operasional: status }
    )
    return response.data
  },

  /**
   * Eksekusi check-in rombongan pendaki berdasarkan ID pesanan
   */
  async checkInOrder(id: number): Promise<ApiResponse<MitraPesanan>> {
    const response = await apiClient.post<ApiResponse<MitraPesanan>>(
      `/mitra/orders/${id}/check-in`
    )
    return response.data
  },

  /**
   * Eksekusi check-in rombongan pendaki berdasarkan Nomor Invoice atau Scan QR Code
   */
  async checkInByInvoice(invoice: string): Promise<ApiResponse<MitraPesanan>> {
    const response = await apiClient.post<ApiResponse<MitraPesanan>>(
      '/mitra/orders/check-in',
      { invoice }
    )
    return response.data
  },

  /**
   * Mengunduh / mengambil berkas stream foto KTP pendaki untuk verifikasi pos basecamp
   */
  async downloadClimberKtp(id: number): Promise<Blob> {
    const response = await apiClient.get<Blob>(`/mitra/orders/${id}/ktp`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Eksekusi check-out & penyelesaian pendakian rombongan pendaki (rilis saldo escrow ke saldo aktif)
   */
  async checkOutOrder(id: number): Promise<ApiResponse<MitraPesanan>> {
    const response = await apiClient.post<ApiResponse<MitraPesanan>>(
      `/mitra/orders/${id}/check-out`
    )
    return response.data
  },
}

export default mitraOrdersApi
