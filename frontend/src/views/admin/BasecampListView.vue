<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  Building2,
  Compass,
  Clock,
  Users,
  Search,
  Plus,
  RefreshCw,
  Edit2,
  Trash2,
  Eye,
  MapPin,
  ExternalLink,
  Phone,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { DataTable, type ColumnDef, MetricCard, ConfirmModal } from '@/components/common'
import BasecampFormModal from '@/components/admin/basecamp/BasecampFormModal.vue'
import BasecampDetailModal from '@/components/admin/basecamp/BasecampDetailModal.vue'
import type { Basecamp, BasecampFilterParams } from '@/types/basecamp'
import type { Mitra } from '@/types/partner'
import type { PaginationMeta } from '@/types/api'
import { getBasecamps, deleteBasecamp } from '@/api/basecamp'
import { getPartners } from '@/api/partner'
import { useToast } from '@/composables/useToast'
import { extractApiError } from '@/lib/normalizer'

const toast = useToast()

// Data states
const basecamps = ref<Basecamp[]>([])
const partners = ref<Mitra[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedMitraId = ref<string>('all')

const paginationMeta = ref<PaginationMeta>({
  current_page: 1,
  from: 1,
  last_page: 1,
  per_page: 15,
  to: 1,
  total: 0,
})

// Modal states
const isFormModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const isConfirmDeleteOpen = ref(false)
const selectedBasecamp = ref<Basecamp | null>(null)
const isActionLoading = ref(false)

// Metric computations
const totalBasecamps = computed(() => paginationMeta.value.total || basecamps.value.length)
const count24Hours = computed(() => basecamps.value.filter(b => b.jam_operasional?.toLowerCase().includes('24')).length)
const uniqueTrailsCount = computed(() => {
  const set = new Set(basecamps.value.map(b => b.jalur_id).filter(Boolean))
  return set.size
})
const uniquePartnersCount = computed(() => {
  const set = new Set(basecamps.value.map(b => b.mitra_id).filter(Boolean))
  return set.size
})

const columns: ColumnDef<Basecamp>[] = [
  {
    key: 'nama_basecamp',
    label: 'Nama Pos Basecamp',
    sortable: true,
  },
  {
    key: 'jalur_info',
    label: 'Gunung & Jalur Pendakian',
  },
  {
    key: 'mitra_info',
    label: 'Mitra Pengelola',
  },
  {
    key: 'koordinat',
    label: 'Titik Koordinat GPS',
  },
  {
    key: 'actions',
    label: 'Aksi',
    align: 'right',
  },
]

async function loadPartnersList() {
  try {
    const res = await getPartners({ per_page: 100 })
    partners.value = res.items
  } catch (err) {
    console.error('Failed to load partners filter list', err)
  }
}

async function fetchData(page = 1) {
  isLoading.value = true
  try {
    const params: BasecampFilterParams = {
      page,
      per_page: paginationMeta.value.per_page,
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    if (selectedMitraId.value !== 'all') {
      params.mitra_id = Number(selectedMitraId.value)
    }

    const res = await getBasecamps(params)
    basecamps.value = res.items
    paginationMeta.value = res.meta
  } catch (err: any) {
    const { message } = extractApiError(err)
    toast.error(message, 'Gagal Memuat Data Basecamp')
  } finally {
    isLoading.value = false
  }
}

// Search debounce
let searchDebounceTimer: any = null
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    fetchData(1)
  }, 350)
})

watch(selectedMitraId, () => {
  fetchData(1)
})

function handlePageChange(newPage: number) {
  fetchData(newPage)
}

function openCreateModal() {
  selectedBasecamp.value = null
  isFormModalOpen.value = true
}

function openEditModal(bc: Basecamp) {
  selectedBasecamp.value = bc
  isDetailModalOpen.value = false
  isFormModalOpen.value = true
}

function openDetailModal(bc: Basecamp) {
  selectedBasecamp.value = bc
  isDetailModalOpen.value = true
}

function openDeleteConfirm(bc: Basecamp) {
  selectedBasecamp.value = bc
  isDetailModalOpen.value = false
  isConfirmDeleteOpen.value = true
}

async function handleConfirmDelete() {
  if (!selectedBasecamp.value) return
  isActionLoading.value = true
  try {
    await deleteBasecamp(selectedBasecamp.value.id)
    toast.success(`Pos basecamp "${selectedBasecamp.value.nama_basecamp}" telah dihapus dari sistem.`, 'Basecamp Dihapus')
    isConfirmDeleteOpen.value = false
    fetchData(paginationMeta.value.current_page)
  } catch (err: any) {
    const { message } = extractApiError(err)
    toast.error(message, 'Gagal Menghapus Basecamp')
  } finally {
    isActionLoading.value = false
  }
}

