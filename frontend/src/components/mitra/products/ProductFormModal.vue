<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  PackagePlus,
  Edit,
  Layers,
  Ticket,
  Compass,
  Tent,
  Users,
  Utensils,
  Check,
  AlertCircle,
} from 'lucide-vue-next'
import type { Produk, ProdukKategori, StoreProductPayload } from '@/types/product'

interface ProductFormData {
  basecamp_id: number
  nama_produk: string
  kategori: ProdukKategori
  deskripsi: string
  harga: number
  stok: number
  satuan: string
  is_active: boolean
  jalur_id?: number
  jam_buka: string
  jam_tutup: string
  tanggal_berangkat: string
  tanggal_pulang: string
  meeting_point: string
  minimal_peserta: number
  maksimal_peserta: number
}

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    mode: 'create' | 'edit'
    product?: Produk | null
    basecamps?: any[]
    defaultBasecampId?: number | null
    loading?: boolean
    serverErrors?: Record<string, string[]> | null
  }>(),
  {
    isOpen: false,
    mode: 'create',
    product: null,
    basecamps: () => [],
    defaultBasecampId: null,
    loading: false,
    serverErrors: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submit', payload: StoreProductPayload): void
  (e: 'close'): void
}>()

// Form Data State
const form = ref<ProductFormData>({
  basecamp_id: 1,
  nama_produk: '',
  kategori: 'rental',
  deskripsi: '',
  harga: 0,
  stok: 10,
  satuan: 'unit/hari',
  is_active: true,
  jalur_id: undefined,
  jam_buka: '06:00',
  jam_tutup: '17:00',
  tanggal_berangkat: '',
  tanggal_pulang: '',
  meeting_point: '',
  minimal_peserta: 1,
  maksimal_peserta: 10,
})

const clientErrors = ref<Record<string, string>>({})

const categoryOptions: Array<{ value: ProdukKategori; label: string; icon: any }> = [
  { value: 'rental', label: 'Sewa Alat (Rental)', icon: Tent },
  { value: 'ticket', label: 'Tiket Pendakian', icon: Ticket },
  { value: 'opentrip', label: 'Paket Open Trip', icon: Compass },
  { value: 'guide', label: 'Pemandu (Guide)', icon: Users },
  { value: 'porter', label: 'Porter Barang', icon: Users },
  { value: 'kuliner', label: 'Logistik & Kuliner', icon: Utensils },
  { value: 'merchandise', label: 'Merchandise / Souvenir', icon: Layers },
]

watch(
  () => [props.isOpen, props.product, props.mode, props.defaultBasecampId],
  () => {
    clientErrors.value = {}
    if (props.mode === 'edit' && props.product) {
      form.value = {
        basecamp_id: props.product.basecamp_id,
        nama_produk: props.product.nama_produk,
        kategori: props.product.kategori,
        deskripsi: props.product.deskripsi || '',
        harga: props.product.harga,
        stok: props.product.stok ?? 0,
        satuan: props.product.satuan || 'unit',
        is_active: props.product.is_active ?? true,
        jalur_id: props.product.tiket?.jalur_id ? Number(props.product.tiket.jalur_id) : undefined,
        jam_buka: props.product.tiket?.jam_buka || '06:00',
        jam_tutup: props.product.tiket?.jam_tutup || '17:00',
        tanggal_berangkat: props.product.opentrip?.tanggal_berangkat || '',
        tanggal_pulang: props.product.opentrip?.tanggal_pulang || '',
        meeting_point: props.product.opentrip?.meeting_point || '',
        minimal_peserta: props.product.opentrip?.minimal_peserta ?? 1,
        maksimal_peserta: props.product.opentrip?.maksimal_peserta ?? 10,
      }
    } else {
      form.value = {
        basecamp_id: props.defaultBasecampId || (props.basecamps[0]?.id ?? 1),
        nama_produk: '',
        kategori: 'rental',
        deskripsi: '',
        harga: 0,
        stok: 10,
        satuan: 'unit/hari',
        is_active: true,
        jalur_id: undefined,
        jam_buka: '06:00',
        jam_tutup: '17:00',
        tanggal_berangkat: '',
        tanggal_pulang: '',
        meeting_point: '',
        minimal_peserta: 1,
        maksimal_peserta: 10,
      }
    }
  },
  { immediate: true }
)

