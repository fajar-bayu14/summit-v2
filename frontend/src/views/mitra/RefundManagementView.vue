<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraRefundsApi from '@/api/mitraRefunds'
import type { RefundItem, RefundStatus, MitraRefundFilterParams } from '@/types/refund'
import { StatusBadge } from '@/components/common'
import RefundReviewModal from '@/components/mitra/refunds/RefundReviewModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  RotateCcw,
  Search,
  RefreshCw,
  Clock,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  AlertCircle,
  Eye,
  X,
  ChevronLeft,
  ChevronRight,
  DollarSign,
  Building2,
  Calendar,
} from 'lucide-vue-next'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const loading = ref(false)
const errorMessage = ref<string | null>(null)
const refunds = ref<RefundItem[]>([])
const selectedRefund = ref<RefundItem | null>(null)
const isModalOpen = ref(false)

// Metrics
const metrics = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
  disputed: 0,
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
  status: RefundStatus | ''
}>({
  search: '',
  status: '',
})

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

const statusTabs: { label: string; value: RefundStatus | '' }[] = [
  { label: 'Semua Pengajuan', value: '' },
  { label: 'Menunggu Review', value: 'pending' },
  { label: 'Disetujui Mitra', value: 'approved_by_mitra' },
  { label: 'Ditolak Mitra', value: 'rejected_by_mitra' },
  { label: 'Sengketa Admin', value: 'disputed' },
  { label: 'Selesai / Ditransfer', value: 'success' },
]

onMounted(() => {
  fetchRefunds()
})

function formatRupiah(amount?: number | null): string {
  if (typeof amount !== 'number') return 'Rp 0'
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

async function fetchRefunds(page = pagination.currentPage) {
  loading.value = true
  errorMessage.value = null

  try {
    const params: MitraRefundFilterParams = {
      page,
      per_page: pagination.perPage,
    }

    if (filters.status) {
      params.status = filters.status
    }
    if (filters.search.trim()) {
      params.search = filters.search.trim()
    }

    const res = await mitraRefundsApi.getRefunds(params)

    if (Array.isArray(res.data)) {
      refunds.value = res.data
      pagination.total = res.data.length
      pagination.currentPage = 1
      pagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      refunds.value = res.data.data
      pagination.total = res.data.total ?? res.data.data.length
      pagination.currentPage = res.data.current_page ?? 1
      pagination.lastPage = res.data.last_page ?? 1
      pagination.perPage = res.data.per_page ?? 15
    }

    recalculateMetrics()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat daftar permohonan refund.')
  } finally {
    loading.value = false
  }
}

function recalculateMetrics() {
  let pendingCount = 0
  let approvedCount = 0
  let rejectedCount = 0
  let disputedCount = 0

  refunds.value.forEach((r) => {
    if (r.status === 'pending') pendingCount++
    else if (r.status === 'approved_by_mitra' || r.status === 'success') approvedCount++
    else if (r.status === 'rejected_by_mitra') rejectedCount++
    else if (r.status === 'disputed') disputedCount++
  })

  metrics.pending = pendingCount
  metrics.approved = approvedCount
  metrics.rejected = rejectedCount
  metrics.disputed = disputedCount
}

function handleSearchInput() {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1
    fetchRefunds(1)
  }, 400)
}

function handleStatusFilter(statusVal: RefundStatus | '') {
  filters.status = statusVal
  pagination.currentPage = 1
  fetchRefunds(1)
}

function resetFilters() {
  filters.search = ''
  filters.status = ''
  pagination.currentPage = 1
  fetchRefunds(1)
}

function openReviewModal(refund: RefundItem) {
  selectedRefund.value = refund
  isModalOpen.value = true
}

