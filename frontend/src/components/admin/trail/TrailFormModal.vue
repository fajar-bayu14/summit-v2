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
import { Compass, AlertTriangle, X } from 'lucide-vue-next'
import { trailApi } from '@/api/trail'
import { useToast } from '@/composables/useToast'
import { extractApiError } from '@/lib/normalizer'
import type { Gunung, JalurPendakian, TrailDifficulty, TrailStatus } from '@/types/mountain'

const props = defineProps<{
  open: boolean
  trail: JalurPendakian | null
  mountains: Gunung[]
  defaultGunungId?: number | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'saved', data: JalurPendakian): void
}>()

const toast = useToast()
const isEdit = computed(() => !!props.trail)

const form = reactive({
  gunung_id: null as number | null,
  nama_jalur: '',
  titik_awal_mdpl: '',
  titik_akhir_mdpl: '',
  waktu_tempuh: '',
  panjang_jalur: '',
  tingkat_kesulitan: 'sedang' as TrailDifficulty,
  status: 'open' as TrailStatus,
  deskripsi: '',
})

const submitting = ref<boolean>(false)
const formErrorMessage = ref<string>('')
const fieldErrors = ref<Record<string, string>>({})

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      formErrorMessage.value = ''
      fieldErrors.value = {}
      if (props.trail) {
        form.gunung_id = props.trail.gunung_id
        form.nama_jalur = props.trail.nama_jalur
        form.titik_awal_mdpl = props.trail.titik_awal_mdpl || ''
        form.titik_akhir_mdpl = props.trail.titik_akhir_mdpl || ''
        form.waktu_tempuh = props.trail.waktu_tempuh || ''
        form.panjang_jalur = props.trail.panjang_jalur || ''
        form.tingkat_kesulitan = props.trail.tingkat_kesulitan || 'sedang'
        form.status = props.trail.status || 'open'
        form.deskripsi = props.trail.deskripsi || ''
      } else {
        form.gunung_id = props.defaultGunungId || (props.mountains.length > 0 ? props.mountains[0].id : null)
        form.nama_jalur = ''
        form.titik_awal_mdpl = '1200 MDPL'
        form.titik_akhir_mdpl = '2800 MDPL'
        form.waktu_tempuh = '6-7 Jam'
        form.panjang_jalur = '8.5 Km'
        form.tingkat_kesulitan = 'sedang'
        form.status = 'open'
        form.deskripsi = ''
      }
    }
  }
)

