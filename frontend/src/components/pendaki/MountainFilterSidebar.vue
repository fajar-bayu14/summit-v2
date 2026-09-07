<script setup lang="ts">
import { ref, watch } from 'vue'
import { Filter, RotateCcw, Mountain, MapPin, Gauge } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import type { MountainFilterParams, TrailDifficulty } from '@/types/pendakiMountain'

const props = defineProps<{
  initialFilters?: MountainFilterParams
}>()

const emit = defineEmits<{
  (e: 'filter-change', filters: MountainFilterParams): void
  (e: 'reset'): void
}>()

const selectedRegion = ref<string>(props.initialFilters?.provinsi || '')
const selectedElevationRange = ref<string>('all')
const selectedDifficulty = ref<TrailDifficulty | ''>(props.initialFilters?.kesulitan || '')

const regionOptions = [
  { label: 'Semua Wilayah', value: '' },
  { label: 'Jawa Tengah', value: 'Jawa Tengah' },
  { label: 'Jawa Timur', value: 'Jawa Timur' },
  { label: 'Jawa Barat', value: 'Jawa Barat' },
  { label: 'Luar Pulau Jawa', value: 'Luar Jawa' },
]

const elevationOptions = [
  { label: 'Semua Ketinggian', value: 'all', min: undefined, max: undefined },
  { label: 'Di bawah 2.000 MDPL (Pemula)', value: 'low', min: 0, max: 2000 },
  { label: '2.000 – 3.000 MDPL (Menengah)', value: 'medium', min: 2000, max: 3000 },
  { label: 'Di atas 3.000 MDPL (Tinggi)', value: 'high', min: 3000, max: 5000 },
]

const difficultyOptions: { label: string; value: TrailDifficulty | '' }[] = [
  { label: 'Semua Tingkat Kesulitan', value: '' },
  { label: 'Mudah', value: 'mudah' },
  { label: 'Sedang', value: 'sedang' },
  { label: 'Sulit', value: 'sulit' },
  { label: 'Ekstrem', value: 'ekstrem' },
]

function applyFilters() {
  const elev = elevationOptions.find(e => e.value === selectedElevationRange.value)
  const filters: MountainFilterParams = {
    provinsi: selectedRegion.value || undefined,
    min_mdpl: elev?.min,
    max_mdpl: elev?.max,
    kesulitan: selectedDifficulty.value || undefined,
  }
  emit('filter-change', filters)
}

function handleReset() {
  selectedRegion.value = ''
  selectedElevationRange.value = 'all'
  selectedDifficulty.value = ''
  emit('reset')
}

watch([selectedRegion, selectedElevationRange, selectedDifficulty], () => {
  applyFilters()
})
</script>

<template>
  <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-2 font-bold text-sm text-slate-900 dark:text-slate-100">
        <Filter class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
        <span>Filter Eksplorasi</span>
      </div>
      <Button
        variant="ghost"
        size="sm"
        class="h-8 px-2 text-xs text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 gap-1 rounded-lg"
        @click="handleReset"
      >
        <RotateCcw class="w-3.5 h-3.5" />
        <span>Reset</span>
      </Button>
    </div>

    <!-- Filter Section: Wilayah -->
    <div class="space-y-2.5">
      <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
        <MapPin class="w-3.5 h-3.5 text-orange-600" />
        <span>Wilayah / Provinsi</span>
      </Label>
      <div class="space-y-1.5">
        <label
          v-for="reg in regionOptions"
          :key="reg.value"
          class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 cursor-pointer p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
        >
          <input
            type="radio"
            name="region_filter"
            :value="reg.value"
            v-model="selectedRegion"
            class="text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5"
          />
          <span>{{ reg.label }}</span>
        </label>
      </div>
    </div>

    <!-- Filter Section: Ketinggian Elevasi -->
    <div class="space-y-2.5">
      <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
        <Mountain class="w-3.5 h-3.5 text-emerald-600" />
        <span>Ketinggian (MDPL)</span>
      </Label>
      <div class="space-y-1.5">
        <label
          v-for="opt in elevationOptions"
          :key="opt.value"
          class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 cursor-pointer p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
        >
          <input
            type="radio"
            name="elevation_filter"
            :value="opt.value"
            v-model="selectedElevationRange"
            class="text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5"
          />
          <span>{{ opt.label }}</span>
        </label>
      </div>
    </div>

    <!-- Filter Section: Tingkat Kesulitan -->
    <div class="space-y-2.5">
      <Label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
        <Gauge class="w-3.5 h-3.5 text-blue-600" />
        <span>Tingkat Kesulitan Jalur</span>
      </Label>
      <div class="space-y-1.5">
        <label
          v-for="diff in difficultyOptions"
          :key="diff.value"
          class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 cursor-pointer p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
        >
          <input
            type="radio"
            name="difficulty_filter"
            :value="diff.value"
            v-model="selectedDifficulty"
            class="text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5"
          />
          <span>{{ diff.label }}</span>
        </label>
      </div>
    </div>
  </div>
</template>
