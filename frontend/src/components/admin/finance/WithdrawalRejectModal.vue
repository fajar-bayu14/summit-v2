<script setup lang="ts">
import { ref, watch } from 'vue'
import type { WithdrawalRequest } from '@/types/finance'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { XCircle, AlertTriangle, Loader2 } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  withdrawal: WithdrawalRequest | null
  isLoading: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [id: number, reason: string]
}>()

const reason = ref('')
const errorMsg = ref('')

watch(
  () => props.open,
  newVal => {
    if (newVal) {
      reason.value = ''
      errorMsg.value = ''
    }
  }
)

function handleSubmit() {
  if (!reason.value.trim() || reason.value.trim().length < 5) {
    errorMsg.value = 'Alasan penolakan wajib diisi minimal 5 karakter.'
    return
  }
  if (props.withdrawal) {
    emit('confirm', props.withdrawal.id, reason.value.trim())
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-md rounded-2xl">
      <DialogHeader>
        <div class="h-12 w-12 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center mb-2">
          <XCircle class="h-6 w-6" />
        </div>
        <DialogTitle class="text-lg font-bold">Tolak Penarikan Dana Mitra</DialogTitle>
        <DialogDescription class="text-xs">
          Saldo penarikan yang ditolak akan dikembalikan secara otomatis ke Saldo Siap Cair (Available) Mitra.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-2">
        <div v-if="withdrawal" class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border text-xs space-y-1.5">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Mitra:</span>
            <span class="font-semibold text-foreground">{{ withdrawal.mitra?.nama_pemilik || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Nominal:</span>
            <span class="font-mono font-bold text-foreground">Rp {{ Number(withdrawal.nominal).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Rekening:</span>
            <span class="font-medium">{{ withdrawal.bank }} - {{ withdrawal.rekening_bank }}</span>
          </div>
        </div>

        <div class="space-y-1.5">
          <Label for="rejectReason" class="text-xs font-semibold">
            Alasan Penolakan <span class="text-destructive">*</span>
          </Label>
          <textarea
            id="rejectReason"
            v-model="reason"
            rows="3"
            placeholder="Contoh: Nama pada rekening bank tidak cocok dengan KTP verifikasi mitra..."
            class="flex min-h-[70px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-xs focus:outline-none focus:ring-1 focus:ring-ring resize-none"
            :disabled="isLoading"
          />
          <p v-if="errorMsg" class="text-xs text-destructive font-medium">{{ errorMsg }}</p>
        </div>

        <div class="flex items-center gap-2 p-3 rounded-lg bg-slate-100 dark:bg-slate-800 text-muted-foreground text-[11px]">
          <AlertTriangle class="h-4 w-4 shrink-0 text-amber-500" />
          <span>Alasan penolakan akan dikirimkan sebagai notifikasi ke dashboard mitra terkait.</span>
        </div>

        <DialogFooter class="gap-2 sm:gap-0 pt-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="isLoading"
            @click="emit('update:open', false)"
          >
            Batal
          </Button>
          <Button
            type="submit"
            variant="destructive"
            size="sm"
            class="gap-1.5"
            :disabled="isLoading"
          >
            <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
            <span>Tolak & Kembalikan Saldo</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
