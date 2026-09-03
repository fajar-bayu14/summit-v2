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
      <h1 class="summit-top-bar__title">Lencana & E-Sertifikat</h1>
      <div style="width: 36px"></div>
    </header>

    <main class="summit-page__content">
      <div v-if="loading" class="text-center q-pa-xl">
        <q-spinner color="primary" size="2.5em" />
      </div>

      <div v-else-if="badges.length === 0" class="empty-state">
        <q-icon name="military_tech" size="48px" class="text-grey-5" />
        <h3 class="empty-state__title">Belum Ada Lencana Summit</h3>
        <p class="empty-state__text">
          Capai puncak gunung, unggah foto bukti summit di riwayat pesanan, dan
          dapatkan sertifikat resmi!
        </p>
        <button class="btn-primary" @click="$router.push('/')">
          Mulai Petualangan
        </button>
      </div>

      <!-- Badges Grid -->
      <div v-else class="badges-grid">
        <div
          v-for="badge in badges"
          :key="badge.id || badge.invoice"
          class="badge-card"
        >
          <div class="badge-icon-circle">
            <q-icon name="emoji_events" size="32px" class="text-secondary" />
          </div>
          <h3 class="badge-title">{{
            badge.badge_title || 'Penakluk ' + badge.gunung_nama
          }}</h3>
          <span class="badge-mountain"
            >{{ badge.gunung_nama }} ({{ badge.tinggi_mdpl }} MDPL)</span
          >
          <span class="badge-date">{{
            formatDate(badge.validated_at || badge.waktu_summit)
          }}</span>

          <a
            :href="downloadCertUrl(badge.invoice)"
            target="_blank"
            class="badge-cert-btn"
          >
            <q-icon name="file_download" size="14px" /> Unduh E-Sertifikat (PDF)
          </a>
        </div>
      </div>
    </main>

    <!-- Bottom navigation -->
    <BottomNav active="profil" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import BottomNav from '@/components/BottomNav.vue'
import { getBadges, getCertificateUrl } from '@/api/logbook'

const $q = useQuasar()
const loading = ref(true)
const badges = ref([])

onMounted(() => {
  fetchBadges()
})

async function fetchBadges() {
  loading.value = true
  try {
    const res = await getBadges()
    badges.value = res.data ?? []
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal memuat lencana'
    })
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function downloadCertUrl(invoice) {
  return getCertificateUrl(invoice)
}
</script>

<style lang="scss">
.badges-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

.badge-card {
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.badge-icon-circle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #fff7ed;
  border: 2px dashed $secondary;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.badge-title {
  font-size: 0.875rem;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: $summit-text-primary;
}

.badge-mountain {
  font-size: 0.75rem;
  font-weight: 600;
  color: $primary;
  margin-bottom: 2px;
}

.badge-date {
  font-size: 0.6875rem;
  color: $summit-text-secondary;
  margin-bottom: 12px;
}

.badge-cert-btn {
  font-size: 0.6875rem;
  font-weight: 700;
  color: $primary;
  background: $summit-surface-container-low;
  border: 1px solid $summit-border;
  border-radius: 6px;
  padding: 6px 10px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;

  &:hover {
    background: $summit-surface-container;
  }
}
</style>
