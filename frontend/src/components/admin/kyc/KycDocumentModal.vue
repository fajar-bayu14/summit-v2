<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useSecureDocument } from '@/composables/useSecureDocument'
import { useToast } from '@/composables/useToast'
import { kycApi } from '@/api/kyc'
import type { KycProfile } from '@/types/kyc'
import {
  ZoomIn,
  ZoomOut,
  RotateCw,
  RefreshCw,
  ExternalLink,
  ShieldCheck,
  ShieldAlert,
  User,
  Phone,
  Calendar,
  MapPin,
  HeartHandshake,
  CheckCircle2,
  XCircle,
  AlertTriangle,
} from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  kyc: KycProfile | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'verified', updatedProfile: KycProfile): void
}>()

const toast = useToast()
const { documentUrl, loading: loadingDoc, error: docError, loadDocument, cleanup } = useSecureDocument()

// Image zoom & rotate states
const zoomLevel = ref<number>(1)
const rotation = ref<number>(0)

// Action states
const isRejecting = ref<boolean>(false)
const rejectionReason = ref<string>('')
const submitting = ref<boolean>(false)

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && props.kyc) {
      zoomLevel.value = 1
      rotation.value = 0
      isRejecting.value = false
      rejectionReason.value = ''
      loadDocument(props.kyc.id)
    } else {
      cleanup()
    }
  }
)

function handleZoomIn() {
  if (zoomLevel.value < 3) zoomLevel.value += 0.25
}

function handleZoomOut() {
  if (zoomLevel.value > 0.5) zoomLevel.value -= 0.25
}

function handleRotate() {
  rotation.value = (rotation.value + 90) % 360
}

function handleResetView() {
  zoomLevel.value = 1
  rotation.value = 0
}

async function handleApprove() {
  if (!props.kyc) return
  submitting.value = true

  try {
    const res = await kycApi.verifyKyc(props.kyc.id, {
      status_verifikasi: 'disetujui',
    })
    toast.success(`Identitas ${props.kyc.nama_lengkap} berhasil diverifikasi (Disetujui).`)
    emit('verified', res.data || { ...props.kyc, status_verifikasi: 'disetujui' })
    emit('update:open', false)
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Gagal memverifikasi identitas.')
  } finally {
    submitting.value = false
  }
}

async function handleReject() {
  if (!props.kyc) return
  if (!rejectionReason.value || rejectionReason.value.trim().length < 10) {
    toast.warning('Alasan penolakan wajib diisi minimal 10 karakter.')
    return
  }

  submitting.value = true

  try {
    const res = await kycApi.verifyKyc(props.kyc.id, {
      status_verifikasi: 'ditolak',
      alasan_penolakan: rejectionReason.value.trim(),
    })
    toast.success(`Pengajuan identitas ${props.kyc.nama_lengkap} ditolak.`)
    emit('verified', res.data || { ...props.kyc, status_verifikasi: 'ditolak', alasan_penolakan: rejectionReason.value })
    emit('update:open', false)
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Gagal menolak identitas.')
  } finally {
    submitting.value = false
  }
}

function openInNewTab() {
  if (documentUrl.value) {
    window.open(documentUrl.value, '_blank')
  }
}

