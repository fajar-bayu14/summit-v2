<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraOrdersApi from '@/api/mitraOrders'
import type { MitraPesanan, PesananFilterParams, DetailPesananItem, ItemOperationalStatus } from '@/types/order'
import { StatusBadge } from '@/components/common'
import OrderDetailDrawer from '@/components/mitra/orders/OrderDetailDrawer.vue'
import CheckInScannerModal from '@/components/mitra/orders/CheckInScannerModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Ticket,
  Search,
  RefreshCw,
  QrCode,
  Users,
  Eye,
  CheckCircle2,
  AlertCircle,
  TrendingUp,
  X,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const loading = ref(false)
const errorMessage = ref<string | null>(null)
const orders = ref<MitraPesanan[]>([])
const selectedOrder = ref<MitraPesanan | null>(null)
const isDetailDrawerOpen = ref(false)
const isScannerModalOpen = ref(false)

// Metrics
const metrics = reactive({
  totalOrders: 0,
  totalClimbers: 0,
  totalNetIncome: 0,
})

// Pagination
const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
})

// Filters
const filters = reactive<{
  search: string
  status: string
  tanggal_booking: string
}>({
  search: '',
  status: '',
  tanggal_booking: '',
})

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

const statusTabs = [
  { label: 'Semua Status', value: '' },
  { label: 'Siap Check-In (Lunas)', value: 'paid' },
  { label: 'Sedang Naik', value: 'on_going' },
  { label: 'Selesai', value: 'completed' },
  { label: 'Menunggu Bayar', value: 'pending' },
  { label: 'Dibatalkan', value: 'cancelled' },
]

onMounted(() => {
  fetchOrders()
})

function formatRupiah(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDateIndo(dateStr?: string | null): string {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

async function fetchOrders(page = pagination.currentPage) {
  loading.value = true
  errorMessage.value = null

  try {
    const params: PesananFilterParams = {
      page,
      per_page: pagination.perPage,
      basecamp_id: mitraStore.activeBasecampId ?? undefined,
    }

    if (filters.search.trim()) {
      params.search = filters.search.trim()
    }
    if (filters.status) {
      params.status = filters.status
    }
    if (filters.tanggal_booking) {
      params.tanggal_booking = filters.tanggal_booking
    }

    const res = await mitraOrdersApi.getOrders(params)

    if (Array.isArray(res.data)) {
      orders.value = res.data
      pagination.total = res.data.length
      pagination.currentPage = 1
      pagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      orders.value = res.data.data
      pagination.total = res.data.total ?? res.data.data.length
      pagination.currentPage = res.data.current_page ?? 1
      pagination.lastPage = res.data.last_page ?? 1
      pagination.perPage = res.data.per_page ?? 15
    }

    // Calculate Summary Metrics
    recalculateMetrics(res.meta)
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat daftar pesanan mitra.')
  } finally {
    loading.value = false
  }
}

function recalculateMetrics(meta?: any) {
  if (meta && typeof meta.total_pendapatan_bersih === 'number') {
    metrics.totalNetIncome = meta.total_pendapatan_bersih
    metrics.totalOrders = meta.total_transaksi_paid ?? pagination.total
  } else {
    // Fallback compute from loaded list
    let sumIncome = 0
    let climbers = 0
    orders.value.forEach((o) => {
      if (o.status === 'paid' || o.status === 'on_going' || o.status === 'completed') {
        sumIncome += Number(o.pendapatan_mitra || (o.total_bayar * 0.95))
        climbers += (o.anggotas?.length ?? 0) + 1
      }
    })
    metrics.totalNetIncome = sumIncome
    metrics.totalOrders = orders.value.length
    metrics.totalClimbers = climbers
  }
}

function handleSearchInput() {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1
    fetchOrders(1)
  }, 400)
}

function handleStatusFilter(statusVal: string) {
  filters.status = statusVal
  pagination.currentPage = 1
  fetchOrders(1)
}

function handleDateFilter() {
  pagination.currentPage = 1
  fetchOrders(1)
}

function resetFilters() {
  filters.search = ''
  filters.status = ''
  filters.tanggal_booking = ''
  pagination.currentPage = 1
  fetchOrders(1)
}

function openDetail(order: MitraPesanan) {
  selectedOrder.value = order
  isDetailDrawerOpen.value = true
}

function openScanner() {
  isScannerModalOpen.value = true
}

async function handleCheckInOrder(order: MitraPesanan) {
  try {
    await mitraOrdersApi.checkInOrder(order.id)
    // Update local state
    const target = orders.value.find((o) => o.id === order.id)
    if (target) {
      target.status = 'on_going'
    }
    if (selectedOrder.value && selectedOrder.value.id === order.id) {
      selectedOrder.value.status = 'on_going'
    }
    // Refresh to update stats
    fetchOrders()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memproses check-in rombongan.')
  }
}

