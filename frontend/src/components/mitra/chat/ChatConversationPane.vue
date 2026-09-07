<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { Button } from '@/components/ui/button'
import {
  ChevronLeft,
  RefreshCw,
  Send,
  Image as ImageIcon,
  X,
  Check,
  CheckCheck,
  Mountain,
  MessageSquare,
  Sparkles,
} from 'lucide-vue-next'
import type { ChatRoom, ChatMessage, SendMessagePayload } from '@/types/chat'

const props = withDefaults(
  defineProps<{
    room: ChatRoom | null
    messages: ChatMessage[]
    loading?: boolean
    sending?: boolean
  }>(),
  {
    loading: false,
    sending: false,
  }
)

const emit = defineEmits<{
  (e: 'send', payload: SendMessagePayload): void
  (e: 'back'): void
  (e: 'refresh'): void
}>()

const inputText = ref('')
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)
const messagesContainerRef = ref<HTMLDivElement | null>(null)

const quickReplies = [
  'Halo, tenda & matras pesanan sudah siap diambil di basecamp ya.',
  'Harap membawa kartu identitas & surat sehat asli saat registrasi.',
  'Kondisi jalur saat ini terpantau cerah dan aman untuk pendakian.',
  'Silakan langsung menuju loket check-in setibanya di basecamp.',
]

watch(
  () => props.messages,
  () => {
    scrollToBottom()
  },
  { deep: true }
)

watch(
  () => props.room?.id,
  () => {
    inputText.value = ''
    clearAttachment()
    scrollToBottom()
  }
)

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainerRef.value) {
      messagesContainerRef.value.scrollTop = messagesContainerRef.value.scrollHeight
    }
  })
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    selectedFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

function clearAttachment() {
  selectedFile.value = null
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function insertQuickReply(text: string) {
  inputText.value = text
}

function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSend()
  }
}

function handleSend() {
  if (!inputText.value.trim() && !selectedFile.value) return

  emit('send', {
    message: inputText.value.trim() || null,
    attachment: selectedFile.value,
  })

  inputText.value = ''
  clearAttachment()
}

