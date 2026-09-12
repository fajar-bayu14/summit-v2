<script setup lang="ts">
import { computed } from 'vue'
import { ShieldCheck, ShieldAlert, Clock, AlertTriangle, ArrowRight, RefreshCw } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { KycStatus } from '@/types/pendakiKyc'

const props = withDefaults(
  defineProps<{
    status?: KycStatus | string | null
    rejectionReason?: string | null
    compact?: boolean
  }>(),
  {
    status: 'unverified',
    rejectionReason: null,
    compact: false,
  }
)

const emit = defineEmits<{
  (e: 'verify'): void
}>()

const normalizedStatus = computed(() => {
  const raw = (props.status || 'unverified').toLowerCase()
  if (raw === 'disetujui' || raw === 'verified') return 'verified'
  if (raw === 'ditolak' || raw === 'rejected') return 'rejected'
  return raw
})
</script>

<template>
  <!-- 1. UNVERIFIED BANNER -->
  <div
    v-if="normalizedStatus === 'unverified'"
    class="relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4 sm:p-5 dark:border-amber-900/50 dark:from-amber-950/30 dark:to-orange-950/20 shadow-sm"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 shrink-0">
          <AlertTriangle class="w-6 h-6" />
        </div>
        <div>
          <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm sm:text-base">
            Akun Anda Belum Terverifikasi (KYC)
          </h4>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">
            Lengkapi identitas resmi KTP/Paspor Anda untuk memenuhi syarat legal SIMAKSI pendakian dan mempercepat proses checkout tiket.
          </p>
        </div>
      </div>
      <Button
        type="button"
        size="sm"
        class="bg-amber-600 hover:bg-amber-700 text-white shadow-sm font-semibold flex items-center gap-1.5 shrink-0 self-start sm:self-center"
        @click="emit('verify')"
      >
        <span>Verifikasi Sekarang</span>
        <ArrowRight class="w-4 h-4" />
      </Button>
    </div>
  </div>

  <!-- 2. PENDING REVIEW BANNER -->
  <div
    v-else-if="normalizedStatus === 'pending'"
    class="relative overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-sky-50 p-4 sm:p-5 dark:border-blue-900/50 dark:from-blue-950/30 dark:to-sky-950/20 shadow-sm"
  >
    <div class="flex items-start sm:items-center gap-3.5">
      <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0 animate-pulse">
        <Clock class="w-6 h-6" />
      </div>
      <div>
        <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm sm:text-base flex items-center gap-2">
          <span>Dokumen Identitas Sedang Ditinjau Admin</span>
          <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-blue-200/80 text-blue-900 dark:bg-blue-900 dark:text-blue-200">
            Proses &lt; 24 Jam
          </span>
        </h4>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">
          Tim Admin Summit sedang memverifikasi keabsahan dokumen KTP/Paspor Anda. Anda akan mendapatkan notifikasi status setelah disetujui.
        </p>
      </div>
    </div>
  </div>

  <!-- 3. VERIFIED BANNER -->
  <div
    v-else-if="normalizedStatus === 'verified'"
    class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-4 sm:p-5 dark:border-emerald-900/50 dark:from-emerald-950/30 dark:to-teal-950/20 shadow-sm"
  >
    <div class="flex items-start sm:items-center gap-3.5">
      <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
        <ShieldCheck class="w-6 h-6" />
      </div>
      <div>
        <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm sm:text-base flex items-center gap-2">
          <span>Identitas Terverifikasi Resmi (KYC Approved)</span>
          <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-emerald-200 text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">
            Aktif
          </span>
        </h4>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">
          Akun Anda telah memenuhi regulasi SIMAKSI Taman Nasional. Anda dapat melakukan booking tiket dan sewa logistik tanpa hambatan.
        </p>
      </div>
    </div>
  </div>

  <!-- 4. REJECTED BANNER -->
  <div
    v-else-if="normalizedStatus === 'rejected'"
    class="relative overflow-hidden rounded-2xl border border-rose-200 bg-gradient-to-r from-rose-50 to-red-50 p-4 sm:p-5 dark:border-rose-900/50 dark:from-rose-950/30 dark:to-red-950/20 shadow-sm"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 shrink-0">
          <ShieldAlert class="w-6 h-6" />
        </div>
        <div>
          <h4 class="font-bold text-rose-900 dark:text-rose-200 text-sm sm:text-base">
            Pengajuan Verifikasi Identitas Ditolak
          </h4>
          <p v-if="rejectionReason" class="text-xs sm:text-sm text-rose-700 dark:text-rose-300 mt-1 bg-rose-100/60 dark:bg-rose-950/50 p-2.5 rounded-lg border border-rose-200 dark:border-rose-900">
            <strong>Alasan Petugas:</strong> {{ rejectionReason }}
          </p>
          <p v-else class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5">
            Dokumen identitas tidak jelas atau data tidak sesuai. Silakan periksa kembali foto dokumen Anda dan ajukan ulang.
          </p>
        </div>
      </div>
      <Button
        type="button"
        size="sm"
        variant="destructive"
        class="shadow-sm font-semibold flex items-center gap-1.5 shrink-0 self-start sm:self-center"
        @click="emit('verify')"
      >
        <RefreshCw class="w-4 h-4" />
        <span>Ajukan Ulang</span>
      </Button>
    </div>
  </div>
</template>
