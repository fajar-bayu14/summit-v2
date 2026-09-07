import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import OrderChatDrawer from '@/components/pendaki/OrderChatDrawer.vue'
import { pendakiChatApi } from '@/api/pendakiChat'

vi.mock('@/api/pendakiChat', () => ({
  pendakiChatApi: {
    createOrGetRoom: vi.fn(),
    getMessages: vi.fn(),
    sendMessage: vi.fn(),
    markAsRead: vi.fn()
  }
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    user: { id: 1, name: 'Fajar' }
  }))
}))

describe('OrderChatDrawer Component (Task 9.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should initialize room when isOpen is true', async () => {
    const mockRoom = {
      id: 8,
      pesanan_id: 15,
      invoice: 'INV-2026-DRAWER-01',
      status: 'active' as const,
      mitra: { id: 2, name: 'Basecamp Selo' }
    }

    vi.mocked(pendakiChatApi.createOrGetRoom).mockResolvedValueOnce({
      status: 'success',
      data: mockRoom as any
    })
    vi.mocked(pendakiChatApi.getMessages).mockResolvedValueOnce({
      status: 'success',
      data: { items: [], meta: {} }
    })
    vi.mocked(pendakiChatApi.markAsRead).mockResolvedValueOnce({
      status: 'success',
      data: null
    })

    const wrapper = mount(OrderChatDrawer, {
      props: {
        isOpen: true,
        pesananId: 15,
        invoice: 'INV-2026-DRAWER-01'
      }
    })

    expect(pendakiChatApi.createOrGetRoom).toHaveBeenCalledWith({ pesanan_id: 15 })

    await vi.dynamicImportSettled()

    expect(wrapper.text()).toContain('Obrolan Basecamp')
    expect(wrapper.text()).toContain('INV-2026-DRAWER-01')
  })
})
