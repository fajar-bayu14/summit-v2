<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraWalletApi from '@/api/mitraWallet'
import type {
  MitraWallet,
  WalletTransaction,
  WithdrawalRequest,
  WalletTransactionType,
  WithdrawalStatus,
  LedgerFilterParams,
  WithdrawalFilterParams,
} from '@/types/wallet'
import { StatusBadge } from '@/components/common'
import WalletBalanceCards from '@/components/mitra/wallet/WalletBalanceCards.vue'
import WithdrawalRequestModal from '@/components/mitra/wallet/WithdrawalRequestModal.vue'
import { Button } from '@/components/ui/button'
import {
  Wallet,
  RefreshCw,
  ArrowUpRight,
  AlertCircle,
  X,
  ChevronLeft,
  ChevronRight,
  History,
} from 'lucide-vue-next'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const loadingWallet = ref(false)
const loadingLedger = ref(false)
const loadingWithdrawals = ref(false)
const errorMessage = ref<string | null>(null)

const wallet = ref<MitraWallet | null>(null)
const transactions = ref<WalletTransaction[]>([])
const withdrawals = ref<WithdrawalRequest[]>([])

const activeTab = ref<'ledger' | 'withdrawals'>('ledger')
const isWithdrawalModalOpen = ref(false)

// Pagination Ledger
const ledgerPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
})

// Pagination Withdrawals
const withdrawalPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
})

// Filters
const ledgerFilterType = ref<WalletTransactionType | ''>('')
const withdrawalFilterStatus = ref<WithdrawalStatus | ''>('')

const ledgerTypeTabs: { label: string; value: WalletTransactionType | '' }[] = [
  { label: 'Semua Mutasi', value: '' },
  { label: 'Escrow Masuk', value: 'inflow_holding' },
  { label: 'Rilis Saldo Tersedia', value: 'release_to_available' },
  { label: 'Kunci Penarikan', value: 'withdrawal_lock' },
  { label: 'Penarikan Selesai', value: 'withdrawal_settled' },
  { label: 'Potongan Refund', value: 'refund_deduction' },
]

const withdrawalStatusTabs: { label: string; value: WithdrawalStatus | '' }[] = [
  { label: 'Semua Status', value: '' },
  { label: 'Menunggu', value: 'pending' },
  { label: 'Diproses', value: 'processing' },
  { label: 'Selesai', value: 'completed' },
  { label: 'Ditolak', value: 'rejected' },
]

onMounted(() => {
  fetchAllData()
})

