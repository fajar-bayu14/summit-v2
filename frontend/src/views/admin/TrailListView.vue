<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  Compass,
  Mountain,
  Plus,
  Search,
  RefreshCw,
  Edit2,
  Trash2,
  Clock,
  CheckCircle2,
  XCircle,
  Building2,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { DataTable, type ColumnDef, MetricCard, ConfirmModal } from '@/components/common'
import TrailFormModal from '@/components/admin/trail/TrailFormModal.vue'
import { trailApi } from '@/api/trail'
import { mountainApi } from '@/api/mountain'
import { useToast } from '@/composables/useToast'
import type {
  Gunung,
  JalurPendakian,
  TrailDifficulty,
  TrailStatus,
} from '@/types/mountain'
import type { PaginationMeta } from '@/types/api'

const route = useRoute()
const toast = useToast()

const loading = ref<boolean>(true)
const trails = ref<JalurPendakian[]>([])
const mountains = ref<Gunung[]>([])

const pagination = ref<PaginationMeta>({
  current_page: 1,
  from: 1,
  last_page: 1,
  per_page: 15,
  to: 1,
  total: 0,
})

// Filters
const filters = reactive({
  gunung_id: (route.query.gunung_id ? Number(route.query.gunung_id) : '') as number | '',
  status: '' as TrailStatus | '',
  tingkat_kesulitan: '' as TrailDifficulty | '',
  search: '',
  page: 1,
  per_page: 15,
})

// Modals
const isFormModalOpen = ref<boolean>(false)
const selectedTrail = ref<JalurPendakian | null>(null)

const isDeleteModalOpen = ref<boolean>(false)
const trailToDelete = ref<JalurPendakian | null>(null)
const deleting = ref<boolean>(false)

const columns: ColumnDef<JalurPendakian>[] = [
  { key: 'id', label: 'ID', width: '80px', align: 'center' },
  { key: 'nama_jalur', label: 'Nama Jalur Pendakian' },
  { key: 'gunung', label: 'Gunung Induk' },
  { key: 'elevasi_jarak', label: 'Elevasi & Jarak' },
  { key: 'tingkat_kesulitan', label: 'Kesulitan', width: '120px', align: 'center' },
  { key: 'basecamps_count', label: 'Basecamp', width: '100px', align: 'center' },
  { key: 'status', label: 'Status Operasional', width: '160px', align: 'center' },
  { key: 'actions', label: 'Aksi', width: '130px', align: 'center' },
]

let searchTimer: ReturnType<typeof setTimeout> | null = null

onMounted(async () => {
  await fetchMountains()
  fetchTrails()
})

watch(
  () => [filters.gunung_id, filters.status, filters.tingkat_kesulitan],
  () => {
    filters.page = 1
    fetchTrails()
  }
)

function handleSearchInput() {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.page = 1
    fetchTrails()
  }, 350)
}

async function fetchMountains() {
  try {
    mountains.value = await mountainApi.getAllMountains()
  } catch {
    mountains.value = []
  }
}

async function fetchTrails() {
  loading.value = true

  try {
    const res = await trailApi.getTrails({
      gunung_id: filters.gunung_id || undefined,
      status: filters.status || undefined,
      tingkat_kesulitan: filters.tingkat_kesulitan || undefined,
      search: filters.search.trim() || undefined,
      page: filters.page,
      per_page: filters.per_page,
    })

    trails.value = res.items
    pagination.value = res.meta
  } catch (err: any) {
    toast.error(err?.message || 'Gagal memuat daftar jalur pendakian.')
  } finally {
    loading.value = false
  }
}

function handlePageChange(page: number) {
  filters.page = page
  fetchTrails()
}

function handlePerPageChange(perPage: number) {
  filters.per_page = perPage
  filters.page = 1
  fetchTrails()
}

// Quick toggle status
async function toggleStatus(trail: JalurPendakian) {
  const newStatus: TrailStatus = trail.status === 'open' ? 'close' : 'open'

  try {
    await trailApi.updateTrailStatus(trail.id, newStatus)
    trail.status = newStatus
    toast.success(`Status ${trail.nama_jalur} diubah menjadi ${newStatus === 'open' ? 'Dibuka (Open)' : 'Ditutup (Closed)'}.`)
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Gagal mengubah status jalur.')
  }
}

function openCreateModal() {
  selectedTrail.value = null
  isFormModalOpen.value = true
}

function openEditModal(trail: JalurPendakian) {
  selectedTrail.value = trail
  isFormModalOpen.value = true
}

function confirmDelete(trail: JalurPendakian) {
  trailToDelete.value = trail
  isDeleteModalOpen.value = true
}

