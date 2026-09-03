<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Mountain,
  Plus,
  Search,
  RefreshCw,
  Edit2,
  Trash2,
  Compass,
  MapPin,
  TrendingUp,
  LayoutGrid,
  List,
  CheckCircle2,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { DataTable, type ColumnDef, MetricCard, ConfirmModal, EmptyState } from '@/components/common'
import MountainFormModal from '@/components/admin/mountain/MountainFormModal.vue'
import { mountainApi } from '@/api/mountain'
import { useToast } from '@/composables/useToast'
import { getStorageUrl } from '@/lib/storage'
import type { Gunung, MountainStatus } from '@/types/mountain'

const router = useRouter()
const toast = useToast()

const loading = ref<boolean>(true)
const mountains = ref<Gunung[]>([])
const viewMode = ref<'grid' | 'table'>('grid')

// Search & filter
const searchQuery = ref<string>('')
const statusFilter = ref<MountainStatus | ''>('')

// Modals
const isFormModalOpen = ref<boolean>(false)
const selectedGunung = ref<Gunung | null>(null)

const isDeleteModalOpen = ref<boolean>(false)
const gunungToDelete = ref<Gunung | null>(null)
const deleting = ref<boolean>(false)

const columns: ColumnDef<Gunung>[] = [
  { key: 'foto', label: 'Cover', width: '90px', align: 'center' },
  { key: 'nama_gunung', label: 'Nama Gunung' },
  { key: 'tinggi_mdpl', label: 'Ketinggian', width: '130px', align: 'center' },
  { key: 'lokasi', label: 'Lokasi Wilayah' },
  { key: 'jalur_count', label: 'Jalur Aktif', width: '110px', align: 'center' },
  { key: 'status', label: 'Status', width: '110px', align: 'center' },
  { key: 'actions', label: 'Aksi', width: '160px', align: 'center' },
]

onMounted(() => {
  fetchMountains()
})

async function fetchMountains() {
  loading.value = true
  try {
    const res = await mountainApi.getMountains({ per_page: 50 })
    mountains.value = res.items || []
  } catch (err: any) {
    mountains.value = []
    toast.error(err?.message || 'Gagal memuat katalog gunung.')
  } finally {
    loading.value = false
  }
}

const filteredMountains = computed(() => {
  const list = Array.isArray(mountains.value) ? mountains.value : []
  return list.filter((g) => {
    const matchesSearch =
      !searchQuery.value.trim() ||
      g.nama_gunung?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      g.lokasi?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesStatus = !statusFilter.value || g.status === statusFilter.value
    return matchesSearch && matchesStatus
  })
})

const stats = computed(() => {
  const list = Array.isArray(mountains.value) ? mountains.value : []
  const total = list.length
  const active = list.filter(g => g.status === 'aktif').length
  const totalTrails = list.reduce((acc, g) => acc + (g.jalurs?.length || g.jalur_pendakians?.length || 0), 0)
  const maxElevation = list.reduce((max, g) => (g.tinggi_mdpl > max ? g.tinggi_mdpl : max), 0)

  return { total, active, totalTrails, maxElevation }
})



function openCreateModal() {
  selectedGunung.value = null
  isFormModalOpen.value = true
}

function openEditModal(gunung: Gunung) {
  selectedGunung.value = gunung
  isFormModalOpen.value = true
}

function confirmDelete(gunung: Gunung) {
  gunungToDelete.value = gunung
  isDeleteModalOpen.value = true
}

async function handleDelete() {
  if (!gunungToDelete.value) return
  deleting.value = true

  try {
    await mountainApi.deleteMountain(gunungToDelete.value.id)
    toast.success(`Gunung ${gunungToDelete.value.nama_gunung} berhasil dihapus.`)
    mountains.value = mountains.value.filter(g => g.id !== gunungToDelete.value?.id)
    isDeleteModalOpen.value = false
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Gagal menghapus data gunung.')
  } finally {
    deleting.value = false
  }
}

function handleSaved(savedGunung: Gunung) {
  const idx = mountains.value.findIndex(g => g.id === savedGunung.id)
  if (idx !== -1) {
    // Merge existing relations (like jalurs) if not present in saved response
    const existing = mountains.value[idx]
    mountains.value[idx] = {
      ...existing,
      ...savedGunung,
      jalurs: savedGunung.jalurs || existing.jalurs,
      jalur_pendakians: savedGunung.jalur_pendakians || existing.jalur_pendakians,
    }
    mountains.value = [...mountains.value]
  } else {
    mountains.value = [savedGunung, ...mountains.value]
  }
}

