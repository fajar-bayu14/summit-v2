<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { pendakiChatApi } from '@/api/pendakiChat'
import { extractApiError } from '@/lib/normalizer'
import type { ChatRoom, ChatMessage } from '@/types/pendakiChat'
import {
  AlertCircle,
  Check,
  CheckCheck,
  Image,
  Loader2,
  Mountain,
  Send,
  X
} from 'lucide-vue-next'

const props = defineProps<{
  activeRoom: ChatRoom | null
  currentUserId: number
}>()

const emit = defineEmits<{
  (e: 'message-sent', message: ChatMessage): void
}>()

const messages = ref<ChatMessage[]>([])
const loading = ref(false)
const sending = ref(false)
const inputText = ref('')
const attachmentFile = ref<File | null>(null)
const attachmentPreview = ref<string | null>(null)
const errorMessage = ref('')
const messageContainer = ref<HTMLElement | null>(null)
let pollTimer: ReturnType<typeof setInterval> | null = null

const roomPartnerName = computed(() => {
  if (!props.activeRoom) return ''
  return props.activeRoom.mitra?.name || props.activeRoom.mitra?.basecamp_nama || 'Pengelola Basecamp'
})

const orderContext = computed(() => {
  if (!props.activeRoom) return null
  return {
    invoice: props.activeRoom.invoice || props.activeRoom.pesanan?.invoice,
    gunung: props.activeRoom.gunung_nama || props.activeRoom.pesanan?.jalur?.gunung?.nama_gunung,
    jalur: props.activeRoom.pesanan?.jalur?.nama_jalur
  }
})

async function fetchMessages(silent = false) {
  if (!props.activeRoom) return
  if (!silent) loading.value = true
  errorMessage.value = ''
  try {
    const res = await pendakiChatApi.getMessages(props.activeRoom.id)
    const list = res.data?.items || (res.data as any)?.data || []
    // Messages from backend are latest first or chronological; ensure oldest first
    const reversed = [...list].reverse()
    messages.value = reversed
    await pendakiChatApi.markAsRead(props.activeRoom.id)
    scrollToBottom()
  } catch (err: unknown) {
    if (!silent) errorMessage.value = extractApiError(err).message
  } finally {
    if (!silent) loading.value = false
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messageContainer.value) {
      messageContainer.value.scrollTop = messageContainer.value.scrollHeight
    }
  })
}

watch(
  () => props.activeRoom?.id,
  (newId) => {
    if (newId) {
      fetchMessages()
    } else {
      messages.value = []
    }
  },
  { immediate: true }
)

