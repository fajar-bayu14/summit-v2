<template>
  <div class="summit-page">
    <!-- Header -->
    <header class="summit-top-bar">
      <h1 class="summit-top-bar__title">Daftar Pesanan</h1>
      <button class="summit-icon-btn" aria-label="Refresh" @click="fetchOrders">
        <q-icon name="refresh" />
      </button>
    </header>

    <main class="summit-page__content">
      <!-- Status Tabs -->
      <div class="orders-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="orders-tabs__item"
          :class="{ 'is-active': activeTab === tab.key }"
          @click="selectTab(tab.key)"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="orders-list">
        <div v-for="n in 3" :key="n" class="order-card order-card--skeleton">
          <div class="skeleton skeleton--header"></div>
          <div class="skeleton skeleton--line"></div>
          <div class="skeleton skeleton--line skeleton--short"></div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="orders.length === 0" class="empty-state">
        <q-icon name="receipt_long" size="48px" class="text-grey-5" />
        <h3 class="empty-state__title">Belum ada pesanan</h3>
        <p class="empty-state__text">
          Pesan tiket pendakian impianmu sekarang di beranda SUMMIT!
        </p>
        <button class="btn-primary" @click="$router.push('/')">
          Cari Tiket Gunung
        </button>
      </div>

      <!-- Orders List -->
      <div v-else class="orders-list">
        <article
          v-for="order in orders"
          :key="order.id || order.invoice"
          class="order-card"
        >
          <div class="order-card__header">
            <div>
              <span class="order-card__invoice">{{ order.invoice }}</span>
              <span class="order-card__date">{{
                formatDate(order.tanggal_booking || order.created_at)
              }}</span>
            </div>
            <span class="status-pill" :class="`status-pill--${order.status}`">
              {{ formatStatus(order.status) }}
            </span>
          </div>

          <div class="order-card__body">
            <h3 class="order-card__mountain">
              {{
                order.jalur?.gunung?.nama_gunung ||
                order.gunung_nama ||
                'Gunung Indonesia'
              }}
            </h3>
            <p class="order-card__trail">
              <q-icon name="explore" size="14px" />
              {{
                order.jalur?.nama_jalur ||
                order.basecamp?.nama_basecamp ||
                'Jalur Pendakian'
              }}
            </p>
            <div class="order-card__price-row">
              <span class="order-card__label">Total Pembayaran</span>
              <span class="order-card__total"
                >Rp {{ formatNumber(order.total_bayar) }}</span
              >
            </div>
          </div>

          <div class="order-card__actions">
            <!-- Paid / On-going actions -->
            <button
              v-if="['paid', 'on_going', 'completed'].includes(order.status)"
              class="btn-outline btn-outline--sm"
              @click="openChat(order.id)"
            >
              <q-icon name="chat" size="14px" /> Chat Basecamp
            </button>

            <button
              v-if="['paid', 'on_going'].includes(order.status)"
              class="btn-outline btn-outline--sm"
              @click="showQrModal(order)"
            >
              <q-icon name="qr_code" size="14px" /> E-Tiket QR
            </button>

            <button
              v-if="['paid', 'on_going', 'completed'].includes(order.status)"
              class="btn-primary btn-primary--sm"
              @click="openSummitLogbook(order)"
            >
              <q-icon name="photo_camera" size="14px" /> Bukti Summit
            </button>

            <!-- Pending action -->
            <button
              v-if="order.status === 'pending'"
              class="btn-primary btn-primary--sm"
              @click="payNow(order)"
            >
              <q-icon name="payment" size="14px" /> Bayar Sekarang
            </button>
          </div>
        </article>
      </div>
    </main>

    <!-- QR E-Tiket Modal -->
    <q-dialog v-model="qrDialog">
      <q-card class="qr-dialog-card">
        <q-card-section class="text-center">
          <h3 class="qr-dialog-title">E-Tiket Check-In</h3>
          <p class="qr-dialog-subtitle">{{ selectedOrder?.invoice }}</p>

          <div class="qr-box">
            <!-- Simulated QR code display -->
            <div class="qr-placeholder">
              <q-icon name="qr_code_2" size="160px" class="text-dark" />
            </div>
            <p class="qr-caption"
              >Tunjukkan QR ini kepada petugas di basecamp untuk check-in &
              verifikasi identitas.</p
            >
          </div>
        </q-card-section>
        <q-card-actions align="center">
          <q-btn flat label="Tutup" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Summit Logbook Upload Modal -->
    <q-dialog v-model="logbookDialog">
      <q-card class="logbook-dialog-card">
        <q-card-section>
          <h3 class="logbook-title">Klaim E-Sertifikat & Logbook</h3>
          <p class="logbook-subtitle"
            >Unggah foto bukti di puncak untuk divalidasi oleh basecamp.</p
          >

          <div class="logbook-form">
            <label class="form-label">Foto Puncak (Summit Proof)</label>
            <input
              type="file"
              accept="image/*"
              class="form-file-input"
              @change="onFileSelected"
            />

            <label class="form-label q-mt-sm"
              >Catatan Pendakian (Opsional)</label
            >
            <textarea
              v-model="logbookNotes"
              class="form-textarea"
              rows="3"
              placeholder="Ceritakan pengalaman tak terlupakanmu..."
            ></textarea>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Batal" v-close-popup />
          <q-btn
            unelevated
            label="Kirim Bukti"
            color="primary"
            :loading="submittingLogbook"
            @click="submitLogbook"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Bottom navigation -->
    <BottomNav active="pesanan" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import BottomNav from '@/components/BottomNav.vue'
