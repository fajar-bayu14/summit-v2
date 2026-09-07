<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraChatApi from '@/api/mitraChat'
import type { ChatRoom, ChatMessage, SendMessagePayload } from '@/types/chat'
import ChatRoomList from '@/components/mitra/chat/ChatRoomList.vue'
import ChatConversationPane from '@/components/mitra/chat/ChatConversationPane.vue'
import { MessageSquare, AlertCircle, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const rooms = ref<ChatRoom[]>([])
const activeRoom = ref<ChatRoom | null>(null)
const messages = ref<ChatMessage[]>([])
const loadingRooms = ref(false)
const loadingMessages = ref(false)
const sendingMessage = ref(false)
const errorMessage = ref<string | null>(null)
const showMobileChat = ref(false)

let roomPollingTimer: ReturnType<typeof setInterval> | null = null
let messagePollingTimer: ReturnType<typeof setInterval> | null = null

const activeRoomId = computed(() => activeRoom.value?.id ?? null)

onMounted(() => {
  fetchRooms()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})

async function fetchRooms(showLoader = true) {
  if (showLoader) loadingRooms.value = true
  errorMessage.value = null

  try {
    const res = await mitraChatApi.getRooms()
    if (Array.isArray(res.data)) {
      rooms.value = res.data
    } else if (res.data && 'data' in res.data) {
      rooms.value = res.data.data
    }
  } catch (err) {
    if (showLoader) {
      errorMessage.value = getApiErrorMessage(err, 'Gagal memuat daftar percakapan.')
    }
  } finally {
    if (showLoader) loadingRooms.value = false
  }
}

async function handleSelectRoom(room: ChatRoom) {
  activeRoom.value = room
  showMobileChat.value = true
  await fetchMessages(room.id)

  if (room.unread_count > 0) {
    room.unread_count = 0
    try {
      await mitraChatApi.markAsRead(room.id)
    } catch {
      // Non-critical, ignore
    }
  }
}

async function fetchMessages(roomId: number, showLoader = true) {
  if (showLoader) loadingMessages.value = true

  try {
    const res = await mitraChatApi.getMessages(roomId)
    let fetchedMessages: ChatMessage[] = []

    if (Array.isArray(res.data)) {
      fetchedMessages = res.data
    } else if (res.data && 'data' in res.data) {
      fetchedMessages = res.data.data
    }

    // Backend returns latest first (descending). Reverse to display chronological (ascending).
    messages.value = [...fetchedMessages].reverse()
  } catch (err) {
    if (showLoader) {
      errorMessage.value = getApiErrorMessage(err, 'Gagal memuat pesan obrolan.')
    }
  } finally {
    if (showLoader) loadingMessages.value = false
  }
}

async function handleSendMessage(payload: SendMessagePayload) {
  if (!activeRoom.value) return
  sendingMessage.value = true

  try {
    const res = await mitraChatApi.sendMessage(activeRoom.value.id, payload)
    if (res.data) {
      messages.value.push(res.data)

      // Update last message in room list
      const idx = rooms.value.findIndex((r) => r.id === activeRoom.value?.id)
      if (idx !== -1) {
        rooms.value[idx].last_message = res.data
        rooms.value[idx].last_message_at = res.data.created_at
      }
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal mengirim pesan.')
  } finally {
    sendingMessage.value = false
  }
}

function handleBackToRooms() {
  showMobileChat.value = false
}

function startPolling() {
  stopPolling()

  // Poll room list every 6 seconds
  roomPollingTimer = setInterval(() => {
    fetchRooms(false)
  }, 6000)

  // Poll active conversation messages every 4 seconds
  messagePollingTimer = setInterval(() => {
    if (activeRoom.value) {
      fetchMessages(activeRoom.value.id, false)
    }
  }, 4000)
}

function stopPolling() {
  if (roomPollingTimer) {
    clearInterval(roomPollingTimer)
    roomPollingTimer = null
  }
  if (messagePollingTimer) {
    clearInterval(messagePollingTimer)
    messagePollingTimer = null
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <MessageSquare class="w-7 h-7 text-[#1E3A2B]" />
          In-App Chat & Komunikasi Pendaki
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Kanal koordinasi langsung dengan pendaki terhubung berdasarkan pesanan tiket di basecamp
          <span v-if="mitraStore.activeBasecamp" class="font-semibold text-stone-700">
            ({{ mitraStore.activeBasecamp.nama_basecamp }})
          </span>.
        </p>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="flex items-center gap-3 p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm"
    >
      <AlertCircle class="w-5 h-5 text-red-600 shrink-0" />
      <span class="flex-1">{{ errorMessage }}</span>
      <Button
        variant="ghost"
        size="sm"
        class="text-red-700 hover:bg-red-100"
        @click="errorMessage = null"
      >
        <X class="w-4 h-4" />
      </Button>
    </div>

    <!-- Main Chat Workspace: Split-Pane Window -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden h-[calc(100vh-250px)] min-h-[520px] flex">
      <!-- Left Pane: Room List (Hidden on mobile if conversation is open) -->
      <div
        :class="[
          'w-full md:w-80 lg:w-96 shrink-0 h-full',
          showMobileChat ? 'hidden md:block' : 'block',
        ]"
      >
        <ChatRoomList
          :rooms="rooms"
          :active-room-id="activeRoomId"
          :loading="loadingRooms"
          @select-room="handleSelectRoom"
        />
      </div>

      <!-- Right Pane: Active Conversation Window (Hidden on mobile if no room open) -->
      <div
        :class="[
          'flex-1 h-full',
          !showMobileChat ? 'hidden md:block' : 'block',
        ]"
      >
        <ChatConversationPane
          :room="activeRoom"
          :messages="messages"
          :loading="loadingMessages"
          :sending="sendingMessage"
          @send="handleSendMessage"
          @back="handleBackToRooms"
          @refresh="activeRoom && fetchMessages(activeRoom.id)"
        />
      </div>
    </div>
  </div>
</template>
