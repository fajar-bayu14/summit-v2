<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
  Mountain,
  Upload,
  X,
  ImageIcon,
  MapPin,
  AlertCircle,
  AlertTriangle,
} from 'lucide-vue-next'
import { mountainApi } from '@/api/mountain'
import { useToast } from '@/composables/useToast'
import { extractApiError } from '@/lib/normalizer'
import { getStorageUrl } from '@/lib/storage'
import type { Gunung, MountainStatus } from '@/types/mountain'

const props = defineProps<{
  open: boolean
  gunung: Gunung | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved', data: Gunung): void
}>()

const toast = useToast()
const isEdit = computed(() => !!props.gunung)

const form = reactive({
  nama_gunung: '',
  tinggi_mdpl: 2500,
  lokasi: '',
  status: 'aktif' as MountainStatus,
  deskripsi: '',
})

const fotoFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const imageLoadError = ref<boolean>(false)
const submitting = ref<boolean>(false)
const formErrorMessage = ref<string>('')
const fieldErrors = ref<Record<string, string>>({})

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      formErrorMessage.value = ''
      fieldErrors.value = {}
      imageLoadError.value = false
      if (props.gunung) {
        form.nama_gunung = props.gunung.nama_gunung
        form.tinggi_mdpl = props.gunung.tinggi_mdpl
        form.lokasi = props.gunung.lokasi
        form.status = props.gunung.status
        form.deskripsi = props.gunung.deskripsi || ''
        previewUrl.value = props.gunung.foto ? getStorageUrl(props.gunung.foto) : null
      } else {
        form.nama_gunung = ''
        form.tinggi_mdpl = 2500
        form.lokasi = ''
        form.status = 'aktif'
        form.deskripsi = ''
        previewUrl.value = null
      }
      fotoFile.value = null
    } else {
      if (previewUrl.value && fotoFile.value) {
        URL.revokeObjectURL(previewUrl.value)
      }
    }
  }
)

function handleImageError() {
  imageLoadError.value = true
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (file.size > 2 * 1024 * 1024) {
    fieldErrors.value.foto = 'Ukuran berkas foto melebihi batas maksimal 2MB.'
    formErrorMessage.value = 'Ukuran berkas foto maksimal adalah 2MB.'
    return
  }

  fotoFile.value = file
  imageLoadError.value = false
  previewUrl.value = URL.createObjectURL(file)
  if (fieldErrors.value.foto) {
    delete fieldErrors.value.foto
  }
  if (formErrorMessage.value) {
    formErrorMessage.value = ''
  }
}

function removePhoto() {
  fotoFile.value = null
  imageLoadError.value = false
  previewUrl.value = props.gunung?.foto ? getStorageUrl(props.gunung.foto) : null
  if (fieldErrors.value.foto) {
    delete fieldErrors.value.foto
  }
}

function formatFileSize(bytes: number) {
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}

