<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { financeService } from '@/api/finance'
import type { RefundRecord, ProcessRefundPayload, ForceMajeurePayload } from '@/types/finance'
import type { PaginationMeta } from '@/types/api'
import { useToast } from '@/composables/useToast'
import {
  RefreshCw,
  Search,
  Scale,
  ShieldAlert,
  CheckCircle2,
  Eye,
  Flame,
  CreditCard,
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
import DisputeResolutionModal from '@/components/admin/finance/DisputeResolutionModal.vue'
import ForceMajeureModal from '@/components/admin/finance/ForceMajeureModal.vue'
import RefundDetailModal from '@/components/admin/finance/RefundDetailModal.vue'

const toast = useToast()

// Tab Active State ('disputes' | 'all')
const activeTab = ref<'disputes' | 'all'>('disputes')

// Data State
const refunds = ref<RefundRecord[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedCategory = ref<string>('all')
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
const isDisputeModalOpen = ref(false)
const isForceMajeureModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const selectedRefund = ref<RefundRecord | null>(null)
const isActionLoading = ref(false)

// Metrics Computations
const stats = computed(() => {
  const disputesCount = refunds.value.filter(r => r.is_disputed && r.status === 'disputed').length
  const successCount = refunds.value.filter(r => r.status === 'success' || r.status === 'approved_by_mitra').length
  const forceMajeureCount = refunds.value.filter(r => r.refund_category === 'force_majeure').length
  const totalRefundNominal = refunds.value
    .filter(r => r.status === 'success' || r.status === 'approved_by_mitra')
    .reduce((sum, item) => sum + (Number(item.nominal_disetujui || item.nominal) || 0), 0)

  return {
    disputesCount,
    successCount,
    forceMajeureCount,
    totalRefundNominal,
  }
})

async function fetchRefunds(page = 1) {
  isLoading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: perPage.value,
    }

    if (activeTab.value === 'disputes') {
      params.is_disputed = true
    } else {
      if (selectedStatus.value !== 'all') {
        params.status = selectedStatus.value
      }
      if (selectedCategory.value !== 'all') {
        params.refund_category = selectedCategory.value
      }
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    const res = await financeService.getRefunds(params)
    const rawData = res.data?.data || res.data
    refunds.value = Array.isArray(rawData) ? rawData : (rawData as any)?.data || []

    const meta = (res.data as any)?.meta || res.data
    if (meta) {
      currentPage.value = meta.current_page || 1
      lastPage.value = meta.last_page || 1
      totalItems.value = meta.total || refunds.value.length
    }
  } catch (err: any) {
    toast.error('Gagal Memuat Refund', err.response?.data?.message || 'Terjadi kesalahan saat memuat data pengembalian dana.')
  } finally {
    isLoading.value = false
  }
}

function handleTabChange(tab: 'disputes' | 'all') {
  activeTab.value = tab
  currentPage.value = 1
  fetchRefunds(1)
}

function handleSearch() {
  currentPage.value = 1
  fetchRefunds(1)
}

function openDisputeModal(refund: RefundRecord) {
  selectedRefund.value = refund
  isDisputeModalOpen.value = true
}

function openDetailModal(refund: RefundRecord) {
  selectedRefund.value = refund
  isDetailModalOpen.value = true
}

async function handleDisputeConfirm(id: number, payload: ProcessRefundPayload) {
  isActionLoading.value = true
  try {
    await financeService.processRefund(id, payload)
    toast.success('Sengketa Diselesaikan', 'Keputusan mediasi admin berhasil diterapkan dan diproses.')
    isDisputeModalOpen.value = false
    fetchRefunds(currentPage.value)
  } catch (err: any) {
    toast.error('Gagal Menyelesaikan Sengketa', err.response?.data?.message || 'Terjadi kesalahan saat memproses keputusan sengketa.')
  } finally {
    isActionLoading.value = false
  }
}

async function handleForceMajeureConfirm(payload: ForceMajeurePayload) {
  isActionLoading.value = true
  try {
    const res = await financeService.triggerForceMajeure(payload)
    const count = res.data?.total_pesanan_refunded || res.data?.total_refunded || 0
    toast.success('Force Majeure Selesai', `Berhasil membatalkan dan merefund massal ${count} tiket pesanan terdampak.`)
    isForceMajeureModalOpen.value = false
    fetchRefunds(currentPage.value)
  } catch (err: any) {
    toast.error('Gagal Eksekusi Force Majeure', err.response?.data?.message || 'Terjadi kesalahan saat eksekusi refund massal.')
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
      return { label: 'Review Mitra', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' }
    case 'approved_by_mitra':
      return { label: 'Disetujui Mitra', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' }
    case 'rejected_by_mitra':
      return { label: 'Ditolak Mitra', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' }
    case 'disputed':
      return { label: 'Sengketa Mediasi', class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' }
    case 'success':
      return { label: 'Refund Berhasil', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' }
    case 'rejected':
      return { label: 'Ditolak Admin', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' }
    default:
      return { label: status, class: 'bg-muted text-muted-foreground' }
  }
}

function getCategoryBadge(cat: string) {
  switch (cat) {
    case 'pre_trip':
      return { label: 'Batal Pra-Trip', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' }
    case 'incident':
      return { label: 'Insiden / Lapangan', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' }
    case 'force_majeure':
      return { label: 'Force Majeure Bencana', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' }
    case 'dispute':
      return { label: 'Sengketa Banding', class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' }
    default:
      return { label: cat, class: 'bg-muted text-muted-foreground' }
  }
}

onMounted(() => {
  fetchRefunds(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header & Emergency Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Pusat Sengketa & Pengembalian Dana (Refund)</h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Mediasi sengketa klaim pendaki vs mitra basecamp serta eksekusi darurat pengembalian dana akibat penutupan jalur.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          variant="destructive"
          size="sm"
          class="gap-1.5 text-xs h-9 bg-rose-700 hover:bg-rose-800 text-white font-semibold"
          @click="isForceMajeureModalOpen = true"
        >
          <ShieldAlert class="h-4 w-4" />
          <span>Force Majeure / Tutup Jalur</span>
        </Button>
        <Button
          variant="outline"
          size="sm"
          class="gap-1.5 text-xs h-9"
          :disabled="isLoading"
          @click="fetchRefunds(currentPage)"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Muat Ulang</span>
        </Button>
      </div>
    </div>

    <!-- 4 Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card class="rounded-2xl border shadow-xs bg-purple-500/5 border-purple-500/20">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400 block">Sengketa Mediasi</span>
            <span class="text-2xl font-extrabold font-mono text-purple-700 dark:text-purple-300 mt-1 block">
              {{ stats.disputesCount }} Kasus
            </span>
            <span class="text-[10px] text-muted-foreground mt-0.5 block">Memerlukan intervensi admin</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center">
            <Scale class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Refund Selesai</span>
            <span class="text-2xl font-bold font-mono text-emerald-600 mt-1 block">
              {{ stats.successCount }} Klaim
            </span>
            <span class="text-[10px] text-muted-foreground mt-0.5 block">Disetujui mitra / admin</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
            <CheckCircle2 class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block">Force Majeure Bencana</span>
            <span class="text-2xl font-bold font-mono text-rose-600 mt-1 block">
              {{ stats.forceMajeureCount }} Kasus
            </span>
            <span class="text-[10px] text-muted-foreground mt-0.5 block">Penutupan jalur resmi</span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center">
            <Flame class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>

      <Card class="rounded-2xl border shadow-xs bg-slate-900 text-white">
        <CardContent class="p-4 flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider block">Total Dana Direfund</span>
            <span class="text-xl font-bold font-mono text-emerald-400 mt-1 block">
              {{ formatCurrency(stats.totalRefundNominal) }}
            </span>
          </div>
          <div class="h-10 w-10 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center">
            <CreditCard class="h-5 w-5" />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Dual Tab Toolbar -->
    <Card class="rounded-2xl border shadow-xs">
      <CardContent class="p-4 space-y-3">
        <div class="flex flex-col md:flex-row items-center justify-between gap-3">
          <!-- Tabs -->
          <div class="flex items-center gap-1.5 p-1 bg-muted/50 rounded-xl w-full md:w-auto">
            <Button
              size="sm"
              variant="ghost"
              class="h-8 text-xs px-4 rounded-lg flex items-center gap-2"
              :class="activeTab === 'disputes' ? 'bg-background text-foreground font-bold shadow-xs' : 'text-muted-foreground'"
              @click="handleTabChange('disputes')"
            >
              <Scale class="h-3.5 w-3.5 text-purple-600" />
              <span>Pusat Sengketa (Disputes)</span>
              <Badge v-if="stats.disputesCount > 0" variant="destructive" class="h-4 px-1 text-[10px]">
                {{ stats.disputesCount }}
              </Badge>
            </Button>

            <Button
              size="sm"
              variant="ghost"
              class="h-8 text-xs px-4 rounded-lg flex items-center gap-2"
              :class="activeTab === 'all' ? 'bg-background text-foreground font-bold shadow-xs' : 'text-muted-foreground'"
              @click="handleTabChange('all')"
            >
              <RefreshCw class="h-3.5 w-3.5" />
              <span>Semua Riwayat Refund</span>
            </Button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full md:w-72">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              placeholder="Cari invoice / nama pemohon..."
              class="pl-9 h-9 text-xs"
              @keydown.enter="handleSearch"
            />
          </div>
        </div>

        <!-- Extra Category Filters jika Tab All -->
        <div v-if="activeTab === 'all'" class="flex flex-wrap items-center gap-1.5 pt-2 border-t">
          <span class="text-xs font-semibold text-muted-foreground mr-1">Kategori:</span>
          <Button
            v-for="cat in [
              { key: 'all', label: 'Semua Kategori' },
              { key: 'pre_trip', label: 'Batal Pra-Trip' },
              { key: 'incident', label: 'Insiden Lapangan' },
              { key: 'force_majeure', label: 'Force Majeure' },
            ]"
            :key="cat.key"
            size="sm"
            variant="ghost"
            class="h-7 text-xs px-2.5 rounded-md"
            :class="selectedCategory === cat.key ? 'bg-muted font-bold text-foreground' : 'text-muted-foreground'"
            @click="selectedCategory = cat.key; fetchRefunds(1)"
          >
            {{ cat.label }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Data Table -->
    <Card class="rounded-2xl border shadow-xs overflow-hidden">
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="text-xs font-semibold">ID Klaim</TableHead>
                <TableHead class="text-xs font-semibold">Invoice & Destinasi</TableHead>
                <TableHead class="text-xs font-semibold">Pendaki Pemohon</TableHead>
                <TableHead class="text-xs font-semibold">Kategori</TableHead>
                <TableHead class="text-xs font-semibold">Nominal Klaim</TableHead>
                <TableHead class="text-xs font-semibold">Status</TableHead>
                <TableHead class="text-xs font-semibold">Diajukan</TableHead>
                <TableHead class="text-xs font-semibold text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <template v-if="isLoading">
                <TableRow v-for="i in 5" :key="i">
                  <TableCell colspan="8" class="h-14 text-center">
                    <div class="animate-pulse flex items-center justify-center gap-2 text-muted-foreground text-xs">
                      <div class="h-4 w-4 bg-muted rounded-full" />
                      <span>Memuat data pengembalian dana...</span>
                    </div>
                  </TableCell>
                </TableRow>
              </template>

              <template v-else-if="refunds.length === 0">
                <TableRow>
                  <TableCell colspan="8" class="py-12">
                    <EmptyState
                      title="Tidak Ada Data Refund"
                      :description="activeTab === 'disputes' ? 'Tidak ada sengketa klaim pending yang membutuhkan intervensi admin saat ini.' : 'Belum ada riwayat refund yang sesuai dengan filter.'"
                    />
                  </TableCell>
                </TableRow>
              </template>

              <template v-else>
                <TableRow
                  v-for="item in refunds"
                  :key="item.id"
                  class="hover:bg-muted/30 transition text-xs"
                >
                  <TableCell class="font-mono font-bold text-foreground">
                    #RF-{{ item.id }}
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="font-mono font-bold text-foreground">{{ item.pesanan?.invoice || '-' }}</span>
                      <span class="text-[11px] text-muted-foreground truncate max-w-[160px]">
                        {{ item.pesanan?.basecamp?.jalur?.gunung?.nama_gunung || 'Gunung' }} - {{ item.pesanan?.basecamp?.jalur?.nama_jalur || '' }}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="font-semibold text-foreground">{{ item.nama_tujuan }}</span>
                      <span class="text-[11px] text-muted-foreground">{{ item.bank_tujuan }} - <span class="font-mono">{{ item.rekening_tujuan }}</span></span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" :class="getCategoryBadge(item.refund_category).class" class="text-[10px]">
                      {{ getCategoryBadge(item.refund_category).label }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="font-mono font-bold text-foreground">
                        {{ formatCurrency(item.nominal_disetujui || item.nominal) }}
                      </span>
                      <span v-if="item.nominal_disetujui && item.nominal_disetujui !== item.nominal" class="text-[10px] text-muted-foreground line-through">
                        {{ formatCurrency(item.nominal) }}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline" :class="getStatusBadge(item.status).class" class="text-[10px] font-semibold">
                      {{ getStatusBadge(item.status).label }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-muted-foreground whitespace-nowrap">
                    {{ formatDate(item.created_at) }}
                  </TableCell>
                  <TableCell class="text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                        title="Lihat Rincian"
                        @click="openDetailModal(item)"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>

                      <!-- Tombol Mediasi jika Disputed -->
                      <Button
                        v-if="item.is_disputed && item.status === 'disputed'"
                        size="sm"
                        class="h-8 text-xs bg-purple-700 hover:bg-purple-800 text-white gap-1 px-2.5"
                        title="Buka Mediasi Sengketa"
                        @click="openDisputeModal(item)"
                      >
                        <Scale class="h-3.5 w-3.5" />
                        <span>Mediasi</span>
                      </Button>
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
            @page-change="fetchRefunds"
            @per-page-change="(val: number) => { perPage = val; fetchRefunds(1); }"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Modals -->
    <DisputeResolutionModal
      v-model:open="isDisputeModalOpen"
      :refund="selectedRefund"
      :is-loading="isActionLoading"
      @confirm="handleDisputeConfirm"
    />

    <ForceMajeureModal
      v-model:open="isForceMajeureModalOpen"
      :is-loading="isActionLoading"
      @confirm="handleForceMajeureConfirm"
    />

    <RefundDetailModal
      v-model:open="isDetailModalOpen"
      :refund="selectedRefund"
    />
  </div>
</template>
