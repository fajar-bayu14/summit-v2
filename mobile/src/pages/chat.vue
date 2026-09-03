<template>
  <div class="summit-chat-page">
    <!-- Header -->
    <header class="summit-top-bar">
      <button
        v-if="activeRoom"
        class="summit-icon-btn"
        aria-label="Kembali"
        @click="activeRoom = null"
      >
        <q-icon name="arrow_back" />
      </button>
      <h1 class="summit-top-bar__title">
        {{
          activeRoom
            ? activeRoom.mitra?.basecamp_nama ||
              activeRoom.mitra?.name ||
              'Chat Basecamp'
            : 'Pesan & Diskusi'
        }}
      </h1>
      <button
        class="summit-icon-btn"
        aria-label="Refresh"
        @click="activeRoom ? fetchMessages(activeRoom.id) : fetchRooms()"
      >
        <q-icon name="refresh" />
      </button>
    </header>

    <!-- Chat Rooms List View -->
    <main v-if="!activeRoom" class="summit-page__content">
      <div v-if="loadingRooms" class="chat-rooms-list">
        <div v-for="n in 3" :key="n" class="chat-room-item skeleton-pulse">
          <div class="skeleton-avatar"></div>
          <div class="skeleton-text"></div>
        </div>
      </div>

      <div v-else-if="rooms.length === 0" class="empty-state">
        <q-icon name="chat_bubble_outline" size="48px" class="text-grey-5" />
        <h3 class="empty-state__title">Belum ada obrolan aktif</h3>
        <p class="empty-state__text">
          Ruang obrolan dengan mitra basecamp otomatis terbuka setelah pemesanan
          tiket terkonfirmasi.
        </p>
      </div>

      <div v-else class="chat-rooms-list">
        <div
          v-for="room in rooms"
          :key="room.id"
          class="chat-room-item"
          @click="selectRoom(room)"
        >
          <div class="chat-room-item__avatar">
            <q-icon name="forest" size="24px" class="text-white" />
          </div>
          <div class="chat-room-item__info">
            <div class="chat-room-item__header">
              <h4 class="chat-room-item__name">{{
                room.mitra?.basecamp_nama || room.mitra?.name || 'Basecamp'
              }}</h4>
              <span class="chat-room-item__time">{{
                formatTime(room.last_message_at || room.updated_at)
              }}</span>
            </div>
            <p class="chat-room-item__preview">
              <span class="chat-room-item__invoice">[{{ room.invoice }}]</span>
              {{
                room.last_message?.message ||
                (room.last_message?.attachment_url
                  ? '📷 [Lampiran Foto]'
                  : 'Belum ada pesan baru')
              }}
            </p>
          </div>
          <span v-if="room.unread_count > 0" class="unread-badge">
            {{ room.unread_count }}
          </span>
        </div>
      </div>
    </main>

    <!-- Active Chat Room Dialogue View -->
    <div v-else class="chat-dialogue">
      <div ref="messagesContainer" class="chat-messages-container">
        <div v-if="loadingMessages" class="text-center q-pa-md">
          <q-spinner color="primary" size="2em" />
        </div>

        <div
          v-else-if="messages.length === 0"
          class="text-center q-pa-lg text-grey-6"
        >
          Mulai percakapan dengan menanyakan persiapan pendakian, SOP, atau
          titik kumpul.
        </div>

        <div
          v-for="msg in messages"
          :key="msg.id"
          class="chat-bubble-row"
          :class="{ 'is-mine': msg.is_mine }"
        >
          <div class="chat-bubble" :class="{ 'is-mine': msg.is_mine }">
            <img
              v-if="msg.attachment_url"
              :src="msg.attachment_url"
              class="chat-attachment-img"
              loading="lazy"
              @click="previewImage(msg.attachment_url)"
            />
            <p v-if="msg.message" class="chat-bubble__text">{{
              msg.message
            }}</p>
            <div class="chat-bubble__meta">
              <span class="chat-bubble__time">{{
                formatTime(msg.created_at)
              }}</span>
              <q-icon
                v-if="msg.is_mine"
                :name="msg.is_read ? 'done_all' : 'done'"
                size="12px"
                :class="msg.is_read ? 'text-primary-tint' : 'text-grey-5'"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Message Input Bar -->
      <footer class="chat-input-bar">
        <label class="chat-input-bar__attach" aria-label="Lampirkan Foto">
          <q-icon name="attach_file" size="20px" />
          <input
            type="file"
            accept="image/*"
            class="hidden-input"
            @change="onAttachSelected"
          />
        </label>
        <input
          v-model="inputMessage"
          class="chat-input-bar__field"
          type="text"
          placeholder="Tulis pesan..."
          @keyup.enter="sendMessage"
        />
        <button
          class="chat-input-bar__send"
          :disabled="!inputMessage && !selectedAttachment"
          aria-label="Kirim Pesan"
          @click="sendMessage"
        >
          <q-icon name="send" />
        </button>
      </footer>
    </div>

    <!-- Bottom navigation (only on room list) -->
    <BottomNav v-if="!activeRoom" active="chat" />
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import BottomNav from '@/components/BottomNav.vue'
import {
  getChatRooms,
  getChatMessages,
  sendChatMessage,
  markChatAsRead
} from '@/api/chat'

