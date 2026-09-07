import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mitraChatApi } from '@/api/mitraChat'
import { apiClient } from '@/lib/axios'
import type { ChatRoom, ChatMessage } from '@/types/chat'

vi.mock('@/lib/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('Mitra Chat API Service (Task 9.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockRoom: ChatRoom = {
    id: 1,
    pesanan_id: 10,
    invoice: 'INV-PRAU-001',
    gunung_nama: 'Gunung Prau',
    status: 'active',
    unread_count: 2,
    pendaki: {
      id: 5,
      name: 'Bayu Pendaki',
      avatar: null,
    },
    mitra: {
      id: 2,
      name: 'Basecamp Prau Dieng',
      basecamp_nama: 'Basecamp Prau Dieng',
    },
    last_message_at: '2026-09-07T12:00:00Z',
  }

  const mockMessage: ChatMessage = {
    id: 101,
    chat_room_id: 1,
    sender_id: 5,
    sender_name: 'Bayu Pendaki',
    message: 'Halo basecamp, apakah tenda dome ready?',
    attachment_url: null,
    is_read: false,
    is_mine: false,
    created_at: '2026-09-07T12:00:00Z',
  }

  it('should fetch chat rooms list with params', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Daftar percakapan berhasil dimuat.',
        data: [mockRoom],
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce(mockResponse)

    const params = { search: 'INV-PRAU', page: 1 }
    const result = await mitraChatApi.getRooms(params)

    expect(apiClient.get).toHaveBeenCalledWith('/chat/rooms', { params })
    expect(result).toEqual(mockResponse.data)
  })

  it('should create or get chat room for an order', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Ruang obrolan berhasil dibuka.',
        data: mockRoom,
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const result = await mitraChatApi.createOrGetRoom(10)

    expect(apiClient.post).toHaveBeenCalledWith('/chat/rooms', { pesanan_id: 10 })
    expect(result).toEqual(mockResponse.data)
  })

  it('should fetch messages history for a chat room', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Riwayat pesan berhasil dimuat.',
        data: [mockMessage],
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce(mockResponse)

    const result = await mitraChatApi.getMessages(1, 1)

    expect(apiClient.get).toHaveBeenCalledWith('/chat/rooms/1/messages', {
      params: { page: 1 },
    })
    expect(result).toEqual(mockResponse.data)
  })

  it('should send a text message using formData', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Pesan berhasil dikirim.',
        data: {
          ...mockMessage,
          id: 102,
          is_mine: true,
          message: 'Tenda dome ready!',
        },
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const result = await mitraChatApi.sendMessage(1, { message: 'Tenda dome ready!' })

    expect(apiClient.post).toHaveBeenCalledWith(
      '/chat/rooms/1/messages',
      expect.any(FormData),
      expect.objectContaining({
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    )
    expect(result).toEqual(mockResponse.data)
  })

  it('should mark unread messages as read', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Pesan ditandai sebagai dibaca.',
        data: null,
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const result = await mitraChatApi.markAsRead(1)

    expect(apiClient.post).toHaveBeenCalledWith('/chat/rooms/1/read')
    expect(result).toEqual(mockResponse.data)
  })
})
