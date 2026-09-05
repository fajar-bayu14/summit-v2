<script setup lang="ts">
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
import { CheckCircle2, AlertCircle, Loader2, CreditCard } from 'lucide-vue-next'

defineProps<{
  open: boolean
  withdrawal: WithdrawalRequest | null
  isLoading: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [id: number]
}>()

function formatCurrency(amount: number | string): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num || 0)
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-md rounded-2xl">
      <DialogHeader>
        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-2">
          <CheckCircle2 class="h-6 w-6" />
        </div>
        <DialogTitle class="text-lg font-bold">Setujui Penarikan Dana Mitra</DialogTitle>
        <DialogDescription class="text-xs">
          Permohonan penarikan dana akan diproses dan diteruskan ke gateway pencairan dana otomatis (Disbursement).
        </DialogDescription>
      </DialogHeader>

      <div v-if="withdrawal" class="space-y-4 py-2">
        <!-- Summary Card -->
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs text-muted-foreground">ID Permohonan:</span>
            <span class="font-mono text-xs font-bold text-foreground">#WD-{{ withdrawal.id }}</span>
          </div>
          <div class="flex items-center justify-between border-t pt-2">
            <span class="text-xs text-muted-foreground">Mitra Pengelola:</span>
            <span class="text-xs font-semibold text-foreground">{{ withdrawal.mitra?.nama_pemilik || '-' }}</span>
          </div>
          <div class="flex items-center justify-between border-t pt-2">
            <span class="text-xs text-muted-foreground">Nominal Pencairan:</span>
            <span class="text-base font-extrabold text-emerald-700 dark:text-emerald-400 font-mono">
              {{ formatCurrency(withdrawal.nominal) }}
            </span>
          </div>
        </div>

        <!-- Bank Account Details -->
        <div class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 space-y-2">
          <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 dark:text-emerald-300">
            <CreditCard class="h-4 w-4 shrink-0" />
            <span>Rekening Tujuan Mitra</span>
          </div>
          <div class="text-xs space-y-1 pt-1">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Bank:</span>
              <span class="font-semibold">{{ withdrawal.bank }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">No. Rekening:</span>
              <span class="font-mono font-bold">{{ withdrawal.rekening_bank }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Atas Nama:</span>
              <span class="font-medium">{{ withdrawal.nama_rekening }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300 text-xs">
          <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
          <span>
            Pastikan nama pemilik rekening sesuai dengan data verifikasi KYC Mitra sebelum mengonfirmasi pencairan dana.
          </span>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button
          variant="outline"
          size="sm"
          :disabled="isLoading"
          @click="emit('update:open', false)"
        >
          Batal
        </Button>
        <Button
          size="sm"
          class="bg-emerald-700 hover:bg-emerald-800 text-white gap-1.5"
          :disabled="isLoading"
          @click="withdrawal && emit('confirm', withdrawal.id)"
        >
          <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
          <span>Konfirmasi & Eksekusi Payout</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