function navigateToTrails(gunungId: number) {
  router.push({
    path: '/admin/trails',
    query: { gunung_id: String(gunungId) },
  })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
          <Mountain class="h-7 w-7 text-primary" />
          Master Destinasi Gunung
        </h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Direktori gunung nasional, elevasi puncak MDPL, peta sebaran wilayah, dan pengelolaan jalur pendakian.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <Button variant="outline" size="sm" class="h-9 gap-1.5 text-xs rounded-xl" @click="fetchMountains">
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" /> Segarkan
        </Button>

        <Button size="sm" class="h-9 gap-1.5 text-xs rounded-xl bg-primary text-primary-foreground shadow-xs" @click="openCreateModal">
          <Plus class="h-4 w-4" /> Tambah Gunung
        </Button>
      </div>
    </div>

    <!-- Analytics Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <MetricCard
        title="Total Gunung"
        :value="stats.total"
        :icon="Mountain"
        description="Destinasi resmi terdaftar"
        icon-class="bg-emerald-500/10 text-emerald-600"
      />
      <MetricCard
        title="Gunung Aktif (Buka)"
        :value="stats.active"
        :icon="CheckCircle2"
        description="Siap menerima reservasi tiket"
        icon-class="bg-blue-500/10 text-blue-600"
      />
      <MetricCard
        title="Total Jalur Terhubung"
        :value="stats.totalTrails"
        :icon="Compass"
        description="Pos registrasi jalur pendakian"
        icon-class="bg-amber-500/10 text-amber-600"
      />
      <MetricCard
        title="Puncak Tertinggi"
        :value="stats.maxElevation ? `${stats.maxElevation} MDPL` : '—'"
        :icon="TrendingUp"
        description="Elevasi tertinggi dalam katalog"
        icon-class="bg-purple-500/10 text-purple-600"
      />
    </div>

    <!-- Toolbar & Search -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 bg-card p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
      <div class="flex items-center gap-2">
        <div class="relative w-full md:w-72">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Cari nama gunung / provinsi..."
            class="h-9 pl-9 text-xs rounded-xl"
          />
        </div>

        <select
          v-model="statusFilter"
          class="h-9 px-3 bg-card border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
        >
          <option value="">Semua Status</option>
          <option value="aktif">Aktif (Buka)</option>
          <option value="tidak_aktif">Tidak Aktif (Tutup)</option>
        </select>
      </div>

      <!-- View Switcher -->
      <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl shrink-0">
        <Button
          variant="ghost"
          size="sm"
          class="h-7 px-2.5 text-xs rounded-lg gap-1.5"
          :class="{ 'bg-card text-foreground shadow-xs font-semibold': viewMode === 'grid' }"
          @click="viewMode = 'grid'"
        >
          <LayoutGrid class="h-3.5 w-3.5" /> Grid
        </Button>
        <Button
          variant="ghost"
          size="sm"
          class="h-7 px-2.5 text-xs rounded-lg gap-1.5"
          :class="{ 'bg-card text-foreground shadow-xs font-semibold': viewMode === 'table' }"
          @click="viewMode = 'table'"
        >
          <List class="h-3.5 w-3.5" /> Tabel
        </Button>
      </div>
    </div>

    <!-- Grid View Mode -->
    <div v-if="viewMode === 'grid'">
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="i in 6" :key="i" class="h-80 rounded-2xl bg-card border animate-pulse p-4 space-y-3">
          <div class="h-44 bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
          <div class="h-5 bg-slate-200 dark:bg-slate-800 rounded w-2/3"></div>
          <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="filteredMountains.length === 0" class="py-12 bg-card rounded-2xl border border-dashed text-center">
        <EmptyState
          title="Tidak Ada Destinasi Gunung"
          :description="searchQuery ? `Tidak ada gunung dengan kata kunci '${searchQuery}'.` : 'Belum ada data destinasi gunung yang terdaftar.'"
          :icon="Mountain"
          :action-label="searchQuery ? 'Reset Pencarian' : 'Tambah Gunung Sekarang'"
          @action="searchQuery ? (searchQuery = '') : openCreateModal()"
        />
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="gunung in filteredMountains"
          :key="gunung.id"
          class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card overflow-hidden shadow-xs hover:shadow-md transition-all duration-200 flex flex-col group"
        >
          <!-- Cover Image Container -->
          <div class="relative h-48 overflow-hidden bg-slate-950/20">
            <img
              :src="getStorageUrl(gunung.foto)"
              :alt="gunung.nama_gunung"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20"></div>

            <!-- Top Badges -->
            <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2">
              <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-black/60 text-white backdrop-blur-xs">
                {{ gunung.tinggi_mdpl }} MDPL
              </span>

              <Badge
                :class="gunung.status === 'aktif' ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white'"
                class="capitalize text-[11px] font-semibold border-none shadow-xs"
              >
                {{ gunung.status === 'aktif' ? 'Dibuka' : 'Ditutup' }}
              </Badge>
            </div>

            <!-- Title on Photo -->
            <div class="absolute bottom-3 left-3 right-3 text-white">
              <h3 class="text-lg font-bold leading-tight">{{ gunung.nama_gunung }}</h3>
              <p class="text-xs text-white/80 flex items-center gap-1 mt-0.5">
                <MapPin class="h-3 w-3 shrink-0" /> {{ gunung.lokasi }}
              </p>
            </div>
          </div>

          <!-- Body Description & Trails -->
          <div class="p-4 flex-1 flex flex-col justify-between space-y-4">
            <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
              {{ gunung.deskripsi || 'Tidak ada keterangan deskripsi.' }}
            </p>

            <div class="pt-3 border-t flex items-center justify-between gap-2">
              <!-- Linked Trails Counter -->
              <Button
                variant="ghost"
                size="sm"
                class="h-8 px-2 text-xs font-semibold gap-1.5 text-primary hover:bg-primary/10"
                @click="navigateToTrails(gunung.id)"
              >
                <Compass class="h-3.5 w-3.5" />
                <span>{{ gunung.jalurs?.length || gunung.jalur_pendakians?.length || 0 }} Jalur</span>
              </Button>

              <!-- Actions -->
              <div class="flex items-center gap-1">
                <Button
                  variant="outline"
                  size="icon"
                  class="h-8 w-8 rounded-lg hover:text-primary hover:border-primary/40"
                  title="Edit Data Gunung"
                  @click="openEditModal(gunung)"
                >
                  <Edit2 class="h-3.5 w-3.5" />
                </Button>

                <Button
                  variant="outline"
                  size="icon"
                  class="h-8 w-8 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300"
                  title="Hapus Gunung"
                  @click="confirmDelete(gunung)"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View Mode -->
    <div v-else>
      <DataTable
        :columns="columns"
        :data="filteredMountains"
        :loading="loading"
        empty-title="Tidak Ada Data Gunung"
        :empty-description="searchQuery ? `Pencarian '${searchQuery}' tidak ditemukan.` : 'Belum ada data destinasi gunung.'"
        @empty-action="searchQuery = ''"
      >
        <template #cell(foto)="{ value, row }">
          <div class="h-10 w-14 rounded-lg overflow-hidden bg-muted shrink-0 mx-auto">
            <img :src="getStorageUrl(value)" :alt="row.nama_gunung" class="h-full w-full object-cover" />
          </div>
        </template>

        <template #cell(nama_gunung)="{ row }">
          <div>
            <strong class="text-sm font-bold text-foreground block">{{ row.nama_gunung }}</strong>
            <span class="text-xs text-muted-foreground line-clamp-1">{{ row.deskripsi }}</span>
          </div>
        </template>

        <template #cell(tinggi_mdpl)="{ value }">
          <span class="font-mono font-bold text-xs text-foreground">{{ value }} MDPL</span>
        </template>

        <template #cell(jalur_count)="{ row }">
          <Button
            variant="ghost"
            size="sm"
            class="h-7 text-xs font-semibold gap-1 text-primary hover:underline"
            @click="navigateToTrails(row.id)"
          >
            <Compass class="h-3.5 w-3.5" /> {{ row.jalurs?.length || row.jalur_pendakians?.length || 0 }} Jalur
          </Button>
        </template>

        <template #cell(status)="{ value }">
          <Badge
            :class="value === 'aktif' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-red-500/10 text-red-600 border-red-500/20'"
            class="capitalize text-xs font-semibold"
          >
            {{ value === 'aktif' ? 'Dibuka' : 'Ditutup' }}
          </Badge>
        </template>

        <template #cell(actions)="{ row }">
          <div class="flex items-center justify-center gap-1.5">
            <Button
              variant="outline"
              size="sm"
              class="h-7 text-xs rounded-lg gap-1"
              @click="openEditModal(row)"
            >
              <Edit2 class="h-3 w-3" /> Edit
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="h-7 text-xs rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
              @click="confirmDelete(row)"
            >
              <Trash2 class="h-3 w-3" />
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create / Edit Mountain Modal -->
    <MountainFormModal
      v-model:open="isFormModalOpen"
      :gunung="selectedGunung"
      @saved="handleSaved"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      v-model:open="isDeleteModalOpen"
      title="Hapus Destinasi Gunung?"
      :description="`Apakah Anda yakin ingin menghapus data destinasi ${gunungToDelete?.nama_gunung}? Seluruh jalur pendakian yang terkait akan terpengaruh.`"
      confirm-label="Ya, Hapus Gunung"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
    />
  </div>
</template>