function formatMessageTime(dateStr?: string | null): string {
  if (!dateStr) return ''
  try {
    return new Intl.DateTimeFormat('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateStr))
  } catch {
    return ''
  }
}

function getInitials(name?: string | null): string {
  if (!name) return 'P'
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}
</script>

<template>
  <div class="flex flex-col h-full bg-stone-50/50">
    <!-- 1. Empty State (No room selected) -->
    <div
      v-if="!props.room"
      class="flex-1 flex flex-col items-center justify-center p-8 text-center text-stone-400 space-y-3"
    >
      <div class="w-16 h-16 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center">
        <MessageSquare class="w-8 h-8 text-stone-300" />
      </div>
      <h3 class="text-base font-bold text-stone-700">Pilih Ruang Obrolan</h3>
      <p class="text-xs text-stone-500 max-w-sm">
        Pilih percakapan dari daftar di sebelah kiri untuk mulai berkoordinasi langsung dengan pendaki.
      </p>
    </div>

    <!-- 2. Active Room Conversation -->
    <template v-else>
      <!-- Chat Room Header -->
      <div class="p-3.5 bg-white border-b border-stone-200 flex items-center justify-between shrink-0 shadow-xs">
        <div class="flex items-center gap-3 min-w-0">
          <!-- Mobile Back Button -->
          <button
            type="button"
            class="md:hidden p-1.5 -ml-1 text-stone-600 hover:text-stone-900 rounded-lg hover:bg-stone-100"
            @click="emit('back')"
          >
            <ChevronLeft class="w-5 h-5" />
          </button>

          <!-- Avatar -->
          <div class="relative shrink-0">
            <img
              v-if="props.room.pendaki?.avatar"
              :src="props.room.pendaki.avatar"
              alt="Avatar"
              class="w-10 h-10 rounded-full object-cover border border-stone-200"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-emerald-100 text-[#1E3A2B] flex items-center justify-center font-bold text-xs border border-emerald-200"
            >
              {{ getInitials(props.room.pendaki?.name) }}
            </div>
          </div>

          <!-- Title & Context -->
          <div class="min-w-0">
            <h3 class="font-bold text-sm text-stone-900 truncate">
              {{ props.room.pendaki?.name || 'Pendaki' }}
            </h3>
            <div class="flex items-center gap-2 text-[11px] text-stone-500 truncate">
              <span class="font-semibold text-stone-700 bg-stone-100 px-1.5 py-0.5 rounded">
                {{ props.room.invoice || 'INV' }}
              </span>
              <span v-if="props.room.gunung_nama" class="flex items-center gap-1 truncate text-stone-600">
                <Mountain class="w-3 h-3 text-[#1E3A2B] shrink-0" />
                {{ props.room.gunung_nama }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-1 shrink-0">
          <Button
            variant="ghost"
            size="sm"
            class="h-8 w-8 p-0 rounded-lg text-stone-600 hover:bg-stone-100"
            :disabled="props.loading"
            title="Muat Ulang Pesan"
            @click="emit('refresh')"
          >
            <RefreshCw :class="['w-4 h-4', props.loading ? 'animate-spin' : '']" />
          </Button>
        </div>
      </div>

      <!-- Messages Stream Scroll Area -->
      <div
        ref="messagesContainerRef"
        class="flex-1 overflow-y-auto p-4 space-y-3"
      >
        <div v-if="props.loading && props.messages.length === 0" class="space-y-3">
          <div v-for="i in 4" :key="i" class="h-12 bg-stone-200/50 rounded-2xl animate-pulse" />
        </div>

        <div
          v-else-if="props.messages.length === 0"
          class="h-full flex flex-col items-center justify-center text-center text-stone-400 p-8 space-y-2 text-xs"
        >
          <MessageSquare class="w-7 h-7 text-stone-300" />
          <p class="font-medium">Belum ada pesan di ruang obrolan ini</p>
          <p class="text-[11px] text-stone-400 max-w-xs">
            Sampaikan salam atau konfirmasi logistik awal kepada pendaki.
          </p>
        </div>

        <div
          v-for="msg in props.messages"
          :key="msg.id"
          :class="['flex flex-col', msg.is_mine ? 'items-end' : 'items-start']"
        >
          <!-- Message Bubble -->
          <div
            :class="[
              'max-w-[85%] sm:max-w-[70%] rounded-2xl p-3 text-xs shadow-xs relative space-y-1.5',
              msg.is_mine
                ? 'bg-[#1E3A2B] text-white rounded-br-xs'
                : 'bg-white text-stone-800 border border-stone-200 rounded-bl-xs',
            ]"
          >
            <!-- Sender Name if not mine -->
            <div
              v-if="!msg.is_mine"
              class="font-bold text-[10px] text-emerald-800 mb-0.5"
            >
              {{ msg.sender_name || 'Pendaki' }}
            </div>

            <!-- Image Attachment Preview -->
            <div v-if="msg.attachment_url" class="rounded-xl overflow-hidden bg-black/5">
              <a
                :href="msg.attachment_url"
                target="_blank"
                rel="noopener noreferrer"
                class="block group relative"
              >
                <img
                  :src="msg.attachment_url"
                  alt="Lampiran"
                  class="w-full max-h-56 object-cover group-hover:scale-105 transition-transform duration-200"
                />
              </a>
            </div>

            <!-- Text Message -->
            <p v-if="msg.message" class="whitespace-pre-wrap leading-relaxed font-normal">
              {{ msg.message }}
            </p>

            <!-- Timestamp & Read Status -->
            <div
              :class="[
                'flex items-center justify-end gap-1 text-[9px] pt-0.5',
                msg.is_mine ? 'text-white/70' : 'text-stone-400',
              ]"
            >
              <span>{{ formatMessageTime(msg.created_at) }}</span>
              <template v-if="msg.is_mine">
                <CheckCheck v-if="msg.is_read" class="w-3 h-3 text-emerald-300" />
                <Check v-else class="w-3 h-3 text-white/60" />
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Reply Templates Bar -->
      <div class="px-4 py-2 bg-white/80 backdrop-blur-xs border-t border-stone-100 flex items-center gap-1.5 overflow-x-auto scrollbar-none shrink-0">
        <span class="text-[10px] font-bold text-stone-400 flex items-center gap-1 shrink-0">
          <Sparkles class="w-3 h-3 text-amber-500" />
          Template Cepat:
        </span>
        <button
          v-for="(reply, idx) in quickReplies"
          :key="idx"
          type="button"
          class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg text-[11px] whitespace-nowrap cursor-pointer transition-colors shrink-0"
          @click="insertQuickReply(reply)"
        >
          {{ reply }}
        </button>
      </div>

      <!-- Attachment Preview (Before Send) -->
      <div
        v-if="previewUrl"
        class="px-4 py-2 bg-stone-100 border-t border-stone-200 flex items-center gap-3 shrink-0"
      >
        <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-stone-300 bg-stone-900">
          <img :src="previewUrl" alt="Preview" class="w-full h-full object-cover" />
          <button
            type="button"
            class="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5 hover:bg-black"
            @click="clearAttachment"
          >
            <X class="w-3 h-3" />
          </button>
        </div>
        <div class="text-[11px] text-stone-600 truncate">
          <span class="font-bold">Foto terpilih:</span> {{ selectedFile?.name }}
        </div>
      </div>

      <!-- Chat Input Area -->
      <div class="p-3 bg-white border-t border-stone-200 shrink-0">
        <form class="flex items-end gap-2" @submit.prevent="handleSend">
          <!-- Hidden File Input -->
          <input
            ref="fileInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleFileSelect"
          />

          <!-- Attachment Trigger Button -->
          <Button
            type="button"
            variant="ghost"
            class="h-10 w-10 p-0 rounded-xl text-stone-500 hover:text-stone-800 hover:bg-stone-100 shrink-0 min-h-[40px]"
            title="Kirim Foto Lampiran"
            @click="fileInputRef?.click()"
          >
            <ImageIcon class="w-5 h-5" />
          </Button>

          <!-- Text Input Area -->
          <div class="flex-1">
            <textarea
              v-model="inputText"
              placeholder="Tulis pesan ke pendaki... (Tekan Enter untuk kirim)"
              rows="1"
              class="w-full p-2.5 bg-stone-50 hover:bg-stone-100/70 focus:bg-white border border-stone-200 focus:border-[#1E3A2B] rounded-xl text-xs resize-none focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/10 max-h-28 transition-colors"
              @keydown="handleKeyDown"
            ></textarea>
          </div>

          <!-- Send Button -->
          <Button
            type="submit"
            :disabled="(!inputText.trim() && !selectedFile) || props.sending"
            class="h-10 px-4 rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white font-bold text-xs shrink-0 min-h-[40px]"
          >
            <Send class="w-4 h-4 mr-1.5" />
            <span>Kirim</span>
          </Button>
        </form>
      </div>
    </template>
  </div>
</template>
