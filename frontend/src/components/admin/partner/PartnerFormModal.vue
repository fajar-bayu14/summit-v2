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
  User,
  Shield,
  CreditCard,
  Building2,
  AlertCircle,
  CheckCircle2,
  Loader2,
  Lock,
  Mail,
  Phone,
  FileText,
  Wallet,
} from 'lucide-vue-next'
import type { Mitra, CreateMitraPayload, UpdateMitraPayload } from '@/types/partner'
import { createPartner, updatePartner } from '@/api/partner'
import { extractApiError } from '@/lib/normalizer'
import { useToast } from '@/composables/useToast'

const props = defineProps<{
  open: boolean
  mitra: Mitra | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}>()

const toast = useToast()
const isSubmitting = ref(false)
const formErrorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const activeTab = ref<'account' | 'identity' | 'banking'>('account')

const isEditMode = computed(() => !!props.mitra?.id)

const form = reactive({
  email: '',
  password: '',
  password_confirmation: '',
  nama_pemilik: '',
  telepon: '',
  alamat: '',
  deskripsi: '',
  status: 'aktif' as 'aktif' | 'suspend',
  nik: '',
  npwp: '',
  bank: 'Bank BCA',
  rekening_bank: '',
  nama_rekening: '',
  ewallet: '',
})

const bankOptions = [
  'Bank BCA',
  'Bank Mandiri',
  'Bank BRI',
  'Bank BNI',
  'Bank Syariah Indonesia (BSI)',
  'Bank CIMB Niaga',
  'Bank Permata',
  'Bank Danamon',
]

watch(
  () => props.open,
  isOpen => {
    if (isOpen) {
      resetForm()
      if (props.mitra) {
        form.email = props.mitra.user?.email || ''
        form.password = ''
        form.password_confirmation = ''
        form.nama_pemilik = props.mitra.nama_pemilik || ''
        form.telepon = props.mitra.telepon || ''
        form.alamat = props.mitra.alamat || ''
        form.deskripsi = props.mitra.deskripsi || ''
        form.status = props.mitra.status || 'aktif'
        form.nik = props.mitra.nik || ''
        form.npwp = props.mitra.npwp || ''
        form.bank = props.mitra.bank || 'Bank BCA'
        form.rekening_bank = props.mitra.rekening_bank || ''
        form.nama_rekening = props.mitra.nama_rekening || ''
        form.ewallet = props.mitra.ewallet || ''
      }
    }
  },
)

function resetForm() {
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  form.nama_pemilik = ''
  form.telepon = ''
  form.alamat = ''
  form.deskripsi = ''
  form.status = 'aktif'
  form.nik = ''
  form.npwp = ''
  form.bank = 'Bank BCA'
  form.rekening_bank = ''
  form.nama_rekening = ''
  form.ewallet = ''
  formErrorMessage.value = ''
  fieldErrors.value = {}
  activeTab.value = 'account'
}

function handleClose() {
  emit('update:open', false)
}

