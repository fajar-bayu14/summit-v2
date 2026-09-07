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
  Mountain,
  MapPin,
  Clock,
  ZoomIn,
  ZoomOut,
  RotateCw,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Award,
  Loader2,
  ExternalLink,
} from 'lucide-vue-next'
import type { LogbookEntry, VerifyLogbookPayload } from '@/types/logbook'
import mitraLogbooksApi from '@/api/mitraLogbooks'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    logbook: LogbookEntry | null
  }>(),
  {
    isOpen: false,
    logbook: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'verified', logbook: LogbookEntry): void
  (e: 'close'): void
}>()

// Photo viewer state
const zoomLevel = ref<number>(1)
const rotationAngle = ref<number>(0)

// Form state
const decision = ref<'approved' | 'rejected'>('approved')
const officerNotes = ref<string>('')
const formError = ref<string | null>(null)
const isSubmitting = ref<boolean>(false)
const serverMessage = ref<string | null>(null)
const serverError = ref<string | null>(null)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      resetViewer()
      decision.value = 'approved'
      officerNotes.value = ''
      formError.value = null
      serverMessage.value = null
      serverError.value = null
    }
  }
)

function resetViewer() {
  zoomLevel.value = 1
  rotationAngle.value = 0
}

function handleZoomIn() {
  if (zoomLevel.value < 2.5) {
    zoomLevel.value = Number((zoomLevel.value + 0.25).toFixed(2))
  }
}

function handleZoomOut() {
  if (zoomLevel.value > 0.5) {
    zoomLevel.value = Number((zoomLevel.value - 0.25).toFixed(2))
  }
}

function handleRotate() {
  rotationAngle.value = (rotationAngle.value + 90) % 360
}