function handleRefundReviewed(updated: RefundItem) {
  const idx = refunds.value.findIndex((r) => r.id === updated.id)
  if (idx !== -1) {
    refunds.value[idx] = updated
  }
  recalculateMetrics()
  fetchRefunds()
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <RotateCcw class="w-7 h-7 text-[#1E3A2B]" />
          Klaim & Permohonan Refund Pendaki
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Review pengajuan pembatalan & pengembalian dana tiket pendaki (Tier-1 Review Basecamp)
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
          @click="fetchRefunds()"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
          Refresh
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

    <!-- KPI Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
          <Clock class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Menunggu Review</span>
          <div class="text-xl font-extrabold text-amber-700 mt-0.5">
            {{ metrics.pending }} Pengajuan
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#1E3A2B] flex items-center justify-center font-bold shrink-0">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Disetujui Mitra</span>
          <div class="text-xl font-extrabold text-[#1E3A2B] mt-0.5">
            {{ metrics.approved }} Tiket
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold shrink-0">
          <XCircle class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Ditolak Mitra</span>
          <div class="text-xl font-extrabold text-red-700 mt-0.5">
            {{ metrics.rejected }} Pengajuan
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-orange-50 text-[#E65100] flex items-center justify-center font-bold shrink-0">
          <AlertTriangle class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Sengketa / Banding</span>
          <div class="text-xl font-extrabold text-[#E65100] mt-0.5">
            {{ metrics.disputed }} Kasus
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
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

      <!-- Search Query Bar -->
      <div class="flex items-center gap-3 pt-2 border-t border-stone-100">
        <div class="relative flex-1 w-full">
          <Search class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <Input
            v-model="filters.search"
            type="text"
            placeholder="Cari nomor invoice, nama pemesan, atau nama bank..."
            class="pl-9 h-10 rounded-xl text-xs sm:text-sm border-stone-200"
            @input="handleSearchInput"
          />
        </div>

        <Button
          v-if="filters.search || filters.status"
          variant="ghost"
          class="h-10 px-3 rounded-xl text-xs text-stone-600 hover:bg-stone-100 shrink-0"
          @click="resetFilters"
        >
          <X class="w-4 h-4 mr-1" />
          Reset
        </Button>
      </div>
    </div>

    <!-- Table Content Section -->
    <div class="space-y-4">
      <!-- Loading Skeleton -->
      <div v-if="loading" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="h-12 bg-stone-100 rounded-xl animate-pulse" />
      </div>

      <!-- Empty State -->
      <div
        v-else-if="refunds.length === 0"
        class="bg-white rounded-2xl border border-stone-200 p-12 text-center space-y-3 shadow-xs"
      >
        <div class="w-16 h-16 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
          <RotateCcw class="w-8 h-8" />
        </div>
        <h3 class="text-base font-bold text-stone-800">Tidak ada pengajuan refund</h3>
        <p class="text-xs text-stone-500 max-w-sm mx-auto">
          Belum ada data permohonan refund atau pembatalan tiket pendaki untuk filter status ini.
        </p>
        <Button
          v-if="filters.search || filters.status"
          variant="outline"
          size="sm"
          class="rounded-xl border-stone-200"
          @click="resetFilters"
        >
          Reset Filter
        </Button>
      </div>

      <!-- Table View -->
      <div v-else class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
              <tr>
                <th class="p-3.5">Invoice & Jadwal</th>
                <th class="p-3.5">Pemesan / Pendaki</th>
                <th class="p-3.5">Nominal Refund</th>
                <th class="p-3.5">Rekening Pengembalian</th>
                <th class="p-3.5">Alasan Pendaki</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-200">
              <tr
                v-for="item in refunds"
                :key="item.id"
                class="hover:bg-stone-50/70 transition-colors"
              >
                <!-- Invoice & Jadwal -->
                <td class="p-3.5">
                  <div class="font-bold text-stone-900">
                    {{ item.pesanan_invoice || item.pesanan?.invoice || '-' }}
                  </div>
                  <div class="flex items-center gap-1 text-[11px] text-stone-500 mt-0.5">
                    <Calendar class="w-3 h-3 text-stone-400" />
                    <span>Jadwal: {{ formatDateIndo(item.pesanan?.tanggal_pendakian || item.pesanan?.tanggal_booking) }}</span>
                  </div>
                </td>

                <!-- Pendaki -->
                <td class="p-3.5">
                  <div class="font-bold text-stone-900">
                    {{ item.pesanan?.user?.name || item.nama_tujuan || '-' }}
                  </div>
                  <div class="text-[11px] text-stone-400">
                    {{ item.pesanan?.user?.telepon || item.pesanan?.user?.email || '-' }}
                  </div>
                </td>

                <!-- Nominal -->
                <td class="p-3.5">
                  <div class="font-extrabold text-red-700 text-sm flex items-center gap-0.5">
                    <DollarSign class="w-3.5 h-3.5" />
                    {{ formatRupiah(item.nominal) }}
                  </div>
                  <div v-if="item.nominal_disetujui" class="text-[10px] text-emerald-700 font-medium">
                    Disetujui: {{ formatRupiah(item.nominal_disetujui) }}
                  </div>
                </td>

                <!-- Rekening -->
                <td class="p-3.5">
                  <div class="flex items-center gap-1 font-semibold text-stone-800">
                    <Building2 class="w-3.5 h-3.5 text-stone-400 shrink-0" />
                    <span>{{ item.bank_tujuan || 'Bank' }} • {{ item.rekening_tujuan || '-' }}</span>
                  </div>
                  <div class="text-[10px] text-stone-500">
                    a.n {{ item.nama_tujuan || '-' }}
                  </div>
                </td>

                <!-- Alasan -->
                <td class="p-3.5 max-w-xs">
                  <p class="italic text-stone-600 line-clamp-2 bg-stone-50 p-2 rounded-lg border border-stone-100">
                    "{{ item.alasan }}"
                  </p>
                </td>

                <!-- Status -->
                <td class="p-3.5 text-center">
                  <StatusBadge :status="item.status" />
                </td>

                <!-- Action Button -->
                <td class="p-3.5 text-right">
                  <Button
                    size="sm"
                    :class="[
                      'h-8 px-3 rounded-lg text-xs font-semibold',
                      item.status === 'pending' || item.status === 'disputed'
                        ? 'bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white shadow-xs'
                        : 'bg-stone-100 hover:bg-stone-200 text-stone-700',
                    ]"
                    @click="openReviewModal(item)"
                  >
                    <Eye class="w-3.5 h-3.5 mr-1" />
                    {{ item.status === 'pending' ? 'Review' : 'Detail' }}
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination Footer -->
      <div
        v-if="pagination.total > 0"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 bg-white rounded-2xl border border-stone-200 text-xs text-stone-500"
      >
        <div>
          Menampilkan <strong class="text-stone-800">{{ refunds.length }}</strong> dari <strong class="text-stone-800">{{ pagination.total }}</strong> permohonan
        </div>

        <div class="flex items-center gap-1.5">
          <Button
            variant="outline"
            size="sm"
            :disabled="pagination.currentPage <= 1 || loading"
            class="h-8 rounded-lg border-stone-200"
            @click="fetchRefunds(pagination.currentPage - 1)"
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
            @click="fetchRefunds(pagination.currentPage + 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Review Modal Component -->
    <RefundReviewModal
      v-model:is-open="isModalOpen"
      :refund="selectedRefund"
      @reviewed="handleRefundReviewed"
    />
  </div>
</template>
