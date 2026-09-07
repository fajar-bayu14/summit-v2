<script setup lang="ts">
import { computed } from 'vue'
import {
  Clock,
  Navigation,
  Mountain,
  Building2,
  ArrowRight,
  Gauge,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { JalurDetail, BasecampMitraSummary } from '@/types/pendakiMountain'

const props = withDefaults(
  defineProps<{
    trail: JalurDetail
    isSelected?: boolean
  }>(),
  {
    isSelected: false,
  }
)

const emit = defineEmits<{
  (e: 'select-trail', trail: JalurDetail): void
  (e: 'select-basecamp', basecamp: BasecampMitraSummary, trail: JalurDetail): void
}>()

const difficultyBadgeConfig = computed(() => {
  switch (props.trail.tingkat_kesulitan) {
    case 'mudah':
      return { label: 'Tingkat: Mudah', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' }
    case 'sedang':
      return { label: 'Tingkat: Sedang', class: 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300' }
    case 'sulit':
      return { label: 'Tingkat: Sulit', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' }
    case 'ekstrem':
      return { label: 'Tingkat: Ekstrem', class: 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300' }
    default:
      return { label: 'Sedang', class: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' }
  }
})

const isOpen = computed(() => props.trail.status === 'open')
const basecampList = computed(() => props.trail.basecamps || [])
</script>

<template>
  <div
    class="rounded-2xl border transition-all duration-300 overflow-hidden bg-white dark:bg-slate-900 shadow-xs"
    :class="[
      isSelected
        ? 'border-emerald-600 ring-2 ring-emerald-600/20 shadow-md'
        : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-md',
    ]"
  >
    <!-- Card Header -->
    <div class="p-5 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :class="isOpen ? 'bg-emerald-500' : 'bg-rose-500'"></span>
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">
              {{ trail.nama_jalur }}
            </h3>
          </div>
          <p v-if="trail.deskripsi" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
            {{ trail.deskripsi }}
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <Badge :class="difficultyBadgeConfig.class" class="text-[11px] font-semibold border-0">
            <Gauge class="w-3 h-3 mr-1" />
            {{ difficultyBadgeConfig.label }}
          </Badge>
          <Badge
            :class="isOpen ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300'"
            class="text-[11px] font-semibold"
          >
            {{ isOpen ? 'Buka' : 'Tutup' }}
          </Badge>
        </div>
      </div>

      <!-- Trail Stats Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs">
        <div class="space-y-0.5">
          <div class="flex items-center gap-1 text-slate-400">
            <Mountain class="w-3.5 h-3.5 text-emerald-600" />
            <span>Elevasi Jalur</span>
          </div>
          <p class="font-bold text-slate-800 dark:text-slate-200">
            {{ trail.titik_awal_mdpl || '1.000 MDPL' }} → {{ trail.titik_akhir_mdpl || 'Puncak' }}
          </p>
        </div>

        <div class="space-y-0.5">
          <div class="flex items-center gap-1 text-slate-400">
            <Navigation class="w-3.5 h-3.5 text-blue-600" />
            <span>Panjang Jalur</span>
          </div>
          <p class="font-bold text-slate-800 dark:text-slate-200">
            {{ trail.panjang_jalur || '8.5 Km' }}
          </p>
        </div>

        <div class="space-y-0.5 col-span-2 sm:col-span-1">
          <div class="flex items-center gap-1 text-slate-400">
            <Clock class="w-3.5 h-3.5 text-amber-600" />
            <span>Estimasi Waktu</span>
          </div>
          <p class="font-bold text-slate-800 dark:text-slate-200">
            {{ trail.waktu_tempuh || '6 - 7 Jam' }}
          </p>
        </div>
      </div>

      <!-- Action Button to Select Trail -->
      <div class="flex items-center justify-between pt-1">
        <span class="text-xs text-slate-500 flex items-center gap-1">
          <Building2 class="w-3.5 h-3.5 text-slate-400" />
          {{ basecampList.length }} Mitra Basecamp Terdaftar
        </span>

        <Button
          size="sm"
          :variant="isSelected ? 'default' : 'outline'"
          :disabled="!isOpen"
          class="rounded-xl text-xs font-semibold gap-1.5 shadow-xs"
          :class="isSelected ? 'bg-emerald-700 hover:bg-emerald-800 text-white' : 'hover:border-emerald-600 hover:text-emerald-700'"
          @click="emit('select-trail', trail)"
        >
          <span v-if="isSelected">Jalur Terpilih</span>
          <span v-else>Pilih Jalur Ini</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </Button>
      </div>
    </div>
  </div>
</template>
