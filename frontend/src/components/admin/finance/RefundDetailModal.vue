<script setup lang="ts">
import type { RefundRecord } from '@/types/finance'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'

defineProps<{
  open: boolean
  refund: RefundRecord | null
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
      return { label: 'Menunggu Review Mitra', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' }
    case 'approved_by_mitra':
      return { label: 'Disetujui Mitra (Selesai)', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' }
    case 'rejected_by_mitra':
      return { label: 'Ditolak Mitra', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    case 'disputed':
      return { label: 'Sengketa Mediasi Admin', class: 'bg-purple-500/10 text-purple-600 border-purple-500/20' }
    case 'success':
      return { label: 'Refund Berhasil', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' }
    case 'rejected':
      return { label: 'Ditolak Admin', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    default:
      return { label: status, class: 'bg-muted text-muted-foreground' }
  }
}

function getCategoryBadge(cat: string) {
  switch (cat) {
    case 'pre_trip':
      return { label: 'Batal Pra-Trip', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' }
    case 'incident':
      return { label: 'Insiden / Kendala Lapangan', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' }
    case 'force_majeure':
      return { label: 'Force Majeure Bencana', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    case 'dispute':
      return { label: 'Banding Sengketa', class: 'bg-purple-500/10 text-purple-600 border-purple-500/20' }
    default:
      return { label: cat, class: 'bg-muted text-muted-foreground' }
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-lg rounded-2xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle class="text-lg font-bold flex items-center justify-between gap-2 pr-6">
          <span>Detail Klaim Refund #RF-{{ refund?.id }}</span>
          <Badge
            v-if="refund"
            variant="outline"
            :class="getStatusBadge(refund.status).class"
            class="text-xs font-semibold"
          >
            {{ getStatusBadge(refund.status).label }}
          </Badge>
        </DialogTitle>
        <DialogDescription class="text-xs">
          Rincian lengkap riwayat pengembalian dana, dokumen komplain, dan audit persetujuan.
        </DialogDescription>
      </DialogHeader>

      <div v-if="refund" class="space-y-4 py-2 text-xs">
        <!-- Banner Nominal -->
        <div class="p-4 rounded-xl bg-slate-900 text-white flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[11px] uppercase tracking-wider block font-semibold">Nominal Refund</span>
            <span class="text-2xl font-extrabold font-mono text-emerald-400">
              {{ formatCurrency(refund.nominal_disetujui || refund.nominal) }}
            </span>
            <span v-if="refund.nominal_disetujui && refund.nominal_disetujui !== refund.nominal" class="text-[11px] text-slate-400 line-through">
              Semula: {{ formatCurrency(refund.nominal) }}
            </span>
          </div>
          <div class="text-right space-y-1">
            <Badge variant="outline" :class="getCategoryBadge(refund.refund_category).class" class="text-[10px]">
              {{ getCategoryBadge(refund.refund_category).label }}
            </Badge>
            <span class="text-[11px] text-slate-300 block">{{ formatDate(refund.created_at) }}</span>
          </div>
        </div>

        <!-- Rincian Pesanan & Destinasi -->
        <div class="p-3.5 rounded-xl border bg-card space-y-2">
          <div class="font-bold text-foreground">Informasi Reservasi Terkait</div>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div>
              <span class="text-muted-foreground block text-[11px]">Nomor Invoice:</span>
              <span class="font-mono font-bold text-foreground">{{ refund.pesanan?.invoice || '-' }}</span>
            </div>
            <div>
              <span class="text-muted-foreground block text-[11px]">Jadwal Booking:</span>
              <span class="font-medium text-foreground">{{ refund.pesanan?.tanggal_booking || '-' }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-muted-foreground block text-[11px]">Destinasi Basecamp & Jalur:</span>
              <span class="font-medium text-foreground">
                {{ refund.pesanan?.basecamp?.nama_basecamp || '-' }} ({{ refund.pesanan?.basecamp?.jalur?.gunung?.nama_gunung || '' }} - {{ refund.pesanan?.basecamp?.jalur?.nama_jalur || '' }})
              </span>
            </div>
          </div>
        </div>

        <!-- Timeline Alasan Komplain & Mediasi -->
        <div class="space-y-2">
          <span class="font-bold text-foreground block">Kronologi Catatan:</span>

          <div class="p-3 rounded-xl border bg-blue-500/5 text-blue-900 dark:text-blue-200 space-y-1">
            <span class="font-semibold block text-[11px]">Alasan Permohonan Pendaki:</span>
            <p class="text-xs leading-relaxed text-foreground">{{ refund.alasan }}</p>
          </div>

          <div v-if="refund.mitra_alasan_penolakan" class="p-3 rounded-xl border bg-rose-500/5 text-rose-900 dark:text-rose-200 space-y-1">
            <span class="font-semibold block text-[11px]">Alasan Penolakan Mitra:</span>
            <p class="text-xs leading-relaxed text-foreground">{{ refund.mitra_alasan_penolakan }}</p>
          </div>

          <div v-if="refund.dispute_reason" class="p-3 rounded-xl border bg-amber-500/5 text-amber-900 dark:text-amber-200 space-y-1">
            <span class="font-semibold block text-[11px]">Argumen Banding Sengketa:</span>
            <p class="text-xs leading-relaxed text-foreground">{{ refund.dispute_reason }}</p>
          </div>

          <div v-if="refund.admin_catatan" class="p-3 rounded-xl border bg-slate-100 dark:bg-slate-800 space-y-1">
            <span class="font-semibold block text-[11px]">Catatan Keputusan Admin:</span>
            <p class="text-xs leading-relaxed text-foreground">{{ refund.admin_catatan }}</p>
          </div>
        </div>

        <!-- Rekening Bank Tujuan -->
        <div class="p-3.5 rounded-xl border bg-card space-y-1">
          <span class="font-bold text-foreground block">Rekening Tujuan Refund (Pendaki)</span>
          <div class="text-xs text-muted-foreground flex gap-4 pt-1">
            <span>Bank: <strong class="text-foreground">{{ refund.bank_tujuan }}</strong></span>
            <span>No. Rek: <strong class="text-foreground font-mono">{{ refund.rekening_tujuan }}</strong></span>
            <span>a.n <strong class="text-foreground">{{ refund.nama_tujuan }}</strong></span>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
