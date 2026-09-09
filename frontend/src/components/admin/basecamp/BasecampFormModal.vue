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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
  Building2,
  Users,
  Compass,
  MapPin,
  Clock,
  AlertCircle,
  Loader2,
  ExternalLink,
} from 'lucide-vue-next'
import type { Basecamp, CreateBasecampPayload, UpdateBasecampPayload } from '@/types/basecamp'
import type { Mitra } from '@/types/partner'
import type { JalurPendakian } from '@/types/mountain'
import { createBasecamp, updateBasecamp } from '@/api/basecamp'
import { getPartners } from '@/api/partner'
import { trailApi } from '@/api/trail'
import { extractApiError } from '@/lib/normalizer'
import { useToast } from '@/composables/useToast'

const props = defineProps<{
  open: boolean
  basecamp: Basecamp | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const toast = useToast()
const isSubmitting = ref(false)
const isLoadingOptions = ref(false)
const formErrorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})

const partners = ref<Mitra[]>([])
const trails = ref<JalurPendakian[]>([])

const isEditMode = computed(() => !!props.basecamp?.id)

const form = reactive({
  mitra_id: '' as string | number,
  jalur_id: '' as string | number,
  nama_basecamp: '',
  latitude: '',
  longitude: '',
  jam_operasional: '24 Jam',
})

const jamPresetOptions = [
  '24 Jam',
  '06:00 - 18:00',
  '07:00 - 22:00',
  '08:00 - 20:00',
  '05:00 - 23:00',
]

async function loadDropdownOptions() {
  isLoadingOptions.value = true
  try {
    const [partnersRes, trailsRes] = await Promise.all([
      getPartners({ per_page: 100 }),
      trailApi.getTrails({ per_page: 100 }),
    ])
    partners.value = partnersRes.items
    trails.value = trailsRes.items
  } catch (err) {
    console.error('Failed to load partners or trails options', err)
  } finally {
    isLoadingOptions.value = false
  }
}

watch(
  () => props.open,
  isOpen => {
    if (isOpen) {
      loadDropdownOptions()
      resetForm()
      if (props.basecamp) {
        form.mitra_id = String(props.basecamp.mitra_id)
        form.jalur_id = String(props.basecamp.jalur_id)
        form.nama_basecamp = props.basecamp.nama_basecamp
        form.latitude = props.basecamp.latitude
        form.longitude = props.basecamp.longitude
        form.jam_operasional = props.basecamp.jam_operasional
      }
    }
  },
)

function resetForm() {
  form.mitra_id = ''
  form.jalur_id = ''
  form.nama_basecamp = ''
  form.latitude = ''
  form.longitude = ''
  form.jam_operasional = '24 Jam'
  formErrorMessage.value = ''
  fieldErrors.value = {}
}

function handleClose() {
  emit('update:open', false)
}

function openMapsPreview() {
  if (form.latitude && form.longitude) {
    window.open(`https://www.google.com/maps?q=${form.latitude},${form.longitude}`, '_blank')
  }
}

