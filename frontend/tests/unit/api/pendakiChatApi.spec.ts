import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiChatApi } from '@/api/pendakiChat'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn()
  }
}))

describe('Pendaki Chat API Client (Task 9.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should create or get chat room for an order', async () => {
    const mockRoom = {
      id: 5,
      pesanan_id: 12,
      status: 'active' as const,
      unread_count: 0
    }
    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { status: 'success', data: mockRoom }
    })

    const res = await pendakiChatApi.createOrGetRoom({ pesanan_id: 12 })

    expect(apiClient.post).toHaveBeenCalledWith('/chat/rooms', { pesanan_id: 12 })
    expect(res.data.id).toBe(5)
  })

  it('should fetch chat rooms list with pagination', async () => {
    const mockRooms = [{ id: 1, pesanan_id: 10, status: 'active' as const }]
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { status: 'success', data: { items: mockRooms, meta: {} } }
    })

    const res = await pendakiChatApi.getRooms(1)

    expect(apiClient.get).toHaveBeenCalledWith('/chat/rooms', { params: { page: 1 } })
    expect(res.data.items).toHaveLength(1)
  })

  it('should send text message to a room', async () => {
    const mockMsg = {
      id: 101,
      chat_room_id: 5,
      sender_id: 1,
      message: 'Halo basecamp, apakah besok jalur dibuka?',
      is_read: false,
      created_at: '2026-09-07T10:00:00Z'
    }
    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { status: 'success', data: mockMsg }
    })

    const res = await pendakiChatApi.sendMessage(5, {
      message: 'Halo basecamp, apakah besok jalur dibuka?'
    })

    expect(apiClient.post).toHaveBeenCalledWith(
      '/chat/rooms/5/messages',
      { message: 'Halo basecamp, apakah besok jalur dibuka?' },
      { headers: {} }
    )
    expect(res.data.message).toBe('Halo basecamp, apakah besok jalur dibuka?')
  })

  it('should mark room messages as read', async () => {
    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { status: 'success', message: 'Read' }
    })

    const res = await pendakiChatApi.markAsRead(5)

    expect(apiClient.post).toHaveBeenCalledWith('/chat/rooms/5/read')
    expect(res.status).toBe('success')
  })
})