const isTicketCategory = computed(() => form.value.kategori === 'ticket')
const isOpenTripCategory = computed(() => form.value.kategori === 'opentrip')
const isStockCategory = computed(
  () => !isTicketCategory.value && !isOpenTripCategory.value
)

function validateForm(): boolean {
  clientErrors.value = {}

  if (!form.value.nama_produk.trim()) {
    clientErrors.value.nama_produk = 'Nama produk wajib diisi.'
  }
  if (!form.value.basecamp_id) {
    clientErrors.value.basecamp_id = 'Pilih basecamp pengelola produk.'
  }
  if (form.value.harga === undefined || form.value.harga === null || form.value.harga < 0) {
    clientErrors.value.harga = 'Harga produk harus bernilai 0 atau lebih.'
  }

  if (isStockCategory.value) {
    if (form.value.stok === undefined || form.value.stok === null || form.value.stok < 0) {
      clientErrors.value.stok = 'Stok barang harus bernilai 0 atau lebih.'
    }
  }

  if (isOpenTripCategory.value) {
    if (!form.value.tanggal_berangkat) {
      clientErrors.value.tanggal_berangkat = 'Tanggal keberangkatan wajib diisi.'
    }
    if (!form.value.tanggal_pulang) {
      clientErrors.value.tanggal_pulang = 'Tanggal kepulangan wajib diisi.'
    }
    if (!form.value.meeting_point?.trim()) {
      clientErrors.value.meeting_point = 'Lokasi meeting point wajib diisi.'
    }
    if (
      form.value.minimal_peserta &&
      form.value.maksimal_peserta &&
      form.value.maksimal_peserta < form.value.minimal_peserta
    ) {
      clientErrors.value.maksimal_peserta =
        'Maksimal peserta tidak boleh lebih kecil dari minimal peserta.'
    }
  }

  return Object.keys(clientErrors.value).length === 0
}

