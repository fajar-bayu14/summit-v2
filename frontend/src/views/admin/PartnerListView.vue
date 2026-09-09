<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  Users,
  Building2,
  ShieldCheck,
  ShieldAlert,
  Search,
  Plus,
  RefreshCw,
  Edit2,
  Trash2,
  Eye,
  CreditCard,
  Phone,
  Mail,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { DataTable, type ColumnDef, MetricCard, ConfirmModal } from '@/components/common'
import PartnerFormModal from '@/components/admin/partner/PartnerFormModal.vue'
import PartnerDetailDrawer from '@/components/admin/partner/PartnerDetailDrawer.vue'
import type { Mitra, MitraFilterParams } from '@/types/partner'
import type { PaginationMeta } from '@/types/api'
import { getPartners, deletePartner, updatePartner } from '@/api/partner'
import { useToast } from '@/composables/useToast'
import { extractApiError } from '@/lib/normalizer'

const toast = useToast()

// Data states
const partners = ref<Mitra[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref<'all' | 'aktif' | 'suspend'>('all')

const paginationMeta = ref<PaginationMeta>({
  current_page: 1,
  from: 1,
  last_page: 1,
  per_page: 15,
  to: 1,
  total: 0,
})

// Modal & Drawer states
const isFormModalOpen = ref(false)
const isDetailDrawerOpen = ref(false)
const isConfirmDeleteOpen = ref(false)
const isConfirmStatusOpen = ref(false)
const selectedPartner = ref<Mitra | null>(null)
const isActionLoading = ref(false)

// Metric computations
const totalPartners = computed(() => paginationMeta.value.total || partners.value.length)
const activePartnersCount = computed(() => partners.value.filter(p => p.status === 'aktif').length)
const suspendedPartnersCount = computed(() => partners.value.filter(p => p.status === 'suspend').length)
const totalBasecampsCount = computed(() => {
  return partners.value.reduce((acc, p) => acc + (p.basecamps?.length || 0), 0)
})

const columns: ColumnDef<Mitra>[] = [
  {
    key: 'nama_pemilik',
    label: 'Mitra & Penanggung Jawab',
    sortable: true,
  },
  {
    key: 'kontak',
    label: 'Kontak & Akun',
  },
  {
    key: 'rekening',
    label: 'Rekening Bank',
  },
  {
    key: 'basecamp_count',
    label: 'Basecamp Dikelola',
    align: 'center',
  },
  {
    key: 'status',
    label: 'Status',
    align: 'center',
    sortable: true,
  },
  {
    key: 'actions',
    label: 'Aksi',
    align: 'right',
  },
]

async function fetchData(page = 1) {
  isLoading.value = true
  try {
    const params: MitraFilterParams = {
      page,
      per_page: paginationMeta.value.per_page,
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }

    if (selectedStatus.value !== 'all') {
      params.status = selectedStatus.value
    }

    const res = await getPartners(params)
    partners.value = res.items
    paginationMeta.value = res.meta
  } catch (err: any) {
    const { message } = extractApiError(err)
    toast.error(message, 'Gagal Memuat Data Mitra')
  } finally {
    isLoading.value = false
  }
}

// Search debounce watcher
let searchDebounceTimer: any = null
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    fetchData(1)
  }, 350)
})

function handleStatusFilter(status: 'all' | 'aktif' | 'suspend') {
  selectedStatus.value = status
  fetchData(1)
}

function handlePageChange(newPage: number) {
  fetchData(newPage)
}

function openCreateModal() {
  selectedPartner.value = null
  isFormModalOpen.value = true
}

function openEditModal(mitra: Mitra) {
  selectedPartner.value = mitra
  isDetailDrawerOpen.value = false
  isFormModalOpen.value = true
}

function openDetailDrawer(mitra: Mitra) {
  selectedPartner.value = mitra
  isDetailDrawerOpen.value = true
}

function openDeleteConfirm(mitra: Mitra) {
  selectedPartner.value = mitra
  isDetailDrawerOpen.value = false
  isConfirmDeleteOpen.value = true
}

function openToggleStatusConfirm(mitra: Mitra) {
  selectedPartner.value = mitra
  isDetailDrawerOpen.value = false
  isConfirmStatusOpen.value = true
}

async function handleConfirmDelete() {
  if (!selectedPartner.value) return
  isActionLoading.value = true
  try {
    await deletePartner(selectedPartner.value.id)
    toast.success(`Akun dan data profil mitra "${selectedPartner.value.nama_pemilik}" telah dihapus.`, 'Mitra Dihapus')
    isConfirmDeleteOpen.value = false
    fetchData(paginationMeta.value.current_page)
  } catch (err: any) {
    const { message } = extractApiError(err)
    toast.error(message, 'Gagal Menghapus Mitra')
  } finally {
    isActionLoading.value = false
  }
}