import { getOrders } from '@/api/orders'
import { uploadSummitProof } from '@/api/logbook'
import { createOrGetChatRoom } from '@/api/chat'

const router = useRouter()
const $q = useQuasar()

const loading = ref(true)
const orders = ref([])
const activeTab = ref('')
const qrDialog = ref(false)
const selectedOrder = ref(null)

const logbookDialog = ref(false)
const logbookFile = ref(null)
const logbookNotes = ref('')
const submittingLogbook = ref(false)

const tabs = [
  { key: '', label: 'Semua' },
  { key: 'pending', label: 'Menunggu' },
  { key: 'paid', label: 'Aktif' },
  { key: 'completed', label: 'Selesai' }
]

onMounted(() => {
  fetchOrders()
})

async function fetchOrders() {
  loading.value = true
  try {
    const res = await getOrders(activeTab.value)
    orders.value = res.data?.data ?? res.data ?? []
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal memuat pesanan'
    })
  } finally {
    loading.value = false
  }
}

function selectTab(tabKey) {
  activeTab.value = tabKey
  fetchOrders()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function formatNumber(num) {
  if (!num) return '0'
  return Number(num).toLocaleString('id-ID')
}

function formatStatus(status) {
  const map = {
    pending: 'Menunggu Pembayaran',
    paid: 'Terbayar / Siap Naik',
    on_going: 'Sedang Mendaki',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
    refunded: 'Refund Selesai'
  }
  return map[status] || status
}

function showQrModal(order) {
  selectedOrder.value = order
  qrDialog.value = true
}

function payNow(order) {
  $q.notify({
    type: 'info',
    message: `Membuka pembayaran untuk ${order.invoice}...`
  })
}

async function openChat(pesananId) {
  try {
    const res = await createOrGetChatRoom(pesananId)
    router.push({ path: '/chat', query: { room_id: res.data?.id } })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal membuka chat'
    })
  }
}

function openSummitLogbook(order) {
  selectedOrder.value = order
  logbookFile.value = null
  logbookNotes.value = ''
  logbookDialog.value = true
}

function onFileSelected(e) {
  if (e.target.files && e.target.files[0]) {
    logbookFile.value = e.target.files[0]
  }
}

