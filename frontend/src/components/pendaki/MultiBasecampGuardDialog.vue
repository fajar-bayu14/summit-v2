<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { AlertTriangle, Trash2 } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    currentBasecampName?: string
    newBasecampName?: string
  }>(),
  {
    isOpen: false,
    currentBasecampName: 'Basecamp Sebelumnya',
    newBasecampName: 'Basecamp Baru',
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
  (e: 'confirm-switch'): void
  (e: 'cancel'): void
}>()

function handleConfirm() {
  emit('confirm-switch')
  emit('update:isOpen', false)
}

function handleCancel() {
  emit('cancel')
  emit('update:isOpen', false)
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-md p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-5">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
          <AlertTriangle class="w-6 h-6" />
        </div>
        <div>
          <DialogTitle class="text-base font-bold text-slate-900 dark:text-slate-100">
            Ganti Mitra Basecamp?
          </DialogTitle>
          <DialogDescription class="text-xs text-slate-500">
            Transaksi hanya dapat dilakukan pada 1 basecamp per pesanan.
          </DialogDescription>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-xs text-amber-900 dark:text-amber-200 leading-relaxed space-y-2">
        <p>
          Keranjang belanja Anda saat ini berisi item dari <strong>{{ currentBasecampName }}</strong>.
        </p>
        <p>
          Memilih item dari <strong>{{ newBasecampName }}</strong> akan <strong>mengosongkan</strong> isi keranjang sebelumnya. Apakah Anda ingin melanjutkan?
        </p>
      </div>

      <div class="grid grid-cols-2 gap-3 pt-2">
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl text-xs font-semibold"
          @click="handleCancel"
        >
          Batal &amp; Pertahankan
        </Button>

        <Button
          size="sm"
          class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs gap-1.5 shadow-xs"
          @click="handleConfirm"
        >
          <Trash2 class="w-3.5 h-3.5" />
          <span>Ganti Basecamp</span>
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>
