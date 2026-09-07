import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  MitraStaff,
  StoreStaffPayload,
  UpdateStaffPayload,
  StaffFilterParams,
} from '@/types/staff'

export interface MitraStaffListResponse
  extends ApiResponse<LaravelPaginatedData<MitraStaff> | MitraStaff[]> {}

export const mitraStaffApi = {
  /**
   * Mengambil daftar staf (guide/porter/petugas) milik mitra
   */
  async getStaffList(params?: StaffFilterParams): Promise<MitraStaffListResponse> {
    const response = await apiClient.get<MitraStaffListResponse>('/mitra/staff', {
      params,
    })
    return response.data
  },

  /**
   * Mengambil detail data staf berdasarkan ID
   */
  async getStaffById(id: number): Promise<ApiResponse<MitraStaff>> {
    const response = await apiClient.get<ApiResponse<MitraStaff>>(`/mitra/staff/${id}`)
    return response.data
  },

  /**
   * Mendaftarkan staf baru
   */
  async createStaff(payload: StoreStaffPayload): Promise<ApiResponse<MitraStaff>> {
    const response = await apiClient.post<ApiResponse<MitraStaff>>('/mitra/staff', payload)
    return response.data
  },

  /**
   * Memperbarui informasi data staf
   */
  async updateStaff(
    id: number,
    payload: UpdateStaffPayload
  ): Promise<ApiResponse<MitraStaff>> {
    const response = await apiClient.put<ApiResponse<MitraStaff>>(`/mitra/staff/${id}`, payload)
    return response.data
  },

  /**
   * Menghapus staf dari daftar basecamp
   */
  async deleteStaff(id: number): Promise<ApiResponse<null>> {
    const response = await apiClient.delete<ApiResponse<null>>(`/mitra/staff/${id}`)
    return response.data
  },

  /**
   * Mengubah status ketersediaan staf secara cepat (Tersedia / Bertugas)
   */
  async toggleStaffAvailability(
    id: number,
    isAvailable: boolean
  ): Promise<ApiResponse<MitraStaff>> {
    const response = await apiClient.put<ApiResponse<MitraStaff>>(`/mitra/staff/${id}`, {
      is_available: isAvailable,
    })
    return response.data
  },
}

export default mitraStaffApi
