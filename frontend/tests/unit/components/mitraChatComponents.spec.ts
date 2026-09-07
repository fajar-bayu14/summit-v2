import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ChatRoomList from '@/components/mitra/chat/ChatRoomList.vue'
import ChatConversationPane from '@/components/mitra/chat/ChatConversationPane.vue'
import type { ChatRoom, ChatMessage } from '@/types/chat'

describe('Mitra In-App Chat Components (Task 9.2)', () => {
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
      last_message: {
        id: 11,
        chat_room_id: 2,
        sender_id: 1,
        sender_name: 'Basecamp',
        message: 'Sudah disiapkan ya kak.',
        is_read: true,
        is_mine: true,
        created_at: '2026-09-07T11:00:00Z',
      },
      last_message_at: '2026-09-07T11:00:00Z',
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
      is_read: true,
      is_mine: false,
      created_at: '2026-09-07T12:00:00Z',
    },
    {
      id: 1002,
      chat_room_id: 1,
      sender_id: 1,
      sender_name: 'Basecamp',
      message: 'Halo kak Bayu, tenda dome sudah siap di loket ya.',
      attachment_url: null,
      is_read: true,
      is_mine: true,
      created_at: '2026-09-07T12:05:00Z',
    },
    {
      id: 1003,
      chat_room_id: 1,
      sender_id: 5,
      sender_name: 'Bayu Pendaki',
      message: 'Ini bukti foto surat kesehatannya:',
      attachment_url: 'http://localhost:8000/storage/chat_attachments/health.jpg',
      is_read: false,
      is_mine: false,
      created_at: '2026-09-07T12:30:00Z',
    },
  ]

  describe('ChatRoomList Component', () => {
    it('should render list of rooms with details', () => {
      const wrapper = mount(ChatRoomList, {
        props: {
          rooms: mockRooms,
          activeRoomId: 1,
        },
      })

      expect(wrapper.text()).toContain('Bayu Pendaki')
      expect(wrapper.text()).toContain('INV-PRAU-001')
      expect(wrapper.text()).toContain('Rian Pratama')
      expect(wrapper.text()).toContain('INV-PRAU-002')
      expect(wrapper.text()).toContain('2') // unread badge for room 1
    })

    it('should filter rooms by search query', async () => {
      const wrapper = mount(ChatRoomList, {
        props: {
          rooms: mockRooms,
          activeRoomId: 1,
        },
      })

      const vm = wrapper.vm as any
      vm.searchQuery = 'Rian'
      await wrapper.vm.$nextTick()

      expect(wrapper.text()).toContain('Rian Pratama')
      expect(wrapper.text()).not.toContain('Bayu Pendaki')
    })

    it('should emit selectRoom event when clicking a room', async () => {
      const wrapper = mount(ChatRoomList, {
        props: {
          rooms: mockRooms,
          activeRoomId: null,
        },
      })

      const roomDivs = wrapper.findAll('.cursor-pointer')
      await roomDivs[0].trigger('click')

      expect(wrapper.emitted('selectRoom')?.[0]).toEqual([mockRooms[0]])
    })
  })

  describe('ChatConversationPane Component', () => {
    it('should display empty placeholder when no room is selected', () => {
      const wrapper = mount(ChatConversationPane, {
        props: {
          room: null,
          messages: [],
        },
      })

      expect(wrapper.text()).toContain('Pilih Ruang Obrolan')
    })

    it('should render conversation header and message stream with attachment', () => {
      const wrapper = mount(ChatConversationPane, {
        props: {
          room: mockRooms[0],
          messages: mockMessages,
        },
      })

      expect(wrapper.text()).toContain('Bayu Pendaki')
      expect(wrapper.text()).toContain('INV-PRAU-001')
      expect(wrapper.text()).toContain('Halo basecamp, apakah tenda dome ready?')
      expect(wrapper.text()).toContain('Halo kak Bayu, tenda dome sudah siap di loket ya.')
      expect(wrapper.find('img[src="http://localhost:8000/storage/chat_attachments/health.jpg"]').exists()).toBe(true)
    })

    it('should insert quick reply template into input text', async () => {
      const wrapper = mount(ChatConversationPane, {
        props: {
          room: mockRooms[0],
          messages: mockMessages,
        },
      })

      const vm = wrapper.vm as any
      vm.insertQuickReply('Halo, tenda & matras pesanan sudah siap diambil di basecamp ya.')

      expect(vm.inputText).toContain('tenda & matras pesanan')
    })

    it('should emit send event with message payload', async () => {
      const wrapper = mount(ChatConversationPane, {
        props: {
          room: mockRooms[0],
          messages: mockMessages,
        },
      })

      const vm = wrapper.vm as any
      vm.inputText = 'Siap kak, ditunggu di basecamp!'
      vm.handleSend()

      expect(wrapper.emitted('send')?.[0]).toEqual([
        {
          message: 'Siap kak, ditunggu di basecamp!',
          attachment: null,
        },
      ])
      expect(vm.inputText).toBe('')
    })
  })
})
