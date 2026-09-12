<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  Search,
  Calendar,
  Building2,
  Ticket,
  CreditCard,
  QrCode,
  Award,
  Loader2,
  PackageOpen,
  ArrowRight,
  AlertCircle,
  CheckCircle2,
  FileText,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import PaymentModal from '@/components/pendaki/PaymentModal.vue'
import OrderDetailModal from '@/components/pendaki/OrderDetailModal.vue'
import { formatRupiah, formatDateIndonesia, formatOrderStatus } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { PesananDetail } from '@/types/pendakiOrder'
import type { Pesanan } from '@/types/pendakiCheckout'

const router = useRouter()
const route = useRoute()

const orders = ref<PesananDetail[]>([])
const isLoading = ref(false)
const errorMessage = ref('')
const activeTab = ref<string>('all')
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)

// Payment modal state for pending orders
const isPaymentModalOpen = ref(false)
const selectedOrderForPayment = ref<Pesanan | null>(null)

// Order detail modal state
const isDetailModalOpen = ref(false)
const selectedOrderForDetail = ref<PesananDetail | null>(null)

const statusTabs = [
  { key: 'all', label: 'Semua' },
  { key: 'pending', label: 'Menunggu Bayar' },
  { key: 'paid', label: 'Sudah Bayar / Siap' },
  { key: 'on_going', label: 'Sedang Berjalan' },
  { key: 'completed', label: 'Selesai' },
  { key: 'expired', label: 'Kadaluarsa' },
  { key: 'cancelled', label: 'Dibatalkan' },
]

async function fetchOrders() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const params: { status?: string; page?: number } = {
      page: currentPage.value,
    }
    if (activeTab.value !== 'all') {
      params.status = activeTab.value
    }

    const response = await pendakiOrdersApi.getMyOrders(params)
    orders.value = response.data || []
    if (response.meta) {
      currentPage.value = response.meta.current_page
      totalPages.value = response.meta.last_page
    }
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

const filteredOrders = computed(() => {
  if (!searchQuery.value.trim()) return orders.value
  const query = searchQuery.value.toLowerCase().trim()

  return orders.value.filter(o => {
    const invMatch = o.invoice.toLowerCase().includes(query)
    const bcMatch = o.basecamp?.nama_basecamp.toLowerCase().includes(query)
    const mountainMatch = o.jalur?.nama_jalur.toLowerCase().includes(query)
    return invMatch || bcMatch || mountainMatch
  })
})

function handlePayOrder(order: PesananDetail) {
  selectedOrderForPayment.value = order as unknown as Pesanan
  isPaymentModalOpen.value = true
}

function handleViewTicket(order: PesananDetail) {
  router.push(`/pendaki/orders/${encodeURIComponent(order.invoice)}/ticket`)
}

function handleViewCertificate(order: PesananDetail) {
  router.push(`/pendaki/certificate/${encodeURIComponent(order.invoice)}`)
}

function handleViewLogbook(order: PesananDetail) {
  router.push(`/pendaki/logbook/${encodeURIComponent(order.invoice)}`)
}

async function handleCancelOrder(order: PesananDetail) {
  if (!confirm(`Apakah Anda yakin ingin membatalkan pesanan ${order.invoice}?`)) return

  try {
    await pendakiOrdersApi.cancelOrder(order.invoice)
    await fetchOrders()
  } catch (err: unknown) {
    alert(extractApiError(err).message)
  }
}

function handlePaymentSuccess() {
  isPaymentModalOpen.value = false
  fetchOrders()
}

function handleOpenDetail(order: PesananDetail) {
  selectedOrderForDetail.value = order
  isDetailModalOpen.value = true
}

function handlePayFromDetail(order: PesananDetail) {
  isDetailModalOpen.value = false
  handlePayOrder(order)
}

watch(activeTab, () => {
  currentPage.value = 1
  fetchOrders()
})

onMounted(async () => {
  await fetchOrders()
  if (route.query.invoice) {
    const match = orders.value.find(o => o.invoice === route.query.invoice)
    if (match) {
      handleOpenDetail(match)
    }
  }
})
</script>

