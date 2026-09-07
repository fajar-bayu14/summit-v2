import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ChatManagementView from '@/views/mitra/ChatManagementView.vue'
import mitraChatApi from '@/api/mitraChat'
import type { ChatRoom, ChatMessage } from '@/types/chat'

describe('ChatManagementView View Component (Task 9.3)', () => {
  const mockRooms: ChatRoom[] = [
    {
      id: 1,
      pesanan_id: 101,
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
        id: 1,
        name: 'Basecamp Prau Dieng',
      },
      last_message: {
        id: 10,
        chat_room_id: 1,
        sender_id: 5,
        sender_name: 'Bayu Pendaki',
        message: 'Halo, tenda apakah sudah ready?',
        is_read: false,
        is_mine: false,
        created_at: '2026-09-07T12:30:00Z',
      },
      last_message_at: '2026-09-07T12:30:00Z',
    },
    {
      id: 2,
      pesanan_id: 102,
      invoice: 'INV-PRAU-002',
      gunung_nama: 'Gunung Prau',
      status: 'active',
      unread_count: 0,
      pendaki: {
        id: 6,
        name: 'Rian Pratama',
        avatar: null,
      },
      mitra: {
        id: 1,
        name: 'Basecamp Prau Dieng',
      },
      last_message: null,
      last_message_at: null,
    },
  ]

  const mockMessages: ChatMessage[] = [
    {
      id: 1001,
      chat_room_id: 1,
      sender_id: 5,
      sender_name: 'Bayu Pendaki',
      message: 'Halo basecamp, apakah tenda dome ready?',
      attachment_url: null,
      is_read: false,
      is_mine: false,
      created_at: '2026-09-07T12:00:00Z',
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('should load chat rooms on mount', async () => {
    const getRoomsSpy = vi.spyOn(mitraChatApi, 'getRooms').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockRooms,
    } as any)

    const wrapper = mount(ChatManagementView)
    await flushPromises()

    expect(getRoomsSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.rooms.length).toBe(2)
    expect(wrapper.text()).toContain('Bayu Pendaki')
    expect(wrapper.text()).toContain('Rian Pratama')
  })

  it('should select room, fetch messages, and mark unread as read', async () => {
    vi.spyOn(mitraChatApi, 'getRooms').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: [...mockRooms],
    } as any)

    const getMessagesSpy = vi.spyOn(mitraChatApi, 'getMessages').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockMessages,
    } as any)

    const markReadSpy = vi.spyOn(mitraChatApi, 'markAsRead').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: null,
    } as any)

    const wrapper = mount(ChatManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleSelectRoom(mockRooms[0])
    await flushPromises()

    expect(getMessagesSpy).toHaveBeenCalledWith(1)
    expect(markReadSpy).toHaveBeenCalledWith(1)
    expect(vm.activeRoom).toEqual(mockRooms[0])
    expect(vm.messages.length).toBe(1)
    expect(vm.showMobileChat).toBe(true)
  })

  it('should send message in active room and update messages array', async () => {
    vi.spyOn(mitraChatApi, 'getRooms').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: [...mockRooms],
    } as any)

    vi.spyOn(mitraChatApi, 'getMessages').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockMessages,
    } as any)

    const sentMessage: ChatMessage = {
      id: 1002,
      chat_room_id: 1,
      sender_id: 1,
      sender_name: 'Basecamp',
      message: 'Tenda dome siap di loket!',
      attachment_url: null,
      is_read: false,
      is_mine: true,
      created_at: '2026-09-07T12:05:00Z',
    }

    const sendSpy = vi.spyOn(mitraChatApi, 'sendMessage').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: sentMessage,
    } as any)

    const wrapper = mount(ChatManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.activeRoom = mockRooms[0]

    await vm.handleSendMessage({ message: 'Tenda dome siap di loket!' })
    await flushPromises()

    expect(sendSpy).toHaveBeenCalledWith(1, { message: 'Tenda dome siap di loket!' })
    expect(vm.messages).toContainEqual(sentMessage)
    expect(vm.rooms[0].last_message).toEqual(sentMessage)
  })

  it('should handle back to rooms on mobile view', async () => {
    vi.spyOn(mitraChatApi, 'getRooms').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockRooms,
    } as any)

    const wrapper = mount(ChatManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.showMobileChat = true
    vm.handleBackToRooms()

    expect(vm.showMobileChat).toBe(false)
  })

  it('should start polling on mount and stop on unmount', async () => {
    const getRoomsSpy = vi.spyOn(mitraChatApi, 'getRooms').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockRooms,
    } as any)

    const wrapper = mount(ChatManagementView)
    await flushPromises()

    // Advance 6 seconds for room polling
    vi.advanceTimersByTime(6500)
    await flushPromises()

    expect(getRoomsSpy).toHaveBeenCalledTimes(2)

    wrapper.unmount()
    vi.advanceTimersByTime(12000)
    await flushPromises()

    // No additional calls after unmount
    expect(getRoomsSpy).toHaveBeenCalledTimes(2)
  })
})
