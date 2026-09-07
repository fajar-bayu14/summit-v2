<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  ShieldCheck,
  UploadCloud,
  X,
  AlertCircle,
  CheckCircle2,
  Phone,
  User,
} from 'lucide-vue-next'
import { pendakiKycApi } from '@/api/pendakiKyc'
import { extractApiError } from '@/lib/normalizer'
import { useAuthStore } from '@/stores/auth'
import type { PendakiProfile, IdentityType, GenderType } from '@/types/pendakiKyc'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    initialData?: Partial<PendakiProfile> | null
  }>(),
  {
    isOpen: false,
    initialData: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submitted', data: PendakiProfile): void
}>()

const authStore = useAuthStore()

const form = reactive({
  nama_lengkap: '',
  jenis_identitas: 'ktp' as IdentityType,
  nomor_identitas: '',
  tanggal_lahir: '',
  jenis_kelamin: 'l' as GenderType,
  alamat: '',
  telepon: '',
  nama_kontak_darurat: '',
  telepon_darurat: '',
  hubungan_darurat: 'Orang Tua',
})

const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const isSubmitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string>>({})

// Populate initial data when modal opens
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errorMessage.value = null
      successMessage.value = null
      fieldErrors.value = {}
      
      const source = props.initialData || authStore.user?.pendaki
      if (source) {
        form.nama_lengkap = source.nama_lengkap || authStore.user?.name || ''
        form.jenis_identitas = (source.jenis_identitas as IdentityType) || 'ktp'
        form.nomor_identitas = source.nomor_identitas || ''
        form.tanggal_lahir = source.tanggal_lahir ? source.tanggal_lahir.substring(0, 10) : ''
        form.jenis_kelamin = (source.jenis_kelamin as GenderType) || 'l'
        form.alamat = source.alamat || ''
        form.telepon = source.telepon || ''
        form.nama_kontak_darurat = source.nama_kontak_darurat || ''
        form.telepon_darurat = source.telepon_darurat || ''
        form.hubungan_darurat = source.hubungan_darurat || 'Orang Tua'
      } else if (authStore.user) {
        form.nama_lengkap = authStore.user.name || ''
      }
    } else {
      clearFile()
    }
  },
  { immediate: true }
)

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    
    // File validation: format & max 2MB
    if (!['image/jpeg', 'image/png', 'image/jpg', 'image/webp'].includes(file.type)) {
      fieldErrors.value.foto_identitas = 'Format file harus berupa gambar JPEG, PNG, atau WebP.'
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      fieldErrors.value.foto_identitas = 'Ukuran file foto maksimal 2MB.'
      return
    }

    fieldErrors.value.foto_identitas = ''
    selectedFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

function clearFile() {
  selectedFile.value = null
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
}

function handleClose() {
  emit('update:isOpen', false)
}

function validateClient(): boolean {
  fieldErrors.value = {}
  let valid = true

  if (!form.nama_lengkap.trim()) {
    fieldErrors.value.nama_lengkap = 'Nama lengkap wajib diisi sesuai KTP.'
    valid = false
  }

  if (!form.nomor_identitas.trim()) {
    fieldErrors.value.nomor_identitas = 'Nomor identitas resmi wajib diisi.'
    valid = false
  } else if (form.jenis_identitas === 'ktp' && !/^\d{16}$/.test(form.nomor_identitas.trim())) {
    fieldErrors.value.nomor_identitas = 'Nomor Induk Kependudukan (NIK) KTP harus terdiri dari 16 digit angka.'
    valid = false
  }

  if (!form.tanggal_lahir) {
    fieldErrors.value.tanggal_lahir = 'Tanggal lahir wajib diisi.'
    valid = false
  }

  if (!form.telepon.trim()) {
    fieldErrors.value.telepon = 'Nomor telepon/WhatsApp wajib diisi.'
    valid = false
  }

  if (!form.alamat.trim()) {
    fieldErrors.value.alamat = 'Alamat lengkap domisili wajib diisi.'
    valid = false
  }

  if (!form.nama_kontak_darurat.trim()) {
    fieldErrors.value.nama_kontak_darurat = 'Nama kontak darurat wajib diisi.'
    valid = false
  }

  if (!form.telepon_darurat.trim()) {
    fieldErrors.value.telepon_darurat = 'Nomor telepon kontak darurat wajib diisi.'
    valid = false
  }

  if (!selectedFile.value && !props.initialData?.foto_identitas) {
    fieldErrors.value.foto_identitas = 'Berkas foto KTP/Paspor wajib diunggah.'
    valid = false
  }

  return valid
}

