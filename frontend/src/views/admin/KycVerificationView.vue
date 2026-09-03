<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import {
  ShieldCheck,
  ShieldAlert,
  Clock,
  CheckCircle2,
  Search,
  RefreshCw,
  Eye,
  Calendar,
  User,
  Phone,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { DataTable, type ColumnDef, MetricCard } from '@/components/common'
import KycDocumentModal from '@/components/admin/kyc/KycDocumentModal.vue'
import { kycApi } from '@/api/kyc'
import { useToast } from '@/composables/useToast'
import type { KycProfile, KycStatus } from '@/types/kyc'
import type { PaginationMeta } from '@/types/api'

const toast = useToast()

// Reactive state
const loading = ref<boolean>(true)
const kycList = ref<KycProfile[]>([])
const pagination = ref<PaginationMeta>({
  current_page: 1,
  from: 1,
  last_page: 1,
  per_page: 15,
  to: 1,
  total: 0,
})

// Filter states
const filters = reactive({
  status: '' as KycStatus | '',
  search: '',
  page: 1,
  per_page: 15,
})

// Selected row for Document Modal
const selectedKyc = ref<KycProfile | null>(null)
const isModalOpen = ref<boolean>(false)

// Metric counters
const stats = reactive({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
})

// Column definitions for DataTable
const columns: ColumnDef<KycProfile>[] = [
  { key: 'id', label: 'ID', width: '80px', align: 'center' },
  { key: 'nama_lengkap', label: 'Nama Pendaki' },
  { key: 'nomor_identitas', label: 'Identitas / NIK' },
  { key: 'kontak_darurat', label: 'Kontak Darurat' },
  { key: 'created_at', label: 'Tgl Pengajuan', width: '130px' },
  { key: 'status_verifikasi', label: 'Status', width: '120px', align: 'center' },
  { key: 'actions', label: 'Aksi', width: '130px', align: 'center' },
]

// Status tabs
const statusTabs: { label: string; value: KycStatus | ''; icon: any }[] = [
  { label: 'Semua Pengajuan', value: '', icon: ShieldCheck },
  { label: 'Menunggu Verifikasi', value: 'pending', icon: Clock },
  { label: 'Disetujui', value: 'disetujui', icon: CheckCircle2 },
  { label: 'Ditolak', value: 'ditolak', icon: ShieldAlert },
]

let searchTimer: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  fetchData()
})

watch(
  () => filters.status,
  () => {
    filters.page = 1
    fetchData()
  }
)

function handleSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.page = 1
    fetchData()
  }, 350)
}

