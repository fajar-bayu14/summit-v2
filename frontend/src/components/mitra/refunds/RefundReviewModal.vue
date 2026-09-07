<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { StatusBadge } from '@/components/common'
import {
  RotateCcw,
  Building2,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Loader2,
  Calendar,
  DollarSign,
} from 'lucide-vue-next'
import type { RefundItem, MitraRejectRefundPayload } from '@/types/refund'
import mitraRefundsApi from '@/api/mitraRefunds'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    refund: RefundItem | null
  }>(),
  {
    isOpen: false,
    refund: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'reviewed', refund: RefundItem): void
  (e: 'close'): void
}>()

const decision = ref<'approved' | 'rejected'>('approved')
const rejectionReason = ref<string>('')
const formError = ref<string | null>(null)
const serverMessage = ref<string | null>(null)
const serverError = ref<string | null>(null)
const isSubmitting = ref<boolean>(false)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      decision.value = 'approved'
      rejectionReason.value = ''
      formError.value = null
      serverMessage.value = null
      serverError.value = null
      isSubmitting.value = false
    }
  }
)

function formatRupiah(amount?: number | null): string {
  if (typeof amount !== 'number') return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDateIndo(dateStr?: string | null): string {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

async function handleApprove() {
  if (!props.refund) return
  isSubmitting.value = true
  serverError.value = null
  serverMessage.value = null

  try {
    const res = await mitraRefundsApi.approveRefund(props.refund.id)
    const updated = res.data ?? {
      ...props.refund,
      status: 'approved_by_mitra' as const,
      nominal_disetujui: props.refund.nominal,
    }
    serverMessage.value = 'Permohonan refund berhasil disetujui! Saldo escrow pending telah disesuaikan.'
    emit('reviewed', updated)
  } catch (err) {
    serverError.value = getApiErrorMessage(err, 'Gagal menyetujui pengajuan refund.')
  } finally {
    isSubmitting.value = false
  }
}

async function handleReject() {
  if (!props.refund) return
  formError.value = null
  serverError.value = null
  serverMessage.value = null

  if (!rejectionReason.value.trim()) {
    formError.value = 'Wajib mencantumkan alasan penolakan refund.'
    return
  }

  isSubmitting.value = true

  try {
    const payload: MitraRejectRefundPayload = {
      alasan_penolakan: rejectionReason.value.trim(),
    }

    const res = await mitraRefundsApi.rejectRefund(props.refund.id, payload)
    const updated = res.data ?? {
      ...props.refund,
      status: 'rejected_by_mitra' as const,
      mitra_alasan_penolakan: payload.alasan_penolakan,
    }
    serverMessage.value = 'Pengajuan refund telah ditolak.'
    emit('reviewed', updated)
  } catch (err) {
    serverError.value = getApiErrorMessage(err, 'Gagal menolak pengajuan refund.')
  } finally {
    isSubmitting.value = false
  }
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-2xl bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex items-center gap-3 border-b border-stone-200 pb-3">
          <div class="w-11 h-11 rounded-xl bg-red-50 text-red-700 flex items-center justify-center font-bold">
            <RotateCcw class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <DialogTitle class="text-lg font-bold text-stone-900">
                Review Permohonan Refund
              </DialogTitle>
              <StatusBadge v-if="props.refund" :status="props.refund.status" />
            </div>
            <DialogDescription class="text-xs text-stone-500 mt-0.5">
              Invoice: <strong class="text-stone-700">{{ props.refund?.pesanan_invoice || props.refund?.pesanan?.invoice || '-' }}</strong>
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Feedback Alerts -->
      <div
        v-if="serverMessage"
        class="flex items-center gap-2.5 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium border border-emerald-200 mt-3"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ serverMessage }}</span>
      </div>

      <div
        v-if="serverError"
        class="flex items-center gap-2.5 p-3.5 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200 mt-3"
      >
        <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
        <span>{{ serverError }}</span>
      </div>

      <div v-if="props.refund" class="space-y-4 py-3 text-xs">
        <!-- Detail Pesanan & Pengajuan Grid -->
        <div class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Pemesan / Pendaki</span>
              <p class="font-bold text-stone-900 text-sm mt-0.5">
                {{ props.refund.pesanan?.user?.name || props.refund.nama_tujuan || '-' }}
              </p>
              <p class="text-[11px] text-stone-500">
                {{ props.refund.pesanan?.user?.telepon || props.refund.pesanan?.user?.email || '-' }}
              </p>
            </div>

            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Jadwal Pendakian</span>
              <div class="flex items-center gap-1.5 font-bold text-stone-800 mt-0.5">
                <Calendar class="w-3.5 h-3.5 text-stone-400" />
                {{ formatDateIndo(props.refund.pesanan?.tanggal_pendakian || props.refund.pesanan?.tanggal_booking) }}
              </div>
              <p class="text-[11px] text-stone-500">
                Jalur: {{ props.refund.pesanan?.jalur?.nama_jalur || 'Jalur Utama' }}
              </p>
            </div>

            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Nominal Pengajuan Refund</span>
              <div class="text-base font-extrabold text-red-700 mt-0.5 flex items-center gap-1">
                <DollarSign class="w-4 h-4" />
                {{ formatRupiah(props.refund.nominal) }}
              </div>
            </div>

            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Rekening Tujuan Pengembalian</span>
              <div class="flex items-center gap-1.5 font-bold text-stone-900 mt-0.5">
                <Building2 class="w-3.5 h-3.5 text-stone-500" />
                {{ props.refund.bank_tujuan || 'Bank' }} — {{ props.refund.rekening_tujuan || '-' }}
              </div>
              <p class="text-[11px] text-stone-500">
                a.n {{ props.refund.nama_tujuan || '-' }}
              </p>
            </div>
          </div>

          <!-- Alasan Pembatalan dari Pendaki -->
          <div class="pt-2 border-t border-stone-200">
            <span class="text-[10px] uppercase font-bold text-stone-400">Alasan Pembatalan Pendaki:</span>
            <p class="text-stone-800 bg-white p-3 rounded-xl border border-stone-200 italic mt-1 font-medium">
              "{{ props.refund.alasan }}"
            </p>
          </div>
        </div>

        <!-- Form Keputusan Review (Jika status pending atau disputed) -->
        <div
          v-if="props.refund.status === 'pending' || props.refund.status === 'disputed'"
          class="p-4 bg-white rounded-2xl border-2 border-stone-200 space-y-3 shadow-xs"
        >
          <div class="font-bold text-stone-900 text-sm">
            Keputusan Mitra Basecamp (Tier-1)
          </div>

          <!-- Decision Tabs -->
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              :class="[
                'p-3 rounded-xl border-2 flex items-center justify-center gap-2 font-bold text-xs cursor-pointer transition-all',
                decision === 'approved'
                  ? 'bg-emerald-50 border-emerald-600 text-emerald-900'
                  : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100',
              ]"
              @click="decision = 'approved'"
            >
              <CheckCircle2 class="w-4 h-4 text-emerald-600" />
              Setujui Refund
            </button>

            <button
              type="button"
              :class="[
                'p-3 rounded-xl border-2 flex items-center justify-center gap-2 font-bold text-xs cursor-pointer transition-all',
                decision === 'rejected'
                  ? 'bg-red-50 border-red-600 text-red-900'
                  : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100',
              ]"
              @click="decision = 'rejected'"
            >
              <XCircle class="w-4 h-4 text-red-600" />
              Tolak Refund
            </button>
          </div>

          <!-- Action Info Description -->
          <div
            v-if="decision === 'approved'"
            class="p-3 bg-emerald-50/70 text-emerald-800 rounded-xl border border-emerald-200"
          >
            <strong>Konfirmasi Persetujuan:</strong> Tiket pendakian akan resmi dibatalkan dan saldo escrow holding mitra akan dipotong secara otomatis sebesar nominal pesanan.
          </div>

          <div
            v-else
            class="space-y-2"
          >
            <div class="p-3 bg-red-50/70 text-red-800 rounded-xl border border-red-200">
              <strong>Perhatian Penolakan:</strong> Harap berikan alasan penolakan yang jelas dan sesuai SOP basecamp. Pendaki dapat mengajukan eskalasi banding ke Admin Pusat jika berkeberatan.
            </div>

            <div class="space-y-1">
              <label class="font-bold text-stone-700">
                Alasan Penolakan <span class="text-red-500">* (Wajib diisi)</span>:
              </label>
              <textarea
                v-model="rejectionReason"
                placeholder="Contoh: Pembatalan melewati batas waktu H-1 dan logistik telah disiapkan..."
                rows="3"
                class="w-full p-2.5 bg-white border border-stone-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
              ></textarea>
              <p v-if="formError" class="text-xs text-red-600 font-medium">{{ formError }}</p>
            </div>
          </div>

          <!-- Action Button -->
          <Button
            :disabled="isSubmitting"
            :class="[
              'w-full h-11 rounded-xl font-bold text-xs shadow-sm min-h-[44px]',
              decision === 'approved'
                ? 'bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white'
                : 'bg-red-600 hover:bg-red-700 text-white',
            ]"
            @click="decision === 'approved' ? handleApprove() : handleReject()"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
            <span v-if="decision === 'approved'">Konfirmasi Setujui Pengajuan Refund</span>
            <span v-else>Konfirmasi Tolak Pengajuan Refund</span>
          </Button>
        </div>

        <!-- History Banner if Already Processed -->
        <div
          v-else
          class="p-4 rounded-2xl border space-y-2"
          :class="props.refund.status === 'approved_by_mitra' || props.refund.status === 'success' ? 'bg-emerald-50/60 border-emerald-200' : 'bg-red-50/60 border-red-200'"
        >
          <div class="flex items-center gap-2 font-bold" :class="props.refund.status === 'approved_by_mitra' || props.refund.status === 'success' ? 'text-emerald-900' : 'text-red-900'">
            <CheckCircle2 v-if="props.refund.status === 'approved_by_mitra' || props.refund.status === 'success'" class="w-4 h-4 text-emerald-600" />
            <XCircle v-else class="w-4 h-4 text-red-600" />
            <span>Status Refund: {{ props.refund.status }}</span>
          </div>
          <div v-if="props.refund.mitra_alasan_penolakan" class="text-stone-700">
            Alasan Penolakan Mitra: <em>"{{ props.refund.mitra_alasan_penolakan }}"</em>
          </div>
          <div v-if="props.refund.admin_catatan" class="text-stone-700">
            Catatan Admin: <em>"{{ props.refund.admin_catatan }}"</em>
          </div>
        </div>
      </div>

      <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3 border-t border-stone-200">
        <Button
          type="button"
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          @click="handleClose"
        >
          Tutup
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
