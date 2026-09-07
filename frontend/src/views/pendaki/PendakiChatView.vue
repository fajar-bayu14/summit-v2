<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { pendakiChatApi } from '@/api/pendakiChat'
import { extractApiError } from '@/lib/normalizer'
import type { ChatRoom } from '@/types/pendakiChat'
import ChatConversationPane from '@/components/pendaki/ChatConversationPane.vue'
import {
  ChevronLeft,
  Loader2,
  MessageSquare,
  Mountain,
  Search
} from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()

const rooms = ref<ChatRoom[]>([])
const activeRoom = ref<ChatRoom | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')
const showMobileChat = ref(false)

const currentUserId = computed(() => authStore.user?.id || 0)

const filteredRooms = computed(() => {
  if (!searchQuery.value.trim()) return rooms.value
  const q = searchQuery.value.toLowerCase()
  return rooms.value.filter((room) => {
    const partner = (room.mitra?.name || room.mitra?.basecamp_nama || '').toLowerCase()
    const mountain = (room.gunung_nama || room.pesanan?.jalur?.gunung?.nama_gunung || '').toLowerCase()
    const invoice = (room.invoice || room.pesanan?.invoice || '').toLowerCase()
    return partner.includes(q) || mountain.includes(q) || invoice.includes(q)
  })
})

async function fetchRooms() {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await pendakiChatApi.getRooms()
    rooms.value = res.data?.items || (res.data as any)?.data || []

    // If order_id is present in query, create or select room
    const orderId = route.query.order_id
    if (orderId) {
      await handleOpenOrderByQuery(Number(orderId))
    } else if (rooms.value.length > 0 && !activeRoom.value) {
      activeRoom.value = rooms.value[0] || null
    }
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    loading.value = false
  }
}

async function handleOpenOrderByQuery(pesananId: number) {
  try {
    const res = await pendakiChatApi.createOrGetRoom({ pesanan_id: pesananId })
    const room = res.data
    if (room) {
      const existingIdx = rooms.value.findIndex((r) => r.id === room.id)
      if (existingIdx >= 0) {
        rooms.value[existingIdx] = room
      } else {
        rooms.value.unshift(room)
      }
      activeRoom.value = room
      showMobileChat.value = true
    }
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  }
}

function selectRoom(room: ChatRoom) {
  activeRoom.value = room
  showMobileChat.value = true
}

onMounted(() => {
  fetchRooms()
})
</script>

<template>
  <div class="min-h-screen bg-[#F8FAF8] py-6">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumb & Title -->
      <div class="mb-4">
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
          <router-link to="/pendaki" class="hover:text-[#1E3A2B] transition">Beranda</router-link>
          <span>/</span>
          <span class="text-[#1E3A2B]">In-App Chat Basecamp</span>
        </div>
        <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
          <MessageSquare class="h-6 w-6 text-[#1E3A2B]" />
          <span>Obrolan Langsung Mitra Basecamp</span>
        </h1>
      </div>

      <!-- Chat Layout Grid -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 h-[calc(100vh-200px)] min-h-[500px]">
        <!-- Left Sidebar: Room List -->
        <div
          :class="[
            'md:col-span-4 flex flex-col bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs',
            showMobileChat ? 'hidden md:flex' : 'flex'
          ]"
        >
          <!-- Search Header -->
          <div class="p-3 border-b border-gray-100 bg-slate-50/75">
            <div class="relative">
              <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari basecamp, gunung, invoice..."
                class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-3 py-1.5 text-xs text-gray-900 placeholder:text-gray-400 focus:border-[#1E3A2B] focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
              />
            </div>
          </div>

          <!-- Rooms List -->
          <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            <div v-if="loading" class="flex justify-center py-10 text-gray-400">
              <Loader2 class="h-6 w-6 animate-spin text-[#1E3A2B]" />
            </div>

            <div
              v-else-if="filteredRooms.length === 0"
              class="flex flex-col items-center justify-center p-8 text-center text-gray-400"
            >
              <MessageSquare class="h-8 w-8 mb-2 text-gray-300" />
              <p class="text-xs">Tidak ada riwayat percakapan.</p>
            </div>

            <button
              v-for="room in filteredRooms"
              :key="room.id"
              type="button"
              :class="[
                'w-full text-left p-3.5 flex items-center gap-3 transition',
                activeRoom?.id === room.id
                  ? 'bg-[#1E3A2B]/5 border-l-4 border-l-[#1E3A2B]'
                  : 'hover:bg-gray-50'
              ]"
              @click="selectRoom(room)"
            >
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-[#1E3A2B] font-bold text-sm">
                {{ (room.mitra?.name || room.mitra?.basecamp_nama || 'B').charAt(0).toUpperCase() }}
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1 mb-0.5">
                  <h4 class="text-xs font-bold text-gray-900 truncate">
                    {{ room.mitra?.name || room.mitra?.basecamp_nama || 'Basecamp Mitra' }}
                  </h4>
                  <span
                    v-if="(room.unread_count ?? 0) > 0"
                    class="flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#E65100] px-1 text-[10px] font-bold text-white"
                  >
                    {{ room.unread_count }}
                  </span>
                </div>

                <p class="text-[11px] text-gray-500 truncate flex items-center gap-1">
                  <Mountain class="h-3 w-3 text-gray-400 shrink-0" />
                  <span>{{ room.gunung_nama || room.pesanan?.jalur?.gunung?.nama_gunung || 'Gunung' }}</span>
                  <span v-if="room.invoice || room.pesanan?.invoice" class="text-gray-400">
                    • {{ room.invoice || room.pesanan?.invoice }}
                  </span>
                </p>
              </div>
            </button>
          </div>
        </div>

        <!-- Right Panel: Conversation Pane -->
        <div
          :class="[
            'md:col-span-8 flex flex-col h-full',
            !showMobileChat ? 'hidden md:flex' : 'flex'
          ]"
        >
          <!-- Mobile Back Button -->
          <div v-if="showMobileChat" class="md:hidden mb-2">
            <button
              @click="showMobileChat = false"
              class="inline-flex items-center gap-1 rounded-xl bg-white px-3 py-1.5 text-xs font-bold text-gray-700 border border-gray-200 shadow-xs"
            >
              <ChevronLeft class="h-4 w-4" />
              <span>Kembali ke Daftar Percakapan</span>
            </button>
          </div>

          <ChatConversationPane
            :active-room="activeRoom"
            :current-user-id="currentUserId"
            @message-sent="fetchRooms"
          />
        </div>
      </div>
    </div>
  </div>
</template>
