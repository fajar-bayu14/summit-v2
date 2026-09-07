import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ChatConversationPane from '@/components/pendaki/ChatConversationPane.vue'
import { pendakiChatApi } from '@/api/pendakiChat'

vi.mock('@/api/pendakiChat', () => ({
  pendakiChatApi: {
    getMessages: vi.fn(),
    sendMessage: vi.fn(),
    markAsRead: vi.fn()
  }
}))

describe('ChatConversationPane Component (Task 9.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render empty state when activeRoom is null', () => {
    const wrapper = mount(ChatConversationPane, {
      props: {
        activeRoom: null,
        currentUserId: 1
      }
    })

    expect(wrapper.text()).toContain('Pilih Percakapan')
  })

  it('should render active room header, load messages and send message', async () => {
    const mockRoom = {
      id: 10,
      pesanan_id: 100,
      invoice: 'INV-2026-CHAT-01',
      gunung_nama: 'Gunung Slamet',
      status: 'active' as const,
      mitra: {
        id: 99,
        name: 'Basecamp Bambangan'
      }
    }

    const mockMessages = [
      {
        id: 1,
        chat_room_id: 10,
        sender_id: 99,
        message: 'Selamat datang! Siap mendaki akhir pekan ini?',
        is_read: true,
        created_at: '2026-09-07T08:00:00Z'
      }
    ]

    vi.mocked(pendakiChatApi.getMessages).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        items: mockMessages,
        meta: {}
      }
    })
    vi.mocked(pendakiChatApi.markAsRead).mockResolvedValueOnce({
      status: 'success',
      data: null
    })

    const wrapper = mount(ChatConversationPane, {
      props: {
        activeRoom: mockRoom as any,
        currentUserId: 1
      }
    })

    expect(wrapper.text()).toContain('Basecamp Bambangan')
    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('INV-2026-CHAT-01')

    await vi.dynamicImportSettled()

    expect(wrapper.text()).toContain('Selamat datang! Siap mendaki akhir pekan ini?')

    // Send new message
    vi.mocked(pendakiChatApi.sendMessage).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        id: 2,
        chat_room_id: 10,
        sender_id: 1,
        message: 'Siap min, peralatan logistik aman.',
        is_read: false,
        created_at: '2026-09-07T08:05:00Z'
      }
    })

    const input = wrapper.find('input[type="text"]')
    await input.setValue('Siap min, peralatan logistik aman.')

    await wrapper.find('form').trigger('submit.prevent')

    expect(pendakiChatApi.sendMessage).toHaveBeenCalledWith(10, {
      message: 'Siap min, peralatan logistik aman.',
      attachment: undefined
    })

    expect(wrapper.emitted('message-sent')).toBeTruthy()
  })
})