onMounted(() => {
  loadPartnersList()
  fetchData(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-foreground">
          Direktori & Pemetaan Basecamp
        </h1>
        <p class="text-xs text-muted-foreground mt-1">
          Tata kelola pos registrasi pendakian resmi, penugasan mitra operasional, koordinat geolokasi, dan jam layanan.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="text-xs gap-1.5"
          :disabled="isLoading"
          @click="fetchData(paginationMeta.current_page)"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Segarkan</span>
        </Button>

        <Button
          size="sm"
          class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white gap-1.5 shadow-xs"
          @click="openCreateModal"
        >
          <Plus class="h-3.5 w-3.5" />
          <span>Tambah Basecamp</span>
        </Button>
      </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <MetricCard
        title="Total Basecamp"
        :value="totalBasecamps"
        :icon="Building2"
        description="Pos registrasi operasional aktif"
        icon-class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
      />
      <MetricCard
        title="Jalur Terhubung"
        :value="uniqueTrailsCount"
        :icon="Compass"
        description="Jalur pendakian yang dinaungi"
        icon-class="bg-sky-500/10 text-sky-500"
      />
      <MetricCard
        title="Buka 24 Jam"
        :value="count24Hours"
        :icon="Clock"
        description="Layanan registrasi non-stop"
        icon-class="bg-amber-500/10 text-amber-500"
      />
      <MetricCard
        title="Mitra Pengelola"
        :value="uniquePartnersCount"
        :icon="Users"
        description="Badan usaha mitra penanggung jawab"
        icon-class="bg-emerald-500/10 text-emerald-500"
      />
    </div>

    <!-- Main Table Container -->
    <div class="bg-card border rounded-xl shadow-xs overflow-hidden">
      <!-- Filter Bar -->
      <div class="p-4 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-muted/10">
        <!-- Mitra Selector Filter -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <Label for="filter-mitra" class="text-xs font-semibold text-muted-foreground whitespace-nowrap">Filter Mitra:</Label>
          <select
            id="filter-mitra"
            v-model="selectedMitraId"
            class="h-9 w-full sm:w-64 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          >
            <option value="all">Semua Mitra Pengelola</option>
            <option v-for="m in partners" :key="m.id" :value="String(m.id)">
              {{ m.nama_pemilik }}
            </option>
          </select>
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-72">
          <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Cari basecamp, gunung, jalur..."
            class="pl-9 h-9 text-xs bg-background"
          />
        </div>
      </div>

      <!-- Main DataTable -->
      <DataTable
        :columns="columns"
        :data="basecamps"
        :loading="isLoading"
        :pagination="paginationMeta"
        @page-change="handlePageChange"
      >
        <!-- Cell: Nama Basecamp & Jam Operasional -->
        <template #cell(nama_basecamp)="{ row }">
          <div class="flex items-start gap-3 py-1">
            <div class="h-9 w-9 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
              <Building2 class="h-4 w-4" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-bold text-xs text-foreground truncate">{{ row.nama_basecamp }}</span>
              <span class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                <Clock class="h-3 w-3 text-emerald-600 shrink-0" />
                <span>{{ row.jam_operasional }}</span>
              </span>
            </div>
          </div>
        </template>

        <!-- Cell: Gunung & Jalur -->
        <template #cell(jalur_info)="{ row }">
          <div class="flex flex-col gap-0.5 text-xs">
            <span class="font-semibold text-foreground flex items-center gap-1">
              <Compass class="h-3.5 w-3.5 text-emerald-600" />
              <span>{{ row.jalur?.gunung?.nama_gunung || '-' }}</span>
            </span>
            <span class="text-[11px] text-muted-foreground">{{ row.jalur?.nama_jalur || '-' }}</span>
            <span v-if="row.jalur?.titik_awal_mdpl" class="text-[10px] text-muted-foreground/80">
              {{ row.jalur?.titik_awal_mdpl }} → {{ row.jalur?.titik_akhir_mdpl }}
            </span>
          </div>
        </template>

        <!-- Cell: Mitra Pengelola -->
        <template #cell(mitra_info)="{ row }">
          <div class="flex flex-col gap-0.5 text-xs">
            <span class="font-semibold text-foreground flex items-center gap-1">
              <Users class="h-3.5 w-3.5 text-muted-foreground" />
              <span>{{ row.mitra?.nama_pemilik || '-' }}</span>
            </span>
            <span v-if="row.mitra?.telepon" class="text-[11px] text-emerald-600 flex items-center gap-1">
              <Phone class="h-3 w-3" />
              <span>{{ row.mitra?.telepon }}</span>
            </span>
          </div>
        </template>

        <!-- Cell: Titik Koordinat GPS -->
        <template #cell(koordinat)="{ row }">
          <div class="flex flex-col gap-1 text-xs">
            <a
              :href="`https://www.google.com/maps?q=${row.latitude},${row.longitude}`"
              target="_blank"
              class="font-mono text-[11px] text-emerald-600 hover:underline flex items-center gap-1 font-medium"
              title="Buka titik koordinat di Google Maps"
            >
              <MapPin class="h-3.5 w-3.5 shrink-0" />
              <span class="truncate max-w-[140px]">{{ row.latitude }}, {{ row.longitude }}</span>
              <ExternalLink class="h-3 w-3 shrink-0" />
            </a>
          </div>
        </template>

        <!-- Cell: Actions -->
        <template #cell(actions)="{ row }">
          <div class="flex items-center justify-end gap-1.5">
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-foreground"
              title="Lihat Detail Basecamp"
              @click="openDetailModal(row)"
            >
              <Eye class="h-4 w-4" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-foreground"
              title="Edit Data Basecamp"
              @click="openEditModal(row)"
            >
              <Edit2 class="h-4 w-4" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
              title="Hapus Basecamp"
              @click="openDeleteConfirm(row)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Modal -->
    <BasecampFormModal
      v-model:open="isFormModalOpen"
      :basecamp="selectedBasecamp"
      @success="fetchData(paginationMeta.current_page)"
    />

    <!-- Quick Detail Modal -->
    <BasecampDetailModal
      v-model:open="isDetailModalOpen"
      :basecamp="selectedBasecamp"
      @edit="openEditModal"
    />

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      v-model:open="isConfirmDeleteOpen"
      title="Hapus Basecamp?"
      :description="`Apakah Anda yakin ingin menghapus pos basecamp '${selectedBasecamp?.nama_basecamp}'? Tindakan ini tidak dapat dibatalkan.`"
      confirm-text="Ya, Hapus Basecamp"
      variant="danger"
      :loading="isActionLoading"
      @confirm="handleConfirmDelete"
    />
  </div>
</template>