onMounted(() => {
  pollTimer = setInterval(() => {
    if (props.activeRoom && !sending.value) {
      fetchMessages(true)
    }
  }, 5000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    attachmentFile.value = file
    attachmentPreview.value = URL.createObjectURL(file)
  }
}

function removeAttachment() {
  attachmentFile.value = null
  if (attachmentPreview.value) {
    URL.revokeObjectURL(attachmentPreview.value)
    attachmentPreview.value = null
  }
}

async function handleSendMessage() {
  if (!props.activeRoom || sending.value) return
  if (!inputText.value.trim() && !attachmentFile.value) return

  sending.value = true
  errorMessage.value = ''
  try {
    const res = await pendakiChatApi.sendMessage(props.activeRoom.id, {
      message: inputText.value.trim() || undefined,
      attachment: attachmentFile.value || undefined
    })

    inputText.value = ''
    removeAttachment()
    if (res.data) {
      messages.value.push(res.data)
      emit('message-sent', res.data)
    }
    scrollToBottom()
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    sending.value = false
  }
}

function formatTime(dateStr: string) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="flex h-full flex-col bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
    <!-- Chat Header -->
    <div
      v-if="activeRoom"
      class="flex items-center justify-between border-b border-gray-100 bg-slate-50/75 px-5 py-3.5"
    >
      <div class="flex items-center gap-3 min-w-0">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] font-bold">
          {{ roomPartnerName.charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-bold text-gray-900 truncate">{{ roomPartnerName }}</h3>
          <div class="flex items-center gap-2 text-xs text-gray-500 truncate">
            <span v-if="orderContext?.gunung" class="flex items-center gap-1 font-medium text-[#1E3A2B]">
              <Mountain class="h-3 w-3" />
              {{ orderContext.gunung }}
            </span>
            <span v-if="orderContext?.invoice" class="text-gray-400">
              • {{ orderContext.invoice }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span
          :class="[
            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold',
            activeRoom.status === 'active'
              ? 'bg-emerald-100 text-emerald-800'
              : 'bg-gray-100 text-gray-600'
          ]"
        >
          <span
            :class="[
              'h-1.5 w-1.5 rounded-full',
              activeRoom.status === 'active' ? 'bg-emerald-500' : 'bg-gray-400'
            ]"
          ></span>
          {{ activeRoom.status === 'active' ? 'Aktif' : 'Selesai' }}
        </span>
      </div>
    </div>

    <!-- Empty / No Room Selected -->
    <div
      v-if="!activeRoom"
      class="flex flex-1 flex-col items-center justify-center p-8 text-center text-gray-400"
    >
      <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-3">
        <Mountain class="h-8 w-8" />
      </div>
      <h3 class="text-base font-bold text-gray-700">Pilih Percakapan</h3>
      <p class="text-xs text-gray-400 max-w-sm mt-1">
        Pilih salah satu ruang obrolan pesanan di sebelah kiri untuk berkoordinasi dengan pengelola basecamp.
      </p>
    </div>

    <!-- Message Stream -->
    <div
      v-else
      ref="messageContainer"
      class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#F8FAF8]/50"
    >
      <div v-if="loading" class="flex justify-center py-8 text-gray-400">
        <Loader2 class="h-6 w-6 animate-spin text-[#1E3A2B]" />
      </div>

      <div
        v-else-if="messages.length === 0"
        class="flex flex-col items-center justify-center py-12 text-center text-gray-400"
      >
        <p class="text-xs">Belum ada percakapan. Mulai percakapan sekarang!</p>
      </div>

      <div
        v-for="msg in messages"
        :key="msg.id"
        :class="[
          'flex flex-col max-w-[75%]',
          msg.sender_id === currentUserId ? 'ml-auto items-end' : 'mr-auto items-start'
        ]"
      >
        <!-- Message Bubble -->
        <div
          :class="[
            'rounded-2xl px-4 py-2.5 text-xs shadow-xs break-words',
            msg.sender_id === currentUserId
              ? 'bg-[#1E3A2B] text-white rounded-br-xs'
              : 'bg-white text-gray-800 border border-gray-200 rounded-bl-xs'
          ]"
        >
          <!-- Attachment Image -->
          <div v-if="msg.attachment_url" class="mb-2 overflow-hidden rounded-lg">
            <img
              :src="msg.attachment_url"
              alt="Attachment"
              class="max-h-48 w-full object-cover rounded-lg"
            />
          </div>

          <p v-if="msg.message" class="leading-relaxed whitespace-pre-wrap">{{ msg.message }}</p>

          <!-- Time & Read Status -->
          <div
            :class="[
              'mt-1 flex items-center justify-end gap-1 text-[10px]',
              msg.sender_id === currentUserId ? 'text-white/70' : 'text-gray-400'
            ]"
          >
            <span>{{ formatTime(msg.created_at) }}</span>
            <template v-if="msg.sender_id === currentUserId">
              <CheckCheck v-if="msg.is_read" class="h-3 w-3 text-emerald-300" />
              <Check v-else class="h-3 w-3" />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Error Alert in Chat -->
    <div
      v-if="errorMessage"
      class="flex items-center gap-2 bg-red-50 px-4 py-2 text-xs text-red-700 border-t border-red-200"
    >
      <AlertCircle class="h-4 w-4 shrink-0" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Attachment Preview Bar -->
    <div
      v-if="attachmentPreview"
      class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-2"
    >
      <div class="flex items-center gap-2">
        <img :src="attachmentPreview" alt="Preview" class="h-10 w-10 rounded-lg object-cover border" />
        <span class="text-xs text-gray-600 truncate max-w-[200px]">{{ attachmentFile?.name }}</span>
      </div>
      <button
        type="button"
        class="rounded-lg p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 transition"
        @click="removeAttachment"
      >
        <X class="h-4 w-4" />
      </button>
    </div>

    <!-- Chat Input Form -->
    <form
      v-if="activeRoom && activeRoom.status === 'active'"
      @submit.prevent="handleSendMessage"
      class="border-t border-gray-200 bg-white p-3 flex items-center gap-2"
    >
      <!-- Attachment Trigger -->
      <label
        class="cursor-pointer rounded-xl p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
        title="Lampirkan Gambar"
      >
        <Image class="h-5 w-5" />
        <input
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFileChange"
        />
      </label>

      <!-- Text Input -->
      <input
        v-model="inputText"
        type="text"
        placeholder="Ketik pesan untuk basecamp..."
        class="flex-1 rounded-xl border border-gray-200 bg-gray-50/50 px-3.5 py-2 text-xs text-gray-900 placeholder:text-gray-400 focus:border-[#1E3A2B] focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
      />

      <!-- Send Button -->
      <button
        type="submit"
        :disabled="sending || (!inputText.trim() && !attachmentFile)"
        class="inline-flex items-center justify-center rounded-xl bg-[#1E3A2B] p-2 text-white hover:bg-[#15281e] disabled:opacity-40 transition shadow-xs"
      >
        <Loader2 v-if="sending" class="h-4 w-4 animate-spin" />
        <Send v-else class="h-4 w-4" />
      </button>
    </form>

    <div
      v-else-if="activeRoom && activeRoom.status === 'closed'"
      class="border-t border-gray-200 bg-gray-100 p-3 text-center text-xs text-gray-500"
    >
      Ruang percakapan ini telah ditutup karena pendakian telah selesai.
    </div>
  </div>
</template>