async function handleDelete() {
  if (!trailToDelete.value) return
  deleting.value = true

  try {
    await trailApi.deleteTrail(trailToDelete.value.id)
    toast.success(`Jalur ${trailToDelete.value.nama_jalur} berhasil dihapus.`)
    trails.value = trails.value.filter(t => t.id !== trailToDelete.value?.id)
    isDeleteModalOpen.value = false
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Gagal menghapus jalur pendakian.')
  } finally {
    deleting.value = false
  }
}

function handleSaved(savedTrail: JalurPendakian) {
  const idx = trails.value.findIndex(t => t.id === savedTrail.id)
  if (idx !== -1) {
    trails.value[idx] = savedTrail
  } else {
    trails.value.unshift(savedTrail)
  }
}

function getDifficultyBadge(diff: TrailDifficulty) {
  switch (diff) {
    case 'mudah':
      return { label: 'Mudah', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' }
    case 'sedang':
      return { label: 'Sedang', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' }
    case 'sulit':
      return { label: 'Sulit', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' }
    case 'ekstrem':
      return { label: 'Ekstrem', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    default:
      return { label: diff, class: 'bg-muted text-muted-foreground' }
  }
}

// Dynamic empty state message
const emptyStateInfo = computed(() => {
  if (filters.search) {
    return {
      title: 'Pencarian Jalur Tidak Ditemukan',
      description: `Tidak ditemukan jalur pendakian dengan kata kunci "${filters.search}".`,
      icon: Search,
      actionLabel: 'Reset Pencarian',
    }
  }
  if (filters.gunung_id) {
    const m = mountains.value.find(g => g.id === filters.gunung_id)
    return {
      title: `Belum Ada Jalur di ${m ? m.nama_gunung : 'Gunung Ini'}`,
      description: 'Belum ada jalur pendakian resmi yang terdaftar untuk gunung yang dipilih.',
      icon: Compass,
      actionLabel: 'Tambah Jalur Sekarang',
    }
  }
  return {
    title: 'Belum Ada Jalur Pendakian',
    description: 'Belum ada data jalur pendakian resmi yang terdaftar di sistem.',
    icon: Compass,
    actionLabel: 'Tambah Jalur Baru',
  }
})

function handleEmptyAction() {
  if (filters.search || filters.gunung_id || filters.status || filters.tingkat_kesulitan) {
    filters.search = ''
    filters.gunung_id = ''
    filters.status = ''
    filters.tingkat_kesulitan = ''
    filters.page = 1
    fetchTrails()
  } else {
    openCreateModal()
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
          <Compass class="h-7 w-7 text-primary" />
          Manajemen Jalur Pendakian
        </h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Kelola rute pendakian resmi, kontrol status buka/tutup pos registrasi, dan tingkat kesulitan medan.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs rounded-xl" @click="fetchTrails">
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" /> Segarkan
        </Button>

        <Button size="sm" class="h-9 gap-1.5 text-xs rounded-xl bg-primary text-primary-foreground shadow-xs" @click="openCreateModal">
          <Plus class="h-4 w-4" /> Tambah Jalur Baru
        </Button>
      </div>
    </div>

    <!-- Analytics Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <MetricCard
        title="Total Jalur Terdaftar"
        :value="pagination.total"
        :icon="Compass"
        description="Seluruh pos registrasi resmi"
        icon-class="bg-emerald-500/10 text-emerald-600"
      />
      <MetricCard
        title="Jalur Dibuka (Open)"
        :value="trails.filter(t => t.status === 'open').length"
        :icon="CheckCircle2"
        description="Siap menerima pendaki"
        icon-class="bg-blue-500/10 text-blue-600"
      />
      <MetricCard
        title="Jalur Ditutup (Closed)"
        :value="trails.filter(t => t.status === 'close').length"
        :icon="XCircle"
        description="Pemulihan ekosistem / cuaca"
        icon-class="bg-red-500/10 text-red-600"
      />
      <MetricCard
        title="Gunung Terhubung"
        :value="mountains.length"
        :icon="Mountain"
        description="Destinasi induk jalur"
        icon-class="bg-purple-500/10 text-purple-600"
      />
    </div>

    <!-- Filter Toolbar -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 bg-card p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
      <div class="flex flex-wrap items-center gap-2 flex-1">
        <!-- Search Input -->
        <div class="relative w-full sm:w-60">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
          <Input
            v-model="filters.search"
            type="search"
            placeholder="Cari nama jalur..."
            class="h-9 pl-9 text-xs rounded-xl"
            @input="handleSearchInput"
          />
        </div>

        <!-- Filter Gunung -->
        <select
          v-model.number="filters.gunung_id"
          class="h-9 px-3 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary max-w-xs"
        >
          <option value="">Semua Gunung</option>
          <option v-for="g in mountains" :key="g.id" :value="g.id">
            {{ g.nama_gunung }}
          </option>
        </select>

        <!-- Filter Status -->
        <select
          v-model="filters.status"
          class="h-9 px-3 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
        >
          <option value="">Semua Status</option>
          <option value="open">Buka (Open)</option>
          <option value="close">Tutup (Closed)</option>
        </select>

        <!-- Filter Kesulitan -->
        <select
          v-model="filters.tingkat_kesulitan"
          class="h-9 px-3 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
        >
          <option value="">Semua Kesulitan</option>
          <option value="mudah">Mudah</option>
          <option value="sedang">Sedang</option>
          <option value="sulit">Sulit</option>
          <option value="ekstrem">Ekstrem</option>
        </select>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="trails"
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
        <span class="font-mono text-xs font-semibold text-muted-foreground">#TRL-{{ value }}</span>
      </template>

      <!-- Nama Jalur Column -->
      <template #cell(nama_jalur)="{ row }">
        <div>
          <strong class="font-bold text-foreground block text-xs">{{ row.nama_jalur }}</strong>
          <span class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
            <Clock class="h-3 w-3" /> {{ row.waktu_tempuh || '-' }}
          </span>
        </div>
      </template>

      <!-- Gunung Induk Column -->
      <template #cell(gunung)="{ row }">
        <div>
          <span class="font-semibold text-foreground text-xs block">{{ row.gunung?.nama_gunung || '-' }}</span>
          <span class="text-[11px] font-mono text-muted-foreground">{{ row.gunung?.tinggi_mdpl }} MDPL</span>
        </div>
      </template>

      <!-- Elevasi & Jarak Column -->
      <template #cell(elevasi_jarak)="{ row }">
        <div>
          <span class="text-xs font-mono font-medium text-foreground block">
            {{ row.titik_awal_mdpl || '-' }} → {{ row.titik_akhir_mdpl || '-' }}
          </span>
          <span class="text-[11px] text-muted-foreground font-mono">{{ row.panjang_jalur || '-' }}</span>
        </div>
      </template>

      <!-- Tingkat Kesulitan Column -->
      <template #cell(tingkat_kesulitan)="{ value }">
        <Badge :class="getDifficultyBadge(value).class" class="capitalize text-xs font-semibold px-2 py-0.5">
          {{ getDifficultyBadge(value).label }}
        </Badge>
      </template>

      <!-- Basecamp Count Column -->
      <template #cell(basecamps_count)="{ row }">
        <div class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
          <Building2 class="h-3.5 w-3.5 text-primary" />
          <span>{{ row.basecamps_count || 0 }} Pos</span>
        </div>
      </template>

      <!-- Status Column & Quick Toggle -->
      <template #cell(status)="{ row }">
        <div class="flex items-center justify-center gap-1.5">
          <Button
            size="sm"
            class="h-7 text-[11px] font-semibold rounded-lg px-2.5 gap-1 transition-all"
            :class="row.status === 'open' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
            @click="toggleStatus(row)"
          >
            <CheckCircle2 v-if="row.status === 'open'" class="h-3.5 w-3.5" />
            <XCircle v-else class="h-3.5 w-3.5" />
            {{ row.status === 'open' ? 'Buka (Open)' : 'Tutup (Close)' }}
          </Button>
        </div>
      </template>

      <!-- Actions Column -->
      <template #cell(actions)="{ row }">
        <div class="flex items-center justify-center gap-1.5">
          <Button
            variant="outline"
            size="sm"
            class="h-7 text-xs rounded-lg gap-1 hover:text-primary hover:border-primary/40"
            @click="openEditModal(row)"
          >
            <Edit2 class="h-3 w-3" /> Edit
          </Button>
          <Button
            variant="outline"
            size="sm"
            class="h-7 text-xs rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300"
            @click="confirmDelete(row)"
          >
            <Trash2 class="h-3 w-3" />
          </Button>
        </div>
      </template>
    </DataTable>

    <!-- Create / Edit Trail Modal -->
    <TrailFormModal
      v-model:open="isFormModalOpen"
      :trail="selectedTrail"
      :mountains="mountains"
      :default-gunung-id="filters.gunung_id ? Number(filters.gunung_id) : undefined"
      @saved="handleSaved"
    />

    <!-- Delete Trail Confirmation Modal -->
    <ConfirmModal
      v-model:open="isDeleteModalOpen"
      title="Hapus Jalur Pendakian?"
      :description="`Apakah Anda yakin ingin menghapus jalur ${trailToDelete?.nama_jalur}? Jalur yang terhubung dengan basecamp aktif tidak dapat dihapus.`"
      confirm-label="Ya, Hapus Jalur"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
    />
  </div>
</template>
