<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import mitraProfileApi from '@/api/mitraProfile'
import type { MitraProfile, UpdateMitraProfilePayload, ChangePasswordPayload } from '@/types/profile'
import type { Basecamp } from '@/types/basecamp'
import {
  Building2,
  CreditCard,
  Lock,
  Mountain,
  CheckCircle2,
  AlertCircle,
  Eye,
  EyeOff,
  Loader2,
  Save,
  ShieldCheck,
  Phone,
  MapPin,
  BadgeCheck,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

type TabType = 'profile' | 'bank' | 'security' | 'basecamps'

const authStore = useAuthStore()

const activeTab = ref<TabType>('profile')
const isLoading = ref<boolean>(false)
const isSavingProfile = ref<boolean>(false)
const isSavingBank = ref<boolean>(false)
const isSavingSecurity = ref<boolean>(false)

const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)
const validationErrors = ref<Record<string, string[]>>({})

const showCurrentPassword = ref<boolean>(false)
const showNewPassword = ref<boolean>(false)
const showConfirmPassword = ref<boolean>(false)

const profileData = ref<MitraProfile | null>(null)
const basecampsList = ref<Basecamp[]>([])

// Form state for Profile
const profileForm = reactive<UpdateMitraProfilePayload>({
  nama_pemilik: '',
  telepon: '',
  alamat: '',
  deskripsi: '',
  nik: '',
  npwp: '',
  bank: '',
  rekening_bank: '',
  nama_rekening: '',
  ewallet: '',
})

// Form state for Security
const securityForm = reactive<ChangePasswordPayload>({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const bankOptions = [
  'Bank BCA',
  'Bank Mandiri',
  'Bank BNI',
  'Bank BRI',
  'Bank CIMB Niaga',
  'Bank Permata',
  'Bank Danamon',
  'Bank Syariah Indonesia (BSI)',
  'Bank BTN',
  'Bank Jago',
  'SeaBank',
]

const passwordStrength = computed(() => {
  const pwd = securityForm.new_password
  if (!pwd) return { score: 0, text: '', color: 'bg-stone-200' }
  if (pwd.length < 8) return { score: 1, text: 'Terlalu pendek (min 8 karakter)', color: 'bg-red-500' }
  
  let score = 1
  if (/[A-Z]/.test(pwd)) score++
  if (/[0-9]/.test(pwd)) score++
  if (/[^A-Za-z0-9]/.test(pwd)) score++

  if (score === 2) return { score: 2, text: 'Sedang', color: 'bg-amber-500' }
  if (score >= 3) return { score: 3, text: 'Kuat & Aman', color: 'bg-emerald-600' }
  return { score: 1, text: 'Lemah', color: 'bg-red-500' }
})

function clearMessages() {
  successMessage.value = null
  errorMessage.value = null
  validationErrors.value = {}
}

async function loadProfile() {
  isLoading.value = true
  clearMessages()
  try {
    const res = await mitraProfileApi.getProfile()
    const user = res.data
    if (user?.mitra) {
      profileData.value = user.mitra as MitraProfile
      
      profileForm.nama_pemilik = user.mitra.nama_pemilik || user.name || ''
      profileForm.telepon = user.mitra.telepon || ''
      profileForm.alamat = user.mitra.alamat || ''
      profileForm.deskripsi = user.mitra.deskripsi || ''
      profileForm.nik = user.mitra.nik || ''
      profileForm.npwp = user.mitra.npwp || ''
      profileForm.bank = user.mitra.bank || bankOptions[0]
      profileForm.rekening_bank = user.mitra.rekening_bank || ''
      profileForm.nama_rekening = user.mitra.nama_rekening || ''
      profileForm.ewallet = user.mitra.ewallet || ''

      if (user.mitra.basecamps) {
        basecampsList.value = user.mitra.basecamps
      }
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Gagal memuat profil mitra.'
  } finally {
    isLoading.value = false
  }
}

async function handleSaveProfile() {
  isSavingProfile.value = true
  clearMessages()
  try {
    const res = await mitraProfileApi.updateProfile({
      ...profileForm,
    })
    successMessage.value = res.message || 'Profil pengelola berhasil disimpan.'
    if (res.data) {
      profileData.value = res.data
    }
    await authStore.fetchProfile()
  } catch (err: any) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validationErrors.value = err.response.data.errors
      errorMessage.value = 'Terdapat data input yang belum sesuai. Mohon periksa form.'
    } else {
      errorMessage.value = err.response?.data?.message || 'Gagal menyimpan profil mitra.'
    }
  } finally {
    isSavingProfile.value = false
  }
}

async function handleSaveBank() {
  isSavingBank.value = true
  clearMessages()
  try {
    const res = await mitraProfileApi.updateProfile({
      ...profileForm,
    })
    successMessage.value = 'Informasi rekening bank berhasil diperbarui.'
    if (res.data) {
      profileData.value = res.data
    }
    await authStore.fetchProfile()
  } catch (err: any) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validationErrors.value = err.response.data.errors
      errorMessage.value = 'Terdapat data rekening yang belum valid. Mohon periksa form.'
    } else {
      errorMessage.value = err.response?.data?.message || 'Gagal menyimpan informasi rekening.'
    }
  } finally {
    isSavingBank.value = false
  }
}