function formatDateTimeIndo(dateStr?: string | null): string {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

async function handleVerificationSubmit() {
  if (!props.logbook) return
  formError.value = null
  serverError.value = null
  serverMessage.value = null

  if (decision.value === 'rejected' && !officerNotes.value.trim()) {
    formError.value = 'Wajib memberikan catatan alasan penolakan bukti summit.'
    return
  }

  isSubmitting.value = true

  try {
    const payload: VerifyLogbookPayload = {
      status_validasi: decision.value,
      catatan_petugas: officerNotes.value.trim() || undefined,
    }

    const res = await mitraLogbooksApi.verifyLogbook(props.logbook.id, payload)
    const updated = res.data ?? {
      ...props.logbook,
      status_validasi: decision.value,
      catatan_petugas: officerNotes.value.trim(),
    }

    serverMessage.value = decision.value === 'approved'
      ? 'Bukti summit berhasil disetujui! Pesanan selesai dan e-sertifikat diterbitkan.'
      : 'Bukti summit telah ditolak.'

    emit('verified', updated)
  } catch (err) {
    serverError.value = getApiErrorMessage(err, 'Gagal memproses validasi bukti summit.')
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
    <DialogContent class="sm:max-w-4xl bg-white rounded-2xl p-6 max-h-[92vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center font-bold">
              <Mountain class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <DialogTitle class="text-lg font-bold text-stone-900">
                  Tinjau Bukti Puncak (Summit Proof)
                </DialogTitle>
                <StatusBadge v-if="props.logbook" :status="props.logbook.status_validasi" />
              </div>
              <DialogDescription class="text-xs text-stone-500 mt-0.5">
                Invoice: <strong class="text-stone-700">{{ props.logbook?.invoice || props.logbook?.pesanan?.invoice || '-' }}</strong> •
                Pendaki: <strong class="text-stone-700">{{ props.logbook?.nama_pendaki || props.logbook?.user?.name || '-' }}</strong>
              </DialogDescription>
            </div>
          </div>

          <div v-if="props.logbook?.certificate_url" class="shrink-0">
            <a
              :href="props.logbook.certificate_url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-300 rounded-xl text-xs font-bold transition-colors"
            >
              <Award class="w-4 h-4 text-emerald-600" />
              Unduh E-Sertifikat
              <ExternalLink class="w-3.5 h-3.5 ml-0.5" />
            </a>
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

      <div v-if="props.logbook" class="grid grid-cols-1 lg:grid-cols-12 gap-5 py-4">
        <!-- Kolom Kiri: Photo Viewer Interaktif -->
        <div class="lg:col-span-7 space-y-3">
          <div class="relative bg-stone-900 rounded-2xl overflow-hidden min-h-[340px] flex items-center justify-center border border-stone-800">
            <!-- Kontrol Toolbar Zoom & Rotate -->
            <div class="absolute top-3 right-3 z-10 flex items-center gap-1.5 bg-black/60 backdrop-blur-md p-1.5 rounded-xl border border-white/10 text-white">
              <button
                type="button"
                title="Zoom In"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/20 transition-colors"
                @click="handleZoomIn"
              >
                <ZoomIn class="w-4 h-4" />
              </button>
              <button
                type="button"
                title="Zoom Out"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/20 transition-colors"
                @click="handleZoomOut"
              >
                <ZoomOut class="w-4 h-4" />
              </button>
              <button
                type="button"
                title="Rotate"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/20 transition-colors"
                @click="handleRotate"
              >
                <RotateCw class="w-4 h-4" />
              </button>
              <button
                type="button"
                title="Reset"
                class="px-2 h-8 text-[11px] font-bold flex items-center justify-center rounded-lg hover:bg-white/20 transition-colors"
                @click="resetViewer"
              >
                {{ Math.round(zoomLevel * 100) }}%
              </button>
            </div>

            <!-- Foto Preview -->
            <div
              v-if="props.logbook.foto_summit"
              class="overflow-hidden p-2 transition-transform duration-200"
            >
              <img
                :src="props.logbook.foto_summit"
                alt="Foto Bukti Summit"
                class="max-h-[380px] w-auto object-contain rounded-lg transition-transform duration-200 ease-out"
                :style="{
                  transform: `scale(${zoomLevel}) rotate(${rotationAngle}deg)`,
                }"
              />
            </div>
            <div v-else class="text-center p-8 text-stone-500 space-y-2">
              <Mountain class="w-12 h-12 mx-auto text-stone-600" />
              <p class="text-xs">Foto summit belum diunggah oleh pendaki.</p>
            </div>
          </div>

          <!-- GPS & Time Bar -->
          <div class="p-3 bg-stone-50 rounded-xl border border-stone-200 text-xs text-stone-600 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1.5">
              <Clock class="w-4 h-4 text-stone-400" />
              <span>Waktu Summit: <strong>{{ formatDateTimeIndo(props.logbook.waktu_summit) }}</strong></span>
            </div>
            <div v-if="props.logbook.latitude && props.logbook.longitude" class="flex items-center gap-1.5">
              <MapPin class="w-4 h-4 text-emerald-600" />
              <span>GPS: <strong>{{ props.logbook.latitude }}, {{ props.logbook.longitude }}</strong></span>
              <a
                :href="`https://maps.google.com/?q=${props.logbook.latitude},${props.logbook.longitude}`"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-600 hover:underline inline-flex items-center ml-1 font-semibold"
              >
                Peta
                <ExternalLink class="w-3 h-3 ml-0.5" />
              </a>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Rincian & Form Validasi -->
        <div class="lg:col-span-5 space-y-4">
          <!-- Detail Ekspedisi Box -->
          <div class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-3 text-xs">
            <div class="font-bold text-stone-800 text-sm border-b border-stone-200 pb-2">
              Rincian Ekspedisi & Pendaki
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <span class="text-[10px] text-stone-400 uppercase font-bold">Gunung & Jalur</span>
                <p class="font-bold text-stone-900">
                  {{ props.logbook.gunung_nama || props.logbook.pesanan?.jalur?.gunung?.nama_gunung || '-' }}
                  <span class="font-normal text-stone-600">({{ props.logbook.jalur_nama || props.logbook.pesanan?.jalur?.nama_jalur || '-' }})</span>
                </p>
              </div>

              <div>
                <span class="text-[10px] text-stone-400 uppercase font-bold">Ketinggian Puncak</span>
                <p class="font-bold text-[#1E3A2B]">
                  {{ props.logbook.tinggi_mdpl || props.logbook.pesanan?.jalur?.gunung?.tinggi_mdpl || 0 }} MDPL
                </p>
              </div>

              <div>
                <span class="text-[10px] text-stone-400 uppercase font-bold">Nama Pendaki</span>
                <p class="font-bold text-stone-900">
                  {{ props.logbook.nama_pendaki || props.logbook.user?.name || '-' }}
                </p>
              </div>

              <div>
                <span class="text-[10px] text-stone-400 uppercase font-bold">Kontak</span>
                <p class="font-bold text-stone-900">
                  {{ props.logbook.user?.telepon || props.logbook.user?.email || '-' }}
                </p>
              </div>
            </div>

            <!-- Catatan Pendaki -->
            <div v-if="props.logbook.catatan_pendaki" class="pt-2 border-t border-stone-200">
              <span class="text-[10px] text-stone-400 uppercase font-bold">Catatan / Kesan Pendaki:</span>
              <p class="text-stone-700 italic mt-0.5 bg-white p-2.5 rounded-xl border border-stone-200">
                "{{ props.logbook.catatan_pendaki }}"
              </p>
            </div>
          </div>

          <!-- Form Keputusan Petugas (Jika Pending) -->
          <div
            v-if="props.logbook.status_validasi === 'pending'"
            class="p-4 bg-white rounded-2xl border-2 border-[#1E3A2B]/20 space-y-3 shadow-xs"
          >
            <div class="font-bold text-stone-900 text-sm">
              Keputusan Validasi Petugas
            </div>

            <!-- Decision Radio Pills -->
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
                Setujui (Sah)
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
                Tolak Bukti
              </button>
            </div>

            <!-- Action Explanations -->
            <div
              v-if="decision === 'approved'"
              class="p-3 bg-emerald-50/70 text-emerald-800 text-xs rounded-xl border border-emerald-200"
            >
              <strong>Dampak Persetujuan:</strong> Pesanan akan ditandai Selesai (Completed), dana escrow otomatis diteruskan ke saldo mitra, dan e-sertifikat digital langsung terbit.
            </div>

            <div
              v-else
              class="p-3 bg-red-50/70 text-red-800 text-xs rounded-xl border border-red-200"
            >
              <strong>Dampak Penolakan:</strong> Klaim summit ditolak dan sertifikat tidak akan diterbitkan.
            </div>

            <!-- Notes Textarea -->
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-stone-700">
                Catatan Petugas Basecamp
                <span v-if="decision === 'rejected'" class="text-red-500">* (Wajib diisi)</span>
                <span v-else class="text-stone-400 font-normal">(Opsional)</span>:
              </label>
              <textarea
                v-model="officerNotes"
                placeholder="Contoh: Foto sah di puncak triangulasi / Foto bukan di puncak..."
                rows="3"
                class="w-full p-3 text-xs rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20 focus:border-[#1E3A2B]"
              ></textarea>
              <p v-if="formError" class="text-xs text-red-600 font-medium">{{ formError }}</p>
            </div>

            <!-- Submit Button -->
            <Button
              :disabled="isSubmitting"
              :class="[
                'w-full h-11 rounded-xl font-bold text-xs shadow-sm min-h-[44px]',
                decision === 'approved'
                  ? 'bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white'
                  : 'bg-red-600 hover:bg-red-700 text-white',
              ]"
              @click="handleVerificationSubmit"
            >
              <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
              <span v-if="decision === 'approved'">Konfirmasi Persetujuan Bukti Summit</span>
              <span v-else>Konfirmasi Penolakan Bukti Summit</span>
            </Button>
          </div>

          <!-- Status Box jika Sudah Diverifikasi Sebelumnya -->
          <div
            v-else
            class="p-4 rounded-2xl border space-y-2 text-xs"
            :class="props.logbook.status_validasi === 'approved' ? 'bg-emerald-50/60 border-emerald-200' : 'bg-red-50/60 border-red-200'"
          >
            <div class="flex items-center gap-2 font-bold" :class="props.logbook.status_validasi === 'approved' ? 'text-emerald-900' : 'text-red-900'">
              <CheckCircle2 v-if="props.logbook.status_validasi === 'approved'" class="w-4 h-4 text-emerald-600" />
              <XCircle v-else class="w-4 h-4 text-red-600" />
              <span>Logbook telah diverifikasi: {{ props.logbook.status_validasi.toUpperCase() }}</span>
            </div>
            <div class="text-stone-600">
              Divalidasi pada: {{ formatDateTimeIndo(props.logbook.validated_at) }}
              <span v-if="props.logbook.validated_by">oleh <strong>{{ props.logbook.validated_by }}</strong></span>
            </div>
            <div v-if="props.logbook.catatan_petugas" class="pt-1 text-stone-700">
              Catatan: <em>"{{ props.logbook.catatan_petugas }}"</em>
            </div>
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
