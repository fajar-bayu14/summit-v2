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
  Camera,
  Clock,
  Upload,
  RotateCcw,
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

// Form State for uploading logbook
const formFile = ref<File | null>(null)
const previewImageUrl = ref<string | null>(null)
const waktuSummit = ref<string>(new Date().toISOString().slice(0, 16))
const catatanPendaki = ref<string>('')
const isSubmitting = ref(false)
const submitError = ref<string | null>(null)
const isReuploading = ref(false)

async function fetchLogbook() {
  if (!invoice.value) return
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await pendakiLogbookApi.getLogbook(invoice.value)
    logbook.value = res.data || null
  } catch (err: unknown) {
    const errorData = extractApiError(err)
    const status = (err as any)?.response?.status
    // If not found / 404, it means climber hasn't submitted logbook yet
    if (status === 404 || errorData.message?.toLowerCase().includes('belum')) {
      logbook.value = null
    } else {
      errorMessage.value = errorData.message
    }
  } finally {
    isLoading.value = false
  }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    formFile.value = file
    previewImageUrl.value = URL.createObjectURL(file)
  }
}

async function handleSubmitLogbook() {
  if (!formFile.value) {
    submitError.value = 'Silakan pilih foto bukti summit terlebih dahulu.'
    return
  }

  isSubmitting.value = true
  submitError.value = null

  try {
    const formData = new FormData()
    formData.append('foto_summit', formFile.value)
    if (waktuSummit.value) {
      formData.append('waktu_summit', waktuSummit.value)
    }
    if (catatanPendaki.value) {
      formData.append('catatan_pendaki', catatanPendaki.value)
    }

    const res = await pendakiLogbookApi.submitLogbook(invoice.value, formData)
    logbook.value = res.data || null
    isReuploading.value = false
  } catch (err: unknown) {
    submitError.value = extractApiError(err).message || 'Gagal mengunggah bukti logbook summit.'
  } finally {
    isSubmitting.value = false
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
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors cursor-pointer"
        @click="router.push('/pendaki/orders')"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Riwayat Pesanan</span>
      </button>

      <div v-if="logbook && logbook.status_validasi === 'approved'" class="flex items-center gap-2">
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
      <p class="text-xs text-slate-500">Memeriksa status logbook &amp; sertifikat...</p>
    </div>

    <!-- General Error State -->
    <div
      v-else-if="errorMessage"
      class="p-6 rounded-3xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center gap-3"
    >
      <AlertCircle class="w-5 h-5 shrink-0 text-rose-600" />
      <div>
        <strong class="font-bold text-sm block">Terjadi Kesalahan</strong>
        <p>{{ errorMessage }}</p>
      </div>
    </div>

    <!-- STATE 1: Logbook Belum Diisi / Mode Upload Form -->
    <div
      v-else-if="!logbook || isReuploading"
      class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden"
    >
      <div class="p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800 bg-emerald-50/40 dark:bg-emerald-950/20">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
            <Camera class="w-6 h-6" />
          </div>
          <div>
            <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
              E-Logbook Pendakian &amp; Klaim Sertifikat
            </span>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-display mt-0.5">
              Unggah Bukti Puncak (Summit Proof)
            </h2>
            <p class="text-xs text-slate-500 mt-1">
              Invoice: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ invoice }}</strong>. Laporkan keberhasilan pendakian Anda untuk divalidasi oleh petugas basecamp.
            </p>
          </div>
        </div>
      </div>

      <form class="p-6 sm:p-8 space-y-6" @submit.prevent="handleSubmitLogbook">
        <!-- Error alert if submit failed -->
        <div
          v-if="submitError"
          class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-center gap-2"
        >
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ submitError }}</span>
        </div>

        <!-- File Upload Area -->
        <div class="space-y-2">
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
            Foto Bukti di Puncak / Titik Tertinggi <span class="text-rose-500">*</span>
          </label>

          <div
            class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-6 text-center hover:border-emerald-500 transition-colors bg-slate-50/50 dark:bg-slate-800/50"
          >
            <div v-if="previewImageUrl" class="space-y-3">
              <img
                :src="previewImageUrl"
                alt="Pratinjau Foto Summit"
                class="max-h-64 mx-auto rounded-xl shadow-sm object-cover border border-slate-200 dark:border-slate-700"
              />
              <p class="text-xs text-slate-500">
                {{ formFile?.name }} ({{ ((formFile?.size || 0) / 1024).toFixed(1) }} KB)
              </p>
              <label class="inline-block px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold cursor-pointer transition-colors">
                Ganti Foto
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/jpg"
                  class="hidden"
                  @change="handleFileChange"
                />
              </label>
            </div>

            <div v-else class="space-y-3 py-4">
              <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center mx-auto">
                <Upload class="w-6 h-6" />
              </div>
              <div>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Klik untuk memilih foto atau seret foto ke sini
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  Format JPG, JPEG, atau PNG (Maksimal 5MB)
                </p>
              </div>
              <label class="inline-block px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold cursor-pointer transition-colors shadow-xs">
                Pilih Berkas Foto
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/jpg"
                  class="hidden"
                  @change="handleFileChange"
                />
              </label>
            </div>
          </div>
        </div>

        <!-- Waktu Summit & Catatan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
              Waktu / Tanggal Mencapai Puncak
            </label>
            <input
              v-model="waktuSummit"
              type="datetime-local"
              class="w-full h-10 px-3.5 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
              Catatan / Pengalaman Pendakian (Opsional)
            </label>
            <textarea
              v-model="catatanPendaki"
              rows="3"
              placeholder="Ceritakan kondisi jalur, cuaca di puncak, atau pesan untuk pengelola..."
              class="w-full p-3 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            ></textarea>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <Button
            v-if="isReuploading"
            type="button"
            variant="outline"
            class="rounded-xl text-xs font-bold"
            @click="isReuploading = false"
          >
            Batal
          </Button>

          <Button
            type="submit"
            class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold min-h-[44px] px-6 gap-2 shadow-xs"
            :disabled="isSubmitting || !formFile"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <Award v-else class="w-4 h-4" />
            <span>{{ isSubmitting ? 'Mengunggah Laporan...' : 'Kirim Laporan Logbook' }}</span>
          </Button>
        </div>
      </form>
    </div>

    <!-- STATE 2: Logbook Pending Validation -->
    <div
      v-else-if="logbook.status_validasi === 'pending'"
      class="bg-white dark:bg-slate-900 rounded-3xl border border-amber-200 dark:border-amber-900/50 p-6 sm:p-8 space-y-6 shadow-xs"
    >
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0">
          <Clock class="w-6 h-6" />
        </div>
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
              Menunggu Validasi Basecamp
            </span>
          </div>
          <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 font-display">
            Laporan Bukti Summit Berhasil Dikirim!
          </h2>
          <p class="text-xs text-slate-500 leading-relaxed max-w-xl">
            Bukti foto puncak Anda sedang ditinjau oleh petugas pos basecamp. Setelah disetujui, pesanan akan diselesaikan secara resmi dan sertifikat digital akan otomatis diterbitkan di halaman ini.
          </p>
        </div>
      </div>

      <!-- Preview Summary Card -->
      <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row gap-5 items-center">
        <img
          v-if="logbook.foto_summit"
          :src="logbook.foto_summit"
          alt="Foto Summit"
          class="w-40 h-28 object-cover rounded-xl border border-slate-200 shadow-xs"
        />
        <div class="space-y-1.5 flex-1 text-xs text-slate-600 dark:text-slate-300">
          <p><strong>Gunung:</strong> {{ logbook.gunung_nama }} ({{ formatMDPL(logbook.tinggi_mdpl) }})</p>
          <p><strong>Jalur:</strong> {{ logbook.jalur_nama }}</p>
          <p><strong>Waktu Summit:</strong> {{ formatDateIndonesia(logbook.waktu_summit || logbook.created_at) }}</p>
          <p v-if="logbook.catatan_pendaki"><strong>Catatan:</strong> "{{ logbook.catatan_pendaki }}"</p>
        </div>
      </div>
    </div>

    <!-- STATE 3: Logbook Rejected -->
    <div
      v-else-if="logbook.status_validasi === 'rejected'"
      class="bg-white dark:bg-slate-900 rounded-3xl border border-rose-200 p-6 sm:p-8 space-y-6 shadow-xs"
    >
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
          <AlertCircle class="w-6 h-6" />
        </div>
        <div class="space-y-1">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
            Validasi Ditolak oleh Basecamp
          </span>
          <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 font-display">
            Bukti Foto Belum Sesuai
          </h2>
          <p class="text-xs text-slate-500">
            Alasan penolakan: <strong class="text-rose-700">{{ logbook.catatan_petugas || 'Foto tidak jelas atau tidak memenuhi kriteria verifikasi pos basecamp.' }}</strong>
          </p>
        </div>
      </div>

      <Button
        class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-2 min-h-[44px]"
        @click="isReuploading = true"
      >
        <RotateCcw class="w-4 h-4" />
        <span>Unggah Ulang Bukti Summit</span>
      </Button>
    </div>

    <!-- STATE 4: Logbook Approved -> Official Certificate Frame -->
    <div
      v-else-if="logbook.status_validasi === 'approved'"
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