async function handleConfirmToggleStatus() {
  if (!selectedPartner.value) return
  isActionLoading.value = true
  const newStatus = selectedPartner.value.status === 'aktif' ? 'suspend' : 'aktif'
  try {
    await updatePartner(selectedPartner.value.id, {
      status: newStatus,
    })
    toast.success(
      `Status mitra "${selectedPartner.value.nama_pemilik}" berhasil diubah menjadi ${newStatus}.`,
      newStatus === 'aktif' ? 'Mitra Diaktifkan' : 'Mitra Disuspend'
    )
    isConfirmStatusOpen.value = false
    fetchData(paginationMeta.value.current_page)
  } catch (err: any) {
    const { message } = extractApiError(err)
    toast.error(message, 'Gagal Mengubah Status Mitra')
  } finally {
    isActionLoading.value = false
  }
}

onMounted(() => {
  fetchData(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-foreground">
          Mitra Pengelola Basecamp
        </h1>
        <p class="text-xs text-muted-foreground mt-1">
          Tata kelola akun otentikasi mitra, informasi legalitas identitas, rekening pencairan, dan relasi basecamp.
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
          <span>Tambah Mitra Baru</span>
        </Button>
      </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <MetricCard
        title="Total Mitra Terdaftar"
        :value="totalPartners"
        :icon="Users"
        description="Akun entitas mitra dalam sistem"
        icon-class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
      />
      <MetricCard
        title="Mitra Aktif"
        :value="activePartnersCount"
        :icon="ShieldCheck"
        description="Operasional penuh melayani pendaki"
        icon-class="bg-emerald-500/10 text-emerald-500"
      />
      <MetricCard
        title="Mitra Disuspend"
        :value="suspendedPartnersCount"
        :icon="ShieldAlert"
        description="Akses login portal dibekukan"
        icon-class="bg-rose-500/10 text-rose-500"
      />
      <MetricCard
        title="Total Basecamp Dikelola"
        :value="totalBasecampsCount"
        :icon="Building2"
        description="Fasilitas pos registrasi terhubung"
        icon-class="bg-sky-500/10 text-sky-500"
      />
    </div>

    <!-- Data Table Container -->
    <div class="bg-card border rounded-xl shadow-xs overflow-hidden">
      <!-- Table Filters & Search Bar -->
      <div class="p-4 border-b flex flex-col md:flex-row items-start md:items-center justify-between gap-3 bg-muted/10">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-muted/40 rounded-lg border border-border/50 text-xs">
          <button
            type="button"
            class="py-1 px-3 rounded-md font-medium transition-all"
            :class="selectedStatus === 'all' ? 'bg-background text-foreground shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="handleStatusFilter('all')"
          >
            Semua ({{ totalPartners }})
          </button>
          <button
            type="button"
            class="py-1 px-3 rounded-md font-medium transition-all"
            :class="selectedStatus === 'aktif' ? 'bg-background text-emerald-600 dark:text-emerald-400 shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="handleStatusFilter('aktif')"
          >
            🟢 Aktif
          </button>
          <button
            type="button"
            class="py-1 px-3 rounded-md font-medium transition-all"
            :class="selectedStatus === 'suspend' ? 'bg-background text-rose-600 dark:text-rose-400 shadow-xs font-semibold' : 'text-muted-foreground hover:text-foreground'"
            @click="handleStatusFilter('suspend')"
          >
            🔴 Suspend
          </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full md:w-72">
          <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Cari nama, NIK, email, rekening..."
            class="pl-9 h-9 text-xs"
          />
        </div>
      </div>

      <!-- Main DataTable -->
      <DataTable
        :columns="columns"
        :data="partners"
        :loading="isLoading"
        :pagination="paginationMeta"
        @page-change="handlePageChange"
      >
        <!-- Cell: Nama Pemilik & Identitas -->
        <template #cell(nama_pemilik)="{ row }">
          <div class="flex items-center gap-3 py-1">
            <Avatar class="h-9 w-9 rounded-lg border border-border shrink-0">
              <AvatarFallback class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-bold text-xs">
                {{ row.nama_pemilik?.slice(0, 2).toUpperCase() || 'MT' }}
              </AvatarFallback>
            </Avatar>
            <div class="flex flex-col min-w-0">
              <span class="font-bold text-xs text-foreground truncate">{{ row.nama_pemilik }}</span>
              <span class="text-[11px] font-mono text-muted-foreground">NIK: {{ row.nik }}</span>
            </div>
          </div>
        </template>

        <!-- Cell: Kontak & Akun -->
        <template #cell(kontak)="{ row }">
          <div class="flex flex-col gap-0.5 text-xs">
            <span class="text-foreground flex items-center gap-1.5 truncate">
              <Mail class="h-3 w-3 text-muted-foreground" />
              <span>{{ row.user?.email || '-' }}</span>
            </span>
            <a
              :href="'https://wa.me/' + row.telepon.replace(/\D/g, '')"
              target="_blank"
              class="text-emerald-600 hover:underline flex items-center gap-1.5 text-[11px]"
            >
              <Phone class="h-3 w-3" />
              <span>{{ row.telepon }}</span>
            </a>
          </div>
        </template>

        <!-- Cell: Rekening Bank -->
        <template #cell(rekening)="{ row }">
          <div class="flex flex-col gap-0.5 text-xs">
            <span class="font-semibold text-foreground flex items-center gap-1.5">
              <CreditCard class="h-3 w-3 text-muted-foreground" />
              <span>{{ row.bank }}</span>
            </span>
            <span class="font-mono text-[11px] text-muted-foreground">{{ row.rekening_bank }}</span>
            <span class="text-[10px] text-muted-foreground/80 truncate">a.n {{ row.nama_rekening }}</span>
          </div>
        </template>

        <!-- Cell: Basecamp Dikelola -->
        <template #cell(basecamp_count)="{ row }">
          <div class="flex items-center justify-center gap-1.5">
            <Badge variant="secondary" class="text-xs font-semibold px-2 py-0.5">
              <Building2 class="h-3 w-3 mr-1 text-emerald-600" />
              <span>{{ row.basecamps?.length || 0 }} Basecamp</span>
            </Badge>
          </div>
        </template>

        <!-- Cell: Status Badge -->
        <template #cell(status)="{ row }">
          <div class="flex justify-center">
            <Badge
              variant="outline"
              :class="
                row.status === 'aktif'
                  ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                  : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
              "
              class="text-[11px] font-semibold"
            >
              {{ row.status === 'aktif' ? '🟢 Aktif' : '🔴 Suspend' }}
            </Badge>
          </div>
        </template>

        <!-- Cell: Actions -->
        <template #cell(actions)="{ row }">
          <div class="flex items-center justify-end gap-1.5">
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-foreground"
              title="Lihat Detail Profil & Basecamp"
              @click="openDetailDrawer(row)"
            >
              <Eye class="h-4 w-4" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-foreground"
              title="Edit Data Mitra"
              @click="openEditModal(row)"
            >
              <Edit2 class="h-4 w-4" />
            </Button>

            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
              title="Hapus Mitra"
              @click="openDeleteConfirm(row)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Partner Create / Edit Modal -->
    <PartnerFormModal
      v-model:open="isFormModalOpen"
      :mitra="selectedPartner"
      @success="fetchData(paginationMeta.current_page)"
    />

    <!-- Partner Detail Slide-over Drawer -->
    <PartnerDetailDrawer
      v-model:open="isDetailDrawerOpen"
      :mitra="selectedPartner"
      @edit="openEditModal"
      @delete="openDeleteConfirm"
      @toggle-status="openToggleStatusConfirm"
    />

    <!-- Confirm Delete Modal -->
    <ConfirmModal
      v-model:open="isConfirmDeleteOpen"
      title="Hapus Akun & Profil Mitra?"
      :description="`Tindakan ini akan menghapus akun login portal dan profil bisnis mitra '${selectedPartner?.nama_pemilik}'. Seluruh relasi operasional akan terhapus secara permanen.`"
      confirm-text="Ya, Hapus Mitra"
      variant="danger"
      :loading="isActionLoading"
      @confirm="handleConfirmDelete"
    />

    <!-- Confirm Toggle Status Modal -->
    <ConfirmModal
      v-model:open="isConfirmStatusOpen"
      :title="selectedPartner?.status === 'aktif' ? 'Suspend Akun Mitra?' : 'Aktifkan Kembali Mitra?'"
      :description="
        selectedPartner?.status === 'aktif'
          ? `Mitra '${selectedPartner?.nama_pemilik}' tidak akan dapat login ke Portal Mitra dan operasional basecamp binaannya akan dihentikan sementara.`
          : `Mitra '${selectedPartner?.nama_pemilik}' akan kembali dapat mengakses portal dan melanjutkan operasional reservasi.`
      "
      :confirm-text="selectedPartner?.status === 'aktif' ? 'Ya, Suspend' : 'Ya, Aktifkan'"
      :variant="selectedPartner?.status === 'aktif' ? 'danger' : 'primary'"
      :loading="isActionLoading"
      @confirm="handleConfirmToggleStatus"
    />
  </div>
</template>