<template>
  <div class="space-y-8 max-w-5xl mx-auto pb-20">
    <!-- Success Banner from Checkout -->
    <div
      v-if="route.query.success === 'true'"
      class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-200 flex items-center justify-between gap-3 shadow-xs animate-in fade-in-50 slide-in-from-top-3"
    >
      <div class="flex items-center gap-2.5">
        <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
        <div>
          <p class="font-bold">Pemesanan Berhasil Dibuat!</p>
          <p class="text-[11px] opacity-90">
            Invoice: <strong>{{ route.query.invoice }}</strong>. Anda dapat melihat rincian pembayaran atau langsung membayar tagihan di bawah.
          </p>
        </div>
      </div>
    </div>

    <!-- Header Title -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-slate-100 font-display">
        Riwayat Booking &amp; Pesanan Tiket
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        Pantau status verifikasi izin SIMAKSI, barcode check-in basecamp, dan pembayaran pesanan pendakian Anda.
      </p>
    </div>

    <!-- Status Filter Tabs & Search Bar -->
    <div class="space-y-4">
      <!-- Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-slate-200 dark:border-slate-800">
        <button
          v-for="tab in statusTabs"
          :key="tab.key"
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200 shrink-0"
          :class="
            activeTab === tab.key
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
          "
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative max-w-md">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
          <Search class="w-4 h-4" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor invoice, basecamp, atau jalur..."
          class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="py-16 text-center space-y-3">
      <Loader2 class="w-8 h-8 animate-spin text-emerald-600 mx-auto" />
      <p class="text-xs text-slate-500">Memuat riwayat pesanan...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="errorMessage"
      class="p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2"
    >
      <AlertCircle class="w-4 h-4 shrink-0" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="filteredOrders.length === 0"
      class="p-16 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4"
    >
      <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
        <PackageOpen class="w-8 h-8" />
      </div>
      <div class="space-y-1 max-w-sm mx-auto">
        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Belum Ada Pesanan</h3>
        <p class="text-xs text-slate-500">
          {{
            activeTab === 'all'
              ? 'Anda belum pernah melakukan booking tiket pendakian.'
              : `Tidak ada pesanan dengan status '${activeTab}'.`
          }}
        </p>
      </div>
      <router-link to="/pendaki/gunung">
        <Button class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-2 shadow-xs">
          <span>Eksplorasi Gunung Sekarang</span>
          <ArrowRight class="w-4 h-4" />
        </Button>
      </router-link>
    </div>

    <!-- Orders Card List -->
    <div v-else class="space-y-4">
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200"
      >
        <!-- Card Header -->
        <div class="p-4 sm:p-5 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="font-mono font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
              {{ order.invoice }}
            </span>
            <span class="text-xs text-slate-400">|</span>
            <span class="text-xs text-slate-500">
              {{ formatDateIndonesia(order.created_at) }}
            </span>
          </div>

          <div>
            <span
              class="px-2.5 py-1 rounded-full text-[11px] font-bold"
              :class="[formatOrderStatus(order.status).bgClass, formatOrderStatus(order.status).textClass]"
            >
              {{ formatOrderStatus(order.status).label }}
            </span>
          </div>
        </div>

        <!-- Card Body -->
        <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
          <!-- Destination Info (2 Cols) -->
          <div class="md:col-span-2 space-y-3">
            <div>
              <div class="flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                <Building2 class="w-3.5 h-3.5" />
                <span>{{ order.basecamp?.nama_basecamp || 'Basecamp Pengelola' }}</span>
              </div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 dark:text-slate-100 mt-0.5">
                {{ order.jalur?.nama_jalur || 'Jalur Pendakian Resmi' }}
              </h3>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-emerald-600" />
                Tanggal Naik: {{ formatDateIndonesia(order.tanggal_booking) }}
              </span>
              <span v-if="order.anggotas && order.anggotas.length > 0" class="flex items-center gap-1.5">
                <Ticket class="w-3.5 h-3.5 text-amber-600" />
                Rombongan: {{ order.anggotas.length }} Orang
              </span>
            </div>

            <!-- Items summary chips -->
            <div v-if="order.details && order.details.length > 0" class="flex flex-wrap gap-1.5 pt-1">
              <span
                v-for="detail in order.details.slice(0, 3)"
                :key="detail.id"
                class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px] text-slate-600 dark:text-slate-400 font-medium"
              >
                {{ detail.produk?.nama_produk || 'Item' }} x{{ detail.qty }}
              </span>
              <span
                v-if="order.details.length > 3"
                class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px] text-slate-500"
              >
                +{{ order.details.length - 3 }} lainnya
              </span>
            </div>
          </div>

          <!-- Total & Actions (1 Col) -->
          <div class="md:border-l md:border-slate-100 dark:md:border-slate-800 md:pl-6 space-y-3">
            <div>
              <span class="text-xs text-slate-500 block">Total Tagihan:</span>
              <span class="text-lg font-black text-emerald-700 dark:text-emerald-400 font-display">
                {{ formatRupiah(order.total_bayar) }}
              </span>
            </div>

            <div class="space-y-2">
              <!-- Detail & Payment Audit Button -->
              <Button
                variant="outline"
                size="sm"
                class="w-full rounded-xl text-xs font-bold gap-1.5 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200"
                @click="handleOpenDetail(order)"
              >
                <FileText class="w-3.5 h-3.5 text-slate-500" />
                <span>Detail &amp; Pembayaran</span>
              </Button>

              <!-- Pending status actions -->
              <template v-if="order.status === 'pending'">
                <Button
                  class="w-full rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold gap-1.5 shadow-xs"
                  @click="handlePayOrder(order)"
                >
                  <CreditCard class="w-3.5 h-3.5" />
                  <span>Bayar Sekarang</span>
                </Button>
                <button
                  type="button"
                  class="w-full text-center text-xs font-semibold text-rose-600 hover:text-rose-700 transition-colors py-1"
                  @click="handleCancelOrder(order)"
                >
                  Batalkan Pesanan
                </button>
              </template>

              <!-- Paid / On-going status actions -->
              <template v-else-if="order.status === 'paid' || order.status === 'confirmed' || order.status === 'active' || order.status === 'on_going'">
                <Button
                  class="w-full rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-1.5 shadow-xs"
                  @click="handleViewTicket(order)"
                >
                  <QrCode class="w-3.5 h-3.5" />
                  <span>Lihat E-Tiket &amp; QR</span>
                </Button>

                <Button
                  v-if="order.status === 'on_going'"
                  variant="outline"
                  class="w-full rounded-xl text-xs font-bold gap-1.5 border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                  @click="handleViewLogbook(order)"
                >
                  <Award class="w-3.5 h-3.5" />
                  <span>Isi Logbook Puncak</span>
                </Button>
              </template>

              <!-- Completed status actions -->
              <template v-else-if="order.status === 'completed'">
                <Button
                  class="w-full rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-xs font-bold gap-1.5 shadow-xs"
                  @click="handleViewCertificate(order)"
                >
                  <Award class="w-3.5 h-3.5" />
                  <span>E-Sertifikat Summit</span>
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  class="w-full text-xs text-slate-600 hover:text-emerald-700 rounded-xl"
                  @click="handleViewTicket(order)"
                >
                  Rincian SIMAKSI
                </Button>
              </template>

              <!-- Cancelled / Expired -->
              <template v-else>
                <Button
                  variant="outline"
                  size="sm"
                  class="w-full text-xs text-slate-500 rounded-xl"
                  disabled
                >
                  Pesanan Ditutup
                </Button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Gateway Modal -->
    <PaymentModal
      v-model:is-open="isPaymentModalOpen"
      :order="selectedOrderForPayment"
      @payment-success="handlePaymentSuccess"
    />

    <!-- Order Detail & Payment Audit Modal -->
    <OrderDetailModal
      v-model:is-open="isDetailModalOpen"
      :order="selectedOrderForDetail"
      @pay-order="handlePayFromDetail"
      @order-updated="fetchOrders"
    />
  </div>
</template>