function formatRupiah(amount?: number): string {
  if (typeof amount !== 'number') return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDateTimeIndo(dateStr?: string | null): string {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

async function fetchWalletSummary() {
  loadingWallet.value = true
  try {
    const res = await mitraWalletApi.getWalletSummary()
    wallet.value = res.data ?? null
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat saldo dompet.')
  } finally {
    loadingWallet.value = false
  }
}

async function fetchLedger(page = ledgerPagination.currentPage) {
  loadingLedger.value = true
  try {
    const params: LedgerFilterParams = {
      page,
      per_page: ledgerPagination.perPage,
    }
    if (ledgerFilterType.value) {
      params.type = ledgerFilterType.value
    }

    const res = await mitraWalletApi.getLedgerTransactions(params)

    if (Array.isArray(res.data)) {
      transactions.value = res.data
      ledgerPagination.total = res.data.length
      ledgerPagination.currentPage = 1
      ledgerPagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      transactions.value = res.data.data
      ledgerPagination.total = res.data.total ?? res.data.data.length
      ledgerPagination.currentPage = res.data.current_page ?? 1
      ledgerPagination.lastPage = res.data.last_page ?? 1
      ledgerPagination.perPage = res.data.per_page ?? 15
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat riwayat mutasi transaksi.')
  } finally {
    loadingLedger.value = false
  }
}

async function fetchWithdrawals(page = withdrawalPagination.currentPage) {
  loadingWithdrawals.value = true
  try {
    const params: WithdrawalFilterParams = {
      page,
      per_page: withdrawalPagination.perPage,
    }
    if (withdrawalFilterStatus.value) {
      params.status = withdrawalFilterStatus.value
    }

    const res = await mitraWalletApi.getWithdrawals(params)

    if (Array.isArray(res.data)) {
      withdrawals.value = res.data
      withdrawalPagination.total = res.data.length
      withdrawalPagination.currentPage = 1
      withdrawalPagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      withdrawals.value = res.data.data
      withdrawalPagination.total = res.data.total ?? res.data.data.length
      withdrawalPagination.currentPage = res.data.current_page ?? 1
      withdrawalPagination.lastPage = res.data.last_page ?? 1
      withdrawalPagination.perPage = res.data.per_page ?? 15
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat riwayat penarikan dana.')
  } finally {
    loadingWithdrawals.value = false
  }
}

function fetchAllData() {
  errorMessage.value = null
  fetchWalletSummary()
  fetchLedger(1)
  fetchWithdrawals(1)
}

function handleLedgerFilter(typeVal: WalletTransactionType | '') {
  ledgerFilterType.value = typeVal
  ledgerPagination.currentPage = 1
  fetchLedger(1)
}

function handleWithdrawalFilter(statusVal: WithdrawalStatus | '') {
  withdrawalFilterStatus.value = statusVal
  withdrawalPagination.currentPage = 1
  fetchWithdrawals(1)
}

function openWithdrawalModal() {
  isWithdrawalModalOpen.value = true
}

function handleWithdrawalSubmitted(_item?: WithdrawalRequest) {
  fetchAllData()
}

function getTransactionBadgeClass(type: WalletTransactionType): string {
  switch (type) {
    case 'inflow_holding':
      return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'release_to_available':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'withdrawal_lock':
      return 'bg-blue-50 text-blue-700 border-blue-200'
    case 'withdrawal_settled':
      return 'bg-purple-50 text-purple-700 border-purple-200'
    case 'refund_deduction':
      return 'bg-red-50 text-red-700 border-red-200'
    default:
      return 'bg-stone-50 text-stone-700 border-stone-200'
  }
}

function getTransactionLabel(type: WalletTransactionType): string {
  switch (type) {
    case 'inflow_holding':
      return 'Escrow Masuk (Holding)'
    case 'release_to_available':
      return 'Rilis ke Saldo Tersedia'
    case 'withdrawal_lock':
      return 'Kunci Payout Penarikan'
    case 'withdrawal_settled':
      return 'Penarikan Selesai (Transfer)'
    case 'withdrawal_refunded':
      return 'Penarikan Dikembalikan'
    case 'refund_deduction':
      return 'Potongan Refund Pendaki'
    default:
      return type
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <Wallet class="w-7 h-7 text-[#1E3A2B]" />
          Dompet Mitra & Penarikan Dana (Payout)
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Pantau saldo escrow, mutasi transaksi buku besar, dan pengajuan pencairan dana ke rekening bank
          <span v-if="mitraStore.activeBasecamp" class="font-semibold text-stone-700">
            ({{ mitraStore.activeBasecamp.nama_basecamp }})
          </span>.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          :disabled="loadingWallet || loadingLedger || loadingWithdrawals"
          @click="fetchAllData()"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', (loadingWallet || loadingLedger || loadingWithdrawals) ? 'animate-spin' : '']" />
          Refresh
        </Button>

        <Button
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px] shadow-sm font-bold"
          :disabled="loadingWallet || (wallet?.saldo_available ?? 0) < 50000"
          @click="openWithdrawalModal"
        >
          <ArrowUpRight class="w-4 h-4 mr-2" />
          Tarik Dana
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

    <!-- Balance Overview Cards -->
    <WalletBalanceCards
      :wallet="wallet"
      :loading="loadingWallet"
      @request-withdrawal="openWithdrawalModal"
    />

    <!-- Dual Tab Main Container -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
      <!-- Tabs Header -->
      <div class="flex items-center justify-between border-b border-stone-200 px-4 pt-3">
        <div class="flex items-center gap-2">
          <button
            type="button"
            :class="[
              'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2',
              activeTab === 'ledger'
                ? 'border-[#1E3A2B] text-[#1E3A2B]'
                : 'border-transparent text-stone-500 hover:text-stone-800',
            ]"
            @click="activeTab = 'ledger'"
          >
            <History class="w-4 h-4" />
            Mutasi Transaksi / Ledger Escrow
          </button>

          <button
            type="button"
            :class="[
              'px-4 py-2.5 text-xs sm:text-sm font-bold border-b-2 transition-all cursor-pointer flex items-center gap-2',
              activeTab === 'withdrawals'
                ? 'border-[#1E3A2B] text-[#1E3A2B]'
                : 'border-transparent text-stone-500 hover:text-stone-800',
            ]"
            @click="activeTab = 'withdrawals'"
          >
            <ArrowUpRight class="w-4 h-4" />
            Riwayat Penarikan Dana (Payout)
          </button>
        </div>
      </div>

      <!-- Tab 1: Ledger Mutations -->
      <div v-if="activeTab === 'ledger'" class="p-4 space-y-4">
        <!-- Filter Bar -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
          <button
            v-for="tab in ledgerTypeTabs"
            :key="tab.value"
            type="button"
            :class="[
              'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer whitespace-nowrap min-h-[36px]',
              ledgerFilterType === tab.value
                ? 'bg-[#1E3A2B] text-white shadow-xs'
                : 'bg-stone-100 text-stone-600 hover:bg-stone-200/80',
            ]"
            @click="handleLedgerFilter(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Ledger Loading Skeleton -->
        <div v-if="loadingLedger" class="space-y-3 py-2">
          <div v-for="i in 5" :key="i" class="h-14 bg-stone-100 rounded-xl animate-pulse" />
        </div>

        <!-- Empty Ledger -->
        <div
          v-else-if="transactions.length === 0"
          class="py-12 text-center px-4 space-y-2"
        >
          <div class="w-14 h-14 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
            <History class="w-7 h-7" />
          </div>
          <h4 class="text-sm font-bold text-stone-800">Belum ada riwayat mutasi transaksi</h4>
          <p class="text-xs text-stone-500 max-w-sm mx-auto">
            Transaksi saldo escrow dan pelepasan pendapatan akan otomatis tercatat di sini saat tiket dipesan atau diselesaikan.
          </p>
        </div>

        <!-- Ledger Table -->
        <div v-else class="border border-stone-200 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
                <tr>
                  <th class="p-3.5">Tanggal & Waktu</th>
                  <th class="p-3.5">Invoice Terkait</th>
                  <th class="p-3.5">Tipe Mutasi</th>
                  <th class="p-3.5 text-right">Nominal</th>
                  <th class="p-3.5 text-right">Saldo Tersedia</th>
                  <th class="p-3.5 text-right">Saldo Pending</th>
                  <th class="p-3.5">Catatan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-stone-200">
                <tr
                  v-for="tx in transactions"
                  :key="tx.id"
                  class="hover:bg-stone-50/70 transition-colors"
                >
                  <td class="p-3.5 text-stone-600 whitespace-nowrap">
                    {{ formatDateTimeIndo(tx.created_at) }}
                  </td>
                  <td class="p-3.5 font-bold text-stone-900">
                    {{ tx.pesanan_invoice || '-' }}
                  </td>
                  <td class="p-3.5">
                    <span
                      class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase border"
                      :class="getTransactionBadgeClass(tx.type)"
                    >
                      {{ getTransactionLabel(tx.type) }}
                    </span>
                  </td>
                  <td class="p-3.5 text-right font-extrabold whitespace-nowrap">
                    <span
                      :class="tx.type === 'release_to_available' || tx.type === 'inflow_holding' ? 'text-emerald-700' : 'text-stone-800'"
                    >
                      {{ tx.type === 'release_to_available' || tx.type === 'inflow_holding' ? '+' : '-' }}
                      {{ formatRupiah(tx.nominal) }}
                    </span>
                  </td>
                  <td class="p-3.5 text-right font-semibold text-stone-900 whitespace-nowrap">
                    {{ formatRupiah(tx.saldo_available_after) }}
                  </td>
                  <td class="p-3.5 text-right font-semibold text-stone-500 whitespace-nowrap">
                    {{ formatRupiah(tx.saldo_pending_after) }}
                  </td>
                  <td class="p-3.5 text-stone-500 max-w-xs truncate">
                    {{ tx.catatan || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Ledger -->
          <div
            v-if="ledgerPagination.total > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-stone-50/60 border-t border-stone-200 text-xs text-stone-500"
          >
            <div>
              Menampilkan <strong class="text-stone-800">{{ transactions.length }}</strong> dari <strong class="text-stone-800">{{ ledgerPagination.total }}</strong> mutasi
            </div>
            <div class="flex items-center gap-1.5">
              <Button
                variant="outline"
                size="sm"
                :disabled="ledgerPagination.currentPage <= 1 || loadingLedger"
                class="h-8 rounded-lg border-stone-200"
                @click="fetchLedger(ledgerPagination.currentPage - 1)"
              >
                <ChevronLeft class="w-4 h-4" />
              </Button>
              <span class="px-2 font-bold text-stone-700">
                Hal {{ ledgerPagination.currentPage }} / {{ ledgerPagination.lastPage || 1 }}
              </span>
              <Button
                variant="outline"
                size="sm"
                :disabled="ledgerPagination.currentPage >= ledgerPagination.lastPage || loadingLedger"
                class="h-8 rounded-lg border-stone-200"
                @click="fetchLedger(ledgerPagination.currentPage + 1)"
              >
                <ChevronRight class="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Withdrawals History -->
      <div v-if="activeTab === 'withdrawals'" class="p-4 space-y-4">
        <!-- Status Filter Bar -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
          <button
            v-for="tab in withdrawalStatusTabs"
            :key="tab.value"
            type="button"
            :class="[
              'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer whitespace-nowrap min-h-[36px]',
              withdrawalFilterStatus === tab.value
                ? 'bg-[#1E3A2B] text-white shadow-xs'
                : 'bg-stone-100 text-stone-600 hover:bg-stone-200/80',
            ]"
            @click="handleWithdrawalFilter(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Withdrawals Loading Skeleton -->
        <div v-if="loadingWithdrawals" class="space-y-3 py-2">
          <div v-for="i in 4" :key="i" class="h-14 bg-stone-100 rounded-xl animate-pulse" />
        </div>

        <!-- Empty Withdrawals -->
        <div
          v-else-if="withdrawals.length === 0"
          class="py-12 text-center px-4 space-y-2"
        >
          <div class="w-14 h-14 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
            <ArrowUpRight class="w-7 h-7" />
          </div>
          <h4 class="text-sm font-bold text-stone-800">Belum ada riwayat penarikan dana</h4>
          <p class="text-xs text-stone-500 max-w-sm mx-auto">
            Ajukan penarikan dana untuk mentransfer saldo tersedia ke rekening bank terdaftar Anda.
          </p>
        </div>

        <!-- Withdrawals Table -->
        <div v-else class="border border-stone-200 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
                <tr>
                  <th class="p-3.5">Tanggal Pengajuan</th>
                  <th class="p-3.5">Rekening Bank Tujuan</th>
                  <th class="p-3.5 text-right">Nominal Payout</th>
                  <th class="p-3.5 text-center">Status</th>
                  <th class="p-3.5">Catatan / Alasan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-stone-200">
                <tr
                  v-for="item in withdrawals"
                  :key="item.id"
                  class="hover:bg-stone-50/70 transition-colors"
                >
                  <td class="p-3.5 text-stone-600 whitespace-nowrap">
                    {{ formatDateTimeIndo(item.created_at) }}
                  </td>
                  <td class="p-3.5">
                    <div class="font-bold text-stone-900">{{ item.bank }} — {{ item.rekening_bank }}</div>
                    <div class="text-[11px] text-stone-500">a.n {{ item.nama_rekening }}</div>
                  </td>
                  <td class="p-3.5 text-right font-extrabold text-[#1E3A2B] whitespace-nowrap">
                    {{ formatRupiah(item.nominal) }}
                  </td>
                  <td class="p-3.5 text-center">
                    <StatusBadge :status="item.status" />
                  </td>
                  <td class="p-3.5 text-stone-500 max-w-xs">
                    <div v-if="item.catatan" class="text-stone-800">{{ item.catatan }}</div>
                    <div v-if="item.alasan_penolakan" class="text-red-600 font-semibold text-[11px] mt-0.5">
                      Ditolak: {{ item.alasan_penolakan }}
                    </div>
                    <div v-if="item.failure_reason" class="text-red-600 font-semibold text-[11px] mt-0.5">
                      Gagal: {{ item.failure_reason }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Withdrawals -->
          <div
            v-if="withdrawalPagination.total > 0"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-stone-50/60 border-t border-stone-200 text-xs text-stone-500"
          >
            <div>
              Menampilkan <strong class="text-stone-800">{{ withdrawals.length }}</strong> dari <strong class="text-stone-800">{{ withdrawalPagination.total }}</strong> pengajuan
            </div>
            <div class="flex items-center gap-1.5">
              <Button
                variant="outline"
                size="sm"
                :disabled="withdrawalPagination.currentPage <= 1 || loadingWithdrawals"
                class="h-8 rounded-lg border-stone-200"
                @click="fetchWithdrawals(withdrawalPagination.currentPage - 1)"
              >
                <ChevronLeft class="w-4 h-4" />
              </Button>
              <span class="px-2 font-bold text-stone-700">
                Hal {{ withdrawalPagination.currentPage }} / {{ withdrawalPagination.lastPage || 1 }}
              </span>
              <Button
                variant="outline"
                size="sm"
                :disabled="withdrawalPagination.currentPage >= withdrawalPagination.lastPage || loadingWithdrawals"
                class="h-8 rounded-lg border-stone-200"
                @click="fetchWithdrawals(withdrawalPagination.currentPage + 1)"
              >
                <ChevronRight class="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Withdrawal Modal -->
    <WithdrawalRequestModal
      v-model:is-open="isWithdrawalModalOpen"
      :wallet="wallet"
      @submitted="handleWithdrawalSubmitted"
    />
  </div>
</template>
