<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    status: string
    size?: 'sm' | 'md' | 'lg'
    dot?: boolean
  }>(),
  {
    size: 'sm',
    dot: true,
  }
)

interface StatusConfig {
  label: string
  bg: string
  text: string
  border: string
  dotColor: string
}

const statusMap: Record<string, StatusConfig> = {
  // Order & Payment Statuses
  paid: {
    label: 'Sudah Bayar (Paid)',
    bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
    text: 'text-emerald-700 dark:text-emerald-400',
    border: 'border-emerald-500/20',
    dotColor: 'bg-emerald-500',
  },
  pending: {
    label: 'Menunggu (Pending)',
    bg: 'bg-amber-500/10 dark:bg-amber-500/20',
    text: 'text-amber-700 dark:text-amber-400',
    border: 'border-amber-500/20',
    dotColor: 'bg-amber-500',
  },
  on_going: {
    label: 'Sedang Berjalan (Active)',
    bg: 'bg-blue-500/10 dark:bg-blue-500/20',
    text: 'text-blue-700 dark:text-blue-400',
    border: 'border-blue-500/20',
    dotColor: 'bg-blue-500',
  },
  completed: {
    label: 'Selesai (Completed)',
    bg: 'bg-teal-500/10 dark:bg-teal-500/20',
    text: 'text-teal-700 dark:text-teal-400',
    border: 'border-teal-500/20',
    dotColor: 'bg-teal-500',
  },
  refunded: {
    label: 'Dana Dikembalikan (Refunded)',
    bg: 'bg-purple-500/10 dark:bg-purple-500/20',
    text: 'text-purple-700 dark:text-purple-400',
    border: 'border-purple-500/20',
    dotColor: 'bg-purple-500',
  },
  cancelled: {
    label: 'Dibatalkan (Cancelled)',
    bg: 'bg-rose-500/10 dark:bg-rose-500/20',
    text: 'text-rose-700 dark:text-rose-400',
    border: 'border-rose-500/20',
    dotColor: 'bg-rose-500',
  },

  // Trail / Basecamp Statuses
  open: {
    label: 'Jalur Buka',
    bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
    text: 'text-emerald-700 dark:text-emerald-400',
    border: 'border-emerald-500/20',
    dotColor: 'bg-emerald-500',
  },
  close: {
    label: 'Jalur Ditutup',
    bg: 'bg-rose-500/10 dark:bg-rose-500/20',
    text: 'text-rose-700 dark:text-rose-400',
    border: 'border-rose-500/20',
    dotColor: 'bg-rose-500',
  },
  closed: {
    label: 'Jalur Ditutup',
    bg: 'bg-rose-500/10 dark:bg-rose-500/20',
    text: 'text-rose-700 dark:text-rose-400',
    border: 'border-rose-500/20',
    dotColor: 'bg-rose-500',
  },

  // Active / Inactive
  active: {
    label: 'Aktif',
    bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
    text: 'text-emerald-700 dark:text-emerald-400',
    border: 'border-emerald-500/20',
    dotColor: 'bg-emerald-500',
  },
  inactive: {
    label: 'Nonaktif',
    bg: 'bg-slate-500/10 dark:bg-slate-500/20',
    text: 'text-slate-600 dark:text-slate-400',
    border: 'border-slate-500/20',
    dotColor: 'bg-slate-400',
  },

  // Logbook / Refund Review
  approved: {
    label: 'Disetujui (Approved)',
    bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
    text: 'text-emerald-700 dark:text-emerald-400',
    border: 'border-emerald-500/20',
    dotColor: 'bg-emerald-500',
  },
  rejected: {
    label: 'Ditolak (Rejected)',
    bg: 'bg-rose-500/10 dark:bg-rose-500/20',
    text: 'text-rose-700 dark:text-rose-400',
    border: 'border-rose-500/20',
    dotColor: 'bg-rose-500',
  },

  // Staff Availability
  available: {
    label: 'Tersedia',
    bg: 'bg-emerald-500/10 dark:bg-emerald-500/20',
    text: 'text-emerald-700 dark:text-emerald-400',
    border: 'border-emerald-500/20',
    dotColor: 'bg-emerald-500',
  },
  on_duty: {
    label: 'Sedang Bertugas',
    bg: 'bg-amber-500/10 dark:bg-amber-500/20',
    text: 'text-amber-700 dark:text-amber-400',
    border: 'border-amber-500/20',
    dotColor: 'bg-amber-500',
  },
}

const config = computed<StatusConfig>(() => {
  const normalized = (props.status || '').toLowerCase().trim()
  return (
    statusMap[normalized] || {
      label: props.status || 'Unknown',
      bg: 'bg-slate-500/10',
      text: 'text-slate-600 dark:text-slate-400',
      border: 'border-slate-500/20',
      dotColor: 'bg-slate-400',
    }
  )
})

const sizeClasses = computed(() => {
  if (props.size === 'lg') return 'px-3 py-1 text-xs gap-1.5'
  if (props.size === 'md') return 'px-2.5 py-0.5 text-xs gap-1.5'
  return 'px-2 py-0.5 text-[11px] gap-1'
})
</script>

<template>
  <span
    class="inline-flex items-center font-medium rounded-full border shadow-2xs select-none transition-colors"
    :class="[config.bg, config.text, config.border, sizeClasses]"
  >
    <span
      v-if="dot"
      class="h-1.5 w-1.5 rounded-full shrink-0 animate-pulse"
      :class="config.dotColor"
    />
    <span>{{ config.label }}</span>
  </span>
</template>
