<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
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
  Users,
  Compass,
  Package,
  ShieldCheck,
  Phone,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Loader2,
} from 'lucide-vue-next'
import type { MitraStaff, StaffRole, StoreStaffPayload, UpdateStaffPayload } from '@/types/staff'
import mitraStaffApi from '@/api/mitraStaff'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    staff?: MitraStaff | null
  }>(),
  {
    isOpen: false,
    staff: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'saved', staff: MitraStaff): void
  (e: 'close'): void
}>()

const isEdit = computed(() => !!props.staff)

const form = reactive({
  nama: '',
  role: 'guide' as StaffRole,
  telepon: '',
  jadwal_tugas: '',
  is_available: true,
})

const errors = reactive<{
  nama?: string
  role?: string
  telepon?: string
}>({})

const isSubmitting = ref(false)
const serverError = ref<string | null>(null)
const serverSuccess = ref<string | null>(null)

const roleOptions: { value: StaffRole; label: string; desc: string; icon: any }[] = [
  {
    value: 'guide',
    label: 'Pemandu (Guide)',
    desc: 'Memimpin jalur & navigasi pendakian',
    icon: Compass,
  },
  {
    value: 'porter',
    label: 'Porter Logistik',
    desc: 'Membawa perlengkapan & logistik tenda',
    icon: Package,
  },
  {
    value: 'petugas',
    label: 'Petugas Basecamp',
    desc: 'Registrasi tiket, briefing, & rescue',
    icon: ShieldCheck,
  },
]

watch(
  [() => props.isOpen, () => props.staff],
  ([open]) => {
    if (open) {
      serverError.value = null
      serverSuccess.value = null
      errors.nama = undefined
      errors.role = undefined
      errors.telepon = undefined

      if (props.staff) {
        form.nama = props.staff.nama
        form.role = props.staff.role
        form.telepon = props.staff.telepon
        form.jadwal_tugas = props.staff.jadwal_tugas || ''
        form.is_available = props.staff.is_available
      } else {
        form.nama = ''
        form.role = 'guide'
        form.telepon = ''
        form.jadwal_tugas = ''
        form.is_available = true
      }
    }
  },
  { immediate: true }
)

function validate(): boolean {
  errors.nama = undefined
  errors.role = undefined
  errors.telepon = undefined
  let isValid = true

  if (!form.nama.trim()) {
    errors.nama = 'Nama lengkap staf wajib diisi.'
    isValid = false
  }

  if (!form.role) {
    errors.role = 'Peran staf wajib dipilih.'
    isValid = false
  }

  const cleanPhone = form.telepon.replace(/[^0-9+]/g, '')
  if (!cleanPhone) {
    errors.telepon = 'Nomor telepon / WhatsApp wajib diisi.'
    isValid = false
  } else if (cleanPhone.length < 8 || cleanPhone.length > 16) {
    errors.telepon = 'Nomor telepon tidak valid (8-16 digit).'
    isValid = false
  }

  return isValid
}

