<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { financeService } from '@/api/finance'
import type { WalletTransaction, WalletTransactionType } from '@/types/finance'
import type { PaginationMeta } from '@/types/api'
import { useToast } from '@/composables/useToast'
import {
  Search,
  RefreshCw,
  Building2,
  FileText,
  Lock,
  Unlock,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import PaginationBar from '@/components/common/PaginationBar.vue'
import EmptyState from '@/components/common/EmptyState.vue'

const toast = useToast()

// Data State
const ledgerTransactions = ref<WalletTransaction[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedType = ref<string>('all')

// Pagination State
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(15)

const paginationMeta = computed<PaginationMeta>(() => ({
  current_page: currentPage.value,
  last_page: lastPage.value,
  per_page: perPage.value,
  total: totalItems.value,
  from: totalItems.value > 0 ? (currentPage.value - 1) * perPage.value + 1 : 0,
  to: Math.min(currentPage.value * perPage.value, totalItems.value),
}))

// Aggregate Metrics Computations
const metrics = computed(() => {
  let totalPending = 0
  let totalAvailable = 0
  let totalWithdrawn = 0

  if (ledgerTransactions.value.length > 0) {
    const latestTx = ledgerTransactions.value[0]
    totalPending = Number(latestTx.saldo_pending_after) || 0
    totalAvailable = Number(latestTx.saldo_available_after) || 0
  }

  return {
    totalPending,
    totalAvailable,
    totalWithdrawn,
  }
})

async function fetchLedger(page = 1) {
  isLoading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: perPage.value,
    }
    if (selectedType.value !== 'all') {
      params.type = selectedType.value
    }
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const res = await financeService.getEscrowLedger(params)
    const rawData = res.data?.data || res.data
    ledgerTransactions.value = Array.isArray(rawData) ? rawData : (rawData as any)?.data || []

    const meta = (res.data as any)?.meta || res.data
    if (meta) {
      currentPage.value = meta.current_page || 1
      lastPage.value = meta.last_page || 1
      totalItems.value = meta.total || ledgerTransactions.value.length
    }
  } catch (err: any) {
    toast.error('Gagal Memuat Ledger', err.response?.data?.message || 'Terjadi kesalahan saat memuat mutasi ledger escrow.')
  } finally {
    isLoading.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  fetchLedger(1)
}

function handleTypeFilter(type: string) {
  selectedType.value = type
  currentPage.value = 1
  fetchLedger(1)
}

function formatCurrency(amount: number | string): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num || 0)
}

function formatDate(dateStr?: string | null): string {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(dateStr))
}

