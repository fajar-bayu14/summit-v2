import apiClient from '@/lib/axios'
import type { ApiResponse } from '@/types/api'
import type {
  ChatRoom,
  ChatMessage,
  SendChatMessagePayload,
  CreateChatRoomPayload
} from '@/types/pendakiChat'

export const pendakiChatApi = {
  async createOrGetRoom(payload: CreateChatRoomPayload): Promise<ApiResponse<ChatRoom>> {
    const response = await apiClient.post<ApiResponse<ChatRoom>>('/chat/rooms', payload)
    return response.data
  },

  async getRooms(page = 1): Promise<ApiResponse<{ items: ChatRoom[]; meta: any }>> {
    const response = await apiClient.get<ApiResponse<{ items: ChatRoom[]; meta: any }>>('/chat/rooms', {
      params: { page }
    })
    return response.data
  },

  async getMessages(roomId: number, page = 1): Promise<ApiResponse<{ items: ChatMessage[]; meta: any }>> {
    const response = await apiClient.get<ApiResponse<{ items: ChatMessage[]; meta: any }>>(
      `/chat/rooms/${roomId}/messages`,
      { params: { page } }
    )
    return response.data
  },

  async sendMessage(roomId: number, payload: SendChatMessagePayload): Promise<ApiResponse<ChatMessage>> {
    let requestData: any
    let headers: any = {}

    if (payload.attachment) {
      const formData = new FormData()
      if (payload.message) {
        formData.append('message', payload.message)
      }
      formData.append('attachment', payload.attachment)
      requestData = formData
      headers['Content-Type'] = 'multipart/form-data'
    } else {
      requestData = {
        message: payload.message
      }
    }

    const response = await apiClient.post<ApiResponse<ChatMessage>>(
      `/chat/rooms/${roomId}/messages`,
      requestData,
      { headers }
    )
    return response.data
  },

  async markAsRead(roomId: number): Promise<ApiResponse<null>> {
    const response = await apiClient.post<ApiResponse<null>>(`/chat/rooms/${roomId}/read`)
    return response.data
  }
}