function formatDate(dateStr: string | null | undefined) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-4xl w-[95vw] p-0 rounded-2xl overflow-hidden max-h-[92vh] flex flex-col bg-card">
      <DialogHeader class="p-5 pb-3 border-b bg-slate-50/50 dark:bg-slate-900/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="h-9 w-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
              <ShieldCheck class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold text-foreground flex items-center gap-2">
                Tinjauan Identitas Pendaki
                <Badge
                  :class="{
                    'bg-emerald-500/10 text-emerald-600 border-emerald-500/20': kyc?.status_verifikasi === 'disetujui',
                    'bg-amber-500/10 text-amber-600 border-amber-500/20': kyc?.status_verifikasi === 'pending',
                    'bg-red-500/10 text-red-600 border-red-500/20': kyc?.status_verifikasi === 'ditolak',
                  }"
                  class="capitalize text-xs font-semibold"
                >
                  {{ kyc?.status_verifikasi || 'pending' }}
                </Badge>
              </DialogTitle>
              <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                Pengajuan #KYC-{{ kyc?.id }} • Diajukan pada {{ formatDate(kyc?.created_at) }}
              </DialogDescription>
            </div>
          </div>
        </div>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-12 divide-y md:divide-y-0 md:divide-x divide-border">
        <!-- Left: Interactive Document Viewer -->
        <div class="md:col-span-7 flex flex-col bg-slate-950/5 dark:bg-slate-950/40 min-h-[380px]">
          <!-- Viewer Controls -->
          <div class="p-2.5 border-b bg-card/80 backdrop-blur-xs flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-1">
              <Button variant="outline" size="icon" class="h-7 w-7 rounded-md" title="Zoom In" @click="handleZoomIn">
                <ZoomIn class="h-3.5 w-3.5" />
              </Button>
              <Button variant="outline" size="icon" class="h-7 w-7 rounded-md" title="Zoom Out" @click="handleZoomOut">
                <ZoomOut class="h-3.5 w-3.5" />
              </Button>
              <Button variant="outline" size="icon" class="h-7 w-7 rounded-md" title="Putar 90°" @click="handleRotate">
                <RotateCw class="h-3.5 w-3.5" />
              </Button>
              <Button variant="outline" size="icon" class="h-7 w-7 rounded-md" title="Reset Tampilan" @click="handleResetView">
                <RefreshCw class="h-3.5 w-3.5" />
              </Button>
              <span class="text-muted-foreground text-[11px] font-mono ml-1">{{ Math.round(zoomLevel * 100) }}%</span>
            </div>

            <Button
              v-if="documentUrl"
              variant="ghost"
              size="sm"
              class="h-7 gap-1 text-xs text-muted-foreground hover:text-foreground"
              @click="openInNewTab"
            >
              <ExternalLink class="h-3.5 w-3.5" /> Tab Baru
            </Button>
          </div>

          <!-- Document Canvas -->
          <div class="flex-1 relative overflow-hidden flex items-center justify-center p-4 select-none min-h-[320px]">
            <div v-if="loadingDoc" class="flex flex-col items-center gap-2 text-muted-foreground">
              <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
              <span class="text-xs">Mengunduh dokumen aman...</span>
            </div>

            <div v-else-if="docError" class="flex flex-col items-center text-center p-6 text-red-500 gap-2">
              <ShieldAlert class="h-10 w-10 stroke-[1.5]" />
              <p class="text-xs font-semibold">{{ docError }}</p>
            </div>

            <div
              v-else-if="documentUrl"
              class="transition-transform duration-200 ease-out origin-center max-w-full max-h-full flex items-center justify-center"
              :style="{
                transform: `scale(${zoomLevel}) rotate(${rotation}deg)`,
              }"
            >
              <img
                :src="documentUrl"
                :alt="`Dokumen KYC ${kyc?.nama_lengkap}`"
                class="max-w-full max-h-[440px] rounded-lg shadow-md object-contain border border-slate-200 dark:border-slate-800"
              />
            </div>
          </div>
        </div>

        <!-- Right: Profile Comparison & Verification Details -->
        <div class="md:col-span-5 p-5 flex flex-col justify-between bg-card space-y-4">
          <div class="space-y-4">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              Rincian Identitas Terdaftar
            </h4>

            <div class="space-y-3 text-xs">
              <div class="flex items-start gap-2.5">
                <User class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <div>
                  <span class="text-muted-foreground block text-[11px]">Nama Lengkap Sesuai KTP</span>
                  <span class="font-bold text-foreground text-sm">{{ kyc?.nama_lengkap }}</span>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <ShieldCheck class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <div>
                  <span class="text-muted-foreground block text-[11px]">
                    {{ kyc?.jenis_identitas === 'ktp' ? 'Nomor Induk Kependudukan (NIK)' : 'Nomor Identitas' }}
                  </span>
                  <span class="font-mono font-bold text-foreground text-sm tracking-wide">
                    {{ kyc?.nomor_identitas }}
                  </span>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <Calendar class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <div>
                  <span class="text-muted-foreground block text-[11px]">Tanggal Lahir & Gender</span>
                  <span class="font-medium text-foreground">
                    {{ formatDate(kyc?.tanggal_lahir) }} • {{ kyc?.jenis_kelamin === 'l' ? 'Laki-laki' : (kyc?.jenis_kelamin === 'p' ? 'Perempuan' : '-') }}
                  </span>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <Phone class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <div>
                  <span class="text-muted-foreground block text-[11px]">Nomor Telepon Pendaki</span>
                  <span class="font-mono font-medium text-foreground">{{ kyc?.telepon || '-' }}</span>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <MapPin class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                <div>
                  <span class="text-muted-foreground block text-[11px]">Alamat Domisili</span>
                  <span class="font-medium text-foreground leading-relaxed">{{ kyc?.alamat || '-' }}</span>
                </div>
              </div>

              <div class="pt-2 border-t">
                <div class="flex items-start gap-2.5">
                  <HeartHandshake class="h-4 w-4 text-red-500 shrink-0 mt-0.5" />
                  <div>
                    <span class="text-muted-foreground block text-[11px]">Kontak Darurat Keluarga</span>
                    <span class="font-bold text-foreground">
                      {{ kyc?.nama_kontak_darurat || '-' }}
                      <span v-if="kyc?.hubungan_darurat" class="text-muted-foreground font-normal">({{ kyc.hubungan_darurat }})</span>
                    </span>
                    <span class="block font-mono text-muted-foreground mt-0.5">{{ kyc?.telepon_darurat || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Rejection Reason if previously rejected -->
              <div v-if="kyc?.status_verifikasi === 'ditolak' && kyc?.alasan_penolakan" class="p-3 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-900 rounded-xl text-red-900 dark:text-red-300">
                <div class="flex items-center gap-1.5 font-bold mb-1 text-[11px]">
                  <AlertTriangle class="h-3.5 w-3.5" /> Alasan Penolakan Sebelumnya:
                </div>
                <p class="text-xs leading-relaxed">{{ kyc.alasan_penolakan }}</p>
              </div>
            </div>
          </div>

          <!-- Rejection Form Input (If user triggers Reject) -->
          <div v-if="isRejecting" class="p-3.5 bg-red-50/60 dark:bg-red-950/30 border border-red-200 dark:border-red-900/60 rounded-xl space-y-2">
            <label class="block text-xs font-bold text-red-700 dark:text-red-400">
              Alasan Penolakan Verifikasi <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="rejectionReason"
              rows="3"
              class="w-full text-xs p-2.5 rounded-lg border border-red-300 dark:border-red-800 bg-card text-foreground focus:outline-none focus:ring-1 focus:ring-red-500 font-sans"
              placeholder="Jelaskan alasan penolakan (misal: Foto KTP buram, NIK tidak terbaca, data nama tidak sesuai)..."
            ></textarea>
            <div class="flex justify-end gap-2 pt-1">
              <Button variant="ghost" size="sm" class="h-7 text-xs" @click="isRejecting = false">
                Batal
              </Button>
              <Button
                variant="destructive"
                size="sm"
                class="h-7 text-xs gap-1"
                :disabled="submitting || rejectionReason.trim().length < 10"
                @click="handleReject"
              >
                <XCircle class="h-3.5 w-3.5" /> Konfirmasi Tolak
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <DialogFooter class="p-4 border-t bg-slate-50/50 dark:bg-slate-900/50 flex sm:justify-between items-center gap-3">
        <Button variant="outline" size="sm" class="rounded-xl text-xs h-9" @click="emit('update:open', false)">
          Tutup
        </Button>

        <div v-if="!isRejecting" class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl text-xs h-9 text-red-600 border-red-200 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/50 gap-1.5"
            :disabled="submitting"
            @click="isRejecting = true"
          >
            <XCircle class="h-4 w-4" /> Tolak Verifikasi
          </Button>

          <Button
            size="sm"
            class="rounded-xl text-xs h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5"
            :disabled="submitting"
            @click="handleApprove"
          >
            <CheckCircle2 class="h-4 w-4" /> Setujui KYC
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
