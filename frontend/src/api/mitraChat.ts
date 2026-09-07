import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  ChatRoom,
  ChatMessage,
  SendMessagePayload,
  ChatFilterParams,
} from '@/types/chat'

export interface ChatRoomsResponse
  extends ApiResponse<LaravelPaginatedData<ChatRoom> | ChatRoom[]> {}

export interface ChatMessagesResponse
  extends ApiResponse<LaravelPaginatedData<ChatMessage> | ChatMessage[]> {}

export const mitraChatApi = {
  /**
   * Mengambil daftar ruang obrolan (chat rooms) aktif mitra
   */
  async getRooms(params?: ChatFilterParams): Promise<ChatRoomsResponse> {
    const response = await apiClient.get<ChatRoomsResponse>('/chat/rooms', {
      params,
    })
    return response.data
  },

  /**
   * Membuka atau membuat ruang obrolan baru untuk pesanan tertentu
   */
  async createOrGetRoom(pesananId: number): Promise<ApiResponse<ChatRoom>> {
    const response = await apiClient.post<ApiResponse<ChatRoom>>('/chat/rooms', {
      pesanan_id: pesananId,
    })
    return response.data
  },

  /**
   * Mengambil riwayat pesan dalam satu ruang obrolan
   */
  async getMessages(roomId: number, page = 1): Promise<ChatMessagesResponse> {
    const response = await apiClient.get<ChatMessagesResponse>(`/chat/rooms/${roomId}/messages`, {
      params: { page },
    })
    return response.data
  },

  /**
   * Mengirim pesan teks atau lampiran gambar ke ruang obrolan
   */
  async sendMessage(
    roomId: number,
    payload: SendMessagePayload
  ): Promise<ApiResponse<ChatMessage>> {
    const formData = new FormData()
    if (payload.message) {
      formData.append('message', payload.message)
    }
    if (payload.attachment) {
      formData.append('attachment', payload.attachment)
    }

    const response = await apiClient.post<ApiResponse<ChatMessage>>(
      `/chat/rooms/${roomId}/messages`,
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
   * Menandai semua pesan belum dibaca di ruang obrolan sebagai sudah dibaca
   */
  async markAsRead(roomId: number): Promise<ApiResponse<null>> {
    const response = await apiClient.post<ApiResponse<null>>(`/chat/rooms/${roomId}/read`)
    return response.data
  },
}

export default mitraChatApi