async function handleSubmit() {
  if (!validate()) return

  isSubmitting.value = true
  serverError.value = null
  serverSuccess.value = null

  try {
    if (isEdit.value && props.staff) {
      const payload: UpdateStaffPayload = {
        nama: form.nama.trim(),
        role: form.role,
        telepon: form.telepon.trim(),
        jadwal_tugas: form.jadwal_tugas.trim() || null,
        is_available: form.is_available,
      }

      const res = await mitraStaffApi.updateStaff(props.staff.id, payload)
      const updated = res.data ?? { ...props.staff, ...payload }
      serverSuccess.value = 'Data staf berhasil diperbarui.'
      emit('saved', updated)
    } else {
      const payload: StoreStaffPayload = {
        nama: form.nama.trim(),
        role: form.role,
        telepon: form.telepon.trim(),
        jadwal_tugas: form.jadwal_tugas.trim() || null,
        is_available: form.is_available,
      }

      const res = await mitraStaffApi.createStaff(payload)
      const created = res.data ?? ({ id: Date.now(), mitra_id: 1, ...payload } as MitraStaff)
      serverSuccess.value = 'Staf baru berhasil didaftarkan.'
      emit('saved', created)
    }
  } catch (err) {
    serverError.value = getApiErrorMessage(err, 'Gagal menyimpan data staf operasional.')
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
    <DialogContent class="sm:max-w-lg bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex items-center gap-3 border-b border-stone-200 pb-3">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#1E3A2B] flex items-center justify-center font-bold">
            <Users class="w-6 h-6" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              {{ isEdit ? 'Edit Data Staf Operasional' : 'Tambah Staf Lapangan Baru' }}
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500 mt-0.5">
              Kelola kru guide, porter logistik, dan staf basecamp untuk penugasan pendakian.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Feedback Alerts -->
      <div
        v-if="serverSuccess"
        class="flex items-center gap-2.5 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium border border-emerald-200 mt-3"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ serverSuccess }}</span>
      </div>

      <div
        v-if="serverError"
        class="flex items-center gap-2.5 p-3.5 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200 mt-3"
      >
        <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
        <span>{{ serverError }}</span>
      </div>

      <!-- Form Inputs -->
      <form class="space-y-4 py-3 text-xs" @submit.prevent="handleSubmit">
        <!-- 1. Peran Staf / Role Selector Cards -->
        <div class="space-y-2">
          <Label class="font-bold text-stone-700 text-xs">
            Peran / Posisi Staf <span class="text-red-500">*</span>
          </Label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <button
              v-for="opt in roleOptions"
              :key="opt.value"
              type="button"
              :class="[
                'p-3 rounded-xl border-2 flex flex-col items-start text-left transition-all cursor-pointer relative',
                form.role === opt.value
                  ? 'bg-emerald-50/70 border-[#1E3A2B] text-[#1E3A2B] shadow-xs'
                  : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100/80',
              ]"
              @click="form.role = opt.value"
            >
              <div class="flex items-center gap-2 font-bold text-xs mb-1">
                <component :is="opt.icon" class="w-4 h-4 shrink-0 text-[#1E3A2B]" />
                <span>{{ opt.label }}</span>
              </div>
              <p class="text-[10px] text-stone-500 line-clamp-2 leading-tight">
                {{ opt.desc }}
              </p>
            </button>
          </div>
          <p v-if="errors.role" class="text-xs text-red-600 font-medium">{{ errors.role }}</p>
        </div>

        <!-- 2. Nama Lengkap -->
        <div class="space-y-1">
          <Label class="font-bold text-stone-700 text-xs">
            Nama Lengkap Staf <span class="text-red-500">*</span>
          </Label>
          <Input
            v-model="form.nama"
            type="text"
            placeholder="Contoh: Ahmad Fauzi / Sutrisno"
            class="h-10 rounded-xl text-xs border-stone-200 focus:border-[#1E3A2B]"
          />
          <p v-if="errors.nama" class="text-xs text-red-600 font-medium">{{ errors.nama }}</p>
        </div>

        <!-- 3. Nomor Telepon / WhatsApp -->
        <div class="space-y-1">
          <Label class="font-bold text-stone-700 text-xs flex items-center gap-1.5">
            <Phone class="w-3.5 h-3.5 text-stone-500" />
            Nomor Telepon / WhatsApp <span class="text-red-500">*</span>
          </Label>
          <Input
            v-model="form.telepon"
            type="tel"
            placeholder="Contoh: 081234567890 / 628123456789"
            class="h-10 rounded-xl text-xs border-stone-200 focus:border-[#1E3A2B]"
          />
          <p class="text-[10px] text-stone-400">
            Digunakan untuk kontak cepat penugasan dan koordinasi langsung dengan pendaki.
          </p>
          <p v-if="errors.telepon" class="text-xs text-red-600 font-medium">{{ errors.telepon }}</p>
        </div>

        <!-- 4. Jadwal Tugas Operasional -->
        <div class="space-y-1">
          <Label class="font-bold text-stone-700 text-xs flex items-center gap-1.5">
            <Calendar class="w-3.5 h-3.5 text-stone-500" />
            Jadwal Tugas / Ketersediaan Hari (Opsional)
          </Label>
          <Input
            v-model="form.jadwal_tugas"
            type="text"
            placeholder="Contoh: Setiap Hari / Jumat - Minggu / On Call"
            class="h-10 rounded-xl text-xs border-stone-200 focus:border-[#1E3A2B]"
          />
        </div>

        <!-- 5. Status Ketersediaan Awal -->
        <div class="p-3.5 bg-stone-50 rounded-xl border border-stone-200 flex items-center justify-between">
          <div>
            <div class="font-bold text-stone-800 text-xs">Status Ketersediaan Operasional</div>
            <p class="text-[11px] text-stone-500">
              {{ form.is_available ? 'Staf siap ditugaskan untuk booking pendakian.' : 'Staf sedang libur atau tidak dapat menerima tugas.' }}
            </p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="form.is_available"
            :class="[
              'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
              form.is_available ? 'bg-[#1E3A2B]' : 'bg-stone-300',
            ]"
            @click="form.is_available = !form.is_available"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                form.is_available ? 'translate-x-5' : 'translate-x-0',
              ]"
            />
          </button>
        </div>
      </form>

      <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3 border-t border-stone-200">
        <Button
          type="button"
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          :disabled="isSubmitting"
          @click="handleClose"
        >
          Batal
        </Button>

        <Button
          type="button"
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px] px-5 font-bold"
          :disabled="isSubmitting"
          @click="handleSubmit"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
          <span>{{ isEdit ? 'Simpan Perubahan' : 'Daftarkan Staf' }}</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