async function handleSaveSecurity() {
  if (securityForm.new_password !== securityForm.new_password_confirmation) {
    errorMessage.value = 'Konfirmasi kata sandi baru tidak cocok.'
    return
  }
  if (securityForm.new_password.length < 8) {
    errorMessage.value = 'Kata sandi baru minimal 8 karakter.'
    return
  }

  isSavingSecurity.value = true
  clearMessages()
  try {
    const res = await mitraProfileApi.updatePassword({
      ...securityForm,
    })
    successMessage.value = res.message || 'Kata sandi akun berhasil diubah.'
    securityForm.current_password = ''
    securityForm.new_password = ''
    securityForm.new_password_confirmation = ''
  } catch (err: any) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      validationErrors.value = err.response.data.errors
      errorMessage.value = 'Gagal mengubah kata sandi. Periksa kata sandi saat ini.'
    } else {
      errorMessage.value = err.response?.data?.message || 'Gagal memperbarui kata sandi.'
    }
  } finally {
    isSavingSecurity.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-stone-900 flex items-center gap-2">
          <Building2 class="w-7 h-7 text-[#1E3A2B]" />
          Pengaturan Akun & Profil Mitra
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Kelola profil identitas pengelola, nomor rekening pencairan dana escrow, dan keamanan akun.
        </p>
      </div>

      <!-- Status Mitra Verification Badge -->
      <div v-if="profileData" class="flex items-center gap-2 self-start sm:self-center">
        <span class="text-xs text-stone-500 font-medium">Status Akun:</span>
        <span
          class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border"
          :class="
            profileData.status === 'aktif'
              ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
              : profileData.status === 'pending'
                ? 'bg-amber-50 text-amber-700 border-amber-200'
                : 'bg-stone-100 text-stone-600 border-stone-300'
          "
        >
          <BadgeCheck class="w-3.5 h-3.5" />
          {{ profileData.status.toUpperCase() }}
        </span>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div v-if="successMessage" class="animate-fadeIn">
      <Alert class="bg-emerald-50 text-emerald-900 border-emerald-200">
        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
        <AlertTitle class="font-semibold">Berhasil Disimpan</AlertTitle>
        <AlertDescription class="text-sm text-emerald-700">
          {{ successMessage }}
        </AlertDescription>
      </Alert>
    </div>

    <div v-if="errorMessage" class="animate-fadeIn">
      <Alert variant="destructive" class="bg-red-50 text-red-900 border-red-200">
        <AlertCircle class="h-4 w-4 text-red-600" />
        <AlertTitle class="font-semibold">Terjadi Kesalahan</AlertTitle>
        <AlertDescription class="text-sm text-red-700">
          {{ errorMessage }}
        </AlertDescription>
      </Alert>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center border-b border-stone-200 overflow-x-auto gap-1 pb-px">
      <button
        type="button"
        @click="activeTab = 'profile'"
        class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
        :class="
          activeTab === 'profile'
            ? 'border-[#1E3A2B] text-[#1E3A2B]'
            : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
        "
      >
        <Building2 class="w-4 h-4" />
        Profil Usaha & Identitas
      </button>

      <button
        type="button"
        @click="activeTab = 'bank'"
        class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
        :class="
          activeTab === 'bank'
            ? 'border-[#1E3A2B] text-[#1E3A2B]'
            : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
        "
      >
        <CreditCard class="w-4 h-4" />
        Rekening Pencairan
      </button>

      <button
        type="button"
        @click="activeTab = 'security'"
        class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
        :class="
          activeTab === 'security'
            ? 'border-[#1E3A2B] text-[#1E3A2B]'
            : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
        "
      >
        <Lock class="w-4 h-4" />
        Keamanan Sandi
      </button>

      <button
        type="button"
        @click="activeTab = 'basecamps'"
        class="flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
        :class="
          activeTab === 'basecamps'
            ? 'border-[#1E3A2B] text-[#1E3A2B]'
            : 'border-transparent text-stone-600 hover:text-stone-900 hover:border-stone-300'
        "
      >
        <Mountain class="w-4 h-4" />
        Basecamp Terdaftar
        <span
          v-if="basecampsList.length"
          class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-stone-100 text-stone-700 font-bold"
        >
          {{ basecampsList.length }}
        </span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 text-stone-400">
      <Loader2 class="w-8 h-8 animate-spin mb-2 text-[#1E3A2B]" />
      <span class="text-sm font-medium">Memuat data pengaturan...</span>
    </div>

    <!-- TAB 1: Profil Usaha & Identitas -->
    <div v-else-if="activeTab === 'profile'" class="space-y-6">
      <Card class="border-stone-200">
        <CardHeader>
          <CardTitle class="text-lg font-bold text-stone-900 flex items-center gap-2">
            <Building2 class="w-5 h-5 text-[#1E3A2B]" />
            Identitas Pengelola & Usaha
          </CardTitle>
          <CardDescription>
            Informasi pengelola resmi basecamp yang tercantum pada sistem perizinan pendakian.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSaveProfile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Nama Pemilik -->
              <div class="space-y-1.5">
                <Label for="nama_pemilik">
                  Nama Lengkap Pemilik / Pengelola <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="nama_pemilik"
                  v-model="profileForm.nama_pemilik"
                  placeholder="Contoh: Budi Santoso"
                  required
                />
                <span v-if="validationErrors.nama_pemilik" class="text-xs text-red-500">
                  {{ validationErrors.nama_pemilik[0] }}
                </span>
              </div>

              <!-- Nomor Telepon -->
              <div class="space-y-1.5">
                <Label for="telepon">
                  Nomor WhatsApp / Telepon <span class="text-red-500">*</span>
                </Label>
                <div class="relative">
                  <Phone class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <Input
                    id="telepon"
                    v-model="profileForm.telepon"
                    placeholder="08xxxxxxxxxx"
                    class="pl-9"
                    required
                  />
                </div>
                <span v-if="validationErrors.telepon" class="text-xs text-red-500">
                  {{ validationErrors.telepon[0] }}
                </span>
              </div>

              <!-- NIK -->
              <div class="space-y-1.5">
                <Label for="nik">
                  NIK (KTP Pengelola) <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="nik"
                  v-model="profileForm.nik"
                  placeholder="16 digit NIK"
                  maxlength="16"
                  required
                />
                <span v-if="validationErrors.nik" class="text-xs text-red-500">
                  {{ validationErrors.nik[0] }}
                </span>
              </div>

              <!-- NPWP -->
              <div class="space-y-1.5">
                <Label for="npwp">NPWP (Opsional)</Label>
                <Input
                  id="npwp"
                  v-model="profileForm.npwp"
                  placeholder="Contoh: 12.345.678.9-012.000"
                />
                <span v-if="validationErrors.npwp" class="text-xs text-red-500">
                  {{ validationErrors.npwp[0] }}
                </span>
              </div>
            </div>

            <!-- Alamat Operasional -->
            <div class="space-y-1.5">
              <Label for="alamat">
                Alamat Kantor / Sekretariat Basecamp <span class="text-red-500">*</span>
              </Label>
              <div class="relative">
                <MapPin class="w-4 h-4 text-stone-400 absolute left-3 top-3" />
                <textarea
                  id="alamat"
                  v-model="profileForm.alamat"
                  rows="2"
                  placeholder="Jl. Raya Pendakian No. X, Desa Y, Kecamatan Z"
                  class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 pl-9 text-sm ring-offset-background placeholder:text-stone-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-400 focus-visible:ring-offset-2"
                  required
                ></textarea>
              </div>
              <span v-if="validationErrors.alamat" class="text-xs text-red-500">
                {{ validationErrors.alamat[0] }}
              </span>
            </div>

            <!-- Deskripsi Basecamp -->
            <div class="space-y-1.5">
              <Label for="deskripsi">Deskripsi Singkat Pengelola / Fasilitas Basecamp</Label>
              <textarea
                id="deskripsi"
                v-model="profileForm.deskripsi"
                rows="3"
                placeholder="Ceritakan tentang pengelola basecamp, fasilitas parkir, shelter istirahat, dll..."
                class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-sm ring-offset-background placeholder:text-stone-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-400 focus-visible:ring-offset-2"
              ></textarea>
            </div>

            <!-- Save Action Button -->
            <div class="pt-2 flex justify-end">
              <Button
                type="submit"
                :disabled="isSavingProfile"
                class="bg-[#1E3A2B] hover:bg-[#15291E] text-white flex items-center gap-2 min-h-[44px]"
              >
                <Loader2 v-if="isSavingProfile" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                Simpan Perubahan Profil
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- TAB 2: Rekening Pencairan Escrow -->
    <div v-else-if="activeTab === 'bank'" class="space-y-6">
      <Card class="border-stone-200">
        <CardHeader>
          <CardTitle class="text-lg font-bold text-stone-900 flex items-center gap-2">
            <CreditCard class="w-5 h-5 text-[#1E3A2B]" />
            Rekening Bank Tujuan Pencairan
          </CardTitle>
          <CardDescription>
            Rekening ini digunakan otomatis untuk menyalurkan dana pencairan dari Dompet Escrow Summit ke rekening Anda.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="mb-5 p-4 rounded-lg bg-amber-50 border border-amber-200 flex items-start gap-3">
            <ShieldCheck class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" />
            <div class="text-xs text-amber-900 space-y-1">
              <p class="font-bold">Proteksi Rekening & Keamanan Saldo Escrow</p>
              <p>
                Pastikan nama pemilik rekening sesuai persis dengan buku tabungan dan nama KTP pengelola terdaftar. Dana pencairan akan ditolak otomatis jika data tidak sinkron.
              </p>
            </div>
          </div>

          <form @submit.prevent="handleSaveBank" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Nama Bank -->
              <div class="space-y-1.5">
                <Label for="bank">
                  Nama Bank Tujuan <span class="text-red-500">*</span>
                </Label>
                <select
                  id="bank"
                  v-model="profileForm.bank"
                  class="w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-400 focus-visible:ring-offset-2 min-h-[44px]"
                  required
                >
                  <option v-for="bank in bankOptions" :key="bank" :value="bank">
                    {{ bank }}
                  </option>
                </select>
                <span v-if="validationErrors.bank" class="text-xs text-red-500">
                  {{ validationErrors.bank[0] }}
                </span>
              </div>

              <!-- Nomor Rekening -->
              <div class="space-y-1.5">
                <Label for="rekening_bank">
                  Nomor Rekening Bank <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="rekening_bank"
                  v-model="profileForm.rekening_bank"
                  placeholder="Contoh: 1234567890"
                  required
                />
                <span v-if="validationErrors.rekening_bank" class="text-xs text-red-500">
                  {{ validationErrors.rekening_bank[0] }}
                </span>
              </div>

              <!-- Nama Pemilik Rekening -->
              <div class="space-y-1.5">
                <Label for="nama_rekening">
                  Nama Pemilik Rekening <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="nama_rekening"
                  v-model="profileForm.nama_rekening"
                  placeholder="Harus sesuai buku tabungan"
                  required
                />
                <span v-if="validationErrors.nama_rekening" class="text-xs text-red-500">
                  {{ validationErrors.nama_rekening[0] }}
                </span>
              </div>

              <!-- E-Wallet Alternatif -->
              <div class="space-y-1.5">
                <Label for="ewallet">Nomor E-Wallet Alternatif (Opsional)</Label>
                <Input
                  id="ewallet"
                  v-model="profileForm.ewallet"
                  placeholder="Contoh: GoPay / OVO / Dana (08xxxxxxxxxx)"
                />
                <span v-if="validationErrors.ewallet" class="text-xs text-red-500">
                  {{ validationErrors.ewallet[0] }}
                </span>
              </div>
            </div>

            <!-- Save Action Button -->
            <div class="pt-2 flex justify-end">
              <Button
                type="submit"
                :disabled="isSavingBank"
                class="bg-[#1E3A2B] hover:bg-[#15291E] text-white flex items-center gap-2 min-h-[44px]"
              >
                <Loader2 v-if="isSavingBank" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                Simpan Informasi Rekening
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- TAB 3: Keamanan Sandi -->
    <div v-else-if="activeTab === 'security'" class="space-y-6">
      <Card class="border-stone-200">
        <CardHeader>
          <CardTitle class="text-lg font-bold text-stone-900 flex items-center gap-2">
            <Lock class="w-5 h-5 text-[#1E3A2B]" />
            Ganti Kata Sandi Akun
          </CardTitle>
          <CardDescription>
            Tingkatkan keamanan akun portal mitra Anda dengan memperbarui kata sandi secara berkala.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="handleSaveSecurity" class="space-y-4 max-w-xl">
            <!-- Password Saat Ini -->
            <div class="space-y-1.5">
              <Label for="current_password">
                Kata Sandi Saat Ini <span class="text-red-500">*</span>
              </Label>
              <div class="relative">
                <Input
                  id="current_password"
                  v-model="securityForm.current_password"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  placeholder="Masukkan kata sandi lama"
                  required
                />
                <button
                  type="button"
                  @click="showCurrentPassword = !showCurrentPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 focus:outline-none"
                >
                  <EyeOff v-if="showCurrentPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
              <span v-if="validationErrors.current_password" class="text-xs text-red-500">
                {{ validationErrors.current_password[0] }}
              </span>
            </div>

            <!-- Password Baru -->
            <div class="space-y-1.5">
              <Label for="new_password">
                Kata Sandi Baru <span class="text-red-500">*</span>
              </Label>
              <div class="relative">
                <Input
                  id="new_password"
                  v-model="securityForm.new_password"
                  :type="showNewPassword ? 'text' : 'password'"
                  placeholder="Minimal 8 karakter"
                  required
                />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 focus:outline-none"
                >
                  <EyeOff v-if="showNewPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>

              <!-- Password Strength Indicator -->
              <div v-if="securityForm.new_password" class="mt-2 space-y-1">
                <div class="flex h-1.5 w-full bg-stone-100 rounded-full overflow-hidden">
                  <div
                    class="transition-all duration-300"
                    :class="[
                      passwordStrength.color,
                      passwordStrength.score === 1 ? 'w-1/3' : passwordStrength.score === 2 ? 'w-2/3' : 'w-full'
                    ]"
                  ></div>
                </div>
                <p class="text-[11px] text-stone-500 font-medium">
                  Kekuatan: <span class="font-bold">{{ passwordStrength.text }}</span>
                </p>
              </div>

              <span v-if="validationErrors.new_password" class="text-xs text-red-500">
                {{ validationErrors.new_password[0] }}
              </span>
            </div>

            <!-- Konfirmasi Password Baru -->
            <div class="space-y-1.5">
              <Label for="new_password_confirmation">
                Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span>
              </Label>
              <div class="relative">
                <Input
                  id="new_password_confirmation"
                  v-model="securityForm.new_password_confirmation"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  placeholder="Ulangi kata sandi baru"
                  required
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 focus:outline-none"
                >
                  <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Save Security Action -->
            <div class="pt-2 flex justify-start">
              <Button
                type="submit"
                :disabled="isSavingSecurity"
                class="bg-[#1E3A2B] hover:bg-[#15291E] text-white flex items-center gap-2 min-h-[44px]"
              >
                <Loader2 v-if="isSavingSecurity" class="w-4 h-4 animate-spin" />
                <ShieldCheck v-else class="w-4 h-4" />
                Perbarui Kata Sandi
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- TAB 4: Basecamp Terdaftar -->
    <div v-else-if="activeTab === 'basecamps'" class="space-y-6">
      <Card class="border-stone-200">
        <CardHeader>
          <CardTitle class="text-lg font-bold text-stone-900 flex items-center gap-2">
            <Mountain class="w-5 h-5 text-[#1E3A2B]" />
            Daftar Basecamp Operasional
          </CardTitle>
          <CardDescription>
            Basecamp dan jalur pendakian yang berada di bawah naungan operasional akun mitra Anda.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="basecampsList.length === 0" class="py-12 text-center text-stone-400">
            <Mountain class="w-12 h-12 mx-auto mb-3 opacity-40" />
            <p class="font-medium text-stone-600">Belum ada basecamp yang terhubung.</p>
            <p class="text-xs text-stone-400 mt-1">Hubungi Administrator untuk pendaftaran jalur basecamp baru.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="bc in basecampsList"
              :key="bc.id"
              class="p-4 rounded-xl border border-stone-200 bg-stone-50 hover:bg-white hover:border-stone-300 transition-all space-y-3"
            >
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-bold text-stone-900 text-base flex items-center gap-1.5">
                    <Mountain class="w-4 h-4 text-[#1E3A2B]" />
                    {{ bc.nama_basecamp }}
                  </h4>
                  <p v-if="bc.jalur?.gunung?.nama_gunung" class="text-xs font-semibold text-[#E65100] mt-0.5">
                    Gunung {{ bc.jalur.gunung.nama_gunung }} • Jalur {{ bc.jalur.nama_jalur }}
                  </p>
                </div>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                  :class="
                    (bc.jalur?.status || 'open') === 'open'
                      ? 'bg-emerald-100 text-emerald-800'
                      : 'bg-red-100 text-red-800'
                  "
                >
                  {{ (bc.jalur?.status || 'open').toUpperCase() }}
                </span>
              </div>

              <div class="text-xs text-stone-600 space-y-1 border-t border-stone-200/60 pt-2">
                <div class="flex items-center justify-between">
                  <span class="text-stone-400">Jam Operasional:</span>
                  <span class="font-medium">{{ bc.jam_operasional || '24 Jam' }}</span>
                </div>
                <div v-if="bc.jalur?.waktu_tempuh" class="flex items-center justify-between">
                  <span class="text-stone-400">Estimasi Tempuh:</span>
                  <span class="font-medium">{{ bc.jalur.waktu_tempuh }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-stone-400">Koordinat GPS:</span>
                  <span class="font-mono text-[11px] text-stone-700">{{ bc.latitude }}, {{ bc.longitude }}</span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
