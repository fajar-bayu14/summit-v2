<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  User,
  Phone,
  Lock,
  AlertCircle,
  CheckCircle2,
  Edit3,
  Key,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import KycStatusBanner from '@/components/pendaki/KycStatusBanner.vue'
import KycSecurityShield from '@/components/pendaki/KycSecurityShield.vue'
import KycSubmissionModal from '@/components/pendaki/KycSubmissionModal.vue'
import { useAuthStore } from '@/stores/auth'
import { pendakiKycApi } from '@/api/pendakiKyc'
import { formatDateIndonesia } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { PendakiProfile } from '@/types/pendakiKyc'

const authStore = useAuthStore()

const activeTab = ref<'biodata' | 'emergency' | 'security'>('biodata')
const isKycModalOpen = ref(false)
const isLoadingProfile = ref(false)

// Password form state
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})
const isChangingPassword = ref(false)
const passwordSuccess = ref<string | null>(null)
const passwordError = ref<string | null>(null)
const passwordFieldErrors = ref<Record<string, string>>({})

const pendaki = computed<PendakiProfile | null>(() => authStore.user?.pendaki || null)
const kycStatus = computed(() => authStore.kycStatus)
const rejectionReason = computed(() => pendaki.value?.alasan_penolakan || null)

onMounted(async () => {
  if (authStore.isAuthenticated) {
    isLoadingProfile.value = true
    try {
      await authStore.fetchKycStatus()
    } finally {
      isLoadingProfile.value = false
    }
  }
})

function handleOpenKycModal() {
  isKycModalOpen.value = true
}

function handleKycSubmitted(profile: PendakiProfile) {
  authStore.updatePendakiProfile(profile)
}