async function fetchData() {
  loading.value = true

  try {
    const res = await kycApi.getKycList({
      status: filters.status || undefined,
      search: filters.search.trim() || undefined,
      page: filters.page,
      per_page: filters.per_page,
    })

    kycList.value = res.items
    pagination.value = res.meta

    // Compute simple metrics from total
    stats.total = res.meta.total
    if (filters.status === 'pending') stats.pending = res.meta.total
    if (filters.status === 'disetujui') stats.approved = res.meta.total
    if (filters.status === 'ditolak') stats.rejected = res.meta.total
  } catch (err: any) {
    toast.error(err?.message || 'Gagal memuat daftar KYC pendaki.')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page: number) {
  filters.page = page
  fetchData()
}

function handlePerPageChange(perPage: number) {
  filters.per_page = perPage
  filters.page = 1
  fetchData()
}

function openReviewModal(kyc: KycProfile) {
  selectedKyc.value = kyc
  isModalOpen.value = true
}

function handleVerified(updated: KycProfile) {
  const index = kycList.value.findIndex(item => item.id === updated.id)
  if (index !== -1) {
    kycList.value[index] = { ...kycList.value[index], ...updated }
  }
}

function formatDate(dateStr: string) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// Dynamic empty state message and action based on active filters
const emptyStateInfo = computed(() => {
  if (filters.search) {
    return {
      title: 'Pencarian Tidak Ditemukan',
      description: `Tidak ditemukan profil KYC dengan kata kunci "${filters.search}".`,
      icon: Search,
      actionLabel: 'Reset Pencarian',
    }
  }
  if (filters.status === 'pending') {
    return {
      title: 'Tidak Ada Antrean Verifikasi',
      description: 'Semua permohonan verifikasi identitas pendaki telah diproses. Tidak ada antrean pending.',
      icon: Clock,
      actionLabel: 'Lihat Semua Pengajuan',
    }
  }
  if (filters.status === 'disetujui') {
    return {
      title: 'Belum Ada KYC Disetujui',
      description: 'Belum ada data identitas pendaki yang berada dalam status disetujui.',
      icon: CheckCircle2,
      actionLabel: 'Lihat Semua Pengajuan',
    }
  }
  if (filters.status === 'ditolak') {
    return {
      title: 'Tidak Ada KYC Ditolak',
      description: 'Tidak ada pengajuan verifikasi identitas pendaki yang ditolak saat ini.',
      icon: ShieldAlert,
      actionLabel: 'Lihat Semua Pengajuan',
    }
  }
  return {
    title: 'Belum Ada Data KYC',
    description: 'Belum ada riwayat pengajuan verifikasi identitas pendaki yang tercatat di sistem.',
    icon: ShieldCheck,
    actionLabel: 'Segarkan Data',
  }
})

function handleEmptyAction() {
  if (filters.search || filters.status) {
    filters.search = ''
    filters.status = ''
    filters.page = 1
    fetchData()
  } else {
    fetchData()
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
          <ShieldCheck class="h-7 w-7 text-primary" />
          Verifikasi Identitas KYC Pendaki
        </h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Validasi berkas foto KTP/Paspor dan data kontak darurat pendaki sebelum izin pendakian diterbitkan.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs rounded-xl" @click="fetchData">
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" /> Segarkan
        </Button>
      </div>
    </div>

    <!-- Metrics Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <MetricCard
        title="Total Pengajuan"
        :value="stats.total"
        :icon="User"
        description="Seluruh arsip verifikasi akun pendaki"
        icon-class="bg-blue-500/10 text-blue-600"
      />
      <MetricCard
        title="Menunggu Review"
        :value="stats.pending || (filters.status === 'pending' ? pagination.total : '—')"
        :icon="Clock"
        description="Perlu tindakan persetujuan admin"
        icon-class="bg-amber-500/10 text-amber-600"
      />
      <MetricCard
        title="Terverifikasi"
        :value="stats.approved || (filters.status === 'disetujui' ? pagination.total : '—')"
        :icon="CheckCircle2"
        description="Identitas sah & diizinkan mendaki"
        icon-class="bg-emerald-500/10 text-emerald-600"
      />
      <MetricCard
        title="Ditolak"
        :value="stats.rejected || (filters.status === 'ditolak' ? pagination.total : '—')"
        :icon="ShieldAlert"
        description="Data tidak valid / foto KTP buram"
        icon-class="bg-red-500/10 text-red-600"
      />
    </div>

    <!-- Filter Toolbar -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 bg-card p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
      <!-- Status Tabs -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
        <Button
          v-for="tab in statusTabs"
          :key="tab.value"
          size="sm"
          :variant="filters.status === tab.value ? 'default' : 'ghost'"
          class="h-8 rounded-xl text-xs gap-1.5 shrink-0"
          @click="filters.status = tab.value"
        >
          <component :is="tab.icon" class="h-3.5 w-3.5" />
          {{ tab.label }}
        </Button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full md:w-72">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
        <Input
          v-model="filters.search"
          type="search"
          placeholder="Cari nama pendaki / NIK..."
          class="h-9 pl-9 text-xs rounded-xl"
          @input="handleSearchInput"
        />
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="kycList"
      :loading="loading"
      :pagination="pagination"
      :empty-title="emptyStateInfo.title"
      :empty-description="emptyStateInfo.description"
      :empty-icon="emptyStateInfo.icon"
      :empty-action-label="emptyStateInfo.actionLabel"
      @empty-action="handleEmptyAction"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <!-- ID Column -->
      <template #cell(id)="{ value }">
        <span class="font-mono text-xs font-semibold text-muted-foreground">#KYC-{{ value }}</span>
      </template>

      <!-- Nama Lengkap & Phone Column -->
      <template #cell(nama_lengkap)="{ row }">
        <div>
          <span class="font-bold text-foreground block">{{ row.nama_lengkap }}</span>
          <span class="text-xs text-muted-foreground font-mono flex items-center gap-1 mt-0.5">
            <Phone class="h-3 w-3" /> {{ row.telepon || '-' }}
          </span>
        </div>
      </template>

      <!-- Nomor Identitas Column -->
      <template #cell(nomor_identitas)="{ row }">
        <div>
          <span class="uppercase text-[10px] font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-muted-foreground inline-block mb-0.5">
            {{ row.jenis_identitas }}
          </span>
          <span class="font-mono font-semibold text-foreground block text-xs tracking-wider">
            {{ row.nomor_identitas }}
          </span>
        </div>
      </template>

      <!-- Kontak Darurat Column -->
      <template #cell(kontak_darurat)="{ row }">
        <div>
          <span class="font-medium text-foreground block text-xs">
            {{ row.nama_kontak_darurat || '-' }}
            <span v-if="row.hubungan_darurat" class="text-muted-foreground text-[11px]">({{ row.hubungan_darurat }})</span>
          </span>
          <span class="text-[11px] text-muted-foreground font-mono">{{ row.telepon_darurat || '-' }}</span>
        </div>
      </template>

      <!-- Created At Column -->
      <template #cell(created_at)="{ value }">
        <span class="text-xs text-muted-foreground font-medium flex items-center gap-1">
          <Calendar class="h-3 w-3" /> {{ formatDate(value) }}
        </span>
      </template>

      <!-- Status Column -->
      <template #cell(status_verifikasi)="{ value }">
        <Badge
          :class="{
            'bg-emerald-500/10 text-emerald-600 border-emerald-500/20': value === 'disetujui',
            'bg-amber-500/10 text-amber-600 border-amber-500/20': value === 'pending',
            'bg-red-500/10 text-red-600 border-red-500/20': value === 'ditolak',
          }"
          class="capitalize text-xs font-semibold px-2 py-0.5"
        >
          {{ value || 'pending' }}
        </Badge>
      </template>

      <!-- Actions Column -->
      <template #cell(actions)="{ row }">
        <Button
          variant="outline"
          size="sm"
          class="h-7 text-xs rounded-lg gap-1 font-medium hover:bg-primary/5 hover:text-primary hover:border-primary/30"
          @click="openReviewModal(row)"
        >
          <Eye class="h-3.5 w-3.5" /> Tinjau Dokumen
        </Button>
      </template>
    </DataTable>

    <!-- Secure Document Preview & Verification Modal -->
    <KycDocumentModal
      v-model:open="isModalOpen"
      :kyc="selectedKyc"
      @verified="handleVerified"
    />
  </div>
</template>