const route = useRoute()
const $q = useQuasar()

const loadingRooms = ref(true)
const loadingMessages = ref(false)
const rooms = ref([])
const activeRoom = ref(null)
const messages = ref([])
const inputMessage = ref('')
const selectedAttachment = ref(null)
const messagesContainer = ref(null)

onMounted(async () => {
  await fetchRooms()
  if (route.query.room_id) {
    const target = rooms.value.find(
      r => String(r.id) === String(route.query.room_id)
    )
    if (target) selectRoom(target)
  }
})

async function fetchRooms() {
  loadingRooms.value = true
  try {
    const res = await getChatRooms()
    rooms.value = res.data?.data ?? res.data ?? []
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal memuat ruang obrolan'
    })
  } finally {
    loadingRooms.value = false
  }
}

async function selectRoom(room) {
  activeRoom.value = room
  loadingMessages.value = true
  await fetchMessages(room.id)
  markChatAsRead(room.id).catch(() => {})
  scrollToBottom()
}

async function fetchMessages(roomId) {
  try {
    const res = await getChatMessages(roomId)
    // Reverse because api returns latest first paginated
    const data = res.data?.data ?? res.data ?? []
    messages.value = [...data].reverse()
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal memuat pesan'
    })
  } finally {
    loadingMessages.value = false
  }
}

async function sendMessage() {
  if (!inputMessage.value && !selectedAttachment.value) return

  const formData = new FormData()
  if (inputMessage.value) formData.append('message', inputMessage.value)
  if (selectedAttachment.value)
    formData.append('attachment', selectedAttachment.value)

  const textTemp = inputMessage.value
  inputMessage.value = ''
  selectedAttachment.value = null

  try {
    const res = await sendChatMessage(activeRoom.value.id, formData)
    messages.value.push(res.data)
    scrollToBottom()
  } catch (err) {
    inputMessage.value = textTemp
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal mengirim pesan'
    })
  }
}

function onAttachSelected(e) {
  if (e.target.files && e.target.files[0]) {
    selectedAttachment.value = e.target.files[0]
    $q.notify({
      type: 'info',
      message: `Foto "${selectedAttachment.value.name}" terpilih.`
    })
  }
}

function previewImage(url) {
  window.open(url, '_blank')
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

function formatTime(isoStr) {
  if (!isoStr) return ''
  const d = new Date(isoStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
</script>

<style lang="scss">
.summit-chat-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: $summit-background;
}

.chat-rooms-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.chat-room-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;

  &__avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: $primary;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  &__info {
    flex: 1;
    min-width: 0;
  }

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__name {
    font-size: 0.875rem;
    font-weight: 700;
    margin: 0;
    color: $summit-text-primary;
  }

  &__time {
    font-size: 0.6875rem;
    color: $summit-text-secondary;
  }

  &__preview {
    font-size: 0.75rem;
    color: $summit-text-secondary;
    margin: 4px 0 0 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__invoice {
    font-weight: 600;
    color: $primary;
  }
}

.unread-badge {
  background: $secondary;
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
}

.chat-dialogue {
  display: flex;
  flex-direction: column;
  flex: 1;
  height: calc(100vh - 60px);
}

.chat-messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.chat-bubble-row {
  display: flex;
  justify-content: flex-start;

  &.is-mine {
    justify-content: flex-end;
  }
}

.chat-bubble {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 16px 16px 16px 4px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  color: $summit-text-primary;

  &.is-mine {
    border-radius: 16px 16px 4px 16px;
    background: $primary;
    color: #fff;
    border-color: $primary;
  }

  &__text {
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.4;
    word-break: break-word;
  }

  &__meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 4px;
    font-size: 0.625rem;
    opacity: 0.8;
  }
}

.chat-attachment-img {
  max-width: 100%;
  border-radius: 8px;
  margin-bottom: 6px;
  cursor: pointer;
}

.chat-input-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: $summit-surface;
  border-top: 1px solid $summit-border;

  &__attach {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    cursor: pointer;
    color: $summit-text-secondary;
  }

  &__field {
    flex: 1;
    height: 40px;
    padding: 0 14px;
    border: 1px solid $summit-border;
    border-radius: 20px;
    font-size: 0.875rem;
    outline: none;

    &:focus {
      border-color: $primary;
    }
  }

  &__send {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: $primary;
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;

    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }
}

.hidden-input {
  display: none;
}
</style>
