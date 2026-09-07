<script setup lang="ts">
import { computed } from 'vue'
import {
  AlertTriangle,
  ShieldCheck,
  Power,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = withDefaults(
  defineProps<{
    status?: string | null
    trailName?: string
    mountainName?: string
    reason?: string | null
  }>(),
  {
    status: 'open',
    trailName: 'Jalur Pendakian',
    mountainName: 'Gunung',
    reason: '',
  }
)

const emit = defineEmits<{
  (e: 'openModal'): void
}>()

const isClosed = computed(() => props.status === 'close')
</script>

<template>
  <div>
    <!-- Critical Emergency Closed Banner -->
    <div
      v-if="isClosed"
      class="rounded-2xl border border-rose-500/30 bg-rose-500/10 dark:bg-rose-950/30 p-4 sm:p-5 text-rose-950 dark:text-rose-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4"
    >
      <div class="flex items-start gap-3.5">
        <div class="h-10 w-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-xs animate-pulse">
          <AlertTriangle class="h-5 w-5" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
              Peringatan Operasional Jalur
            </span>
            <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
            <span class="text-xs font-semibold text-rose-800 dark:text-rose-300">
              {{ trailName }} ({{ mountainName }})
            </span>
          </div>
          <h4 class="text-base font-bold text-rose-950 dark:text-rose-100 mt-0.5">
            Jalur Ini Sedang Ditutup Sementara
          </h4>
          <p v-if="reason" class="text-xs text-rose-900/80 dark:text-rose-300/80 mt-1 leading-relaxed">
            Alasan: <span class="font-medium italic">"{{ reason }}"</span>
          </p>
          <p v-else class="text-xs text-rose-900/80 dark:text-rose-300/80 mt-1">
            Penutupan darurat aktif untuk keselamatan pendaki di pos dan jalur atas.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 shrink-0">
        <Button
          size="sm"
          class="h-9 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-semibold shadow-xs"
          @click="emit('openModal')"
        >
          <ShieldCheck class="h-4 w-4" />
          <span>Buka Kembali Jalur</span>
        </Button>
      </div>
    </div>

    <!-- Quick Emergency Close Control Bar when Open -->
    <div
      v-else
      class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-emerald-500/5 dark:bg-emerald-950/10 p-3 sm:px-5 sm:py-3.5 flex items-center justify-between gap-3"
    >
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></div>
        <div class="flex items-center gap-2 truncate text-xs text-muted-foreground">
          <span class="font-semibold text-emerald-700 dark:text-emerald-400">Status Jalur Normal (Open)</span>
          <span class="hidden sm:inline">·</span>
          <span class="truncate hidden sm:inline">{{ trailName }} · {{ mountainName }}</span>
        </div>
      </div>

      <Button
        variant="outline"
        size="sm"
        class="h-8 px-3 rounded-lg border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 gap-1.5 text-xs font-semibold shrink-0"
        @click="emit('openModal')"
      >
        <Power class="h-3.5 w-3.5" />
        <span>Tutup Jalur Darurat</span>
      </Button>
    </div>
  </div>
</template>
