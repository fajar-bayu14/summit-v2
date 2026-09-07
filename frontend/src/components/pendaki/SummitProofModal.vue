<script setup lang="ts">
import { ref } from 'vue'
import {
  X,
  UploadCloud,
  MapPin,
  Clock,
  AlertCircle,
  Loader2,
  Mountain,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'
import { extractApiError } from '@/lib/normalizer'
import type { LogbookEntry } from '@/types/pendakiLogbook'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    invoice: string
    mountainName?: string
    trailName?: string
  }>(),
  {
    mountainName: 'Gunung',
    trailName: 'Jalur Resmi',
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submitted', logbook: LogbookEntry): void
}>()

const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)
const waktuSummit = ref(new Date().toISOString().slice(0, 16))
const catatan = ref('')
const latitude = ref('')
const longitude = ref('')
const isFetchingLocation = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

function handleFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    if (file.size > 5 * 1024 * 1024) {
      errorMessage.value = 'Ukuran foto maksimal 5 MB.'
      return
    }
    selectedFile.value = file
    previewUrl.value = URL.createObjectURL(file)
    errorMessage.value = ''
  }
}

function handleGetLocation() {
  if (!navigator.geolocation) {
    errorMessage.value = 'Fitur GPS Geolocation tidak didukung di browser ini.'
    return
  }

  isFetchingLocation.value = true
  navigator.geolocation.getCurrentPosition(
    position => {
      latitude.value = position.coords.latitude.toFixed(6)
      longitude.value = position.coords.longitude.toFixed(6)
      isFetchingLocation.value = false
    },
    error => {
      errorMessage.value = `Gagal mendeteksi lokasi GPS: ${error.message}`
      isFetchingLocation.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function handleSubmit() {
  if (!selectedFile.value) {
    errorMessage.value = 'Foto bukti summit di puncak wajib diunggah.'
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('foto_summit', selectedFile.value)
    if (waktuSummit.value) formData.append('waktu_summit', waktuSummit.value)
    if (catatan.value.trim()) formData.append('catatan_pendaki', catatan.value.trim())
    if (latitude.value) formData.append('latitude', latitude.value)
    if (longitude.value) formData.append('longitude', longitude.value)

    const response = await pendakiLogbookApi.submitLogbook(props.invoice, formData)
    if (response.data) {
      emit('submitted', response.data)
      emit('update:isOpen', false)
    }
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isSubmitting.value = false
  }
}

function handleClose() {
  emit('update:isOpen', false)
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200"
  >
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/40">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-forest-600 text-white shadow-xs">
            <Mountain class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base">
              Unggah Bukti Puncak (Summit Proof)
            </h3>
            <p class="text-xs text-slate-500">{{ mountainName }} • {{ trailName }}</p>
          </div>
        </div>
        <button
          type="button"
          class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          @click="handleClose"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Form Body -->
      <form class="p-6 overflow-y-auto space-y-4" @submit.prevent="handleSubmit">
        <!-- Error alert -->
        <div
          v-if="errorMessage"
          class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2"
        >
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Upload File Dropzone -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
            Foto Bukti di Puncak (Tugu / Plang Puncak) <span class="text-red-500">*</span>
          </label>
          <div
            class="border-2 border-dashed rounded-2xl p-5 text-center transition-colors relative cursor-pointer group"
            :class="
              previewUrl
                ? 'border-forest-400 bg-forest-50/20'
                : 'border-slate-200 dark:border-slate-700 hover:border-forest-500 hover:bg-slate-50 dark:hover:bg-slate-800/50'
            "
          >
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              @change="handleFileChange"
            />
            <div v-if="previewUrl" class="space-y-2">
              <img
                :src="previewUrl"
                alt="Preview Foto Puncak"
                class="w-full h-44 object-cover rounded-xl mx-auto shadow-xs"
              />
              <p class="text-[11px] text-forest-700 font-semibold">
                Klik untuk mengganti foto terpilih
              </p>
            </div>
            <div v-else class="space-y-2 py-4 text-slate-500">
              <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto group-hover:text-forest-600 transition-colors">
                <UploadCloud class="w-6 h-6" />
              </div>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                Pilih atau seret foto puncak gunung ke sini
              </p>
              <p class="text-[11px] text-slate-400">
                Format JPG, PNG, atau WEBP (Maksimal 5 MB)
              </p>
            </div>
          </div>
        </div>

        <!-- Waktu Tiba di Puncak -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
            Waktu Tiba di Puncak (Summit Timestamp)
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <Clock class="w-4 h-4" />
            </div>
            <input
              v-model="waktuSummit"
              type="datetime-local"
              class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-forest-500"
            />
          </div>
        </div>

        <!-- GPS Coordinates (Optional / Auto) -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
              Latitude (Opsional)
            </label>
            <input
              v-model="latitude"
              type="text"
              placeholder="-7.123456"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 font-mono"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
              Longitude (Opsional)
            </label>
            <input
              v-model="longitude"
              type="text"
              placeholder="109.123456"
              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 font-mono"
            />
          </div>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 text-xs text-forest-700 font-semibold hover:underline"
          :disabled="isFetchingLocation"
          @click="handleGetLocation"
        >
          <MapPin class="w-3.5 h-3.5" />
          <span>{{ isFetchingLocation ? 'Mendeteksi Koordinat...' : 'Dapatkan Koordinat GPS Otomatis' }}</span>
        </button>

        <!-- Catatan Pendaki -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
            Catatan Pendakian / Kesan Jalur (Opsional)
          </label>
          <textarea
            v-model="catatan"
            rows="3"
            placeholder="Contoh: Kondisi cuaca cerah berawan, jalur pos 3 sedikit licin karena kabut..."
            class="w-full p-3 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-forest-500"
          ></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
          <Button
            type="button"
            variant="ghost"
            class="rounded-xl text-xs"
            @click="handleClose"
          >
            Batal
          </Button>
          <Button
            type="submit"
            class="rounded-xl bg-forest-600 hover:bg-forest-700 text-white font-bold text-xs gap-1.5 shadow-md"
            :disabled="isSubmitting || !selectedFile"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <UploadCloud v-else class="w-4 h-4" />
            <span>{{ isSubmitting ? 'Mengunggah...' : 'Kirim Bukti Summit' }}</span>
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>
