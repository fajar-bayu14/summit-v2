<script setup lang="ts">
import { ref, watch } from 'vue'
import type { RefundRecord, ProcessRefundPayload } from '@/types/finance'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Scale,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Loader2,
  Building2,
  User,
  CreditCard,
} from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  refund: RefundRecord | null
  isLoading: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [id: number, payload: ProcessRefundPayload]
}>()

const decision = ref<'full' | 'partial' | 'reject'>('full')
const partialNominal = ref<number>(0)
const adminNotes = ref('')
const transferProof = ref('')
const errorMsg = ref('')

watch(
  () => props.refund,
  newRefund => {
    if (newRefund) {
      decision.value = 'full'
      partialNominal.value = Math.round(Number(newRefund.nominal) / 2)
      adminNotes.value = ''
      transferProof.value = ''
      errorMsg.value = ''
    }
  }
)

function formatCurrency(amount: number | string): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num || 0)
}

function handleSubmit() {
  errorMsg.value = ''

  if (!props.refund) return

  if (decision.value === 'partial') {
    if (!partialNominal.value || partialNominal.value <= 0) {
      errorMsg.value = 'Nominal persetujuan parsial harus lebih besar dari Rp 0.'
      return
    }
    if (partialNominal.value > Number(props.refund.nominal)) {
      errorMsg.value = 'Nominal persetujuan parsial tidak boleh melebihi total nominal pengajuan (' + formatCurrency(props.refund.nominal) + ').'
      return
    }
  }

  if (decision.value === 'reject' && (!adminNotes.value.trim() || adminNotes.value.trim().length < 5)) {
    errorMsg.value = 'Alasan penolakan sengketa wajib dicatat minimal 5 karakter.'
    return
  }

  const payload: ProcessRefundPayload = {
    status: decision.value === 'reject' ? 'rejected' : 'success',
    tipe: 'manual',
    catatan: adminNotes.value.trim() || undefined,
    bukti_transfer: transferProof.value.trim() || undefined,
  }

  if (decision.value === 'partial') {
    payload.nominal = Number(partialNominal.value)
  }

  emit('confirm', props.refund.id, payload)
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-xl rounded-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="h-12 w-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center mb-2">
          <Scale class="h-6 w-6" />
        </div>
        <DialogTitle class="text-lg font-bold">Pusat Mediasi Sengketa (Dispute Resolution)</DialogTitle>
        <DialogDescription class="text-xs">
          Tinjau klaim pengembalian dana antara pendaki dan mitra, lalu tentukan resolusi keputusan admin yang adil.
        </DialogDescription>
      </DialogHeader>

      <div v-if="refund" class="space-y-4 py-2 text-xs">
        <!-- Rincian Pesanan Header -->
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border grid grid-cols-2 gap-2">
          <div>
            <span class="text-muted-foreground block text-[11px]">Invoice Transaksi:</span>
            <span class="font-mono font-bold text-foreground">{{ refund.pesanan?.invoice || '-' }}</span>
          </div>
          <div>
            <span class="text-muted-foreground block text-[11px]">Destinasi:</span>
            <span class="font-semibold text-foreground">
              {{ refund.pesanan?.basecamp?.jalur?.gunung?.nama_gunung || 'Gunung' }} - {{ refund.pesanan?.basecamp?.jalur?.nama_jalur || '' }}
            </span>
          </div>
          <div>
            <span class="text-muted-foreground block text-[11px]">Pendaki Pemohon:</span>
            <span class="font-medium text-foreground">{{ refund.nama_tujuan }} ({{ refund.pesanan?.user?.email || '-' }})</span>
          </div>
          <div>
            <span class="text-muted-foreground block text-[11px]">Mitra Pengelola:</span>
            <span class="font-medium text-foreground">{{ refund.mitra?.nama_pemilik || '-' }}</span>
          </div>
          <div class="col-span-2 border-t pt-2 mt-1 flex justify-between items-center">
            <span class="text-muted-foreground font-medium">Total Nilai Transaksi:</span>
            <span class="text-sm font-extrabold font-mono text-foreground">{{ formatCurrency(refund.pesanan?.total_bayar || refund.nominal) }}</span>
          </div>
        </div>

        <!-- Dispute Case Timeline -->
        <div class="space-y-2.5">
          <span class="font-bold text-foreground block text-xs">Kronologi & Alasan Sengketa:</span>

          <!-- Step 1: Pendaki Request -->
          <div class="p-3 rounded-xl border border-blue-500/20 bg-blue-500/5 space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-blue-700 dark:text-blue-400">
              <User class="h-3.5 w-3.5" />
              <span>Klaim Awal Pendaki (Kategori: {{ refund.refund_category }}):</span>
            </div>
            <p class="text-xs text-foreground pl-5 leading-relaxed">{{ refund.alasan }}</p>
          </div>

          <!-- Step 2: Mitra Rejection -->
          <div v-if="refund.mitra_alasan_penolakan" class="p-3 rounded-xl border border-rose-500/20 bg-rose-500/5 space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-rose-700 dark:text-rose-400">
              <Building2 class="h-3.5 w-3.5" />
              <span>Alasan Penolakan oleh Mitra Basecamp:</span>
            </div>
            <p class="text-xs text-foreground pl-5 leading-relaxed">{{ refund.mitra_alasan_penolakan }}</p>
          </div>

          <!-- Step 3: Climber Dispute Reason -->
          <div v-if="refund.dispute_reason" class="p-3 rounded-xl border border-amber-500/20 bg-amber-500/5 space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-amber-700 dark:text-amber-400">
              <AlertTriangle class="h-3.5 w-3.5" />
              <span>Argumen Banding Sengketa Pendaki:</span>
            </div>
            <p class="text-xs text-foreground pl-5 leading-relaxed">{{ refund.dispute_reason }}</p>
          </div>
        </div>

        <!-- Rekening Pengembalian Pendaki -->
        <div class="p-3 rounded-xl border bg-muted/30 space-y-1">
          <div class="flex items-center gap-1.5 font-semibold text-foreground">
            <CreditCard class="h-3.5 w-3.5 text-primary" />
            <span>Rekening Pengembalian Dana Pendaki:</span>
          </div>
          <div class="text-xs flex gap-4 text-muted-foreground pt-0.5">
            <span>Bank: <strong class="text-foreground">{{ refund.bank_tujuan }}</strong></span>
            <span>No. Rekening: <strong class="text-foreground font-mono">{{ refund.rekening_tujuan }}</strong></span>
            <span>a.n <strong class="text-foreground">{{ refund.nama_tujuan }}</strong></span>
          </div>
        </div>

        <!-- Form Keputusan Mediasi Admin -->
        <form @submit.prevent="handleSubmit" class="space-y-3.5 border-t pt-3">
          <div class="space-y-1.5">
            <Label class="text-xs font-semibold">Keputusan Mediasi Platform <span class="text-destructive">*</span></Label>
            <div class="grid grid-cols-3 gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-auto py-2.5 flex flex-col items-center gap-1 text-center"
                :class="decision === 'full' ? 'border-emerald-600 bg-emerald-500/10 text-emerald-700 font-bold' : ''"
                @click="decision = 'full'"
              >
                <CheckCircle2 class="h-4 w-4" />
                <span class="text-xs">Setujui Penuh (100%)</span>
              </Button>

              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-auto py-2.5 flex flex-col items-center gap-1 text-center"
                :class="decision === 'partial' ? 'border-blue-600 bg-blue-500/10 text-blue-700 font-bold' : ''"
                @click="decision = 'partial'"
              >
                <Scale class="h-4 w-4" />
                <span class="text-xs">Setujui Parsial</span>
              </Button>

              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-auto py-2.5 flex flex-col items-center gap-1 text-center"
                :class="decision === 'reject' ? 'border-rose-600 bg-rose-500/10 text-rose-700 font-bold' : ''"
                @click="decision = 'reject'"
              >
                <XCircle class="h-4 w-4" />
                <span class="text-xs">Tolak Sengketa</span>
              </Button>
            </div>
          </div>

          <!-- Input Nominal Parsial jika Partial -->
          <div v-if="decision === 'partial'" class="space-y-1.5 p-3 rounded-xl bg-blue-500/5 border border-blue-500/20">
            <Label for="partialNominal" class="text-xs font-semibold text-blue-900 dark:text-blue-300">
              Nominal Refund Yang Disetujui (IDR) <span class="text-destructive">*</span>
            </Label>
            <Input
              id="partialNominal"
              v-model.number="partialNominal"
              type="number"
              placeholder="Contoh: 50000"
              class="h-9 text-xs font-mono font-bold"
              :disabled="isLoading"
            />
            <p class="text-[11px] text-muted-foreground">
              Sisa nilai pesanan akan tetap menjadi hak mitra basecamp.
            </p>
          </div>

          <!-- Negative Balance Notice -->
          <div v-if="decision !== 'reject'" class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-muted-foreground text-[11px] flex items-start gap-2">
            <AlertTriangle class="h-4 w-4 shrink-0 text-amber-500 mt-0.5" />
            <span>
              <strong>Proteksi Negative Balance Recovery:</strong> Jika mitra telah mencairkan seluruh saldonya, nominal refund akan memotong saldo mitra ke angka minus dan ter-recoup otomatis pada booking berikutnya.
            </span>
          </div>

          <!-- Catatan / Bukti Transfer -->
          <div class="space-y-1.5">
            <Label for="adminNotes" class="text-xs font-semibold">
              Catatan Mediasi Admin {{ decision === 'reject' ? '(Wajib)' : '(Opsional)' }}
            </Label>
            <textarea
              id="adminNotes"
              v-model="adminNotes"
              rows="2"
              placeholder="Contoh: Disetujui 50% karena insiden tenda robek terjadi di pertengahan malam pertama..."
              class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-xs focus:outline-none focus:ring-1 focus:ring-ring resize-none"
              :disabled="isLoading"
            />
          </div>

          <p v-if="errorMsg" class="text-xs text-destructive font-medium">{{ errorMsg }}</p>

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
              size="sm"
              class="bg-emerald-700 hover:bg-emerald-800 text-white gap-1.5"
              :disabled="isLoading"
            >
              <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
              <span>Simpan Resolusi Sengketa</span>
            </Button>
          </DialogFooter>
        </form>
      </div>
    </DialogContent>
  </Dialog>
</template>
