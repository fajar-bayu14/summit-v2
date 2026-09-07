<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraLogbooksApi from '@/api/mitraLogbooks'
import type { LogbookEntry, LogbookValidationStatus, LogbookFilterParams } from '@/types/logbook'
import { StatusBadge } from '@/components/common'
import SummitProofModal from '@/components/mitra/logbook/SummitProofModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Mountain,
  Search,
  RefreshCw,
  Clock,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Award,
  Eye,
  LayoutGrid,
  List,
  X,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const loading = ref(false)
const errorMessage = ref<string | null>(null)
const logbooks = ref<LogbookEntry[]>([])
const selectedLogbook = ref<LogbookEntry | null>(null)
const isModalOpen = ref(false)
const viewMode = ref<'grid' | 'table'>('grid')

// Metrics
const metrics = reactive({
  pending: 0,
  approved: 0,
  rejected: 0,
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
  status_validasi: LogbookValidationStatus | ''
}>({
  search: '',
  status_validasi: '',
})

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

const statusTabs: { label: string; value: LogbookValidationStatus | '' }[] = [
  { label: 'Semua Status', value: '' },
  { label: 'Menunggu Validasi', value: 'pending' },
  { label: 'Disetujui', value: 'approved' },
  { label: 'Ditolak', value: 'rejected' },
]

onMounted(() => {
  fetchLogbooks()
})

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

async function fetchLogbooks(page = pagination.currentPage) {
  loading.value = true
  errorMessage.value = null

  try {
    const params: LogbookFilterParams = {
      page,
      per_page: pagination.perPage,
    }

    if (filters.status_validasi) {
      params.status_validasi = filters.status_validasi
    }
    if (filters.search.trim()) {
      params.search = filters.search.trim()
    }

    const res = await mitraLogbooksApi.getLogbooks(params)

    if (Array.isArray(res.data)) {
      logbooks.value = res.data
      pagination.total = res.data.length
      pagination.currentPage = 1
      pagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      logbooks.value = res.data.data
      pagination.total = res.data.total ?? res.data.data.length
      pagination.currentPage = res.data.current_page ?? 1
      pagination.lastPage = res.data.last_page ?? 1
      pagination.perPage = res.data.per_page ?? 15
    }

    recalculateMetrics()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat data logbook summit.')
  } finally {
    loading.value = false
  }
}

function recalculateMetrics() {
  let pendingCount = 0
  let approvedCount = 0
  let rejectedCount = 0

  logbooks.value.forEach((l) => {
    if (l.status_validasi === 'pending') pendingCount++
    else if (l.status_validasi === 'approved') approvedCount++
    else if (l.status_validasi === 'rejected') rejectedCount++
  })

  metrics.pending = pendingCount
  metrics.approved = approvedCount
  metrics.rejected = rejectedCount
}

function handleSearchInput() {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1
    fetchLogbooks(1)
  }, 400)
}

function handleStatusFilter(statusVal: LogbookValidationStatus | '') {
  filters.status_validasi = statusVal
  pagination.currentPage = 1
  fetchLogbooks(1)
}

function resetFilters() {
  filters.search = ''
  filters.status_validasi = ''
  pagination.currentPage = 1
  fetchLogbooks(1)
}

function openModal(logbook: LogbookEntry) {
  selectedLogbook.value = logbook
  isModalOpen.value = true
}

