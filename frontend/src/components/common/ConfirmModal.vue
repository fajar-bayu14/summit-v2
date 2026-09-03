<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { AlertTriangle, AlertCircle, CheckCircle2 } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    description?: string
    confirmLabel?: string
    cancelLabel?: string
    variant?: 'danger' | 'warning' | 'primary'
    loading?: boolean
  }>(),
  {
    title: 'Konfirmasi Tindakan',
    description: 'Apakah Anda yakin ingin melanjutkan tindakan ini? Data yang diubah mungkin tidak dapat dikembalikan.',
    confirmLabel: 'Ya, Lanjutkan',
    cancelLabel: 'Batal',
    variant: 'danger',
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

function handleConfirm() {
  emit('confirm')
}

function handleCancel() {
  emit('update:open', false)
  emit('cancel')
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md rounded-2xl p-6">
      <DialogHeader class="flex flex-col items-center text-center gap-2">
        <div
          class="h-12 w-12 rounded-full flex items-center justify-center mb-1"
          :class="{
            'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400': variant === 'danger',
            'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400': variant === 'warning',
            'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400': variant === 'primary',
          }"
        >
          <AlertTriangle v-if="variant === 'danger'" class="h-6 w-6 stroke-[2]" />
          <AlertCircle v-else-if="variant === 'warning'" class="h-6 w-6 stroke-[2]" />
          <CheckCircle2 v-else class="h-6 w-6 stroke-[2]" />
        </div>

        <DialogTitle class="text-lg font-bold text-foreground">
          {{ title }}
        </DialogTitle>
        <DialogDescription class="text-sm text-muted-foreground">
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <slot></slot>

      <DialogFooter class="flex sm:justify-center gap-2 mt-4 bg-transparent border-t-0 p-0">
        <Button
          type="button"
          variant="outline"
          class="flex-1 rounded-xl"
          :disabled="loading"
          @click="handleCancel"
        >
          {{ cancelLabel }}
        </Button>
        <Button
          type="button"
          class="flex-1 rounded-xl"
          :variant="variant === 'danger' ? 'destructive' : 'default'"
          :disabled="loading"
          @click="handleConfirm"
        >
          <span v-if="loading" class="inline-block animate-spin mr-2">⏳</span>
          {{ confirmLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
