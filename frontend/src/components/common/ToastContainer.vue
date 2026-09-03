<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const { toasts, remove } = useToast()
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none">
    <TransitionGroup
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto rounded-xl border p-4 shadow-lg flex items-start gap-3 bg-card text-card-foreground select-none"
        :class="{
          'border-emerald-500/30 bg-emerald-50/90 dark:bg-emerald-950/90 text-emerald-950 dark:text-emerald-50': toast.type === 'success',
          'border-red-500/30 bg-red-50/90 dark:bg-red-950/90 text-red-950 dark:text-red-50': toast.type === 'error',
          'border-amber-500/30 bg-amber-50/90 dark:bg-amber-950/90 text-amber-950 dark:text-amber-50': toast.type === 'warning',
          'border-blue-500/30 bg-blue-50/90 dark:bg-blue-950/90 text-blue-950 dark:text-blue-50': toast.type === 'info',
        }"
      >
        <div class="shrink-0 mt-0.5">
          <CheckCircle2 v-if="toast.type === 'success'" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          <AlertCircle v-else-if="toast.type === 'error'" class="h-5 w-5 text-red-600 dark:text-red-400" />
          <AlertTriangle v-else-if="toast.type === 'warning'" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
          <Info v-else class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        </div>

        <div class="flex-1 min-w-0">
          <h5 v-if="toast.title" class="text-sm font-semibold leading-none mb-1">
            {{ toast.title }}
          </h5>
          <p class="text-xs opacity-90 leading-relaxed break-words">
            {{ toast.message }}
          </p>
        </div>

        <button
          type="button"
          class="shrink-0 rounded-md p-1 opacity-70 hover:opacity-100 transition-opacity"
          aria-label="Tutup"
          @click="remove(toast.id)"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