async function handleSubmit() {
  formErrorMessage.value = ''
  fieldErrors.value = {}

  // Client-side quick validations
  if (!isEditMode.value) {
    if (!form.password) {
      fieldErrors.value.password = 'Password wajib diisi.'
      activeTab.value = 'account'
      return
    }
    if (form.password.length < 8) {
      fieldErrors.value.password = 'Password minimal 8 karakter.'
      activeTab.value = 'account'
      return
    }
    if (form.password !== form.password_confirmation) {
      fieldErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.'
      activeTab.value = 'account'
      return
    }
  } else if (form.password && form.password !== form.password_confirmation) {
    fieldErrors.value.password_confirmation = 'Konfirmasi password tidak cocok.'
    activeTab.value = 'account'
    return
  }

  if (!form.nama_pemilik.trim()) {
    fieldErrors.value.nama_pemilik = 'Nama pemilik/penanggung jawab wajib diisi.'
    activeTab.value = 'identity'
    return
  }

  if (!form.nik.trim() || form.nik.length !== 16) {
    fieldErrors.value.nik = 'NIK harus berjumlah 16 digit angka.'
    activeTab.value = 'identity'
    return
  }

  if (!form.rekening_bank.trim()) {
    fieldErrors.value.rekening_bank = 'Nomor rekening bank wajib diisi.'
    activeTab.value = 'banking'
    return
  }

  isSubmitting.value = true

  try {
    if (isEditMode.value && props.mitra) {
      const payload: UpdateMitraPayload = {
        email: form.email,
        nama_pemilik: form.nama_pemilik,
        telepon: form.telepon,
        alamat: form.alamat,
        deskripsi: form.deskripsi || undefined,
        status: form.status,
        nik: form.nik,
        npwp: form.npwp || undefined,
        bank: form.bank,
        rekening_bank: form.rekening_bank,
        nama_rekening: form.nama_rekening || form.nama_pemilik,
        ewallet: form.ewallet || undefined,
      }

      if (form.password.trim()) {
        payload.password = form.password
      }

      await updatePartner(props.mitra.id, payload)
      toast.success(`Profil dan akun mitra "${form.nama_pemilik}" telah berhasil diperbarui.`, 'Mitra Berhasil Diperbarui')
    } else {
      const payload: CreateMitraPayload = {
        email: form.email,
        password: form.password,
        nama_pemilik: form.nama_pemilik,
        telepon: form.telepon,
        alamat: form.alamat,
        deskripsi: form.deskripsi || undefined,
        status: form.status,
        nik: form.nik,
        npwp: form.npwp || undefined,
        bank: form.bank,
        rekening_bank: form.rekening_bank,
        nama_rekening: form.nama_rekening || form.nama_pemilik,
        ewallet: form.ewallet || undefined,
      }

      await createPartner(payload)
      toast.success(`Akun portal dan profil mitra "${form.nama_pemilik}" berhasil dibuat.`, 'Mitra Baru Didaftarkan')
    }

    emit('success')
    handleClose()
  } catch (err: any) {
    const errorDetails = extractApiError(err)
    formErrorMessage.value = errorDetails.message
    fieldErrors.value = errorDetails.fieldErrors

    // Auto switch tab based on error field
    if (fieldErrors.value.email || fieldErrors.value.password) {
      activeTab.value = 'account'
    } else if (fieldErrors.value.nik || fieldErrors.value.nama_pemilik || fieldErrors.value.telepon || fieldErrors.value.alamat) {
      activeTab.value = 'identity'
    } else if (fieldErrors.value.rekening_bank || fieldErrors.value.bank || fieldErrors.value.nama_rekening) {
      activeTab.value = 'banking'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-2xl max-h-[92vh] flex flex-col p-0 gap-0 overflow-hidden">
      <!-- Modal Header -->
      <DialogHeader class="p-5 border-b bg-card shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="h-9 w-9 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <Building2 class="h-5 w-5" />
          </div>
          <div>
            <DialogTitle class="text-base font-bold tracking-tight">
              {{ isEditMode ? 'Perbarui Data Mitra Basecamp' : 'Pendaftaran Mitra Baru' }}
            </DialogTitle>
            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
              {{ isEditMode ? 'Ubah informasi akun, legalitas bisnis, atau data rekening mitra.' : 'Registrasi akun login portal dan profil bisnis mitra pengelola basecamp terpadu.' }}
            </DialogDescription>
          </div>
        </div>

        <!-- Tab Selector Header -->
        <div class="flex items-center gap-1 mt-4 p-1 bg-muted/40 rounded-lg border border-border/50 text-xs">
          <button
            type="button"
            class="flex-1 py-1.5 px-2.5 rounded-md font-medium transition-all flex items-center justify-center gap-1.5"
            :class="activeTab === 'account' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="activeTab = 'account'"
          >
            <User class="h-3.5 w-3.5" />
            <span>1. Akun Akses</span>
          </button>
          <button
            type="button"
            class="flex-1 py-1.5 px-2.5 rounded-md font-medium transition-all flex items-center justify-center gap-1.5"
            :class="activeTab === 'identity' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="activeTab = 'identity'"
          >
            <Shield class="h-3.5 w-3.5" />
            <span>2. Legalitas & Kontak</span>
          </button>
          <button
            type="button"
            class="flex-1 py-1.5 px-2.5 rounded-md font-medium transition-all flex items-center justify-center gap-1.5"
            :class="activeTab === 'banking' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="activeTab = 'banking'"
          >
            <CreditCard class="h-3.5 w-3.5" />
            <span>3. Rekening Finansial</span>
          </button>
        </div>
      </DialogHeader>

      <!-- Modal Body (Scrollable) -->
      <div class="flex-1 overflow-y-auto p-5 space-y-4">
        <!-- Inline Error Banner -->
        <Alert v-if="formErrorMessage" variant="destructive" class="py-2.5 px-3.5">
          <AlertCircle class="h-4 w-4 shrink-0" />
          <div class="ml-2">
            <AlertTitle class="text-xs font-semibold">Validasi Gagal</AlertTitle>
            <AlertDescription class="text-xs text-destructive-foreground/90">
              {{ formErrorMessage }}
            </AlertDescription>
          </div>
        </Alert>

        <!-- Tab 1: Akun Akses Portal -->
        <div v-show="activeTab === 'account'" class="space-y-3.5 animate-in fade-in-50 duration-150">
          <div class="space-y-1.5">
            <Label for="email" class="text-xs font-semibold flex items-center gap-1.5">
              <Mail class="h-3.5 w-3.5 text-muted-foreground" />
              <span>Email Akun Login <span class="text-destructive">*</span></span>
            </Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="mitra@contoh.id"
              class="h-9 text-xs"
              :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.email }"
            />
            <p v-if="fieldErrors.email" class="text-[11px] text-destructive font-medium">{{ fieldErrors.email }}</p>
            <p class="text-[10px] text-muted-foreground">Digunakan oleh penanggung jawab mitra untuk login ke Portal Mitra.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="password" class="text-xs font-semibold flex items-center gap-1.5">
                <Lock class="h-3.5 w-3.5 text-muted-foreground" />
                <span>
                  {{ isEditMode ? 'Password Baru (Opsional)' : 'Password Akun' }}
                  <span v-if="!isEditMode" class="text-destructive">*</span>
                </span>
              </Label>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Minimal 8 karakter"
                class="h-9 text-xs"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.password }"
              />
              <p v-if="fieldErrors.password" class="text-[11px] text-destructive font-medium">{{ fieldErrors.password }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="password_confirmation" class="text-xs font-semibold flex items-center gap-1.5">
                <Lock class="h-3.5 w-3.5 text-muted-foreground" />
                <span>Konfirmasi Password</span>
              </Label>
              <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="Ulangi password"
                class="h-9 text-xs"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.password_confirmation }"
              />
              <p v-if="fieldErrors.password_confirmation" class="text-[11px] text-destructive font-medium">{{ fieldErrors.password_confirmation }}</p>
            </div>
          </div>

          <div class="p-3 bg-muted/30 rounded-lg border border-border/50 text-[11px] text-muted-foreground space-y-1">
            <p class="font-semibold text-foreground flex items-center gap-1.5">
              <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
              <span>Otomatis Terverifikasi</span>
            </p>
            <p>Akun yang didaftarkan oleh Administrator akan langsung aktif dan terverifikasi tanpa perlu konfirmasi email OTP.</p>
          </div>
        </div>

        <!-- Tab 2: Legalitas & Kontak -->
        <div v-show="activeTab === 'identity'" class="space-y-3.5 animate-in fade-in-50 duration-150">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="nama_pemilik" class="text-xs font-semibold">
                Nama Pemilik / Penanggung Jawab <span class="text-destructive">*</span>
              </Label>
              <Input
                id="nama_pemilik"
                v-model="form.nama_pemilik"
                placeholder="Contoh: Budi Santoso"
                class="h-9 text-xs"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.nama_pemilik }"
              />
              <p v-if="fieldErrors.nama_pemilik" class="text-[11px] text-destructive font-medium">{{ fieldErrors.nama_pemilik }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="telepon" class="text-xs font-semibold flex items-center gap-1.5">
                <Phone class="h-3.5 w-3.5 text-muted-foreground" />
                <span>Nomor WhatsApp / Telepon <span class="text-destructive">*</span></span>
              </Label>
              <Input
                id="telepon"
                v-model="form.telepon"
                placeholder="Contoh: 081234567890"
                class="h-9 text-xs"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.telepon }"
              />
              <p v-if="fieldErrors.telepon" class="text-[11px] text-destructive font-medium">{{ fieldErrors.telepon }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="nik" class="text-xs font-semibold flex items-center gap-1.5">
                <FileText class="h-3.5 w-3.5 text-muted-foreground" />
                <span>NIK KTP (16 Digit) <span class="text-destructive">*</span></span>
              </Label>
              <Input
                id="nik"
                v-model="form.nik"
                maxlength="16"
                placeholder="3201xxxxxxxxxxxx"
                class="h-9 text-xs font-mono"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.nik }"
              />
              <p v-if="fieldErrors.nik" class="text-[11px] text-destructive font-medium">{{ fieldErrors.nik }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="npwp" class="text-xs font-semibold">
                NPWP Usaha / Pribadi (Opsional)
              </Label>
              <Input
                id="npwp"
                v-model="form.npwp"
                placeholder="Contoh: 12.345.678.9-012.000"
                class="h-9 text-xs font-mono"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.npwp }"
              />
              <p v-if="fieldErrors.npwp" class="text-[11px] text-destructive font-medium">{{ fieldErrors.npwp }}</p>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="status" class="text-xs font-semibold">Status Kemitraan <span class="text-destructive">*</span></Label>
            <select
              id="status"
              v-model="form.status"
              class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
              <option value="aktif">🟢 Aktif (Operasional Penuh)</option>
              <option value="suspend">🔴 Suspend (Akses Dibekukan)</option>
            </select>
          </div>

          <div class="space-y-1.5">
            <Label for="alamat" class="text-xs font-semibold">
              Alamat Lengkap / Kantor Operasional <span class="text-destructive">*</span>
            </Label>
            <textarea
              id="alamat"
              v-model="form.alamat"
              rows="2"
              placeholder="Jl. Raya Basecamp No. 12, Desa Tarub, Boyolali"
              class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
              :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.alamat }"
            />
            <p v-if="fieldErrors.alamat" class="text-[11px] text-destructive font-medium">{{ fieldErrors.alamat }}</p>
          </div>

          <div class="space-y-1.5">
            <Label for="deskripsi" class="text-xs font-semibold">Deskripsi / Profil Singkat</Label>
            <textarea
              id="deskripsi"
              v-model="form.deskripsi"
              rows="2"
              placeholder="Pengelola Basecamp Gunung Merbabu jalur Selo dan sekitarnya."
              class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
            />
          </div>
        </div>

        <!-- Tab 3: Rekening Finansial -->
        <div v-show="activeTab === 'banking'" class="space-y-3.5 animate-in fade-in-50 duration-150">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="bank" class="text-xs font-semibold">Bank Tujuan Pencairan <span class="text-destructive">*</span></Label>
              <select
                id="bank"
                v-model="form.bank"
                class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option v-for="b in bankOptions" :key="b" :value="b">
                  {{ b }}
                </option>
              </select>
            </div>

            <div class="space-y-1.5">
              <Label for="rekening_bank" class="text-xs font-semibold flex items-center gap-1.5">
                <CreditCard class="h-3.5 w-3.5 text-muted-foreground" />
                <span>Nomor Rekening Bank <span class="text-destructive">*</span></span>
              </Label>
              <Input
                id="rekening_bank"
                v-model="form.rekening_bank"
                placeholder="Contoh: 1234567890"
                class="h-9 text-xs font-mono"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.rekening_bank }"
              />
              <p v-if="fieldErrors.rekening_bank" class="text-[11px] text-destructive font-medium">{{ fieldErrors.rekening_bank }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <Label for="nama_rekening" class="text-xs font-semibold">
                Nama Pemilik Rekening <span class="text-destructive">*</span>
              </Label>
              <Input
                id="nama_rekening"
                v-model="form.nama_rekening"
                :placeholder="form.nama_pemilik || 'Sesuai buku tabungan'"
                class="h-9 text-xs"
                :class="{ 'border-destructive focus-visible:ring-destructive': fieldErrors.nama_rekening }"
              />
              <p v-if="fieldErrors.nama_rekening" class="text-[11px] text-destructive font-medium">{{ fieldErrors.nama_rekening }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="ewallet" class="text-xs font-semibold flex items-center gap-1.5">
                <Wallet class="h-3.5 w-3.5 text-muted-foreground" />
                <span>Nomor E-Wallet (Opsional)</span>
              </Label>
              <Input
                id="ewallet"
                v-model="form.ewallet"
                placeholder="Contoh: 081234567890 (GoPay/OVO/DANA)"
                class="h-9 text-xs"
              />
            </div>
          </div>

          <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-800 dark:text-amber-300 space-y-1">
            <p class="font-semibold">⚠️ Ketentuan Rekening Pencairan</p>
            <p class="text-[11px] text-amber-700/90 dark:text-amber-400">
              Pastikan nama pemilik rekening bank sesuai dengan nama pada identitas penanggung jawab untuk kelancaran proses settlement escrow dan withdrawal otomatis.
            </p>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <DialogFooter class="p-4 border-t bg-card shrink-0 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Button
            v-if="activeTab !== 'account'"
            type="button"
            variant="ghost"
            size="sm"
            class="text-xs"
            @click="activeTab = activeTab === 'banking' ? 'identity' : 'account'"
          >
            ← Kembali
          </Button>
        </div>

        <div class="flex items-center gap-2">
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
            v-if="activeTab !== 'banking'"
            type="button"
            size="sm"
            class="text-xs"
            @click="activeTab = activeTab === 'account' ? 'identity' : 'banking'"
          >
            Lanjut →
          </Button>

          <Button
            v-else
            type="button"
            size="sm"
            class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white gap-1.5"
            :disabled="isSubmitting"
            @click="handleSubmit"
          >
            <Loader2 v-if="isSubmitting" class="h-3.5 w-3.5 animate-spin" />
            <span>{{ isEditMode ? 'Simpan Perubahan' : 'Daftarkan Mitra' }}</span>
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
