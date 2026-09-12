<template>
  <div class="summit-page">
    <!-- Header -->
    <header class="summit-top-bar">
      <button
        class="summit-icon-btn"
        aria-label="Kembali"
        @click="$router.push('/profile')"
      >
        <q-icon name="arrow_back" />
      </button>
      <h1 class="summit-top-bar__title">Verifikasi Identitas (KYC)</h1>
      <div style="width: 36px"></div>
    </header>

    <main class="summit-page__content">
      <div class="kyc-guidelines-box">
        <q-icon name="info" size="20px" class="text-primary" />
        <p>
          Sesuai SOP Taman Nasional & Basecamp, seluruh pendaki wajib
          memverifikasi identitas resmi (KTP/Paspor) serta nomor kontak darurat
          sebelum melakukan booking tiket.
        </p>
      </div>

      <!-- Current Status Banner if exists -->
      <div
        v-if="existingKyc"
        class="kyc-status-banner q-mb-md"
        :class="`is-${normalizedStatus}`"
      >
        <q-icon name="shield" size="18px" />
        <span
          >Status KYC Saat Ini:
          <strong>{{ formatKycStatus(rawStatus) }}</strong></span
        >
      </div>

      <!-- Form -->
      <form class="kyc-form-card" @submit.prevent="handleSubmit">
        <div class="form-group">
          <label class="form-label"
            >Nama Lengkap (Sesuai KTP) <span class="text-red">*</span></label
          >
          <input
            v-model="form.nama_lengkap"
            type="text"
            class="form-input"
            placeholder="Contoh: Budi Santoso"
            required
            :disabled="isVerified"
          />
        </div>

        <div class="form-group">
          <label class="form-label"
            >Nomor Induk Kependudukan (NIK)
            <span class="text-red">*</span></label
          >
          <input
            v-model="form.nik"
            type="text"
            class="form-input"
            placeholder="16 digit NIK KTP"
            maxlength="16"
            required
            :disabled="isVerified"
          />
        </div>

        <div class="form-group">
          <label class="form-label"
            >Nomor Kontak Darurat (Keluarga)
            <span class="text-red">*</span></label
          >
          <input
            v-model="form.kontak_darurat"
            type="tel"
            class="form-input"
            placeholder="Contoh: 08123456789"
            required
            :disabled="isVerified"
          />
        </div>

        <div class="form-group">
          <label class="form-label"
            >Nama Kontak Darurat <span class="text-red">*</span></label
          >
          <input
            v-model="form.nama_kontak_darurat"
            type="text"
            class="form-input"
            placeholder="Contoh: Siti Rahma (Ibu)"
            required
            :disabled="isVerified"
          />
        </div>

        <div v-if="!isVerified" class="form-group">
          <label class="form-label"
            >Foto Kartu Identitas (KTP) <span class="text-red">*</span></label
          >
          <input
            type="file"
            accept="image/*"
            class="form-file-input"
            required
            @change="onFileChange"
          />
          <span class="form-hint"
            >Format JPG/PNG, ukuran maks 2MB, teks dan foto harus terlihat
            jelas.</span
          >
        </div>

        <button
          v-if="!isVerified"
          type="submit"
          class="btn-primary btn-primary--block q-mt-lg"
          :disabled="submitting"
        >
          <q-spinner-dots v-if="submitting" />
          <span v-else>Kirim Verifikasi Identitas</span>
        </button>
      </form>
    </main>

    <!-- Bottom navigation -->
    <BottomNav active="profil" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import BottomNav from '@/components/BottomNav.vue'
import { getKycStatus, submitKyc } from '@/api/kyc'

const router = useRouter()
const $q = useQuasar()

const existingKyc = ref(null)
const submitting = ref(false)
const ktpFile = ref(null)

const form = ref({
  nama_lengkap: '',
  nik: '',
  kontak_darurat: '',
  nama_kontak_darurat: ''
})

onMounted(async () => {
  try {
    const res = await getKycStatus()
    if (res.data) {
      existingKyc.value = res.data
      form.value.nama_lengkap = res.data.nama_lengkap || ''
      form.value.nik = res.data.nik || ''
      form.value.kontak_darurat = res.data.kontak_darurat || ''
      form.value.nama_kontak_darurat = res.data.nama_kontak_darurat || ''
    }
  } catch (err) {
    // KYC not submitted yet
  }
})

const rawStatus = computed(() => {
  return (
    existingKyc.value?.status_verifikasi ||
    existingKyc.value?.status ||
    'unverified'
  )
})

const normalizedStatus = computed(() => {
  if (rawStatus.value === 'disetujui') return 'verified'
  if (rawStatus.value === 'ditolak') return 'rejected'
  return rawStatus.value
})

const isVerified = computed(() => {
  return normalizedStatus.value === 'verified'
})

function formatKycStatus(status) {
  const map = {
    disetujui: 'Terverifikasi (Approved)',
    verified: 'Terverifikasi (Approved)',
    pending: 'Sedang Ditinjau Admin',
    ditolak: 'Ditolak (Perlu Perbaikan)',
    rejected: 'Ditolak (Perlu Perbaikan)'
  }
  return map[status] || status
}

function onFileChange(e) {
  if (e.target.files && e.target.files[0]) {
    ktpFile.value = e.target.files[0]
  }
}

async function handleSubmit() {
  if (!ktpFile.value) {
    $q.notify({ type: 'warning', message: 'Silakan pilih foto KTP Anda.' })
    return
  }

  submitting.value = true
  const formData = new FormData()
  formData.append('nama_lengkap', form.value.nama_lengkap)
  formData.append('nik', form.value.nik)
  formData.append('kontak_darurat', form.value.kontak_darurat)
  formData.append('nama_kontak_darurat', form.value.nama_kontak_darurat)
  formData.append('foto_ktp', ktpFile.value)

  try {
    const res = await submitKyc(formData)
    $q.notify({
      type: 'positive',
      message: 'Data KYC berhasil dikirim untuk verifikasi!'
    })
    existingKyc.value = res.data
    router.push('/profile')
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal mengirim data KYC'
    })
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss">
.kyc-guidelines-box {
  display: flex;
  gap: 10px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 12px;
  margin-bottom: 16px;

  p {
    font-size: 0.75rem;
    color: #166534;
    margin: 0;
    line-height: 1.4;
  }
}

.kyc-form-card {
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 16px;
  padding: 20px 16px;
}

.form-group {
  margin-bottom: 16px;
}

.form-input {
  width: 100%;
  height: 44px;
  padding: 0 12px;
  border: 1px solid $summit-border;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;

  &:focus {
    outline: none;
    border-color: $primary;
  }

  &:disabled {
    background: $summit-surface-container-low;
    color: $summit-text-secondary;
  }
}

.form-hint {
  display: block;
  font-size: 0.6875rem;
  color: $summit-text-secondary;
  margin-top: 4px;
}

.btn-primary--block {
  width: 100%;
  height: 44px;
  justify-content: center;
}

.kyc-status-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-weight: 600;

  &.is-verified {
    background: #ecfdf5;
    color: #047857;
  }
  &.is-pending {
    background: #fef3c7;
    color: #92400e;
  }
  &.is-unverified,
  &.is-rejected {
    background: #fee2e2;
    color: #991b1b;
  }
}
</style>
