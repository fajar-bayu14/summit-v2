<script setup lang="ts">
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Search, MessageSquare, Mountain, X } from 'lucide-vue-next'
import type { ChatRoom } from '@/types/chat'

const props = withDefaults(
  defineProps<{
    rooms: ChatRoom[]
    activeRoomId?: number | null
    loading?: boolean
  }>(),
  {
    activeRoomId: null,
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'selectRoom', room: ChatRoom): void
}>()

const searchQuery = ref('')

const filteredRooms = computed(() => {
  if (!searchQuery.value.trim()) return props.rooms
  const q = searchQuery.value.toLowerCase().trim()
  return props.rooms.filter((room) => {
    const name = (room.pendaki?.name || '').toLowerCase()
    const inv = (room.invoice || '').toLowerCase()
    const gunung = (room.gunung_nama || '').toLowerCase()
    return name.includes(q) || inv.includes(q) || gunung.includes(q)
  })
})

function formatTime(dateStr?: string | null): string {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    const now = new Date()
    const isToday =
      d.getDate() === now.getDate() &&
      d.getMonth() === now.getMonth() &&
      d.getFullYear() === now.getFullYear()

    if (isToday) {
      return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
      }).format(d)
    }

    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
    }).format(d)
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
  <div class="flex flex-col h-full bg-white border-r border-stone-200">
    <!-- Header & Search -->
    <div class="p-3.5 border-b border-stone-200 space-y-2.5 shrink-0">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-stone-900 text-sm flex items-center gap-2">
          <MessageSquare class="w-4 h-4 text-[#1E3A2B]" />
          Daftar Percakapan
        </h3>
        <span class="text-[11px] font-bold text-stone-500 bg-stone-100 px-2 py-0.5 rounded-full">
          {{ props.rooms.length }} Ruang
        </span>
      </div>

      <div class="relative">
        <Search class="w-3.5 h-3.5 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
        <Input
          v-model="searchQuery"
          type="text"
          placeholder="Cari pendaki atau invoice..."
          class="pl-8 h-9 rounded-xl text-xs border-stone-200 focus:border-[#1E3A2B]"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-700"
          @click="searchQuery = ''"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Room List Stream -->
    <div class="flex-1 overflow-y-auto divide-y divide-stone-100">
      <div v-if="props.loading" class="p-4 space-y-3">
        <div v-for="i in 5" :key="i" class="h-14 bg-stone-100 rounded-xl animate-pulse" />
      </div>

      <div
        v-else-if="filteredRooms.length === 0"
        class="p-8 text-center text-stone-400 space-y-2 text-xs"
      >
        <MessageSquare class="w-8 h-8 mx-auto text-stone-300" />
        <p class="font-medium">Tidak ada percakapan ditemukan</p>
      </div>

      <div
        v-for="room in filteredRooms"
        :key="room.id"
        :class="[
          'p-3.5 flex items-start gap-3 cursor-pointer transition-colors relative',
          props.activeRoomId === room.id
            ? 'bg-emerald-50/70 border-l-4 border-[#1E3A2B]'
            : 'hover:bg-stone-50/80',
        ]"
        @click="emit('selectRoom', room)"
      >
        <!-- Avatar / Placeholder -->
        <div class="relative shrink-0">
          <img
            v-if="room.pendaki?.avatar"
            :src="room.pendaki.avatar"
            alt="Avatar"
            class="w-10 h-10 rounded-full object-cover border border-stone-200"
          />
          <div
            v-else
            class="w-10 h-10 rounded-full bg-emerald-100 text-[#1E3A2B] flex items-center justify-center font-bold text-xs border border-emerald-200"
          >
            {{ getInitials(room.pendaki?.name) }}
          </div>

          <!-- Unread Dot on Avatar for quick glance -->
          <span
            v-if="room.unread_count > 0"
            class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-[#E65100] border-2 border-white rounded-full"
          />
        </div>

        <!-- Room Information -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-1 mb-0.5">
            <h4 class="font-bold text-xs text-stone-900 truncate">
              {{ room.pendaki?.name || 'Pendaki' }}
            </h4>
            <span class="text-[10px] text-stone-400 shrink-0">
              {{ formatTime(room.last_message_at || room.created_at) }}
            </span>
          </div>

          <!-- Invoice & Mountain Pill -->
          <div class="flex items-center gap-1.5 text-[10px] text-stone-500 mb-1">
            <span class="font-semibold text-stone-700 bg-stone-100 px-1.5 py-0.5 rounded">
              {{ room.invoice || 'INV' }}
            </span>
            <span v-if="room.gunung_nama" class="flex items-center gap-0.5 truncate text-stone-600">
              <Mountain class="w-3 h-3 text-[#1E3A2B] shrink-0" />
              {{ room.gunung_nama }}
            </span>
          </div>

          <!-- Last Message Snippet & Badge -->
          <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] text-stone-500 truncate line-clamp-1">
              <span v-if="room.last_message?.is_mine" class="font-semibold text-stone-700">Anda: </span>
              {{ room.last_message?.message || (room.last_message?.attachment_url ? '📷 [Foto Lampiran]' : 'Belum ada pesan...') }}
            </p>

            <span
              v-if="room.unread_count > 0"
              class="min-w-[18px] h-[18px] px-1 bg-[#E65100] text-white text-[10px] font-extrabold rounded-full flex items-center justify-center shrink-0"
            >
              {{ room.unread_count }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
