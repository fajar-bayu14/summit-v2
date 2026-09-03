import { request } from './http'

export async function createOrGetChatRoom(pesananId) {
  return request('/chat/rooms', {
    method: 'POST',
    body: { pesanan_id: pesananId }
  })
}

export async function getChatRooms(page = 1) {
  return request(`/chat/rooms?page=${page}`)
}

export async function getChatMessages(roomId, page = 1) {
  return request(`/chat/rooms/${roomId}/messages?page=${page}`)
}

export async function sendChatMessage(roomId, formDataOrPayload) {
  return request(`/chat/rooms/${roomId}/messages`, {
    method: 'POST',
    body: formDataOrPayload
  })
}

export async function markChatAsRead(roomId) {
  return request(`/chat/rooms/${roomId}/read`, {
    method: 'POST'
  })
}
