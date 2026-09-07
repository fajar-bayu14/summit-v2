<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Award,
  Printer,
  AlertCircle,
  Loader2,
  ShieldCheck,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'
import { formatDateIndonesia, formatMDPL } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { LogbookEntry } from '@/types/pendakiLogbook'

const route = useRoute()
const router = useRouter()

const invoice = computed(() => String(route.params.invoice || ''))
const logbook = ref<LogbookEntry | null>(null)
const isLoading = ref(true)
const errorMessage = ref('')

async function fetchLogbook() {
  if (!invoice.value) return
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await pendakiLogbookApi.getLogbook(invoice.value)
    logbook.value = res.data || null
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

function handlePrint() {
  window.print()
}

const qrVerificationUrl = computed(() => {
  const code = logbook.value?.certificate_url || `https://summit.id/verify/${invoice.value}`
  return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(code)}&margin=5`
})

onMounted(() => {
  fetchLogbook()
})
</script>

<template>
  <div class="max-w-4xl mx-auto pb-24 space-y-6">
    <!-- Non-printable top action bar -->
    <div class="print:hidden flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
        @click="router.push('/pendaki/orders')"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Riwayat Pesanan</span>
      </button>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl text-xs font-bold gap-1.5 shadow-xs"
          @click="handlePrint"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Cetak / Simpan PDF</span>
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="py-20 text-center space-y-3">
      <Loader2 class="w-8 h-8 animate-spin text-emerald-600 mx-auto" />
      <p class="text-xs text-slate-500">Memuat sertifikat summit...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="errorMessage || !logbook"
      class="p-6 rounded-3xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-center gap-3"
    >
      <AlertCircle class="w-5 h-5 shrink-0" />
      <div>
        <strong class="font-bold text-sm block">Sertifikat Belum Tersedia</strong>
        <p>{{ errorMessage || 'Bukti summit sedang menunggu verifikasi atau belum diunggah.' }}</p>
      </div>
    </div>

    <!-- Official Certificate Frame -->
    <div
      v-else
      class="bg-amber-50/20 dark:bg-slate-900 rounded-3xl border-4 border-amber-400/60 p-4 sm:p-8 relative shadow-2xl overflow-hidden print:m-0 print:border-amber-500 print:shadow-none"
    >
      <!-- Outer Certificate Border Pattern -->
      <div class="border-2 border-emerald-800/80 rounded-2xl p-6 sm:p-12 text-center relative bg-white/90 dark:bg-slate-900/90 backdrop-blur-xs space-y-8">
        <!-- Top Seal & Logo -->
        <div class="space-y-2">
          <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-amber-600 via-amber-400 to-yellow-300 text-slate-950 flex items-center justify-center mx-auto shadow-md">
            <Award class="w-9 h-9" />
          </div>
          <span class="text-xs font-black tracking-widest uppercase text-emerald-800 dark:text-emerald-400 block">
            SUMMIT INDONESIA • OFFICIAL CERTIFICATE
          </span>
          <h1 class="text-2xl sm:text-4xl font-black font-display tracking-tight text-slate-900 dark:text-slate-100 uppercase">
            Sertifikat Penakluk Puncak
          </h1>
          <p class="text-xs text-slate-500 font-mono">
            No. Sertifikat: SUMMIT-CERT-{{ logbook.invoice.replace(/[^a-zA-Z0-9]/g, '') }}
          </p>
        </div>

        <!-- Awarded To -->
        <div class="space-y-2 py-4">
          <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">
            Diberikan dengan bangga dan kehormatan kepada:
          </span>
          <h2 class="text-2xl sm:text-4xl font-extrabold text-emerald-900 dark:text-emerald-300 font-display tracking-wide uppercase underline decoration-amber-400 decoration-2 underline-offset-8">
            {{ logbook.nama_pendaki || 'Pendaki Tangguh' }}
          </h2>
        </div>

        <!-- Mountain & Achievement Details -->
        <div class="max-w-xl mx-auto space-y-3 text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
          <p>
            Telah berhasil menyelesaikan pendakian dan mencapai titik tertinggi puncak:
          </p>
          <div class="p-4 rounded-2xl bg-emerald-50/80 dark:bg-slate-800/80 border border-emerald-200/80 dark:border-slate-700 space-y-1">
            <h3 class="text-xl sm:text-2xl font-black text-emerald-950 dark:text-emerald-100">
              {{ logbook.gunung_nama }}
            </h3>
            <p class="font-bold text-amber-700 dark:text-amber-400">
              Ketinggian: {{ formatMDPL(logbook.tinggi_mdpl) }}
            </p>
            <p class="text-xs text-slate-500">
              Melalui {{ logbook.jalur_nama }} • Tanggal Summit: {{ formatDateIndonesia(logbook.waktu_summit || logbook.validated_at) }}
            </p>
          </div>
        </div>

        <!-- Summit Photo Snapshot (if present) -->
        <div v-if="logbook.foto_summit" class="max-w-xs mx-auto text-center space-y-1">
          <img
            :src="logbook.foto_summit"
            alt="Bukti Foto Puncak"
            class="w-48 h-32 object-cover rounded-xl mx-auto border-2 border-amber-300 shadow-sm"
          />
          <span class="text-[10px] text-slate-400 italic">Snapshot Foto di Puncak</span>
        </div>

        <!-- Signatures & Verification Seal Grid -->
        <div class="pt-6 border-t border-slate-200 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-6 items-end text-xs">
          <!-- Left: Verification QR -->
          <div class="flex flex-col items-center sm:items-start text-center sm:text-left space-y-1">
            <img
              :src="qrVerificationUrl"
              alt="QR Verification"
              class="w-16 h-16 object-contain rounded-lg border border-slate-200 shadow-xs"
            />
            <span class="text-[10px] text-slate-400 font-mono">Scan untuk Verifikasi</span>
          </div>

          <!-- Middle: Official Stamp Badge -->
          <div class="text-center space-y-1">
            <div class="w-12 h-12 rounded-full border-2 border-dashed border-emerald-600 text-emerald-700 flex items-center justify-center mx-auto">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <span class="font-bold text-[11px] text-emerald-800 dark:text-emerald-400 block uppercase">
              Resmi Terverifikasi
            </span>
            <span class="text-[10px] text-slate-400">Balai Pengelola Kawasan</span>
          </div>

          <!-- Right: Basecamp Validator Signature -->
          <div class="text-center sm:text-right space-y-1">
            <span class="text-[11px] text-slate-500 block">Divalidasi oleh Petugas:</span>
            <strong class="text-xs font-bold text-slate-900 dark:text-slate-100 block underline">
              {{ logbook.validated_by || 'Petugas Pos Basecamp' }}
            </strong>
            <span class="text-[10px] text-slate-400">Basecamp Pengelola</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
