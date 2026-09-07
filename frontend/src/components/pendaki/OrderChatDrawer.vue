<script setup lang="ts">
import { ref, watch } from 'vue'
import { pendakiChatApi } from '@/api/pendakiChat'
import { useAuthStore } from '@/stores/auth'
import { extractApiError } from '@/lib/normalizer'
import type { ChatRoom } from '@/types/pendakiChat'
import ChatConversationPane from './ChatConversationPane.vue'
import { Loader2, MessageSquare, X } from 'lucide-vue-next'

const props = defineProps<{
  isOpen: boolean
  pesananId: number
  invoice?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const authStore = useAuthStore()
const activeRoom = ref<ChatRoom | null>(null)
const loading = ref(false)
const errorMessage = ref('')

async function initializeRoom() {
  if (!props.pesananId) return
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await pendakiChatApi.createOrGetRoom({ pesanan_id: props.pesananId })
    activeRoom.value = res.data || null
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    loading.value = false
  }
}

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      initializeRoom()
    } else {
      activeRoom.value = null
    }
  },
  { immediate: true }
)
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
      @click="emit('close')"
    ></div>

    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
      <div
        class="w-screen max-w-md transform bg-white shadow-2xl transition ease-in-out duration-300 flex flex-col"
      >
        <!-- Drawer Top Header -->
        <div class="flex items-center justify-between border-b border-gray-100 bg-[#1E3A2B] px-5 py-4 text-white">
          <div class="flex items-center gap-2">
            <MessageSquare class="h-5 w-5 text-emerald-300" />
            <div>
              <h3 class="text-sm font-bold">Obrolan Basecamp</h3>
              <p class="text-[11px] text-emerald-200">Pesanan: {{ invoice || `#${pesananId}` }}</p>
            </div>
          </div>
          <button
            type="button"
            class="rounded-lg p-1 text-emerald-100 hover:bg-white/10 hover:text-white transition"
            @click="emit('close')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Drawer Content -->
        <div class="flex-1 flex flex-col p-3 overflow-hidden bg-[#F8FAF8]">
          <div v-if="loading" class="flex flex-1 flex-col items-center justify-center text-gray-400">
            <Loader2 class="h-8 w-8 animate-spin text-[#1E3A2B] mb-2" />
            <p class="text-xs">Menghubungkan ke ruang obrolan...</p>
          </div>

          <div
            v-else-if="errorMessage"
            class="m-4 rounded-xl bg-red-50 p-4 text-center text-xs text-red-700 border border-red-200"
          >
            <p>{{ errorMessage }}</p>
            <button
              @click="initializeRoom"
              class="mt-2 inline-flex rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white"
            >
              Coba Lagi
            </button>
          </div>

          <ChatConversationPane
            v-else
            :active-room="activeRoom"
            :current-user-id="authStore.user?.id || 0"
            class="flex-1"
          />
        </div>
      </div>
    </div>
  </div>
</template>
