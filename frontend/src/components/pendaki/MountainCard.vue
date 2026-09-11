<script setup lang="ts">
import { computed } from 'vue'
import { Mountain, MapPin, Compass, ArrowRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatMdpl } from '@/lib/formatters'
import type { GunungItem } from '@/types/pendakiMountain'

const props = defineProps<{
  mountain: GunungItem
}>()

const emit = defineEmits<{
  (e: 'select', mountain: GunungItem): void
}>()

const activeTrailsCount = computed(() => {
  if (!props.mountain.jalurs) return 0
  return props.mountain.jalurs.filter(j => j.status === 'open').length
})

const isMountainActive = computed(() => {
  return props.mountain.status === 'aktif' || props.mountain.status === 'open'
})
</script>

<template>
  <div
    class="group relative flex flex-col rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
  >
    <!-- Mountain Photo / Fallback Hero -->
    <div class="relative h-48 sm:h-52 w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
      <img
        v-if="mountain.foto"
        :src="mountain.foto"
        :alt="mountain.nama_gunung"
        class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out"
        loading="lazy"
        @error="e => ((e.target as HTMLImageElement).src = '/images/hero-summit.jpg')"
      />
      <div
        v-else
        class="h-full w-full flex flex-col items-center justify-center bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900 text-slate-300"
      >
        <Mountain class="w-12 h-12 text-emerald-400/60 mb-1" />
        <span class="text-xs font-medium text-emerald-200/70">Summit Destination</span>
      </div>

      <!-- Elevation Badge Overlay -->
      <div class="absolute top-3 left-3">
        <Badge class="bg-slate-900/85 text-white backdrop-blur-md border-0 text-xs font-bold px-2.5 py-1 shadow-md">
          {{ formatMdpl(mountain.tinggi_mdpl) }}
        </Badge>
      </div>

      <!-- Active Status Badge Overlay -->
      <div class="absolute top-3 right-3">
        <Badge
          v-if="isMountainActive"
          class="bg-emerald-600 text-white font-semibold text-[11px] px-2 py-0.5 shadow-sm"
        >
          Jalur Buka
        </Badge>
        <Badge
          v-else
          class="bg-rose-600 text-white font-semibold text-[11px] px-2 py-0.5 shadow-sm"
        >
          Tutup Sementara
        </Badge>
      </div>
    </div>

    <!-- Mountain Content Body -->
    <div class="flex flex-1 flex-col p-5 justify-between space-y-4">
      <div class="space-y-2">
        <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
          <MapPin class="w-3.5 h-3.5 shrink-0 text-orange-600" />
          <span class="truncate font-medium">{{ mountain.lokasi }}</span>
        </div>

        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors line-clamp-1">
          {{ mountain.nama_gunung }}
        </h3>

        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
          {{ mountain.deskripsi || 'Nikmati keindahan panorama puncak dan sensasi petualangan alam terbuka yang menantang.' }}
        </p>
      </div>

      <!-- Footer Info & Action CTA -->
      <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
        <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
          <Compass class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <span>
            <strong class="font-bold text-slate-900 dark:text-slate-100">{{ activeTrailsCount }}</strong> Jalur Resmi
          </span>
        </div>

        <Button
          size="sm"
          class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs gap-1.5 shadow-xs group/btn"
          @click="emit('select', mountain)"
        >
          <span>Pilih Jalur</span>
          <ArrowRight class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform" />
        </Button>
      </div>
    </div>
  </div>
</template>