async function handleSubmit() {
  if (!validateClient()) return

  isSubmitting.value = true
  errorMessage.value = null
  successMessage.value = null

  try {
    const formData = new FormData()
    formData.append('nama_lengkap', form.nama_lengkap)
    formData.append('jenis_identitas', form.jenis_identitas)
    formData.append('nomor_identitas', form.nomor_identitas)
    formData.append('tanggal_lahir', form.tanggal_lahir)
    formData.append('jenis_kelamin', form.jenis_kelamin)
    formData.append('alamat', form.alamat)
    formData.append('telepon', form.telepon)
    formData.append('nama_kontak_darurat', form.nama_kontak_darurat)
    formData.append('telepon_darurat', form.telepon_darurat)
    formData.append('hubungan_darurat', form.hubungan_darurat)

    if (selectedFile.value) {
      formData.append('foto_identitas', selectedFile.value)
    }

    const response = await pendakiKycApi.submitKyc(formData)
    
    if (response.data) {
      successMessage.value = 'Dokumen KYC berhasil dikirim! Status verifikasi telah diperbarui menjadi pending.'
      authStore.updatePendakiProfile(response.data)
      emit('submitted', response.data)
      
      setTimeout(() => {
        handleClose()
      }, 1200)
    }
  } catch (err: any) {
    const { message, fieldErrors: serverFieldErrors } = extractApiError(err)
    errorMessage.value = message
    if (Object.keys(serverFieldErrors).length > 0) {
      fieldErrors.value = serverFieldErrors
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto p-0 rounded-2xl sm:rounded-3xl border-slate-200 dark:border-slate-800">
      <!-- Modal Header -->
      <div class="sticky top-0 z-10 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100 leading-tight">
              Verifikasi Identitas Pendaki (KYC)
            </DialogTitle>
            <DialogDescription class="text-xs text-slate-500 dark:text-slate-400">
              Isi data identitas resmi sesuai KTP/Paspor untuk memenuhi syarat legal SIMAKSI.
            </DialogDescription>
          </div>
        </div>
      </div>

      <!-- Modal Body Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Global Feedback Messages -->
        <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-2.5">
          <AlertCircle class="w-5 h-5 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2.5">
          <CheckCircle2 class="w-5 h-5 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Section 1: Data Identitas Resmi -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100 font-bold text-sm">
            <User class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            <span>1. Data Identitas Resmi</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Jenis Identitas -->
            <div class="space-y-1.5">
              <Label for="jenis_identitas" class="text-xs font-semibold">Jenis Dokumen Identitas</Label>
              <select
                id="jenis_identitas"
                v-model="form.jenis_identitas"
                class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 font-medium"
              >
                <option value="ktp">KTP (Kartu Tanda Penduduk)</option>
                <option value="paspor">Paspor (WNA / International)</option>
                <option value="sim">SIM (Surat Izin Mengemudi)</option>
                <option value="lainnya">Lainnya / Kartu Pelajar</option>
              </select>
            </div>

            <!-- Nomor Identitas (NIK) -->
            <div class="space-y-1.5">
              <Label for="nomor_identitas" class="text-xs font-semibold">
                {{ form.jenis_identitas === 'ktp' ? 'Nomor Induk Kependudukan (NIK 16 Digit)' : 'Nomor Identitas Dokumen' }}
                <span class="text-rose-500">*</span>
              </Label>
              <Input
                id="nomor_identitas"
                v-model="form.nomor_identitas"
                :placeholder="form.jenis_identitas === 'ktp' ? 'Contoh: 3201123456780001' : 'Nomor Dokumen'"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.nomor_identitas }"
              />
              <p v-if="fieldErrors.nomor_identitas" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.nomor_identitas }}</p>
            </div>
          </div>

          <!-- Nama Lengkap Sesuai KTP -->
          <div class="space-y-1.5">
            <Label for="nama_lengkap" class="text-xs font-semibold">Nama Lengkap Sesuai Identitas <span class="text-rose-500">*</span></Label>
            <Input
              id="nama_lengkap"
              v-model="form.nama_lengkap"
              placeholder="Masukkan nama lengkap tanpa singkatan"
              class="rounded-xl h-10 text-sm"
              :class="{ 'border-rose-500': fieldErrors.nama_lengkap }"
            />
            <p v-if="fieldErrors.nama_lengkap" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.nama_lengkap }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Tanggal Lahir -->
            <div class="space-y-1.5">
              <Label for="tanggal_lahir" class="text-xs font-semibold">Tanggal Lahir <span class="text-rose-500">*</span></Label>
              <Input
                id="tanggal_lahir"
                type="date"
                v-model="form.tanggal_lahir"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.tanggal_lahir }"
              />
              <p v-if="fieldErrors.tanggal_lahir" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.tanggal_lahir }}</p>
            </div>

            <!-- Jenis Kelamin -->
            <div class="space-y-1.5">
              <Label class="text-xs font-semibold">Jenis Kelamin <span class="text-rose-500">*</span></Label>
              <div class="flex items-center gap-4 h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" value="l" v-model="form.jenis_kelamin" class="text-emerald-700" />
                  <span>Laki-Laki</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" value="p" v-model="form.jenis_kelamin" class="text-emerald-700" />
                  <span>Perempuan</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Alamat Lengkap & No WhatsApp -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2 space-y-1.5">
              <Label for="alamat" class="text-xs font-semibold">Alamat Lengkap Domisili <span class="text-rose-500">*</span></Label>
              <Input
                id="alamat"
                v-model="form.alamat"
                placeholder="Jl. Raya No. 12, Kelurahan, Kecamatan, Kota"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.alamat }"
              />
              <p v-if="fieldErrors.alamat" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.alamat }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="telepon" class="text-xs font-semibold">No. HP / WhatsApp <span class="text-rose-500">*</span></Label>
              <Input
                id="telepon"
                v-model="form.telepon"
                placeholder="081234567890"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.telepon }"
              />
              <p v-if="fieldErrors.telepon" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.telepon }}</p>
            </div>
          </div>
        </div>

        <!-- Section 2: Kontak Darurat Wajib SOP -->
        <div class="space-y-4 pt-2">
          <div class="flex items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100 font-bold text-sm">
            <Phone class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            <span>2. Kontak Darurat (Keluarga / Kerabat)</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="nama_kontak_darurat" class="text-xs font-semibold">Nama Kontak Darurat <span class="text-rose-500">*</span></Label>
              <Input
                id="nama_kontak_darurat"
                v-model="form.nama_kontak_darurat"
                placeholder="Nama Orang Tua/Wali"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.nama_kontak_darurat }"
              />
              <p v-if="fieldErrors.nama_kontak_darurat" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.nama_kontak_darurat }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="telepon_darurat" class="text-xs font-semibold">No. Telepon Darurat <span class="text-rose-500">*</span></Label>
              <Input
                id="telepon_darurat"
                v-model="form.telepon_darurat"
                placeholder="081298765432"
                class="rounded-xl h-10 text-sm"
                :class="{ 'border-rose-500': fieldErrors.telepon_darurat }"
              />
              <p v-if="fieldErrors.telepon_darurat" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.telepon_darurat }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="hubungan_darurat" class="text-xs font-semibold">Hubungan Keluarga <span class="text-rose-500">*</span></Label>
              <select
                id="hubungan_darurat"
                v-model="form.hubungan_darurat"
                class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-600 font-medium"
              >
                <option value="Orang Tua">Orang Tua</option>
                <option value="Pasangan (Suami/Istri)">Pasangan (Suami/Istri)</option>
                <option value="Saudara Kandung">Saudara Kandung</option>
                <option value="Keluarga Lainnya">Keluarga Lainnya</option>
                <option value="Teman/Rekan">Teman/Rekan</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 3: Unggah Foto Dokumen Identitas -->
        <div class="space-y-4 pt-2">
          <div class="flex items-center gap-2 pb-1 border-b border-slate-100 dark:border-slate-800 text-slate-900 dark:text-slate-100 font-bold text-sm">
            <UploadCloud class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            <span>3. Unggah Berkas Foto KTP / Dokumen Identitas</span>
          </div>

          <div
            v-if="!selectedFile && !previewUrl"
            class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center hover:border-emerald-600 dark:hover:border-emerald-500 transition-colors bg-slate-50/50 dark:bg-slate-900/50 cursor-pointer relative"
          >
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/webp"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              @change="handleFileChange"
            />
            <div class="flex flex-col items-center justify-center">
              <div class="p-3 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 mb-2">
                <UploadCloud class="w-6 h-6" />
              </div>
              <p class="font-semibold text-sm text-slate-800 dark:text-slate-200">
                Klik atau seret file foto KTP ke area ini
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Format yang didukung: JPG, JPEG, PNG, WebP (Maksimal 2MB). Pastikan NIK & foto terbaca jelas.
              </p>
            </div>
          </div>

          <!-- Preview Berkas Terpilih -->
          <div v-else class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 p-2">
            <img
              :src="previewUrl!"
              alt="Pratinjau KTP"
              class="w-full max-h-48 object-contain rounded-xl"
            />
            <button
              type="button"
              class="absolute top-4 right-4 p-1.5 rounded-full bg-rose-600 text-white hover:bg-rose-700 shadow-md transition-colors"
              title="Hapus / Ganti Foto"
              @click="clearFile"
            >
              <X class="w-4 h-4" />
            </button>
            <div class="p-2 text-xs text-slate-300 flex items-center justify-between">
              <span class="truncate max-w-xs">{{ selectedFile?.name }}</span>
              <span>{{ (selectedFile!.size / (1024 * 1024)).toFixed(2) }} MB</span>
            </div>
          </div>
          <p v-if="fieldErrors.foto_identitas" class="text-xs text-rose-500 mt-0.5">{{ fieldErrors.foto_identitas }}</p>
        </div>

        <!-- Dialog Action Buttons -->
        <DialogFooter class="flex flex-col-reverse sm:flex-row gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
          <Button
            type="button"
            variant="outline"
            class="rounded-xl"
            :disabled="isSubmitting"
            @click="handleClose"
          >
            Batal
          </Button>
          <Button
            type="submit"
            class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-semibold shadow-sm flex items-center gap-2"
            :disabled="isSubmitting"
          >
            <span v-if="isSubmitting">Mengirim Dokumen...</span>
            <span v-else>Kirim Verifikasi KYC</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