async function handleSubmit() {
  formErrorMessage.value = ''
  fieldErrors.value = {}

  if (!form.nama_gunung.trim()) {
    fieldErrors.value.nama_gunung = 'Nama destinasi gunung wajib diisi.'
  }
  if (!form.tinggi_mdpl || form.tinggi_mdpl < 1) {
    fieldErrors.value.tinggi_mdpl = 'Ketinggian MDPL tidak valid.'
  }
  if (!form.lokasi.trim()) {
    fieldErrors.value.lokasi = 'Lokasi wilayah / provinsi wajib diisi.'
  }
  if (!form.deskripsi.trim()) {
    fieldErrors.value.deskripsi = 'Deskripsi profil gunung wajib diisi.'
  }
  if (!isEdit.value && !fotoFile.value) {
    fieldErrors.value.foto = 'Foto cover lanskap wajib diunggah.'
  }

  if (Object.keys(fieldErrors.value).length > 0) {
    formErrorMessage.value = 'Mohon lengkapi seluruh formulir bertanda bintang (*) dengan data yang valid.'
    return
  }

  submitting.value = true

  try {
    let savedGunung: Gunung
    if (isEdit.value && props.gunung) {
      const res = await mountainApi.updateMountain(props.gunung.id, {
        nama_gunung: form.nama_gunung.trim(),
        tinggi_mdpl: form.tinggi_mdpl,
        lokasi: form.lokasi.trim(),
        status: form.status,
        deskripsi: form.deskripsi.trim(),
        foto: fotoFile.value || undefined,
      })
      savedGunung = res.data!
      toast.success(`Data destinasi ${savedGunung.nama_gunung} berhasil diperbarui.`)
    } else {
      const res = await mountainApi.createMountain({
        nama_gunung: form.nama_gunung.trim(),
        tinggi_mdpl: form.tinggi_mdpl,
        lokasi: form.lokasi.trim(),
        status: form.status,
        deskripsi: form.deskripsi.trim(),
        foto: fotoFile.value,
      })
      savedGunung = res.data!
      toast.success(`Gunung baru ${savedGunung.nama_gunung} berhasil didaftarkan.`)
    }

    emit('saved', savedGunung)
    emit('update:open', false)
  } catch (err: any) {
    const { message, fieldErrors: errors } = extractApiError(err)
    fieldErrors.value = errors
    formErrorMessage.value = message || 'Gagal menyimpan data gunung. Silakan periksa kembali isian formulir.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-4xl max-w-4xl w-[96vw] p-0 rounded-2xl overflow-hidden max-h-[92vh] flex flex-col bg-card border shadow-2xl">
      <!-- Modal Header -->
      <DialogHeader class="p-5 md:p-6 pb-4 border-b bg-slate-50/70 dark:bg-slate-900/60">
        <div class="flex items-center gap-3.5">
          <div class="h-11 w-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
            <Mountain class="h-6 w-6" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-foreground flex items-center gap-2">
              {{ isEdit ? `Edit Data Master: ${props.gunung?.nama_gunung}` : 'Tambah Destinasi Gunung Baru' }}
              <Badge v-if="isEdit" variant="outline" class="text-xs font-mono uppercase px-2 py-0.5">
                ID #{{ props.gunung?.id }}
              </Badge>
            </DialogTitle>
            <DialogDescription class="text-xs md:text-sm text-muted-foreground mt-0.5">
              Kelola spesifikasi teknis gunung, elevasi puncak MDPL, wilayah geografis, dan foto lanskap resmi.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Modal Body (Spacious 2-Column Layout) -->
      <form class="flex-1 overflow-y-auto p-5 md:p-6 lg:p-8 space-y-6" @submit.prevent="handleSubmit">
        
        <!-- Inline Validation Alert Banner (Inside Modal) -->
        <div
          v-if="formErrorMessage"
          class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 flex items-start justify-between gap-3 shadow-xs animate-in fade-in slide-in-from-top-2 duration-200"
        >
          <div class="flex items-start gap-3">
            <AlertTriangle class="h-5 w-5 shrink-0 mt-0.5 text-red-500" />
            <div>
              <strong class="font-bold text-xs md:text-sm block">Terjadi Kesalahan Validasi</strong>
              <p class="text-xs mt-0.5 leading-relaxed">{{ formErrorMessage }}</p>
            </div>
          </div>
          <button
            type="button"
            class="text-red-500 hover:text-red-700 dark:hover:text-red-300 p-1 rounded-lg shrink-0 transition-colors"
            title="Tutup pesan peringatan"
            @click="formErrorMessage = ''"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
          
          <!-- Left Column: Photo Management & Preview (5 Cols) -->
          <div class="lg:col-span-5 space-y-3.5">
            <div class="flex items-center justify-between">
              <Label class="text-sm font-semibold text-foreground flex items-center gap-1.5">
                <ImageIcon class="h-4 w-4 text-primary" />
                Foto Cover Lanskap <span v-if="!isEdit" class="text-red-500">*</span>
              </Label>
              <Badge v-if="fotoFile" variant="outline" class="bg-emerald-500/10 text-emerald-600 border-emerald-500/20 text-[11px] font-semibold">
                {{ formatFileSize(fotoFile.size) }}
              </Badge>
            </div>

            <!-- Image Preview Box -->
            <div
              v-if="previewUrl && !imageLoadError"
              class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 aspect-[16/10] shadow-sm flex items-center justify-center"
            >
              <img
                :src="previewUrl"
                alt="Foto Cover"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-black/20 pointer-events-none"></div>

              <!-- Badges on Preview -->
              <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-black/70 text-white backdrop-blur-xs">
                  {{ form.tinggi_mdpl || 0 }} MDPL
                </span>
                <Badge
                  :class="form.status === 'aktif' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'"
                  class="capitalize text-xs font-semibold border-none px-2.5 py-0.5"
                >
                  {{ form.status === 'aktif' ? 'Dibuka' : 'Ditutup' }}
                </Badge>
              </div>

              <!-- Title & Location Overlay -->
              <div class="absolute bottom-3 left-3 right-3 text-white pointer-events-none">
                <h4 class="text-base font-bold leading-tight truncate">
                  {{ form.nama_gunung || 'Nama Destinasi' }}
                </h4>
                <p class="text-xs text-white/80 flex items-center gap-1 mt-0.5 truncate">
                  <MapPin class="h-3 w-3 shrink-0" /> {{ form.lokasi || 'Lokasi Wilayah' }}
                </p>
              </div>
            </div>

            <!-- Fallback Dropzone when no photo or image error -->
            <div
              v-else
              class="border-2 border-dashed rounded-2xl p-6 text-center hover:border-primary/50 transition-colors bg-slate-50/70 dark:bg-slate-900/40 flex flex-col items-center justify-center aspect-[16/10]"
              :class="{ 'border-red-400 bg-red-50/20': fieldErrors.foto }"
            >
              <div class="h-12 w-12 rounded-2xl bg-muted flex items-center justify-center text-muted-foreground mb-2.5">
                <ImageIcon class="h-6 w-6" />
              </div>
              <p class="text-xs font-bold text-foreground mb-0.5">Unggah Foto Lanskap Gunung</p>
              <p class="text-[11px] text-muted-foreground mb-3">Format JPEG, PNG, WebP (Maks. 2MB)</p>
              <label
                for="foto-upload-picker"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-primary-foreground text-xs font-semibold cursor-pointer hover:bg-primary/90 shadow-xs transition-all"
              >
                <Upload class="h-3.5 w-3.5" /> Pilih Berkas Foto
              </label>
              <input id="foto-upload-picker" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange" />
            </div>

            <!-- Action Controls Strip Below Photo -->
            <div v-if="previewUrl && !imageLoadError" class="flex items-center gap-2 pt-1">
              <label
                for="foto-upload-picker"
                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-card hover:bg-accent text-foreground text-xs font-semibold cursor-pointer shadow-xs transition-all text-center"
              >
                <Upload class="h-3.5 w-3.5 text-primary" /> {{ fotoFile ? 'Ganti File Pilihan' : 'Ganti Foto Cover' }}
              </label>
              <input id="foto-upload-picker" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange" />

              <Button
                v-if="fotoFile"
                type="button"
                variant="outline"
                size="sm"
                class="h-9 px-3 rounded-xl text-xs font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300 gap-1"
                @click="removePhoto"
              >
                <X class="h-3.5 w-3.5" /> Batal Ganti
              </Button>
            </div>

            <span v-if="fieldErrors.foto" class="text-xs text-red-500 font-medium flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" /> {{ fieldErrors.foto }}
            </span>
          </div>

          <!-- Right Column: Form Fields (7 Cols) -->
          <div class="lg:col-span-7 space-y-4">
            <!-- Row 1: Nama Gunung & Ketinggian MDPL -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
              <div class="sm:col-span-7 space-y-1.5">
                <Label for="nama_gunung" class="text-sm font-semibold text-foreground">
                  Nama Destinasi Gunung <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="nama_gunung"
                  v-model="form.nama_gunung"
                  placeholder="Contoh: Gunung Rinjani"
                  class="h-10 text-sm rounded-xl px-3"
                  :class="{ 'border-red-500 ring-red-500/20': fieldErrors.nama_gunung }"
                />
                <span v-if="fieldErrors.nama_gunung" class="text-xs text-red-500 font-medium block">
                  {{ fieldErrors.nama_gunung }}
                </span>
              </div>

              <div class="sm:col-span-5 space-y-1.5">
                <Label for="tinggi_mdpl" class="text-sm font-semibold text-foreground">
                  Ketinggian Puncak <span class="text-red-500">*</span>
                </Label>
                <div class="relative">
                  <Input
                    id="tinggi_mdpl"
                    v-model.number="form.tinggi_mdpl"
                    type="number"
                    min="100"
                    placeholder="3726"
                    class="h-10 text-sm rounded-xl font-mono pr-14 px-3"
                    :class="{ 'border-red-500 ring-red-500/20': fieldErrors.tinggi_mdpl }"
                  />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-mono text-muted-foreground font-bold">
                    MDPL
                  </span>
                </div>
                <span v-if="fieldErrors.tinggi_mdpl" class="text-xs text-red-500 font-medium block">
                  {{ fieldErrors.tinggi_mdpl }}
                </span>
              </div>
            </div>

            <!-- Row 2: Lokasi & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
              <div class="sm:col-span-7 space-y-1.5">
                <Label for="lokasi" class="text-sm font-semibold text-foreground">
                  Lokasi Wilayah / Provinsi <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="lokasi"
                  v-model="form.lokasi"
                  placeholder="Contoh: Cianjur, Jawa Barat"
                  class="h-10 text-sm rounded-xl px-3"
                  :class="{ 'border-red-500 ring-red-500/20': fieldErrors.lokasi }"
                />
                <span v-if="fieldErrors.lokasi" class="text-xs text-red-500 font-medium block">
                  {{ fieldErrors.lokasi }}
                </span>
              </div>

              <div class="sm:col-span-5 space-y-1.5">
                <Label for="status" class="text-sm font-semibold text-foreground">Status Operasional</Label>
                <select
                  id="status"
                  v-model="form.status"
                  class="w-full h-10 px-3 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="aktif">🟢 Aktif (Buka)</option>
                  <option value="tidak_aktif">🔴 Tidak Aktif (Tutup)</option>
                </select>
              </div>
            </div>

            <!-- Row 3: Deskripsi Lengkap -->
            <div class="space-y-1.5">
              <Label for="deskripsi" class="text-sm font-semibold text-foreground">
                Deskripsi & Profil Karakteristik Gunung <span class="text-red-500">*</span>
              </Label>
              <textarea
                id="deskripsi"
                v-model="form.deskripsi"
                rows="4"
                placeholder="Tuliskan profil lengkap rute pendakian, regulasi kawasan konservasi, titik mata air, iklim mikro, serta daya tarik wisata alam..."
                class="w-full text-sm p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-card text-foreground focus:outline-none focus:ring-1 focus:ring-primary leading-relaxed resize-none"
                :class="{ 'border-red-500 ring-red-500/20': fieldErrors.deskripsi }"
              ></textarea>
              <span v-if="fieldErrors.deskripsi" class="text-xs text-red-500 font-medium block">
                {{ fieldErrors.deskripsi }}
              </span>
            </div>
          </div>

        </div>
      </form>

      <!-- Modal Footer -->
      <DialogFooter class="p-4 px-6 border-t bg-slate-50/70 dark:bg-slate-900/60 flex sm:justify-between items-center gap-3">
        <Button variant="outline" size="sm" class="rounded-xl text-xs h-9 px-4" :disabled="submitting" @click="emit('update:open', false)">
          Batal
        </Button>
        <Button size="sm" class="rounded-xl text-xs h-9 bg-primary hover:bg-primary/90 text-primary-foreground gap-1.5 px-5 font-semibold shadow-xs" :disabled="submitting" @click="handleSubmit">
          <span v-if="submitting" class="inline-block animate-spin mr-1">⏳</span>
          {{ isEdit ? 'Simpan Perubahan Data' : 'Daftarkan Gunung' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