async function submitLogbook() {
  if (!logbookFile.value) {
    $q.notify({
      type: 'warning',
      message: 'Silakan pilih foto puncak terlebih dahulu.'
    })
    return
  }

  submittingLogbook.value = true
  const formData = new FormData()
  formData.append('foto_summit', logbookFile.value)
  if (logbookNotes.value) formData.append('catatan_pendaki', logbookNotes.value)

  try {
    await uploadSummitProof(selectedOrder.value.invoice, formData)
    $q.notify({
      type: 'positive',
      message: 'Bukti puncak berhasil diunggah! Menunggu verifikasi basecamp.'
    })
    logbookDialog.value = false
    fetchOrders()
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal mengunggah bukti puncak'
    })
  } finally {
    submittingLogbook.value = false
  }
}
</script>

<style lang="scss">
.summit-page {
  min-height: 100vh;
  padding-bottom: 80px;
  background: $summit-background;
}

.summit-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: $primary;
  color: #fff;

  &__title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0;
  }
}

.summit-page__content {
  padding: 16px;
}

.orders-tabs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 8px;
  margin-bottom: 16px;

  &__item {
    padding: 8px 16px;
    border-radius: 9999px;
    border: 1px solid $summit-border;
    background: $summit-surface;
    font-size: 0.8125rem;
    font-weight: 600;
    color: $summit-text-secondary;
    white-space: nowrap;
    cursor: pointer;

    &.is-active {
      background: $primary;
      color: #fff;
      border-color: $primary;
    }
  }
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.order-card {
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  padding: 16px;

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid $summit-surface-container;
  }

  &__invoice {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    color: $summit-text-primary;
  }

  &__date {
    font-size: 0.75rem;
    color: $summit-text-secondary;
  }

  &__mountain {
    font-size: 1rem;
    font-weight: 700;
    color: $summit-text-primary;
    margin: 0 0 4px 0;
  }

  &__trail {
    font-size: 0.8125rem;
    color: $summit-text-secondary;
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0 0 12px 0;
  }

  &__price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__label {
    font-size: 0.75rem;
    color: $summit-text-secondary;
  }

  &__total {
    font-size: 0.9375rem;
    font-weight: 700;
    color: $primary;
    font-variant-numeric: tabular-nums;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid $summit-surface-container;
  }
}

.status-pill {
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 4px 8px;
  border-radius: 9999px;

  &--pending {
    background: #fef3c7;
    color: #92400e;
  }
  &--paid {
    background: #d1fae5;
    color: #065f46;
  }
  &--on_going {
    background: #e0e7ff;
    color: #3730a3;
  }
  &--completed {
    background: #ecfdf5;
    color: #047857;
  }
  &--cancelled {
    background: #fee2e2;
    color: #991b1b;
  }
}

.btn-primary {
  background: $primary;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 16px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;

  &--sm {
    padding: 6px 12px;
    font-size: 0.75rem;
  }
}

.btn-outline {
  background: transparent;
  color: $summit-text-primary;
  border: 1px solid $summit-border;
  border-radius: 8px;
  padding: 10px 16px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;

  &--sm {
    padding: 6px 12px;
    font-size: 0.75rem;
  }
}

.empty-state {
  text-align: center;
  padding: 40px 16px;

  &__title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 12px 0 4px 0;
  }

  &__text {
    font-size: 0.875rem;
    color: $summit-text-secondary;
    margin-bottom: 20px;
  }
}

.qr-dialog-card,
.logbook-dialog-card {
  width: 100%;
  max-width: 360px;
  border-radius: 16px;
}

.qr-box {
  margin: 16px 0;
  padding: 16px;
  background: $summit-surface-container-low;
  border-radius: 12px;
}

.qr-placeholder {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 12px;
}

.qr-caption {
  font-size: 0.75rem;
  color: $summit-text-secondary;
  margin: 0;
}

.form-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: $summit-text-primary;
  margin-bottom: 6px;
}

.form-file-input,
.form-textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid $summit-border;
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.875rem;
}
</style>
