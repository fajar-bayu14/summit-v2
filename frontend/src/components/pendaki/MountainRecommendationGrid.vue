<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'
import type { GunungItem } from '@/types/pendakiMountain'
import MountainCard from './MountainCard.vue'
import { Compass, Loader2, Mountain, Sparkles } from 'lucide-vue-next'

const mountains = ref<GunungItem[]>([])
const loading = ref(true)
const activeCategory = ref<'all' | 'beginner' | 'seven_summits' | 'scenic'>('all')

const categories = [
  { id: 'all', label: 'Semua Destinasi' },
  { id: 'beginner', label: '🌱 Ramah Pemula' },
  { id: 'seven_summits', label: '👑 7 Summits Indonesia' },
  { id: 'scenic', label: '🌅 Sunrise & Pemandangan Terbaik' }
]

async function fetchMountains() {
  loading.value = true
  try {
    const res = await pendakiMountainsApi.getMountains({ per_page: 8 })
    mountains.value = (res.data as any)?.items || res.data || []
  } catch {
    mountains.value = []
  } finally {
    loading.value = false
  }
}

const filteredMountains = computed(() => {
  if (activeCategory.value === 'beginner') {
    return mountains.value.filter(
      (m) => (m.tinggi_mdpl || (m as any).elevasi_mdpl || 0) <= 2600
    )
  }
  if (activeCategory.value === 'seven_summits') {
    return mountains.value.filter((m) => (m.tinggi_mdpl || (m as any).elevasi_mdpl || 0) >= 3000)
  }
  if (activeCategory.value === 'scenic') {
    return mountains.value.slice(0, 4)
  }
  return mountains.value
})

onMounted(() => {
  fetchMountains()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
            <Mountain class="h-5 w-5 text-[#1E3A2B]" />
            <span>Rekomendasi Gunung Terpopuler</span>
          </h3>
          <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
            <Sparkles class="h-3 w-3" />
            Terverifikasi
          </span>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">
          Eksplorasi jalur resmi, status kuota harian, dan basecamp pengelola di seluruh Indonesia.
        </p>
      </div>

      <router-link
        to="/pendaki/gunung"
        class="text-xs font-bold text-[#1E3A2B] hover:text-[#15281e] flex items-center gap-1 transition"
      >
        <span>Lihat Semua Katalog</span>
        <Compass class="h-3.5 w-3.5" />
      </router-link>
    </div>

    <!-- Filter Category Chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
      <button
        v-for="cat in categories"
        :key="cat.id"
        type="button"
        :class="[
          'px-3.5 py-1.5 text-xs font-bold rounded-full transition whitespace-nowrap border',
          activeCategory === cat.id
            ? 'bg-[#1E3A2B] text-white border-[#1E3A2B] shadow-xs'
            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
        ]"
        @click="activeCategory = cat.id as any"
      >
        {{ cat.label }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12 text-gray-400">
      <Loader2 class="h-8 w-8 animate-spin text-[#1E3A2B]" />
    </div>

    <!-- Mountain Cards Grid -->
    <div
      v-else-if="filteredMountains.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
    >
      <MountainCard
        v-for="mountain in filteredMountains"
        :key="mountain.id"
        :mountain="mountain"
      />
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center text-xs text-gray-400"
    >
      Tidak ada gunung yang sesuai dengan kategori terpilih.
    </div>
  </div>
</template>