async function handleCheckOutOrder(order: MitraPesanan) {
  if (
    !confirm(
      `Apakah Anda yakin ingin menyelesaikan pendakian (Check-Out) untuk invoice ${order.invoice}? Tindakan ini akan menyelesaikan status pesanan dan mencairkan dana escrow ke saldo aktif mitra.`
    )
  ) {
    return
  }

  try {
    await mitraOrdersApi.checkOutOrder(order.id)
    const target = orders.value.find((o) => o.id === order.id)
    if (target) {
      target.status = 'completed'
      target.status_escrow = 'released'
    }
    if (selectedOrder.value && selectedOrder.value.id === order.id) {
      selectedOrder.value.status = 'completed'
      selectedOrder.value.status_escrow = 'released'
    }
    await fetchOrders()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memproses check-out pendakian.')
  }
}

function handleItemStatusUpdated(payload: { item: DetailPesananItem; status: ItemOperationalStatus }) {
  if (selectedOrder.value?.details) {
    const itm = selectedOrder.value.details.find((d) => d.id === payload.item.id)
    if (itm) {
      itm.status_operasional = payload.status
    }
  }
}

function handleCheckedInFromScanner(_order?: MitraPesanan) {
  fetchOrders()
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <Ticket class="w-7 h-7 text-[#1E3A2B]" />
          Manajemen Pesanan & Check-In
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Pantau pesanan tiket & rental pendaki, serta validasi check-in rombongan di basecamp
          <span v-if="mitraStore.activeBasecamp" class="font-semibold text-stone-700">
            ({{ mitraStore.activeBasecamp.nama_basecamp }})
          </span>.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          :disabled="loading"
          @click="fetchOrders()"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
          Refresh
        </Button>

        <Button
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px] shadow-sm font-bold"
          @click="openScanner"
        >
          <QrCode class="w-4 h-4 mr-2" />
          Scan QR / Check-In
        </Button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="flex items-center gap-3 p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm"
    >
      <AlertCircle class="w-5 h-5 text-red-600 shrink-0" />
      <span class="flex-1">{{ errorMessage }}</span>
      <Button
        variant="ghost"
        size="sm"
        class="text-red-700 hover:bg-red-100"
        @click="errorMessage = null"
      >
        <X class="w-4 h-4" />
      </Button>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#1E3A2B] flex items-center justify-center font-bold shrink-0">
          <Ticket class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Total Pesanan</span>
          <div class="text-xl font-extrabold text-stone-900 mt-0.5">
            {{ metrics.totalOrders }} Transaksi
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold shrink-0">
          <Users class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Total Pendaki</span>
          <div class="text-xl font-extrabold text-stone-900 mt-0.5">
            {{ metrics.totalClimbers || pagination.total }} Orang
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold shrink-0">
          <TrendingUp class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Estimasi Pendapatan Bersih</span>
          <div class="text-xl font-extrabold text-[#1E3A2B] mt-0.5">
            {{ formatRupiah(metrics.totalNetIncome) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs space-y-3">
      <!-- Status Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <button
          v-for="tab in statusTabs"
          :key="tab.value"
          type="button"
          :class="[
            'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer whitespace-nowrap min-h-[36px]',
            filters.status === tab.value
              ? 'bg-[#1E3A2B] text-white shadow-xs'
              : 'bg-stone-100 text-stone-600 hover:bg-stone-200/80',
          ]"
          @click="handleStatusFilter(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search & Date Filter Bar -->
      <div class="flex flex-col sm:flex-row items-center gap-3 pt-2 border-t border-stone-100">
        <div class="relative flex-1 w-full">
          <Search class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <Input
            v-model="filters.search"
            type="text"
            placeholder="Cari nomor invoice atau nama ketua rombongan..."
            class="pl-9 h-10 rounded-xl text-xs sm:text-sm border-stone-200"
            @input="handleSearchInput"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <div class="relative flex-1 sm:w-44">
            <Input
              v-model="filters.tanggal_booking"
              type="date"
              class="h-10 rounded-xl text-xs border-stone-200"
              @change="handleDateFilter"
            />
          </div>

          <Button
            v-if="filters.search || filters.status || filters.tanggal_booking"
            variant="ghost"
            class="h-10 px-3 rounded-xl text-xs text-stone-600 hover:bg-stone-100 shrink-0"
            @click="resetFilters"
          >
            <X class="w-4 h-4 mr-1" />
            Reset
          </Button>
        </div>
      </div>
    </div>

    <!-- Orders Table Section -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
      <!-- Loading Skeleton -->
      <div v-if="loading" class="p-8 space-y-4">
        <div v-for="i in 5" :key="i" class="h-14 bg-stone-100 rounded-xl animate-pulse" />
      </div>

      <!-- Empty State -->
      <div
        v-else-if="orders.length === 0"
        class="py-16 text-center px-4 space-y-3"
      >
        <div class="w-16 h-16 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
          <Ticket class="w-8 h-8" />
        </div>
        <h3 class="text-base font-bold text-stone-800">Tidak ada data pesanan</h3>
        <p class="text-xs text-stone-500 max-w-sm mx-auto">
          Belum ada pesanan yang sesuai dengan filter yang Anda pilih. Coba sesuaikan kata kunci pencarian atau tanggal.
        </p>
        <Button
          v-if="filters.search || filters.status || filters.tanggal_booking"
          variant="outline"
          size="sm"
          class="rounded-xl border-stone-200"
          @click="resetFilters"
        >
          Reset Filter
        </Button>
      </div>

      <!-- Table View -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
            <tr>
              <th class="p-3.5">Invoice & Waktu</th>
              <th class="p-3.5">Ketua Rombongan</th>
              <th class="p-3.5">Tgl Booking / Naik</th>
              <th class="p-3.5">Jumlah Pendaki</th>
              <th class="p-3.5">Total Bayar</th>
              <th class="p-3.5 text-center">Status</th>
              <th class="p-3.5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-200">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-stone-50/70 transition-colors"
            >
              <!-- Invoice -->
              <td class="p-3.5">
                <div class="font-bold text-stone-900">{{ order.invoice }}</div>
                <div class="text-[11px] text-stone-400">
                  {{ formatDateIndo(order.created_at || order.tanggal_booking) }}
                </div>
              </td>

              <!-- Ketua Rombongan -->
              <td class="p-3.5">
                <div class="font-bold text-stone-900">{{ order.user?.name || '-' }}</div>
                <div class="text-[11px] text-stone-500">{{ order.user?.telepon || order.user?.email || '-' }}</div>
              </td>

              <!-- Tanggal Booking / Naik -->
              <td class="p-3.5">
                <div class="font-semibold text-stone-800">
                  {{ formatDateIndo(order.tanggal_pendakian || order.tanggal_booking) }}
                </div>
                <div class="text-[10px] text-stone-400">
                  Jalur: {{ order.jalur?.nama_jalur || 'Utama' }}
                </div>
              </td>

              <!-- Jumlah Pendaki & Items -->
              <td class="p-3.5">
                <div class="font-bold text-stone-900">
                  {{ (order.anggotas?.length ?? 0) + 1 }} Orang
                </div>
                <div class="text-[11px] text-stone-500">
                  {{ order.details?.length ?? 0 }} Item layanan
                </div>
              </td>

              <!-- Total Bayar -->
              <td class="p-3.5">
                <div class="font-bold text-stone-900">{{ formatRupiah(order.total_bayar) }}</div>
                <div v-if="order.pendapatan_mitra" class="text-[10px] text-emerald-700 font-semibold">
                  Net: {{ formatRupiah(order.pendapatan_mitra) }}
                </div>
              </td>

              <!-- Status Badge -->
              <td class="p-3.5 text-center">
                <StatusBadge :status="order.status" />
              </td>

              <!-- Action Buttons -->
              <td class="p-3.5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <Button
                    v-if="order.status === 'paid'"
                    size="sm"
                    class="h-8 px-2.5 rounded-lg bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white text-xs font-semibold"
                    @click="handleCheckInOrder(order)"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5 mr-1" />
                    Check-In
                  </Button>

                  <Button
                    v-if="order.status === 'on_going'"
                    size="sm"
                    class="h-8 px-2.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs"
                    @click="handleCheckOutOrder(order)"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5 mr-1" />
                    Check-Out
                  </Button>

                  <Button
                    variant="outline"
                    size="sm"
                    class="h-8 px-2.5 rounded-lg border-stone-200 text-stone-700 hover:bg-stone-100 text-xs font-semibold"
                    @click="openDetail(order)"
                  >
                    <Eye class="w-3.5 h-3.5 mr-1" />
                    Detail
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div
        v-if="pagination.total > 0"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-t border-stone-200 text-xs text-stone-500"
      >
        <div>
          Menampilkan <strong class="text-stone-800">{{ orders.length }}</strong> dari <strong class="text-stone-800">{{ pagination.total }}</strong> pesanan
        </div>

        <div class="flex items-center gap-1.5">
          <Button
            variant="outline"
            size="sm"
            :disabled="pagination.currentPage <= 1 || loading"
            class="h-8 rounded-lg border-stone-200"
            @click="fetchOrders(pagination.currentPage - 1)"
          >
            <ChevronLeft class="w-4 h-4" />
          </Button>

          <span class="px-2 font-bold text-stone-700">
            Hal {{ pagination.currentPage }} / {{ pagination.lastPage || 1 }}
          </span>

          <Button
            variant="outline"
            size="sm"
            :disabled="pagination.currentPage >= pagination.lastPage || loading"
            class="h-8 rounded-lg border-stone-200"
            @click="fetchOrders(pagination.currentPage + 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Order Detail Drawer Modal -->
    <OrderDetailDrawer
      v-model:is-open="isDetailDrawerOpen"
      :order="selectedOrder"
      @check-in="handleCheckInOrder"
      @check-out="handleCheckOutOrder"
      @item-status-updated="handleItemStatusUpdated"
    />

    <!-- Check-In Scanner Modal -->
    <CheckInScannerModal
      v-model:is-open="isScannerModalOpen"
      @checked-in="handleCheckedInFromScanner"
    />
  </div>
</template>