async function handlePasswordSubmit() {
  passwordSuccess.value = null
  passwordError.value = null
  passwordFieldErrors.value = {}

  if (!passwordForm.value.current_password) {
    passwordFieldErrors.value.current_password = 'Kata sandi saat ini wajib diisi.'
    return
  }
  if (!passwordForm.value.new_password) {
    passwordFieldErrors.value.new_password = 'Kata sandi baru wajib diisi.'
    return
  }
  if (passwordForm.value.new_password.length < 8) {
    passwordFieldErrors.value.new_password = 'Kata sandi baru minimal 8 karakter.'
    return
  }
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    passwordFieldErrors.value.new_password_confirmation = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  isChangingPassword.value = true
  try {
    const response = await pendakiKycApi.updatePassword(passwordForm.value)
    passwordSuccess.value = response.message || 'Kata sandi berhasil diperbarui.'
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }
  } catch (err: any) {
    const { message, fieldErrors } = extractApiError(err)
    passwordError.value = message
    if (Object.keys(fieldErrors).length > 0) {
      passwordFieldErrors.value = fieldErrors
    }
  } finally {
    isChangingPassword.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header Profil Card -->
    <div class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-extrabold text-2xl sm:text-3xl flex items-center justify-center border-2 border-emerald-300 dark:border-emerald-700 shadow-sm shrink-0">
            {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'P' }}
          </div>
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-slate-100 font-display">
                {{ authStore.user?.name || 'Pendaki Summit' }}
              </h1>
              <KycSecurityShield :status="kycStatus" />
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ authStore.user?.email }}
            </p>
            <p v-if="pendaki?.telepon" class="text-xs text-slate-500 flex items-center gap-1.5">
              <Phone class="w-3.5 h-3.5 text-slate-400" />
              <span>{{ pendaki.telepon }}</span>
            </p>
          </div>
        </div>

        <Button
          type="button"
          class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-xl shadow-xs self-start sm:self-center flex items-center gap-2"
          @click="handleOpenKycModal"
        >
          <Edit3 class="w-4 h-4" />
          <span>{{ pendaki ? 'Perbarui Data KYC' : 'Lengkapi KYC Sekarang' }}</span>
        </Button>
      </div>
    </div>

    <!-- Status Banner KYC -->
    <KycStatusBanner
      :status="kycStatus"
      :rejection-reason="rejectionReason"
      @verify="handleOpenKycModal"
    />

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-px">
      <button
        type="button"
        class="px-5 py-3 text-xs sm:text-sm font-semibold rounded-t-xl transition-all border-b-2 flex items-center gap-2"
        :class="[
          activeTab === 'biodata'
            ? 'border-emerald-700 text-emerald-800 dark:text-emerald-400 bg-white dark:bg-slate-900'
            : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-200',
        ]"
        @click="activeTab = 'biodata'"
      >
        <User class="w-4 h-4" />
        <span>Biodata &amp; Identitas</span>
      </button>

      <button
        type="button"
        class="px-5 py-3 text-xs sm:text-sm font-semibold rounded-t-xl transition-all border-b-2 flex items-center gap-2"
        :class="[
          activeTab === 'emergency'
            ? 'border-emerald-700 text-emerald-800 dark:text-emerald-400 bg-white dark:bg-slate-900'
            : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-200',
        ]"
        @click="activeTab = 'emergency'"
      >
        <Phone class="w-4 h-4" />
        <span>Kontak Darurat</span>
      </button>

      <button
        type="button"
        class="px-5 py-3 text-xs sm:text-sm font-semibold rounded-t-xl transition-all border-b-2 flex items-center gap-2"
        :class="[
          activeTab === 'security'
            ? 'border-emerald-700 text-emerald-800 dark:text-emerald-400 bg-white dark:bg-slate-900'
            : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-200',
        ]"
        @click="activeTab = 'security'"
      >
        <Lock class="w-4 h-4" />
        <span>Keamanan Akun</span>
      </button>
    </div>

    <!-- TAB 1: BIODATA & IDENTITAS -->
    <div v-if="activeTab === 'biodata'" class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-6">
      <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
        <div>
          <h2 class="font-bold text-base sm:text-lg text-slate-900 dark:text-slate-100">Informasi Identitas Resmi</h2>
          <p class="text-xs text-slate-500">Data resmi yang digunakan untuk verifikasi SIMAKSI dan manifes rombongan.</p>
        </div>
        <KycSecurityShield :status="kycStatus" compact />
      </div>

      <div v-if="pendaki" class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Nama Lengkap</p>
          <p class="font-bold text-slate-900 dark:text-slate-100">{{ pendaki.nama_lengkap || authStore.user?.name }}</p>
        </div>

        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Jenis &amp; Nomor Identitas</p>
          <p class="font-bold text-slate-900 dark:text-slate-100 uppercase">
            {{ pendaki.jenis_identitas }}: <span class="font-mono">{{ pendaki.nomor_identitas }}</span>
          </p>
        </div>

        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Tanggal Lahir</p>
          <p class="font-medium text-slate-900 dark:text-slate-100">{{ formatDateIndonesia(pendaki.tanggal_lahir) }}</p>
        </div>

        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Jenis Kelamin</p>
          <p class="font-medium text-slate-900 dark:text-slate-100">{{ pendaki.jenis_kelamin === 'l' ? 'Laki-Laki' : 'Perempuan' }}</p>
        </div>

        <div class="space-y-1 sm:col-span-2">
          <p class="text-xs text-slate-400 font-semibold">Alamat Domisili</p>
          <p class="font-medium text-slate-900 dark:text-slate-100">{{ pendaki.alamat || '-' }}</p>
        </div>
      </div>

      <!-- Empty state jika belum pernah isi KYC -->
      <div v-else class="text-center py-8 space-y-3">
        <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto">
          <AlertCircle class="w-6 h-6" />
        </div>
        <h3 class="font-bold text-slate-800 dark:text-slate-200">Data KYC Belum Diisi</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Anda belum mengunggah dokumen KTP/Paspor resmi. Lengkapi data diri Anda agar siap mendaki kapan saja.
        </p>
        <Button
          type="button"
          class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-semibold"
          @click="handleOpenKycModal"
        >
          Isi Formulir KYC
        </Button>
      </div>
    </div>

    <!-- TAB 2: KONTAK DARURAT -->
    <div v-else-if="activeTab === 'emergency'" class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-6">
      <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
        <h2 class="font-bold text-base sm:text-lg text-slate-900 dark:text-slate-100">Kontak Darurat (Emergency Contact)</h2>
        <p class="text-xs text-slate-500">Pihak keluarga/kerabat yang dapat dihubungi oleh petugas basecamp dalam kondisi darurat di gunung.</p>
      </div>

      <div v-if="pendaki?.nama_kontak_darurat" class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Nama Kontak Darurat</p>
          <p class="font-bold text-slate-900 dark:text-slate-100">{{ pendaki.nama_kontak_darurat }}</p>
        </div>

        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Nomor Telepon Darurat</p>
          <p class="font-bold text-slate-900 dark:text-slate-100 font-mono">{{ pendaki.telepon_darurat }}</p>
        </div>

        <div class="space-y-1">
          <p class="text-xs text-slate-400 font-semibold">Hubungan Keluarga</p>
          <p class="font-medium text-slate-900 dark:text-slate-100">{{ pendaki.hubungan_darurat }}</p>
        </div>
      </div>

      <div v-else class="text-center py-8 space-y-3">
        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center mx-auto">
          <Phone class="w-6 h-6" />
        </div>
        <h3 class="font-bold text-slate-800 dark:text-slate-200">Kontak Darurat Belum Diisi</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Kontak darurat wajib diisi untuk pemenuhan SOP keselamatan pendakian Taman Nasional.
        </p>
        <Button
          type="button"
          class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-semibold"
          @click="handleOpenKycModal"
        >
          Lengkapi Kontak Darurat
        </Button>
      </div>
    </div>

    <!-- TAB 3: KEAMANAN AKUN (GANTI PASSWORD) -->
    <div v-else-if="activeTab === 'security'" class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-6">
      <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
        <h2 class="font-bold text-base sm:text-lg text-slate-900 dark:text-slate-100">Ganti Kata Sandi</h2>
        <p class="text-xs text-slate-500">Perbarui kata sandi secara berkala untuk menjaga keamanan akun pendaki Anda.</p>
      </div>

      <!-- Feedback Alerts -->
      <div v-if="passwordSuccess" class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 shrink-0" />
        <span>{{ passwordSuccess }}</span>
      </div>

      <div v-if="passwordError" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 text-rose-800 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ passwordError }}</span>
      </div>

      <form @submit.prevent="handlePasswordSubmit" class="max-w-md space-y-4">
        <div class="space-y-1.5">
          <Label for="current_password" class="text-xs font-semibold">Kata Sandi Saat Ini</Label>
          <Input
            id="current_password"
            type="password"
            v-model="passwordForm.current_password"
            placeholder="••••••••"
            class="rounded-xl h-10 text-sm"
            :class="{ 'border-rose-500': passwordFieldErrors.current_password }"
          />
          <p v-if="passwordFieldErrors.current_password" class="text-xs text-rose-500">{{ passwordFieldErrors.current_password }}</p>
        </div>

        <div class="space-y-1.5">
          <Label for="new_password" class="text-xs font-semibold">Kata Sandi Baru</Label>
          <Input
            id="new_password"
            type="password"
            v-model="passwordForm.new_password"
            placeholder="Minimal 8 karakter"
            class="rounded-xl h-10 text-sm"
            :class="{ 'border-rose-500': passwordFieldErrors.new_password }"
          />
          <p v-if="passwordFieldErrors.new_password" class="text-xs text-rose-500">{{ passwordFieldErrors.new_password }}</p>
        </div>

        <div class="space-y-1.5">
          <Label for="new_password_confirmation" class="text-xs font-semibold">Konfirmasi Kata Sandi Baru</Label>
          <Input
            id="new_password_confirmation"
            type="password"
            v-model="passwordForm.new_password_confirmation"
            placeholder="Ulangi kata sandi baru"
            class="rounded-xl h-10 text-sm"
            :class="{ 'border-rose-500': passwordFieldErrors.new_password_confirmation }"
          />
          <p v-if="passwordFieldErrors.new_password_confirmation" class="text-xs text-rose-500">{{ passwordFieldErrors.new_password_confirmation }}</p>
        </div>

        <Button
          type="submit"
          class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-semibold shadow-xs flex items-center gap-2"
          :disabled="isChangingPassword"
        >
          <Key class="w-4 h-4" />
          <span>{{ isChangingPassword ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
        </Button>
      </form>
    </div>

    <!-- Modal Form KYC -->
    <KycSubmissionModal
      :is-open="isKycModalOpen"
      :initial-data="pendaki"
      @update:is-open="isKycModalOpen = $event"
      @submitted="handleKycSubmitted"
    />
  </div>
</template>
