<script setup lang="ts">
import { computed } from 'vue'
import { formatRupiah, formatDate } from '@/lib/formatters'
import type { RefundItem } from '@/types/pendakiRefund'
import {
  AlertCircle,
  Banknote,
  CheckCircle2,
  Clock,
  Scale,
  ShieldAlert,
  XCircle
} from 'lucide-vue-next'

const props = defineProps<{
  refund: RefundItem
}>()

const emit = defineEmits<{
  (e: 'dispute', refund: RefundItem): void
}>()

const statusBadge = computed(() => {
  switch (props.refund.status) {
    case 'pending':
      return {
        label: 'Menunggu Review Mitra',
        class: 'bg-amber-50 text-amber-700 border-amber-200',
        icon: Clock
      }
    case 'approved_by_mitra':
      return {
        label: 'Disetujui Mitra (Proses Transfer)',
        class: 'bg-blue-50 text-blue-700 border-blue-200',
        icon: CheckCircle2
      }
    case 'rejected_by_mitra':
      return {
        label: 'Ditolak oleh Mitra',
        class: 'bg-rose-50 text-rose-700 border-rose-200',
        icon: XCircle
      }
    case 'disputed':
      return {
        label: 'Dalam Mediasi Admin (Dispute)',
        class: 'bg-purple-50 text-purple-700 border-purple-200',
        icon: Scale
      }
    case 'success':
      return {
        label: 'Dana Berhasil Dikembalikan',
        class: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        icon: CheckCircle2
      }
    default:
      return {
        label: props.refund.status,
        class: 'bg-gray-50 text-gray-700 border-gray-200',
        icon: AlertCircle
      }
  }
})
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition hover:shadow-md space-y-4">
    <!-- Header Card -->
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">ID Refund #{{ refund.id }}</span>
        <span v-if="refund.pesanan?.invoice" class="text-xs font-semibold text-gray-700">
          • Inv: {{ refund.pesanan.invoice }}
        </span>
      </div>
      <div
        :class="[
          'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border',
          statusBadge.class
        ]"
      >
        <component :is="statusBadge.icon" class="h-3.5 w-3.5" />
        <span>{{ statusBadge.label }}</span>
      </div>
    </div>

    <!-- Body Info -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-gray-600">
      <div>
        <p class="text-gray-400">Nominal Pengembalian:</p>
        <p class="text-base font-extrabold text-[#1E3A2B]">{{ formatRupiah(refund.nominal) }}</p>
      </div>
      <div>
        <p class="text-gray-400">Rekening Tujuan:</p>
        <p class="font-semibold text-gray-900">
          {{ refund.bank_tujuan }} - {{ refund.rekening_tujuan }} (a.n {{ refund.nama_tujuan }})
        </p>
      </div>
      <div class="sm:col-span-2">
        <p class="text-gray-400">Alasan Pengajuan:</p>
        <p class="text-gray-800 bg-gray-50 rounded-lg p-2.5 mt-0.5">{{ refund.alasan }}</p>
      </div>
    </div>

    <!-- Mitra Rejection / Dispute Context -->
    <div
      v-if="refund.status === 'rejected_by_mitra' && refund.mitra_alasan_penolakan"
      class="rounded-xl border border-rose-200 bg-rose-50/60 p-3 text-xs space-y-2"
    >
      <div class="flex items-center gap-1.5 font-bold text-rose-800">
        <ShieldAlert class="h-4 w-4" />
        <span>Alasan Penolakan Mitra:</span>
      </div>
      <p class="text-rose-700">{{ refund.mitra_alasan_penolakan }}</p>

      <div v-if="!refund.is_disputed" class="pt-2 flex justify-end">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-rose-700 transition"
          @click="emit('dispute', refund)"
        >
          <Scale class="h-3.5 w-3.5" />
          <span>Ajukan Banding ke Admin (Dispute)</span>
        </button>
      </div>
    </div>

    <div
      v-if="refund.is_disputed && refund.dispute_reason"
      class="rounded-xl border border-purple-200 bg-purple-50/60 p-3 text-xs space-y-1"
    >
      <div class="flex items-center gap-1.5 font-bold text-purple-800">
        <Scale class="h-4 w-4" />
        <span>Alasan Banding Anda ke Tim Admin:</span>
      </div>
      <p class="text-purple-700 italic">"{{ refund.dispute_reason }}"</p>
    </div>

    <!-- Footer Date -->
    <div class="flex items-center justify-between text-[11px] text-gray-400 pt-2 border-t border-gray-100">
      <div class="flex items-center gap-1">
        <Banknote class="h-3.5 w-3.5" />
        <span>Diajukan pada {{ formatDate(refund.created_at || new Date().toISOString()) }}</span>
      </div>
    </div>
  </div>
</template>
