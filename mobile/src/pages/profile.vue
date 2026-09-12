<template>
  <div class="summit-page">
    <!-- Header -->
    <header class="summit-top-bar">
      <h1 class="summit-top-bar__title">Profil Pendaki</h1>
    </header>

    <main class="summit-page__content">
      <!-- Profile Card -->
      <section class="profile-header-card">
        <div class="profile-avatar-wrap">
          <img
            :src="profile?.avatar ? profile.avatar : defaultAvatar"
            class="profile-avatar"
            alt="Avatar"
          />
        </div>
        <h2 class="profile-name">{{ profile?.name || 'Pendaki SUMMIT' }}</h2>
        <p class="profile-email">{{ profile?.email }}</p>

        <!-- KYC Status Badge -->
        <div class="kyc-status-banner" :class="`is-${kycStatus}`">
          <q-icon :name="kycIcon" size="18px" />
          <span>{{ kycText }}</span>
          <button
            v-if="kycStatus === 'unverified' || kycStatus === 'rejected'"
            class="kyc-action-btn"
            @click="$router.push('/kyc')"
          >
            Verifikasi Sekarang
          </button>
        </div>
      </section>

      <!-- Menu Options -->
      <section class="profile-menu-section">
        <div class="profile-menu-item" @click="$router.push('/badges')">
          <div class="profile-menu-icon is-badge">
            <q-icon name="military_tech" />
          </div>
          <div class="profile-menu-label">
            <h4>Lencana & Prestasi Summit</h4>
            <p>Koleksi badge puncak & sertifikat digital</p>
          </div>
          <q-icon name="chevron_right" class="text-grey-5" />
        </div>

        <div class="profile-menu-item" @click="$router.push('/orders')">
          <div class="profile-menu-icon is-orders">
            <q-icon name="receipt_long" />
          </div>
          <div class="profile-menu-label">
            <h4>Riwayat Transaksi</h4>
            <p>Tiket pendakian & sewa alat</p>
          </div>
          <q-icon name="chevron_right" class="text-grey-5" />
        </div>

        <div class="profile-menu-item" @click="$router.push('/kyc')">
          <div class="profile-menu-icon is-kyc">
            <q-icon name="verified_user" />
          </div>
          <div class="profile-menu-label">
            <h4>Data Verifikasi KYC & KTP</h4>
            <p>Kelola NIK & kontak darurat keluarga</p>
          </div>
          <q-icon name="chevron_right" class="text-grey-5" />
        </div>

        <div class="profile-menu-item is-danger" @click="handleLogout">
          <div class="profile-menu-icon is-logout">
            <q-icon name="logout" />
          </div>
          <div class="profile-menu-label">
            <h4>Keluar Akun</h4>
            <p>Logout dari sesi aplikasi mobile</p>
          </div>
        </div>
      </section>
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
import { getProfile } from '@/api/profile'
import { getKycStatus } from '@/api/kyc'
import { clearToken } from '@/api/http'
import defaultAvatar from '@/assets/summit-logo.jpg'

const router = useRouter()
const $q = useQuasar()

const profile = ref(null)
const kycData = ref(null)

onMounted(async () => {
  try {
    const p = await getProfile()
    profile.value = p?.data ?? p

    const k = await getKycStatus().catch(() => null)
    if (k) kycData.value = k.data
  } catch (err) {
    // If not authenticated, redirect to auth
    router.push('/auth')
  }
})

const kycStatus = computed(() => {
  const raw =
    kycData.value?.status_verifikasi ||
    kycData.value?.status ||
    profile.value?.pendaki?.status_verifikasi ||
    profile.value?.kyc_status
  if (raw === 'disetujui') return 'verified'
  if (raw === 'ditolak') return 'rejected'
  return raw || 'unverified'
})

const kycIcon = computed(() => {
  if (kycStatus.value === 'verified') return 'verified'
  if (kycStatus.value === 'pending') return 'hourglass_top'
  return 'shield_alert'
})

const kycText = computed(() => {
  if (kycStatus.value === 'verified')
    return 'Identitas Terverifikasi (KYC Approved)'
  if (kycStatus.value === 'pending') return 'Verifikasi KYC Sedang Ditinjau'
  if (kycStatus.value === 'rejected')
    return 'Verifikasi Ditolak, Harap Unggah Ulang'
  return 'Belum Verifikasi KYC (Wajib untuk Booking)'
})

function handleLogout() {
  clearToken()
  $q.notify({ type: 'positive', message: 'Berhasil keluar.' })
  router.push('/auth')
}
</script>

<style lang="scss">
.profile-header-card {
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 16px;
  padding: 24px 16px;
  text-align: center;
  margin-bottom: 16px;
}

.profile-avatar-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.profile-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  border: 3px solid $primary;
  object-fit: cover;
}

.profile-name {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: $summit-text-primary;
}

.profile-email {
  font-size: 0.8125rem;
  color: $summit-text-secondary;
  margin: 0 0 16px 0;
}

.kyc-status-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.75rem;
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

.kyc-action-btn {
  background: $primary;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 0.6875rem;
  font-weight: 700;
  cursor: pointer;
}

.profile-menu-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.profile-menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  padding: 14px 16px;
  cursor: pointer;

  &.is-danger {
    color: #ef4444;
  }
}

.profile-menu-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;

  &.is-badge {
    background: $secondary;
  }
  &.is-orders {
    background: $primary;
  }
  &.is-kyc {
    background: #0284c7;
  }
  &.is-logout {
    background: #ef4444;
  }
}

.profile-menu-label {
  flex: 1;

  h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 2px 0;
    color: inherit;
  }

  p {
    font-size: 0.75rem;
    color: $summit-text-secondary;
    margin: 0;
  }
}
</style>
