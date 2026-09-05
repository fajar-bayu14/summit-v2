<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { financeService } from '@/api/finance'
import type { WithdrawalRequest } from '@/types/finance'
import type { PaginationMeta } from '@/types/api'
import { useToast } from '@/composables/useToast'
import {
  CreditCard,
  Search,
  RefreshCw,
  CheckCircle2,
  XCircle,
  Clock,
  Eye,
  Check,
  X,
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
import WithdrawalApproveModal from '@/components/admin/finance/WithdrawalApproveModal.vue'
import WithdrawalRejectModal from '@/components/admin/finance/WithdrawalRejectModal.vue'
import WithdrawalDetailModal from '@/components/admin/finance/WithdrawalDetailModal.vue'

const toast = useToast()

// Data State
const withdrawals = ref<WithdrawalRequest[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref<string>('all')

// Pagination State
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(10)

const paginationMeta = computed<PaginationMeta>(() => ({
  current_page: currentPage.value,
  last_page: lastPage.value,
  per_page: perPage.value,
  total: totalItems.value,
  from: totalItems.value > 0 ? (currentPage.value - 1) * perPage.value + 1 : 0,
  to: Math.min(currentPage.value * perPage.value, totalItems.value),
}))

// Modal States
const isApproveModalOpen = ref(false)
const isRejectModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const selectedWithdrawal = ref<WithdrawalRequest | null>(null)
const isActionLoading = ref(false)

// Metric Computations
const stats = computed(() => {
  const pending = withdrawals.value.filter(w => w.status === 'pending').length
  const completed = withdrawals.value.filter(w => w.status === 'completed').length
  const rejected = withdrawals.value.filter(w => w.status === 'rejected' || w.status === 'failed').length
  const totalCompletedNominal = withdrawals.value
    .filter(w => w.status === 'completed')
    .reduce((sum, item) => sum + (Number(item.nominal) || 0), 0)

  return {
    pending,
    completed,
    rejected,
    totalCompletedNominal,
  }
})

async function fetchWithdrawals(page = 1) {
  isLoading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: perPage.value,
    }
    if (selectedStatus.value !== 'all') {
      params.status = selectedStatus.value
    }
    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const res = await financeService.getWithdrawals(params)
    const rawData = res.data?.data || res.data
    withdrawals.value = Array.isArray(rawData) ? rawData : (rawData as any)?.data || []

    const meta = (res.data as any)?.meta || res.data
    if (meta) {
      currentPage.value = meta.current_page || 1
      lastPage.value = meta.last_page || 1
      totalItems.value = meta.total || withdrawals.value.length
    }
  } catch (err: any) {
    toast.error('Gagal Memuat Data', err.response?.data?.message || 'Terjadi kesalahan saat memuat daftar penarikan dana.')
  } finally {
    isLoading.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  fetchWithdrawals(1)
}

function handleStatusFilter(status: string) {
  selectedStatus.value = status
  currentPage.value = 1
  fetchWithdrawals(1)
}

function openApproveModal(withdrawal: WithdrawalRequest) {
  selectedWithdrawal.value = withdrawal
  isApproveModalOpen.value = true
}

function openRejectModal(withdrawal: WithdrawalRequest) {
  selectedWithdrawal.value = withdrawal
  isRejectModalOpen.value = true
}

function openDetailModal(withdrawal: WithdrawalRequest) {
  selectedWithdrawal.value = withdrawal
  isDetailModalOpen.value = true
}

async function handleApproveConfirm(id: number) {
  isActionLoading.value = true
  try {
    await financeService.approveWithdrawal(id)
    toast.success('Pencairan Disetujui', 'Permohonan penarikan dana berhasil disetujui dan diteruskan ke sistem payout.')
    isApproveModalOpen.value = false
    fetchWithdrawals(currentPage.value)
  } catch (err: any) {
    toast.error('Gagal Menyetujui', err.response?.data?.message || 'Terjadi kesalahan saat memproses persetujuan.')
  } finally {
    isActionLoading.value = false
  }
}

async function handleRejectConfirm(id: number, reason: string) {
  isActionLoading.value = true
  try {
    await financeService.rejectWithdrawal(id, { alasan_penolakan: reason })
    toast.success('Penarikan Ditolak', 'Permohonan penarikan berhasil ditolak dan saldo dikembalikan ke akun mitra.')
    isRejectModalOpen.value = false
    fetchWithdrawals(currentPage.value)
  } catch (err: any) {
    toast.error('Gagal Menolak', err.response?.data?.message || 'Terjadi kesalahan saat memproses penolakan.')
  } finally {
    isActionLoading.value = false
  }
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

function getStatusBadge(status: string) {
  switch (status) {
    case 'pending':
      return { label: 'Menunggu Review', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' }
    case 'processing':
      return { label: 'Sedang Proses', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' }
    case 'completed':
      return { label: 'Berhasil Dicairkan', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' }
    case 'rejected':
      return { label: 'Ditolak', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' }
    case 'failed':
      return { label: 'Gagal Eksekusi', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' }
    default:
      return { label: status, class: 'bg-muted text-muted-foreground' }
  }
}

onMounted(() => {
  fetchWithdrawals(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Penarikan Dana Mitra (Disbursement)</h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Kelola dan tinjau pengajuan pencairan saldo dompet mitra basecamp ke rekening bank terdaftar.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="gap-1.5 text-xs h-9"
          :disabled="isLoading"
          @click="fetchWithdrawals(currentPage)"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Muat Ulang</span>
        </Button>
      </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Menunggu Review</span>
            <span class="text-2xl font-bold font-mono text-amber-600 mt-1 block">{{ stats.pending }} Pengajuan</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
            <Clock class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Berhasil Dicairkan</span>
            <span class="text-2xl font-bold font-mono text-emerald-600 mt-1 block">{{ stats.completed }} Transaksi</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
            <CheckCircle2 class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Total Ditolak</span>
            <span class="text-2xl font-bold font-mono text-rose-600 mt-1 block">{{ stats.rejected }} Pengajuan</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center">
            <XCircle class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs bg-emerald-900 text-white dark:bg-emerald-950">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-emerald-300 text-xs font-semibold uppercase tracking-wider block">Total Terbayar</span>
            <span class="text-xl font-bold font-mono text-white mt-1 block">
              {{ formatCurrency(stats.totalCompletedNominal) }}
            </span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-emerald-800 text-emerald-200 flex items-center justify-center">
            <CreditCard class="h-5 w-5" />
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
              placeholder="Cari nama mitra / no rekening..."
              class="pl-9 h-9 text-xs"
              @keydown.enter="handleSearch"
            />
          </div>

          <!-- Status Filter Tabs -->
          <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
            <Button
              v-for="status in [
                { key: 'all', label: 'Semua Status' },
                { key: 'pending', label: 'Menunggu' },
                { key: 'completed', label: 'Selesai' },
                { key: 'rejected', label: 'Ditolak' },
              ]"
              :key="status.key"
              size="sm"
              variant="ghost"
              class="h-8 text-xs px-3 rounded-lg"
              :class="selectedStatus === status.key ? 'bg-primary text-primary-foreground font-semibold shadow-xs' : 'text-muted-foreground hover:bg-muted'"
              @click="handleStatusFilter(status.key)"
            >
              {{ status.label }}
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Data Table Card -->
    <Card class="rounded-2xl border shadow-xs overflow-hidden">
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="text-xs font-semibold">ID Pengajuan</TableHead>
                <TableHead class="text-xs font-semibold">Mitra Basecamp</TableHead>
                <TableHead class="text-xs font-semibold">Rekening Tujuan</TableHead>
                <TableHead class="text-xs font-semibold">Nominal Payout</TableHead>
                <TableHead class="text-xs font-semibold">Status</TableHead>
                <TableHead class="text-xs font-semibold">Tanggal Diajukan</TableHead>
                <TableHead class="text-xs font-semibold text-right">Tindakan</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="isLoading">
                <TableRow v-for="i in 5" :key="i">
                  <TableCell colspan="7" class="h-14 text-center">
                    <div class="animate-pulse flex items-center justify-center gap-2 text-muted-foreground text-xs">
                      <div class="h-4 w-4 bg-muted rounded-full" />
                      <span>Memuat data penarikan dana...</span>
                    </div>
                  </TableCell>
                </TableRow>
              </template>

              <template v-else-if="withdrawals.length === 0">
                <TableRow>
                  <TableCell colspan="7" class="py-12">
                    <EmptyState
                      title="Tidak Ada Data Penarikan"
                      description="Belum ada permohonan penarikan dana yang sesuai dengan filter pencarian saat ini."
                    />
                  </TableCell>
                </TableRow>
              </template>

              <template v-else>
                <TableRow
                  v-for="item in withdrawals"
                  :key="item.id"
                  class="hover:bg-muted/30 transition text-xs"
                >
                  <TableCell class="font-mono font-bold text-foreground">
                    #WD-{{ item.id }}
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="font-semibold text-foreground">{{ item.mitra?.nama_pemilik || '-' }}</span>
                      <span class="text-[11px] text-muted-foreground">{{ item.mitra?.telepon || '-' }}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="font-semibold text-foreground">{{ item.bank }} - <span class="font-mono">{{ item.rekening_bank }}</span></span>
                      <span class="text-[11px] text-muted-foreground">a.n {{ item.nama_rekening }}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span class="font-mono font-extrabold text-foreground text-sm">
                      {{ formatCurrency(item.nominal) }}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" :class="getStatusBadge(item.status).class" class="text-[10px] font-semibold">
                      {{ getStatusBadge(item.status).label }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-muted-foreground">
                    {{ formatDate(item.created_at) }}
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                        title="Lihat Detail"
                        @click="openDetailModal(item)"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>

                      <!-- Action Buttons jika Status Pending -->
                      <template v-if="item.status === 'pending'">
                        <Button
                          size="sm"
                          class="h-8 text-xs bg-emerald-700 hover:bg-emerald-800 text-white gap-1 px-2.5"
                          title="Setujui Pencairan"
                          @click="openApproveModal(item)"
                        >
                          <Check class="h-3.5 w-3.5" />
                          <span>Approve</span>
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          class="h-8 text-xs text-destructive hover:bg-destructive/10 border-destructive/20 gap-1 px-2.5"
                          title="Tolak Pengajuan"
                          @click="openRejectModal(item)"
                        >
                          <X class="h-3.5 w-3.5" />
                          <span>Tolak</span>
                        </Button>
                      </template>
                    </div>
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
            @page-change="fetchWithdrawals"
            @per-page-change="(val: number) => { perPage = val; fetchWithdrawals(1); }"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Modals -->
    <WithdrawalApproveModal
      v-model:open="isApproveModalOpen"
      :withdrawal="selectedWithdrawal"
      :is-loading="isActionLoading"
      @confirm="handleApproveConfirm"
    />

    <WithdrawalRejectModal
      v-model:open="isRejectModalOpen"
      :withdrawal="selectedWithdrawal"
      :is-loading="isActionLoading"
      @confirm="handleRejectConfirm"
    />

    <WithdrawalDetailModal
      v-model:open="isDetailModalOpen"
      :withdrawal="selectedWithdrawal"
    />
  </div>
</template>
