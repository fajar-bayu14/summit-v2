<script setup lang="ts">
import { computed } from 'vue'
import {
  ChevronLeft,
  ChevronRight,
  Calendar as CalendarIcon,
  RefreshCw,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { KuotaHarian } from '@/types/quota'

export interface CalendarDayCell {
  dateStr: string
  dayNumber: number
  isCurrentMonth: boolean
  isToday: boolean
  isPast: boolean
  quota?: KuotaHarian | null
}

const props = withDefaults(
  defineProps<{
    year: number
    month: number // 1 to 12
    quotas?: KuotaHarian[]
    loading?: boolean
  }>(),
  {
    quotas: () => [],
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'prevMonth'): void
  (e: 'nextMonth'): void
  (e: 'today'): void
  (e: 'selectDate', payload: { dateStr: string; quota?: KuotaHarian | null }): void
}>()

const monthNames = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
]

const currentMonthLabel = computed(() => {
  return `${monthNames[props.month - 1]} ${props.year}`
})

const daysOfWeek = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const quotasMap = computed(() => {
  const map = new Map<string, KuotaHarian>()
  props.quotas.forEach((q) => {
    map.set(q.tanggal, q)
  })
  return map
})

const calendarDays = computed<CalendarDayCell[]>(() => {
  const result: CalendarDayCell[] = []
  const today = new Date()
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  const firstDayOfMonth = new Date(props.year, props.month - 1, 1)
  const lastDayOfMonth = new Date(props.year, props.month, 0)
  const totalDaysInMonth = lastDayOfMonth.getDate()

  // Convert getDay() (0=Sun, 1=Mon, ..., 6=Sat) to Monday-based index (0=Mon, ..., 6=Sun)
  let firstDayIndex = firstDayOfMonth.getDay() - 1
  if (firstDayIndex === -1) firstDayIndex = 6

  // Previous month trailing days
  const prevMonthLastDate = new Date(props.year, props.month - 1, 0).getDate()
  for (let i = firstDayIndex - 1; i >= 0; i--) {
    const dayNum = prevMonthLastDate - i
    const prevMonthNum = props.month === 1 ? 12 : props.month - 1
    const prevYearNum = props.month === 1 ? props.year - 1 : props.year
    const dateStr = `${prevYearNum}-${String(prevMonthNum).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`

    result.push({
      dateStr,
      dayNumber: dayNum,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      isPast: dateStr < todayStr,
      quota: quotasMap.value.get(dateStr) || null,
    })
  }

  // Current month days
  for (let d = 1; d <= totalDaysInMonth; d++) {
    const dateStr = `${props.year}-${String(props.month).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    result.push({
      dateStr,
      dayNumber: d,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      isPast: dateStr < todayStr,
      quota: quotasMap.value.get(dateStr) || null,
    })
  }

  // Next month leading days to fill full weeks (multiple of 7)
  const remainingCells = (7 - (result.length % 7)) % 7
  for (let nextDay = 1; nextDay <= remainingCells; nextDay++) {
    const nextMonthNum = props.month === 12 ? 1 : props.month + 1
    const nextYearNum = props.month === 12 ? props.year + 1 : props.year
    const dateStr = `${nextYearNum}-${String(nextMonthNum).padStart(2, '0')}-${String(nextDay).padStart(2, '0')}`

    result.push({
      dateStr,
      dayNumber: nextDay,
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      isPast: dateStr < todayStr,
      quota: quotasMap.value.get(dateStr) || null,
    })
  }

  return result
})

function handleCellClick(cell: CalendarDayCell) {
  emit('selectDate', {
    dateStr: cell.dateStr,
    quota: cell.quota,
  })
}

function getQuotaStatusClass(quota: KuotaHarian): {
  bg: string
  text: string
  label: string
} {
  const remaining = quota.kuota_tersisa
  const total = quota.kuota_total
  if (total === 0 || remaining === 0) {
    return {
      bg: 'bg-red-50 border-red-200',
      text: 'text-red-700',
      label: 'Penuh (0 sisa)',
    }
  }

  const ratio = remaining / total
  if (ratio <= 0.3) {
    return {
      bg: 'bg-amber-50 border-amber-200',
      text: 'text-amber-800',
      label: `Sisa ${remaining}`,
    }
  }

  return {
    bg: 'bg-emerald-50 border-emerald-200',
    text: 'text-emerald-800',
    label: `Sisa ${remaining}`,
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
    <!-- Calendar Navigation Header -->
    <div class="flex flex-col sm:flex-row items-center justify-between p-4 border-b border-stone-200 gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center font-bold">
          <CalendarIcon class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-stone-900 capitalize">
            {{ currentMonthLabel }}
          </h2>
          <p class="text-xs text-stone-500">
            Klik pada tanggal untuk menyesuaikan kuota hari tersebut
          </p>
        </div>
      </div>

      <!-- Controls -->
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="h-9 px-3 text-xs rounded-xl border-stone-200 hover:bg-stone-100"
          :disabled="props.loading"
          @click="emit('today')"
        >
          Hari Ini
        </Button>
        <div class="flex items-center border border-stone-200 rounded-xl overflow-hidden">
          <Button
            variant="ghost"
            size="icon"
            class="h-9 w-9 rounded-none hover:bg-stone-100"
            :disabled="props.loading"
            @click="emit('prevMonth')"
          >
            <ChevronLeft class="w-4 h-4" />
          </Button>
          <div class="w-[1px] h-5 bg-stone-200"></div>
          <Button
            variant="ghost"
            size="icon"
            class="h-9 w-9 rounded-none hover:bg-stone-100"
            :disabled="props.loading"
            @click="emit('nextMonth')"
          >
            <ChevronRight class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Days of Week Header -->
    <div class="grid grid-cols-7 bg-stone-50 border-b border-stone-200 text-center text-xs font-bold text-stone-600 py-2.5">
      <div v-for="day in daysOfWeek" :key="day">
        {{ day }}
      </div>
    </div>

    <!-- Loading State Overlay -->
    <div v-if="props.loading" class="p-12 text-center text-stone-500 space-y-2">
      <RefreshCw class="w-7 h-7 animate-spin mx-auto text-[#1E3A2B]" />
      <p class="text-xs font-medium">Memuat ketersediaan kuota...</p>
    </div>

    <!-- Calendar Grid Cells -->
    <div v-else class="grid grid-cols-7 divide-x divide-y divide-stone-200">
      <div
        v-for="cell in calendarDays"
        :key="cell.dateStr"
        :class="[
          'min-h-[100px] p-2 flex flex-col justify-between transition-colors cursor-pointer group',
          !cell.isCurrentMonth && 'bg-stone-50/60 opacity-60',
          cell.isCurrentMonth && !cell.isPast && 'hover:bg-emerald-50/30',
          cell.isPast && 'bg-stone-50/40 opacity-75',
          cell.isToday && 'ring-2 ring-inset ring-[#1E3A2B]/40 bg-emerald-50/20',
        ]"
        @click="handleCellClick(cell)"
      >
        <!-- Cell Header (Date Number & Today Pill) -->
        <div class="flex items-center justify-between">
          <span
            :class="[
              'text-xs font-bold w-6 h-6 flex items-center justify-center rounded-lg',
              cell.isToday
                ? 'bg-[#1E3A2B] text-white shadow-xs'
                : cell.isCurrentMonth
                ? 'text-stone-800 group-hover:text-[#1E3A2B]'
                : 'text-stone-400',
            ]"
          >
            {{ cell.dayNumber }}
          </span>
          <span v-if="cell.isToday" class="text-[10px] font-bold text-[#1E3A2B] uppercase tracking-wider">
            Hari Ini
          </span>
        </div>

        <!-- Quota Information Badge -->
        <div class="mt-2 space-y-1">
          <div
            v-if="cell.quota"
            :class="[
              'p-1.5 rounded-lg border text-xs space-y-0.5',
              getQuotaStatusClass(cell.quota).bg,
            ]"
          >
            <div class="flex items-center justify-between text-[11px] font-semibold" :class="getQuotaStatusClass(cell.quota).text">
              <span>{{ getQuotaStatusClass(cell.quota).label }}</span>
              <span class="text-stone-500 font-normal text-[10px]">
                / {{ cell.quota.kuota_total }}
              </span>
            </div>
            <!-- Visual Capacity Bar -->
            <div class="w-full h-1 bg-stone-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="[
                  cell.quota.kuota_tersisa === 0
                    ? 'bg-red-500'
                    : cell.quota.kuota_tersisa / cell.quota.kuota_total <= 0.3
                    ? 'bg-amber-500'
                    : 'bg-emerald-600',
                ]"
                :style="{
                  width: `${Math.min(100, (cell.quota.kuota_tersisa / cell.quota.kuota_total) * 100)}%`,
                }"
              ></div>
            </div>
          </div>

          <div
            v-else-if="cell.isCurrentMonth && !cell.isPast"
            class="p-1 text-center border border-dashed border-stone-200 rounded-lg text-[10px] text-stone-400 group-hover:border-[#1E3A2B]/40 group-hover:text-[#1E3A2B]"
          >
            + Set Kuota
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
