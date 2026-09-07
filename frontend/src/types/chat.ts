export interface ChatParticipant {
  id: number
  name?: string | null
  avatar?: string | null
  basecamp_nama?: string | null
}

export interface ChatMessage {
  id: number
  chat_room_id: number
  sender_id: number
  sender_name?: string | null
  message?: string | null
  attachment_url?: string | null
  is_read: boolean
  is_mine: boolean
  created_at: string
}

export interface ChatRoom {
  id: number
  pesanan_id: number
  invoice?: string | null
  gunung_nama?: string | null
  pendaki?: ChatParticipant | null
  mitra?: ChatParticipant | null
  status: 'active' | 'closed'
  unread_count: number
  last_message?: ChatMessage | null
  last_message_at?: string | null
  created_at?: string | null
}

export interface SendMessagePayload {
  message?: string | null
  attachment?: File | null
}

export interface ChatFilterParams {
  search?: string
  page?: number
  per_page?: number
}
