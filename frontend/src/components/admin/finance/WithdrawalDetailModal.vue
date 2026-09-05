<script setup lang="ts">
import type { WithdrawalRequest } from '@/types/finance'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { CreditCard, Building2, XCircle } from 'lucide-vue-next'

defineProps<{
  open: boolean
  withdrawal: WithdrawalRequest | null
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
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

function formatDate(dateStr?: string | null): string {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(dateStr))
}

function getStatusBadge(status: string) {
  switch (status) {
    case 'pending':
      return { label: 'Menunggu Persetujuan', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' }
    case 'processing':
      return { label: 'Sedang Diproses Gateway', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' }
    case 'completed':
      return { label: 'Berhasil Dicairkan', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' }
    case 'rejected':
      return { label: 'Ditolak Admin', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    case 'failed':
      return { label: 'Gagal Eksekusi', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    default:
      return { label: status, class: 'bg-muted text-muted-foreground' }
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-lg rounded-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="text-lg font-bold flex items-center justify-between gap-2 pr-6">
          <span>Detail Penarikan Dana #WD-{{ withdrawal?.id }}</span>
          <Badge
            v-if="withdrawal"
            variant="outline"
            :class="getStatusBadge(withdrawal.status).class"
            class="text-xs font-semibold"
          >
            {{ getStatusBadge(withdrawal.status).label }}
          </Badge>
        </DialogTitle>
        <DialogDescription class="text-xs">
          Rincian lengkap pengajuan penarikan dana dompet mitra dan log disbursement.
        </DialogDescription>
      </DialogHeader>

      <div v-if="withdrawal" class="space-y-4 py-2 text-xs">
        <!-- Nominal Banner -->
        <div class="p-4 rounded-xl bg-slate-900 text-white flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[11px] uppercase tracking-wider block font-semibold">Nominal Pencairan</span>
            <span class="text-2xl font-extrabold font-mono text-emerald-400">
              {{ formatCurrency(withdrawal.nominal) }}
            </span>
          </div>
          <div class="text-right">
            <span class="text-slate-400 text-[11px] block">Waktu Pengajuan</span>
            <span class="text-xs font-medium text-slate-200">{{ formatDate(withdrawal.created_at) }}</span>
          </div>
        </div>

        <!-- Rekening Bank Tujuan -->
        <div class="p-3.5 rounded-xl border bg-card space-y-2">
          <div class="flex items-center gap-1.5 font-bold text-foreground">
            <CreditCard class="h-4 w-4 text-primary" />
            <span>Informasi Rekening Bank Tujuan</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div>
              <span class="text-muted-foreground block text-[11px]">Nama Bank:</span>
              <span class="font-semibold text-foreground">{{ withdrawal.bank }}</span>
            </div>
            <div>
              <span class="text-muted-foreground block text-[11px]">Nomor Rekening:</span>
              <span class="font-mono font-bold text-foreground">{{ withdrawal.rekening_bank }}</span>
            </div>
            <div class="col-span-2 border-t pt-1.5 mt-1">
              <span class="text-muted-foreground block text-[11px]">Pemilik Rekening (Atas Nama):</span>
              <span class="font-semibold text-foreground">{{ withdrawal.nama_rekening }}</span>
            </div>
          </div>
        </div>

        <!-- Profil Mitra Pengaju -->
        <div class="p-3.5 rounded-xl border bg-card space-y-2">
          <div class="flex items-center gap-1.5 font-bold text-foreground">
            <Building2 class="h-4 w-4 text-primary" />
            <span>Profil Mitra Pemilik</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div>
              <span class="text-muted-foreground block text-[11px]">Nama Pengelola:</span>
              <span class="font-medium text-foreground">{{ withdrawal.mitra?.nama_pemilik || '-' }}</span>
            </div>
            <div>
              <span class="text-muted-foreground block text-[11px]">No. Kontak:</span>
              <span class="font-medium text-foreground">{{ withdrawal.mitra?.telepon || '-' }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-muted-foreground block text-[11px]">Akun Email:</span>
              <span class="font-medium text-foreground">{{ withdrawal.mitra?.user?.email || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Catatan & Rejection Reason jika ada -->
        <div
          v-if="withdrawal.alasan_penolakan"
          class="p-3.5 rounded-xl border border-rose-500/20 bg-rose-500/5 space-y-1 text-rose-700 dark:text-rose-400"
        >
          <div class="flex items-center gap-1.5 font-bold">
            <XCircle class="h-4 w-4 shrink-0" />
            <span>Alasan Penolakan Admin:</span>
          </div>
          <p class="text-xs leading-relaxed pl-5">{{ withdrawal.alasan_penolakan }}</p>
        </div>

        <!-- Log Disbursement -->
        <div v-if="withdrawal.disbursement_id" class="p-3 rounded-xl bg-muted/40 border text-[11px] space-y-1">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Gateway Disbursement ID:</span>
            <span class="font-mono font-bold">{{ withdrawal.disbursement_id }}</span>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