function handleLogbookVerified(updated: LogbookEntry) {
  const idx = logbooks.value.findIndex((l) => l.id === updated.id)
  if (idx !== -1) {
    logbooks.value[idx] = updated
  }
  recalculateMetrics()
  fetchLogbooks()
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <Mountain class="w-7 h-7 text-[#1E3A2B]" />
          Validasi Digital Logbook & Bukti Summit
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Verifikasi foto puncak pendaki, selesaikan status pesanan, dan rilis sertifikat pendakian digital di basecamp
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
          @click="fetchLogbooks()"
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
          <Clock class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Menunggu Validasi</span>
          <div class="text-xl font-extrabold text-amber-700 mt-0.5">
            {{ metrics.pending }} Bukti
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#1E3A2B] flex items-center justify-center font-bold shrink-0">
          <CheckCircle2 class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Summit Disetujui</span>
          <div class="text-xl font-extrabold text-[#1E3A2B] mt-0.5">
            {{ metrics.approved }} Pendaki
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold shrink-0">
          <XCircle class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Bukti Ditolak</span>
          <div class="text-xl font-extrabold text-red-700 mt-0.5">
            {{ metrics.rejected }} Bukti
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & View Switcher Toolbar -->
    <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs space-y-3">
      <!-- Status Filter Tabs -->
      <div class="flex items-center justify-between gap-3 overflow-x-auto pb-1 scrollbar-none">
        <div class="flex items-center gap-2">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            type="button"
            :class="[
              'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer whitespace-nowrap min-h-[36px]',
              filters.status_validasi === tab.value
                ? 'bg-[#1E3A2B] text-white shadow-xs'
                : 'bg-stone-100 text-stone-600 hover:bg-stone-200/80',
            ]"
            @click="handleStatusFilter(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- View Mode Switcher -->
        <div class="flex items-center gap-1 bg-stone-100 p-1 rounded-xl shrink-0">
          <button
            type="button"
            title="Grid View"
            :class="[
              'p-1.5 rounded-lg transition-colors cursor-pointer',
              viewMode === 'grid' ? 'bg-white shadow-xs text-[#1E3A2B]' : 'text-stone-500 hover:text-stone-800',
            ]"
            @click="viewMode = 'grid'"
          >
            <LayoutGrid class="w-4 h-4" />
          </button>
          <button
            type="button"
            title="Table View"
            :class="[
              'p-1.5 rounded-lg transition-colors cursor-pointer',
              viewMode === 'table' ? 'bg-white shadow-xs text-[#1E3A2B]' : 'text-stone-500 hover:text-stone-800',
            ]"
            @click="viewMode = 'table'"
          >
            <List class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Search Query Bar -->
      <div class="flex items-center gap-3 pt-2 border-t border-stone-100">
        <div class="relative flex-1 w-full">
          <Search class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <Input
            v-model="filters.search"
            type="text"
            placeholder="Cari nomor invoice, nama pendaki, atau nama gunung..."
            class="pl-9 h-10 rounded-xl text-xs sm:text-sm border-stone-200"
            @input="handleSearchInput"
          />
        </div>

        <Button
          v-if="filters.search || filters.status_validasi"
          variant="ghost"
          class="h-10 px-3 rounded-xl text-xs text-stone-600 hover:bg-stone-100 shrink-0"
          @click="resetFilters"
        >
          <X class="w-4 h-4 mr-1" />
          Reset
        </Button>
      </div>
    </div>

    <!-- Content: Grid or Table View -->
    <div class="space-y-4">
      <!-- Loading Skeleton -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" class="h-64 bg-stone-100 rounded-2xl animate-pulse" />
      </div>

      <!-- Empty State -->
      <div
        v-else-if="logbooks.length === 0"
        class="bg-white rounded-2xl border border-stone-200 p-12 text-center space-y-3 shadow-xs"
      >
        <div class="w-16 h-16 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
          <Mountain class="w-8 h-8" />
        </div>
        <h3 class="text-base font-bold text-stone-800">Tidak ada data logbook summit</h3>
        <p class="text-xs text-stone-500 max-w-sm mx-auto">
          Belum ada foto bukti puncak yang diunggah oleh pendaki untuk filter status ini.
        </p>
        <Button
          v-if="filters.search || filters.status_validasi"
          variant="outline"
          size="sm"
          class="rounded-xl border-stone-200"
          @click="resetFilters"
        >
          Reset Filter
        </Button>
      </div>

      <!-- 1. Grid Card View -->
      <div
        v-else-if="viewMode === 'grid'"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div
          v-for="item in logbooks"
          :key="item.id"
          class="bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs hover:shadow-md transition-shadow flex flex-col"
        >
          <!-- Thumbnail Image -->
          <div class="relative h-44 bg-stone-900 flex items-center justify-center overflow-hidden group">
            <img
              v-if="item.foto_summit"
              :src="item.foto_summit"
              alt="Bukti Summit"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer"
              @click="openModal(item)"
            />
            <div v-else class="text-center text-stone-500 space-y-1">
              <Mountain class="w-8 h-8 mx-auto text-stone-600" />
              <span class="text-[11px]">Foto Belum Diunggah</span>
            </div>

            <!-- Floating Status Badge -->
            <div class="absolute top-3 left-3">
              <StatusBadge :status="item.status_validasi" />
            </div>

            <!-- Zoom Button Overlay -->
            <button
              type="button"
              class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-md p-2 rounded-xl text-white hover:bg-black/80 transition-colors"
              title="Perbesar Foto"
              @click="openModal(item)"
            >
              <Eye class="w-4 h-4" />
            </button>
          </div>

          <!-- Card Content -->
          <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center justify-between text-[11px] text-stone-400 mb-1">
                <span>{{ item.invoice || item.pesanan?.invoice || '-' }}</span>
                <span>{{ formatDateTimeIndo(item.waktu_summit || item.created_at) }}</span>
              </div>

              <h4 class="text-sm font-bold text-stone-900 line-clamp-1">
                {{ item.nama_pendaki || item.user?.name || '-' }}
              </h4>

              <div class="text-xs text-stone-600 mt-1 flex items-center gap-1.5">
                <Mountain class="w-3.5 h-3.5 text-[#1E3A2B]" />
                <span class="font-medium">
                  {{ item.gunung_nama || item.pesanan?.jalur?.gunung?.nama_gunung || '-' }}
                </span>
                <span class="text-stone-400">•</span>
                <span class="text-[#1E3A2B] font-bold">
                  {{ item.tinggi_mdpl || item.pesanan?.jalur?.gunung?.tinggi_mdpl || 0 }} MDPL
                </span>
              </div>

              <p v-if="item.catatan_pendaki" class="text-xs text-stone-500 italic mt-2 line-clamp-2 bg-stone-50 p-2 rounded-lg border border-stone-100">
                "{{ item.catatan_pendaki }}"
              </p>
            </div>

            <!-- Action Button Footer -->
            <div class="pt-2 border-t border-stone-100 flex items-center gap-2">
              <Button
                class="flex-1 rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white text-xs font-bold min-h-[38px]"
                @click="openModal(item)"
              >
                <Eye class="w-3.5 h-3.5 mr-1.5" />
                {{ item.status_validasi === 'pending' ? 'Tinjau & Validasi' : 'Lihat Detail' }}
              </Button>

              <a
                v-if="item.certificate_url"
                :href="item.certificate_url"
                target="_blank"
                rel="noopener noreferrer"
                class="p-2 rounded-xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-300 shrink-0"
                title="Unduh E-Sertifikat"
              >
                <Award class="w-4 h-4 text-emerald-600" />
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Table View -->
      <div v-else class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
              <tr>
                <th class="p-3.5">Foto</th>
                <th class="p-3.5">Invoice & Waktu</th>
                <th class="p-3.5">Nama Pendaki</th>
                <th class="p-3.5">Gunung & Jalur</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-200">
              <tr
                v-for="item in logbooks"
                :key="item.id"
                class="hover:bg-stone-50/70 transition-colors"
              >
                <!-- Thumbnail -->
                <td class="p-3.5">
                  <div
                    class="w-12 h-12 rounded-xl bg-stone-900 overflow-hidden shrink-0 flex items-center justify-center cursor-pointer"
                    @click="openModal(item)"
                  >
                    <img
                      v-if="item.foto_summit"
                      :src="item.foto_summit"
                      alt="Thumbnail"
                      class="w-full h-full object-cover"
                    />
                    <Mountain v-else class="w-5 h-5 text-stone-500" />
                  </div>
                </td>

                <!-- Invoice -->
                <td class="p-3.5">
                  <div class="font-bold text-stone-900">{{ item.invoice || item.pesanan?.invoice || '-' }}</div>
                  <div class="text-[11px] text-stone-400">
                    {{ formatDateTimeIndo(item.waktu_summit || item.created_at) }}
                  </div>
                </td>

                <!-- Pendaki -->
                <td class="p-3.5 font-bold text-stone-900">
                  {{ item.nama_pendaki || item.user?.name || '-' }}
                </td>

                <!-- Gunung & MDPL -->
                <td class="p-3.5">
                  <div class="font-semibold text-stone-800">
                    {{ item.gunung_nama || item.pesanan?.jalur?.gunung?.nama_gunung || '-' }}
                  </div>
                  <div class="text-[10px] text-stone-500">
                    {{ item.jalur_nama || item.pesanan?.jalur?.nama_jalur || '-' }} • {{ item.tinggi_mdpl || 0 }} MDPL
                  </div>
                </td>

                <!-- Status -->
                <td class="p-3.5 text-center">
                  <StatusBadge :status="item.status_validasi" />
                </td>

                <!-- Action Buttons -->
                <td class="p-3.5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Button
                      size="sm"
                      class="h-8 px-2.5 rounded-lg bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white text-xs font-semibold"
                      @click="openModal(item)"
                    >
                      <Eye class="w-3.5 h-3.5 mr-1" />
                      {{ item.status_validasi === 'pending' ? 'Tinjau' : 'Detail' }}
                    </Button>

                    <a
                      v-if="item.certificate_url"
                      :href="item.certificate_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="h-8 px-2.5 inline-flex items-center rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-semibold hover:bg-emerald-100"
                    >
                      <Award class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                      Sertifikat
                    </a>
                  </div>
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
          Menampilkan <strong class="text-stone-800">{{ logbooks.length }}</strong> dari <strong class="text-stone-800">{{ pagination.total }}</strong> logbook
        </div>

        <div class="flex items-center gap-1.5">
          <Button
            variant="outline"
            size="sm"
            :disabled="pagination.currentPage <= 1 || loading"
            class="h-8 rounded-lg border-stone-200"
            @click="fetchLogbooks(pagination.currentPage - 1)"
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
            @click="fetchLogbooks(pagination.currentPage + 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal Summit Proof Viewer & Verification Form -->
    <SummitProofModal
      v-model:is-open="isModalOpen"
      :logbook="selectedLogbook"
      @verified="handleLogbookVerified"
    />
  </div>
</template>