async function handleSubmit() {
  formErrorMessage.value = ''
  fieldErrors.value = {}

  if (!form.gunung_id) {
    fieldErrors.value.gunung_id = 'Pilih gunung induk untuk jalur ini.'
  }
  if (!form.nama_jalur.trim()) {
    fieldErrors.value.nama_jalur = 'Nama jalur pendakian wajib diisi.'
  }
  if (!form.titik_awal_mdpl.trim()) {
    fieldErrors.value.titik_awal_mdpl = 'Titik awal MDPL wajib diisi.'
  }
  if (!form.titik_akhir_mdpl.trim()) {
    fieldErrors.value.titik_akhir_mdpl = 'Titik akhir MDPL wajib diisi.'
  }
  if (!form.waktu_tempuh.trim()) {
    fieldErrors.value.waktu_tempuh = 'Estimasi waktu tempuh wajib diisi.'
  }
  if (!form.panjang_jalur.trim()) {
    fieldErrors.value.panjang_jalur = 'Panjang jalur pendakian wajib diisi.'
  }
  if (!form.deskripsi.trim()) {
    fieldErrors.value.deskripsi = 'Deskripsi jalur wajib diisi.'
  }

  if (Object.keys(fieldErrors.value).length > 0) {
    formErrorMessage.value = 'Mohon lengkapi seluruh isian formulir bertanda bintang (*).'
    return
  }

  submitting.value = true

  try {
    let savedTrail: JalurPendakian
    if (isEdit.value && props.trail) {
      const res = await trailApi.updateTrail(props.trail.id, {
        gunung_id: form.gunung_id!,
        nama_jalur: form.nama_jalur.trim(),
        titik_awal_mdpl: form.titik_awal_mdpl.trim(),
        titik_akhir_mdpl: form.titik_akhir_mdpl.trim(),
        waktu_tempuh: form.waktu_tempuh.trim(),
        panjang_jalur: form.panjang_jalur.trim(),
        tingkat_kesulitan: form.tingkat_kesulitan,
        status: form.status,
        deskripsi: form.deskripsi.trim(),
      })
      savedTrail = res.data!
      toast.success(`Jalur ${savedTrail.nama_jalur} berhasil diperbarui.`)
    } else {
      const res = await trailApi.createTrail({
        gunung_id: form.gunung_id!,
        nama_jalur: form.nama_jalur.trim(),
        titik_awal_mdpl: form.titik_awal_mdpl.trim(),
        titik_akhir_mdpl: form.titik_akhir_mdpl.trim(),
        waktu_tempuh: form.waktu_tempuh.trim(),
        panjang_jalur: form.panjang_jalur.trim(),
        tingkat_kesulitan: form.tingkat_kesulitan,
        status: form.status,
        deskripsi: form.deskripsi.trim(),
      })
      savedTrail = res.data!
      toast.success(`Jalur baru ${savedTrail.nama_jalur} berhasil ditambahkan.`)
    }

    emit('saved', savedTrail)
    emit('update:open', false)
  } catch (err: any) {
    const { message, fieldErrors: errors } = extractApiError(err)
    fieldErrors.value = errors
    formErrorMessage.value = message || 'Gagal menyimpan data jalur pendakian. Silakan periksa kembali isian formulir.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-2xl max-w-2xl w-[96vw] p-0 rounded-2xl overflow-hidden max-h-[90vh] flex flex-col bg-card border shadow-2xl">
      <!-- Modal Header -->
      <DialogHeader class="p-6 pb-4 border-b bg-slate-50/70 dark:bg-slate-900/60">
        <div class="flex items-center gap-3.5">
          <div class="h-11 w-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
            <Compass class="h-6 w-6" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-foreground flex items-center gap-2">
              {{ isEdit ? `Edit Jalur: ${props.trail?.nama_jalur}` : 'Tambah Jalur Pendakian Baru' }}
              <Badge v-if="isEdit" variant="outline" class="text-xs font-mono uppercase px-2 py-0.5">
                ID #{{ props.trail?.id }}
              </Badge>
            </DialogTitle>
            <DialogDescription class="text-sm text-muted-foreground mt-0.5">
              Kelola rincian jalur, elevasi awal/akhir MDPL, estimasi durasi, dan tingkat kesulitan medan.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <form class="flex-1 overflow-y-auto p-6 md:p-8 space-y-5" @submit.prevent="handleSubmit">
        
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

        <!-- Gunung Induk & Nama Jalur -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
          <div class="sm:col-span-5 space-y-2">
            <Label for="gunung_id" class="text-sm font-semibold text-foreground">
              Gunung Induk <span class="text-red-500">*</span>
            </Label>
            <select
              id="gunung_id"
              v-model.number="form.gunung_id"
              class="w-full h-11 px-3.5 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
              :class="{ 'border-red-500': fieldErrors.gunung_id }"
            >
              <option :value="null" disabled>Pilih Gunung...</option>
              <option v-for="g in mountains" :key="g.id" :value="g.id">
                {{ g.nama_gunung }} ({{ g.tinggi_mdpl }} MDPL)
              </option>
            </select>
            <span v-if="fieldErrors.gunung_id" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.gunung_id }}
            </span>
          </div>

          <div class="sm:col-span-7 space-y-2">
            <Label for="nama_jalur" class="text-sm font-semibold text-foreground">
              Nama Jalur Pendakian <span class="text-red-500">*</span>
            </Label>
            <Input
              id="nama_jalur"
              v-model="form.nama_jalur"
              placeholder="Contoh: Jalur Sembalun / Jalur Selo"
              class="h-11 text-sm rounded-xl px-3.5"
              :class="{ 'border-red-500': fieldErrors.nama_jalur }"
            />
            <span v-if="fieldErrors.nama_jalur" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.nama_jalur }}
            </span>
          </div>
        </div>

        <!-- Titik Awal & Akhir MDPL -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="titik_awal" class="text-sm font-semibold text-foreground">
              Titik Elevasi Awal (Pos Basecamp) <span class="text-red-500">*</span>
            </Label>
            <Input
              id="titik_awal"
              v-model="form.titik_awal_mdpl"
              placeholder="Contoh: 1400 MDPL"
              class="h-11 text-sm rounded-xl font-mono px-3.5"
              :class="{ 'border-red-500': fieldErrors.titik_awal_mdpl }"
            />
            <span v-if="fieldErrors.titik_awal_mdpl" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.titik_awal_mdpl }}
            </span>
          </div>

          <div class="space-y-2">
            <Label for="titik_akhir" class="text-sm font-semibold text-foreground">
              Titik Elevasi Akhir (Puncak) <span class="text-red-500">*</span>
            </Label>
            <Input
              id="titik_akhir"
              v-model="form.titik_akhir_mdpl"
              placeholder="Contoh: 3726 MDPL"
              class="h-11 text-sm rounded-xl font-mono px-3.5"
              :class="{ 'border-red-500': fieldErrors.titik_akhir_mdpl }"
            />
            <span v-if="fieldErrors.titik_akhir_mdpl" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.titik_akhir_mdpl }}
            </span>
          </div>
        </div>

        <!-- Waktu Tempuh & Panjang Jalur -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="waktu_tempuh" class="text-sm font-semibold text-foreground">
              Estimasi Waktu Tempuh <span class="text-red-500">*</span>
            </Label>
            <Input
              id="waktu_tempuh"
              v-model="form.waktu_tempuh"
              placeholder="Contoh: 7-8 Jam (2 Hari 1 Malam)"
              class="h-11 text-sm rounded-xl px-3.5"
              :class="{ 'border-red-500': fieldErrors.waktu_tempuh }"
            />
            <span v-if="fieldErrors.waktu_tempuh" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.waktu_tempuh }}
            </span>
          </div>

          <div class="space-y-2">
            <Label for="panjang_jalur" class="text-sm font-semibold text-foreground">
              Panjang / Jarak Jalur <span class="text-red-500">*</span>
            </Label>
            <Input
              id="panjang_jalur"
              v-model="form.panjang_jalur"
              placeholder="Contoh: 9.7 Km"
              class="h-11 text-sm rounded-xl px-3.5"
              :class="{ 'border-red-500': fieldErrors.panjang_jalur }"
            />
            <span v-if="fieldErrors.panjang_jalur" class="text-xs text-red-500 font-medium block">
              {{ fieldErrors.panjang_jalur }}
            </span>
          </div>
        </div>

        <!-- Tingkat Kesulitan & Status Operasional -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="kesulitan" class="text-sm font-semibold text-foreground">Tingkat Kesulitan Medan</Label>
            <select
              id="kesulitan"
              v-model="form.tingkat_kesulitan"
              class="w-full h-11 px-3.5 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
            >
              <option value="mudah">Mudah (Pemula)</option>
              <option value="sedang">Sedang (Menengah)</option>
              <option value="sulit">Sulit (Berpengalaman)</option>
              <option value="ekstrem">Ekstrem (Ahli / Teknis)</option>
            </select>
          </div>

          <div class="space-y-2">
            <Label for="status" class="text-sm font-semibold text-foreground">Status Operasional Jalur</Label>
            <select
              id="status"
              v-model="form.status"
              class="w-full h-11 px-3.5 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
            >
              <option value="open">Buka (Open - Dapat Dibooking)</option>
              <option value="close">Tutup (Closed - Pemulihan / Cuaca)</option>
            </select>
          </div>
        </div>

        <!-- Deskripsi Jalur -->
        <div class="space-y-2">
          <Label for="deskripsi" class="text-sm font-semibold text-foreground">
            Deskripsi Jalur & Sumber Air <span class="text-red-500">*</span>
          </Label>
          <textarea
            id="deskripsi"
            v-model="form.deskripsi"
            rows="4"
            placeholder="Jelaskan karakteristik jalur, ketersediaan sumber air (Pos 2 / Pos 3), titik shelter/kemah, dan potensi bahaya..."
            class="w-full text-sm p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-card text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 leading-relaxed resize-none"
            :class="{ 'border-red-500 ring-red-500/20': fieldErrors.deskripsi }"
          ></textarea>
          <span v-if="fieldErrors.deskripsi" class="text-xs text-red-500 font-medium block">
            {{ fieldErrors.deskripsi }}
          </span>
        </div>
      </form>

      <!-- Modal Footer -->
      <DialogFooter class="p-4 px-8 border-t bg-slate-50/70 dark:bg-slate-900/60 flex sm:justify-between items-center gap-3">
        <Button variant="outline" size="sm" class="rounded-xl text-sm h-10 px-5" :disabled="submitting" @click="emit('update:open', false)">
          Batal
        </Button>
        <Button size="sm" class="rounded-xl text-sm h-10 bg-primary hover:bg-primary/90 text-primary-foreground gap-2 px-6 font-semibold shadow-xs" :disabled="submitting" @click="handleSubmit">
          <span v-if="submitting" class="inline-block animate-spin mr-1">⏳</span>
          {{ isEdit ? 'Simpan Perubahan Jalur' : 'Tambah Jalur' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