function getTypeBadge(type: WalletTransactionType) {
  switch (type) {
    case 'order_holding':
      return { label: 'Inbound Escrow', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', isCredit: true }
    case 'release_order':
      return { label: 'Pelepasan Dana (Completed)', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', isCredit: true }
    case 'withdrawal_lock':
      return { label: 'Kunci Saldo Tarik', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', isCredit: false }
    case 'withdrawal_success':
      return { label: 'Disbursement Sukses', class: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20', isCredit: false }
    case 'withdrawal_refund':
      return { label: 'Pengembalian Batal Tarik', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', isCredit: true }
    case 'refund_deduction':
      return { label: 'Pemotongan Refund/Dispute', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', isCredit: false }
    default:
      return { label: type, class: 'bg-muted text-muted-foreground', isCredit: true }
  }
}

onMounted(() => {
  fetchLedger(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Ledger Escrow & Mutasi Keuangan</h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Audit transparansi akuntansi rekening bersama (Escrow Holding vs Available Balance) seluruh mitra se-Indonesia.
        </p>
      </div>
      <Button
        variant="outline"
        size="sm"
        class="gap-1.5 text-xs h-9"
        :disabled="isLoading"
        @click="fetchLedger(currentPage)"
      >
        <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
        <span>Segarkan Ledger</span>
      </Button>
    </div>

    <!-- 3 Metrics Card (Escrow Status) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Card class="rounded-2xl border shadow-xs bg-slate-900 text-white">
        <CardContent class="p-5 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Saldo Holding (Escrow)</span>
            <span class="text-2xl font-extrabold font-mono text-blue-400 mt-1 block">
              {{ formatCurrency(metrics.totalPending) }}
            </span>
            <span class="text-[11px] text-slate-400 mt-1 block">Tertahan aman hingga pendakian selesai</span>
          </div>
          <div class="h-12 w-12 rounded-2xl bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
            <Lock class="h-6 w-6" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs bg-emerald-900 text-white">
        <CardContent class="p-5 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-300 block">Saldo Siap Cair (Available)</span>
            <span class="text-2xl font-extrabold font-mono text-emerald-400 mt-1 block">
              {{ formatCurrency(metrics.totalAvailable) }}
            </span>
            <span class="text-[11px] text-emerald-300/80 mt-1 block">Hak mitra yang siap diajukan penarikan</span>
          </div>
          <div class="h-12 w-12 rounded-2xl bg-emerald-800 text-emerald-300 flex items-center justify-center shrink-0">
            <Unlock class="h-6 w-6" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-5 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Total Transaksi Mutasi</span>
            <span class="text-2xl font-extrabold font-mono text-foreground mt-1 block">
              {{ totalItems }} Mutasi
            </span>
            <span class="text-[11px] text-muted-foreground mt-1 block">Log akuntansi post-payment tercatat</span>
          </div>
          <div class="h-12 w-12 rounded-2xl bg-muted flex items-center justify-center shrink-0 text-muted-foreground">
            <FileText class="h-6 w-6" />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Filter & Search Toolbar -->
    <Card class="rounded-2xl border shadow-xs">
      <CardContent class="p-4 space-y-3">
        <div class="flex flex-col md:flex-row items-center justify-between gap-3">
          <div class="relative w-full md:w-80">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              placeholder="Cari nomor invoice / nama mitra..."
              class="pl-9 h-9 text-xs"
              @keydown.enter="handleSearch"
            />
          </div>

          <!-- Type Filter Tabs -->
          <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
            <Button
              v-for="type in [
                { key: 'all', label: 'Semua Mutasi' },
                { key: 'order_holding', label: 'Escrow Masuk' },
                { key: 'release_order', label: 'Pelepasan Dana' },
                { key: 'withdrawal_deduction', label: 'Payout Mitra' },
                { key: 'refund_deduction', label: 'Refund/Dispute' },
              ]"
              :key="type.key"
              size="sm"
              variant="ghost"
              class="h-8 text-xs px-3 rounded-lg"
              :class="selectedType === type.key ? 'bg-primary text-primary-foreground font-semibold shadow-xs' : 'text-muted-foreground hover:bg-muted'"
              @click="handleTypeFilter(type.key)"
            >
              {{ type.label }}
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Consolidated Ledger Table -->
    <Card class="rounded-2xl border shadow-xs overflow-hidden">
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="text-xs font-semibold">Waktu Mutasi</TableHead>
                <TableHead class="text-xs font-semibold">Mitra / Dompet</TableHead>
                <TableHead class="text-xs font-semibold">Invoice / Referensi</TableHead>
                <TableHead class="text-xs font-semibold">Jenis Mutasi</TableHead>
                <TableHead class="text-xs font-semibold">Nominal</TableHead>
                <TableHead class="text-xs font-semibold">Saldo Pending Sisa</TableHead>
                <TableHead class="text-xs font-semibold">Saldo Available Sisa</TableHead>
                <TableHead class="text-xs font-semibold">Catatan</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="isLoading">
                <TableRow v-for="i in 5" :key="i">
                  <TableCell colspan="8" class="h-14 text-center">
                    <div class="animate-pulse flex items-center justify-center gap-2 text-muted-foreground text-xs">
                      <div class="h-4 w-4 bg-muted rounded-full" />
                      <span>Memuat catatan mutasi ledger...</span>
                    </div>
                  </TableCell>
                </TableRow>
              </template>

              <template v-else-if="ledgerTransactions.length === 0">
                <TableRow>
                  <TableCell colspan="8" class="py-12">
                    <EmptyState
                      title="Tidak Ada Riwayat Mutasi"
                      description="Belum ada transaksi ledger escrow yang tercatat sesuai filter saat ini."
                    />
                  </TableCell>
                </TableRow>
              </template>

              <template v-else>
                <TableRow
                  v-for="item in ledgerTransactions"
                  :key="item.id"
                  class="hover:bg-muted/30 transition text-xs"
                >
                  <TableCell class="text-muted-foreground whitespace-nowrap">
                    {{ formatDate(item.created_at) }}
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <div class="h-7 w-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <Building2 class="h-3.5 w-3.5" />
                      </div>
                      <div class="flex flex-col">
                        <span class="font-semibold text-foreground">{{ item.wallet?.mitra?.nama_pemilik || 'Mitra #' + item.wallet?.mitra_id }}</span>
                        <span class="text-[10px] text-muted-foreground">Wallet #{{ item.wallet_id }}</span>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span v-if="item.pesanan?.invoice" class="font-mono font-bold text-foreground">
                      {{ item.pesanan.invoice }}
                    </span>
                    <span v-else-if="item.withdrawal_id" class="font-mono text-muted-foreground">
                      #WD-{{ item.withdrawal_id }}
                    </span>
                    <span v-else class="text-muted-foreground">-</span>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" :class="getTypeBadge(item.type).class" class="text-[10px] font-semibold">
                      {{ getTypeBadge(item.type).label }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <span
                      class="font-mono font-bold"
                      :class="getTypeBadge(item.type).isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                    >
                      {{ getTypeBadge(item.type).isCredit ? '+' : '-' }}{{ formatCurrency(item.nominal) }}
                    </span>
                  </TableCell>
                  <TableCell class="font-mono font-medium text-blue-600 dark:text-blue-400">
                    {{ formatCurrency(item.saldo_pending_after) }}
                  </TableCell>
                  <TableCell class="font-mono font-medium" :class="Number(item.saldo_available_after) < 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 dark:text-emerald-400'">
                    {{ formatCurrency(item.saldo_available_after) }}
                  </TableCell>
                  <TableCell class="text-muted-foreground max-w-xs truncate" :title="item.catatan || ''">
                    {{ item.catatan || '-' }}
                  </TableCell>
                </TableRow>
              </template>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div v-if="totalItems > 0" class="p-4 border-t">
          <PaginationBar
            :meta="paginationMeta"
            @page-change="fetchLedger"
            @per-page-change="(val: number) => { perPage = val; fetchLedger(1); }"
          />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
