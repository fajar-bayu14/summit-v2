export interface ChatParticipant {
  id: number
  name?: string | null
  avatar?: string | null
  basecamp_nama?: string | null
  role?: string
}

export interface ChatMessage {
  id: number
  chat_room_id: number
  sender_id: number
  sender_name?: string | null
  sender?: {
    id: number
    name: string
    avatar?: string | null
  } | null
  message?: string | null
  attachment_url?: string | null
  is_read: boolean
  is_mine?: boolean
  created_at: string
}

export interface ChatRoom {
  id: number
  pesanan_id: number
  invoice?: string | null
  gunung_nama?: string | null
  pendaki_user_id?: number
  mitra_user_id?: number
  pendaki?: ChatParticipant | null
  mitra?: ChatParticipant | null
  pesanan?: {
    id: number
    invoice: string
    status: string
    tanggal_booking?: string
    jalur?: {
      id: number
      nama_jalur: string
      gunung?: {
        id: number
        nama_gunung: string
      } | null
    } | null
  } | null
  status: 'active' | 'closed'
  unread_count?: number
  last_message?: ChatMessage | null
  last_message_at?: string | null
  created_at?: string | null
}

export interface SendChatMessagePayload {
  message?: string | null
  attachment?: File | null
}

export interface CreateChatRoomPayload {
  pesanan_id: number
}