function handleSubmit() {
  if (!validateForm()) return

  // Format payload according to category
  const payload: StoreProductPayload = {
    basecamp_id: Number(form.value.basecamp_id),
    nama_produk: form.value.nama_produk.trim(),
    kategori: form.value.kategori,
    deskripsi: form.value.deskripsi?.trim() || null,
    harga: Number(form.value.harga),
    is_active: Boolean(form.value.is_active),
  }

  if (isStockCategory.value) {
    payload.stok = Number(form.value.stok ?? 0)
    payload.satuan = form.value.satuan?.trim() || 'unit'
  }

  if (isTicketCategory.value) {
    payload.jalur_id = form.value.jalur_id ? Number(form.value.jalur_id) : null
    payload.jam_buka = form.value.jam_buka || '06:00'
    payload.jam_tutup = form.value.jam_tutup || '17:00'
  }

  if (isOpenTripCategory.value) {
    payload.tanggal_berangkat = form.value.tanggal_berangkat
    payload.tanggal_pulang = form.value.tanggal_pulang
    payload.meeting_point = form.value.meeting_point?.trim()
    payload.minimal_peserta = Number(form.value.minimal_peserta ?? 1)
    payload.maksimal_peserta = Number(form.value.maksimal_peserta ?? 10)
  }

  emit('submit', payload)
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-2xl bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div
            :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center',
              props.mode === 'create'
                ? 'bg-[#1E3A2B]/10 text-[#1E3A2B]'
                : 'bg-blue-50 text-blue-700',
            ]"
          >
            <PackagePlus v-if="props.mode === 'create'" class="w-5 h-5" />
            <Edit v-else class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              {{ props.mode === 'create' ? 'Tambah Produk Baru' : 'Edit Data Produk' }}
            </DialogTitle>
            <DialogDescription class="text-sm text-stone-500">
              Kelola informasi tiket, rental alat, open trip, atau layanan operasional basecamp.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Global Server Errors Banner -->
      <div
        v-if="props.serverErrors && Object.keys(props.serverErrors).length > 0"
        class="p-3 bg-red-50 text-red-700 rounded-xl text-xs space-y-1 border border-red-200"
      >
        <div class="flex items-center gap-2 font-bold">
          <AlertCircle class="w-4 h-4" />
          <span>Terdapat kesalahan pada formulir:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 pl-2">
          <li v-for="(errMessages, field) in props.serverErrors" :key="field">
            {{ errMessages.join(', ') }}
          </li>
        </ul>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-2">
        <!-- Basecamp Selector & Category Selection -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="basecamp-select" class="text-xs font-semibold text-stone-700">
              Basecamp Pengelola <span class="text-red-500">*</span>
            </Label>
            <select
              id="basecamp-select"
              v-model="form.basecamp_id"
              class="w-full h-11 px-3 bg-white border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
              :disabled="props.loading"
            >
              <option
                v-for="bc in props.basecamps"
                :key="bc.id"
                :value="bc.id"
              >
                {{ bc.nama_basecamp || bc.nama }} {{ bc.nama_gunung ? `(${bc.nama_gunung})` : '' }}
              </option>
            </select>
            <p v-if="clientErrors.basecamp_id" class="text-xs text-red-600">
              {{ clientErrors.basecamp_id }}
            </p>
          </div>

          <div class="space-y-1.5">
            <Label for="kategori-select" class="text-xs font-semibold text-stone-700">
              Kategori Produk <span class="text-red-500">*</span>
            </Label>
            <select
              id="kategori-select"
              v-model="form.kategori"
              class="w-full h-11 px-3 bg-white border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
              :disabled="props.loading || props.mode === 'edit'"
            >
              <option
                v-for="cat in categoryOptions"
                :key="cat.value"
                :value="cat.value"
              >
                {{ cat.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Nama Produk & Harga -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-2 space-y-1.5">
            <Label for="nama-produk" class="text-xs font-semibold text-stone-700">
              Nama Produk / Layanan <span class="text-red-500">*</span>
            </Label>
            <Input
              id="nama-produk"
              v-model="form.nama_produk"
              placeholder="Contoh: Sewa Tenda Dome 4P / Tiket Jalur Selo"
              class="h-11 rounded-xl border-stone-200"
              :disabled="props.loading"
            />
            <p v-if="clientErrors.nama_produk" class="text-xs text-red-600">
              {{ clientErrors.nama_produk }}
            </p>
          </div>

          <div class="space-y-1.5">
            <Label for="harga-produk" class="text-xs font-semibold text-stone-700">
              Harga Satuan (Rp) <span class="text-red-500">*</span>
            </Label>
            <Input
              id="harga-produk"
              type="number"
              min="0"
              v-model.number="form.harga"
              placeholder="50000"
              class="h-11 rounded-xl border-stone-200"
              :disabled="props.loading"
            />
            <p v-if="clientErrors.harga" class="text-xs text-red-600">
              {{ clientErrors.harga }}
            </p>
          </div>
        </div>

        <!-- Conditional: Rental & Logistics Fields (Stok & Satuan) -->
        <div
          v-if="isStockCategory"
          class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-stone-50 rounded-xl border border-stone-200/80"
        >
          <div class="space-y-1.5">
            <Label for="stok-produk" class="text-xs font-semibold text-stone-700">
              Jumlah Stok Fisik <span class="text-red-500">*</span>
            </Label>
            <Input
              id="stok-produk"
              type="number"
              min="0"
              v-model.number="form.stok"
              placeholder="10"
              class="h-11 rounded-xl border-stone-200 bg-white"
              :disabled="props.loading"
            />
            <p v-if="clientErrors.stok" class="text-xs text-red-600">
              {{ clientErrors.stok }}
            </p>
          </div>

          <div class="space-y-1.5">
            <Label for="satuan-produk" class="text-xs font-semibold text-stone-700">
              Satuan Sewa / Pemakaian
            </Label>
            <Input
              id="satuan-produk"
              v-model="form.satuan"
              placeholder="Contoh: unit/hari, porsi, paket"
              class="h-11 rounded-xl border-stone-200 bg-white"
              :disabled="props.loading"
            />
          </div>
        </div>

        <!-- Conditional: Tiket Fields (Jam Buka & Tutup) -->
        <div
          v-if="isTicketCategory"
          class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-emerald-50/50 rounded-xl border border-emerald-200/80"
        >
          <div class="space-y-1.5">
            <Label for="jam-buka" class="text-xs font-semibold text-stone-700">
              Jam Buka Registrasi
            </Label>
            <Input
              id="jam-buka"
              type="time"
              v-model="form.jam_buka"
              class="h-11 rounded-xl border-stone-200 bg-white"
              :disabled="props.loading"
            />
          </div>
          <div class="space-y-1.5">
            <Label for="jam-tutup" class="text-xs font-semibold text-stone-700">
              Jam Tutup Registrasi
            </Label>
            <Input
              id="jam-tutup"
              type="time"
              v-model="form.jam_tutup"
              class="h-11 rounded-xl border-stone-200 bg-white"
              :disabled="props.loading"
            />
          </div>
        </div>

        <!-- Conditional: Open Trip Fields -->
        <div
          v-if="isOpenTripCategory"
          class="space-y-3 p-4 bg-sky-50/50 rounded-xl border border-sky-200/80"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="tgl-berangkat" class="text-xs font-semibold text-stone-700">
                Tanggal Berangkat <span class="text-red-500">*</span>
              </Label>
              <Input
                id="tgl-berangkat"
                type="date"
                v-model="form.tanggal_berangkat"
                class="h-11 rounded-xl border-stone-200 bg-white"
                :disabled="props.loading"
              />
              <p v-if="clientErrors.tanggal_berangkat" class="text-xs text-red-600">
                {{ clientErrors.tanggal_berangkat }}
              </p>
            </div>
            <div class="space-y-1.5">
              <Label for="tgl-pulang" class="text-xs font-semibold text-stone-700">
                Tanggal Pulang <span class="text-red-500">*</span>
              </Label>
              <Input
                id="tgl-pulang"
                type="date"
                v-model="form.tanggal_pulang"
                class="h-11 rounded-xl border-stone-200 bg-white"
                :disabled="props.loading"
              />
              <p v-if="clientErrors.tanggal_pulang" class="text-xs text-red-600">
                {{ clientErrors.tanggal_pulang }}
              </p>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="meeting-point" class="text-xs font-semibold text-stone-700">
              Lokasi Meeting Point <span class="text-red-500">*</span>
            </Label>
            <Input
              id="meeting-point"
              v-model="form.meeting_point"
              placeholder="Contoh: Basecamp Selo / Stasiun Solo Balapan"
              class="h-11 rounded-xl border-stone-200 bg-white"
              :disabled="props.loading"
            />
            <p v-if="clientErrors.meeting_point" class="text-xs text-red-600">
              {{ clientErrors.meeting_point }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="min-peserta" class="text-xs font-semibold text-stone-700">
                Minimal Peserta
              </Label>
              <Input
                id="min-peserta"
                type="number"
                min="1"
                v-model.number="form.minimal_peserta"
                class="h-11 rounded-xl border-stone-200 bg-white"
                :disabled="props.loading"
              />
            </div>
            <div class="space-y-1.5">
              <Label for="max-peserta" class="text-xs font-semibold text-stone-700">
                Maksimal Peserta
              </Label>
              <Input
                id="max-peserta"
                type="number"
                min="1"
                v-model.number="form.maksimal_peserta"
                class="h-11 rounded-xl border-stone-200 bg-white"
                :disabled="props.loading"
              />
              <p v-if="clientErrors.maksimal_peserta" class="text-xs text-red-600">
                {{ clientErrors.maksimal_peserta }}
              </p>
            </div>
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="space-y-1.5">
          <Label for="deskripsi" class="text-xs font-semibold text-stone-700">
            Deskripsi Produk & Fasilitas
          </Label>
          <textarea
            id="deskripsi"
            v-model="form.deskripsi"
            rows="3"
            placeholder="Tuliskan spesifikasi, kelengkapan, syarat sewa, atau fasilitas paket..."
            class="w-full p-3 bg-white border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
            :disabled="props.loading"
          ></textarea>
        </div>

        <!-- Status Aktif Switch -->
        <div class="flex items-center justify-between p-3 bg-stone-50 rounded-xl border border-stone-200">
          <div>
            <span class="text-sm font-semibold text-stone-800">Status Publikasi Produk</span>
            <p class="text-xs text-stone-500">
              Jika aktif, produk dapat dicari dan dipesan oleh pendaki di aplikasi.
            </p>
          </div>
          <input
            type="checkbox"
            v-model="form.is_active"
            class="w-5 h-5 rounded text-[#1E3A2B] focus:ring-[#1E3A2B] accent-[#1E3A2B] cursor-pointer"
            :disabled="props.loading"
          />
        </div>

        <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
          <Button
            type="button"
            variant="outline"
            class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
            :disabled="props.loading"
            @click="handleClose"
          >
            Batal
          </Button>
          <Button
            type="submit"
            class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px]"
            :disabled="props.loading"
          >
            <Check class="w-4 h-4 mr-2" />
            {{ props.loading ? 'Menyimpan...' : props.mode === 'create' ? 'Buat Produk' : 'Simpan Perubahan' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