async function handleSubmit() {
  formErrorMessage.value = ''
  fieldErrors.value = {}

  if (!form.mitra_id) {
    fieldErrors.value.mitra_id = 'Pilih mitra pengelola basecamp.'
    return
  }

  if (!form.jalur_id) {
    fieldErrors.value.jalur_id = 'Pilih jalur pendakian yang dilayani.'
    return
  }

  if (!form.nama_basecamp.trim()) {
    fieldErrors.value.nama_basecamp = 'Nama basecamp wajib diisi.'
    return
  }

  if (!form.latitude.trim()) {
    fieldErrors.value.latitude = 'Titik koordinat Latitude wajib diisi.'
    return
  }

  if (!form.longitude.trim()) {
    fieldErrors.value.longitude = 'Titik koordinat Longitude wajib diisi.'
    return
  }

  isSubmitting.value = true

  try {
    if (isEditMode.value && props.basecamp) {
      const payload: UpdateBasecampPayload = {
        mitra_id: Number(form.mitra_id),
        jalur_id: Number(form.jalur_id),
        nama_basecamp: form.nama_basecamp,
        latitude: form.latitude,
        longitude: form.longitude,
        jam_operasional: form.jam_operasional,
      }

      await updateBasecamp(props.basecamp.id, payload)
      toast.success(`Informasi basecamp "${form.nama_basecamp}" telah diperbarui.`, 'Basecamp Diperbarui')
    } else {
      const payload: CreateBasecampPayload = {
        mitra_id: Number(form.mitra_id),
        jalur_id: Number(form.jalur_id),
        nama_basecamp: form.nama_basecamp,
        latitude: form.latitude,
        longitude: form.longitude,
        jam_operasional: form.jam_operasional,
      }

      await createBasecamp(payload)
      toast.success(`Basecamp "${form.nama_basecamp}" berhasil didaftarkan dan dipetakan.`, 'Basecamp Didaftarkan')
    }

    emit('success')
    handleClose()
  } catch (err: any) {
    const errorDetails = extractApiError(err)
    formErrorMessage.value = errorDetails.message
    fieldErrors.value = errorDetails.fieldErrors
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-xl max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden">
      <!-- Header -->
      <DialogHeader class="p-5 border-b bg-card shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="h-9 w-9 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <Building2 class="h-5 w-5" />
          </div>
          <div>
            <DialogTitle class="text-base font-bold tracking-tight">
              {{ isEditMode ? 'Perbarui Data Basecamp' : 'Daftarkan Basecamp Baru' }}
            </DialogTitle>
            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
              {{ isEditMode ? 'Ubah informasi pos registrasi basecamp, mitra pengelola, atau koordinat geolokasi.' : 'Petakan pos basecamp operasional ke mitra penanggung jawab dan jalur pendakian resmi.' }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Body Form -->
      <div class="flex-1 overflow-y-auto p-5 space-y-4">
        <!-- Inline Alert Error -->
        <Alert v-if="formErrorMessage" variant="destructive" class="py-2.5 px-3.5">
          <AlertCircle class="h-4 w-4 shrink-0" />
          <div class="ml-2">
            <AlertTitle class="text-xs font-semibold">Validasi Gagal</AlertTitle>
            <AlertDescription class="text-xs text-destructive-foreground/90">
              {{ formErrorMessage }}
            </AlertDescription>
          </div>
        </Alert>

        <!-- Mitra & Jalur Selectors -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div class="space-y-1.5">
            <Label for="mitra_id" class="text-xs font-semibold flex items-center gap-1.5">
              <Users class="h-3.5 w-3.5 text-muted-foreground" />
              <span>Mitra Pengelola <span class="text-destructive">*</span></span>
            </Label>
            <select
              id="mitra_id"
              v-model="form.mitra_id"
              class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              :class="{ 'border-destructive': fieldErrors.mitra_id }"
            >
              <option value="" disabled>Pilih Mitra Penanggung Jawab</option>
              <option
                v-for="m in partners"
                :key="m.id"
                :value="String(m.id)"
                :disabled="m.status === 'suspend'"
              >
                {{ m.nama_pemilik }} ({{ m.bank }}) {{ m.status === 'suspend' ? '[SUSPEND]' : '' }}
              </option>
            </select>
            <p v-if="fieldErrors.mitra_id" class="text-[11px] text-destructive font-medium">{{ fieldErrors.mitra_id }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="jalur_id" class="text-xs font-semibold flex items-center gap-1.5">
              <Compass class="h-3.5 w-3.5 text-muted-foreground" />
              <span>Jalur Pendakian Resmi <span class="text-destructive">*</span></span>
            </Label>
            <select
              id="jalur_id"
              v-model="form.jalur_id"
              class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              :class="{ 'border-destructive': fieldErrors.jalur_id }"
            >
              <option value="" disabled>Pilih Jalur Gunung</option>
              <option
                v-for="t in trails"
                :key="t.id"
                :value="String(t.id)"
              >
                {{ t.gunung?.nama_gunung || 'Gunung' }} — {{ t.nama_jalur }}
              </option>
            </select>
            <p v-if="fieldErrors.jalur_id" class="text-[11px] text-destructive font-medium">{{ fieldErrors.jalur_id }}</p>
          </div>
        </div>

        <!-- Nama Basecamp -->
        <div class="space-y-1.5">
          <Label for="nama_basecamp" class="text-xs font-semibold flex items-center gap-1.5">
            <Building2 class="h-3.5 w-3.5 text-muted-foreground" />
            <span>Nama Pos Basecamp <span class="text-destructive">*</span></span>
          </Label>
          <Input
            id="nama_basecamp"
            v-model="form.nama_basecamp"
            placeholder="Contoh: Basecamp Merbabu via Selo Permai"
            class="h-9 text-xs"
            :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.nama_basecamp }"
          />
          <p v-if="fieldErrors.nama_basecamp" class="text-[11px] text-destructive font-medium">{{ fieldErrors.nama_basecamp }}</p>
        </div>

        <!-- Jam Operasional -->
        <div class="space-y-1.5">
          <Label for="jam_operasional" class="text-xs font-semibold flex items-center gap-1.5">
            <Clock class="h-3.5 w-3.5 text-muted-foreground" />
            <span>Jam Operasional Pos Registrasi <span class="text-destructive">*</span></span>
          </Label>
          <div class="flex items-center gap-2">
            <select
              v-model="form.jam_operasional"
              class="h-9 w-1/2 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
              <option v-for="j in jamPresetOptions" :key="j" :value="j">
                {{ j }}
              </option>
            </select>
            <Input
              v-model="form.jam_operasional"
              placeholder="Atau ketik custom jam"
              class="h-9 text-xs w-1/2"
              :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.jam_operasional }"
            />
          </div>
          <p v-if="fieldErrors.jam_operasional" class="text-[11px] text-destructive font-medium">{{ fieldErrors.jam_operasional }}</p>
        </div>

        <!-- Koordinat Geolocation -->
        <div class="p-3.5 bg-muted/20 rounded-xl border border-border/60 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-foreground flex items-center gap-1.5">
              <MapPin class="h-3.5 w-3.5 text-emerald-600" />
              <span>Titik Koordinat GPS (Latitude & Longitude)</span>
            </span>

            <Button
              v-if="form.latitude && form.longitude"
              type="button"
              variant="ghost"
              size="sm"
              class="h-7 text-[11px] text-emerald-600 hover:text-emerald-700 gap-1 px-2"
              @click="openMapsPreview"
            >
              <span>Uji di Google Maps</span>
              <ExternalLink class="h-3 w-3" />
            </Button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="latitude" class="text-[11px] font-semibold text-muted-foreground">
                Latitude <span class="text-destructive">*</span>
              </Label>
              <Input
                id="latitude"
                v-model="form.latitude"
                placeholder="-7.441234"
                class="h-9 text-xs font-mono"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.latitude }"
              />
              <p v-if="fieldErrors.latitude" class="text-[11px] text-destructive font-medium">{{ fieldErrors.latitude }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="longitude" class="text-[11px] font-semibold text-muted-foreground">
                Longitude <span class="text-destructive">*</span>
              </Label>
              <Input
                id="longitude"
                v-model="form.longitude"
                placeholder="110.421234"
                class="h-9 text-xs font-mono"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.longitude }"
              />
              <p v-if="fieldErrors.longitude" class="text-[11px] text-destructive font-medium">{{ fieldErrors.longitude }}</p>
            </div>
          </div>
          <p class="text-[10px] text-muted-foreground">
            Koordinat ini digunakan pendaki pada aplikasi mobile untuk navigasi GPS langsung ke lokasi titik pos basecamp.
          </p>
        </div>
      </div>

      <!-- Footer -->
      <DialogFooter class="p-4 border-t bg-card shrink-0 flex items-center justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="text-xs"
          :disabled="isSubmitting"
          @click="handleClose"
        >
          Batal
        </Button>
        <Button
          type="button"
          size="sm"
          class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white gap-1.5"
          :disabled="isSubmitting"
          @click="handleSubmit"
        >
          <Loader2 v-if="isSubmitting" class="h-3.5 w-3.5 animate-spin" />
          <span>{{ isEditMode ? 'Simpan Perubahan' : 'Daftarkan Basecamp' }}</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
