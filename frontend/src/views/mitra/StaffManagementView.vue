<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraStaffApi from '@/api/mitraStaff'
import type { MitraStaff, StaffRole, StaffFilterParams } from '@/types/staff'
import { ConfirmModal } from '@/components/common'
import StaffFormModal from '@/components/mitra/staff/StaffFormModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Users,
  Compass,
  Package,
  ShieldCheck,
  Phone,
  Calendar,
  Search,
  Plus,
  RefreshCw,
  Edit,
  Trash2,
  AlertCircle,
  X,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  CheckCircle2,
} from 'lucide-vue-next'
import { getApiErrorMessage } from '@/lib/axios'

const mitraStore = useMitraStore()

// State
const loading = ref(false)
const errorMessage = ref<string | null>(null)
const successFeedback = ref<string | null>(null)
const staffList = ref<MitraStaff[]>([])
const selectedStaff = ref<MitraStaff | null>(null)
const isFormModalOpen = ref(false)
const isDeleteModalOpen = ref(false)
const staffToDelete = ref<MitraStaff | null>(null)
const isDeleting = ref(false)

// Metrics
const metrics = reactive({
  total: 0,
  guidesAvailable: 0,
  portersAvailable: 0,
  onDutyCount: 0,
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
  role: StaffRole | ''
}>({
  search: '',
  role: '',
})

let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

const roleTabs: { label: string; value: StaffRole | ''; icon: any }[] = [
  { label: 'Semua Staf', value: '', icon: Users },
  { label: 'Pemandu (Guide)', value: 'guide', icon: Compass },
  { label: 'Porter Logistik', value: 'porter', icon: Package },
  { label: 'Petugas Basecamp', value: 'petugas', icon: ShieldCheck },
]

onMounted(() => {
  fetchStaffList()
})

async function fetchStaffList(page = pagination.currentPage) {
  loading.value = true
  errorMessage.value = null

  try {
    const params: StaffFilterParams = {
      page,
      per_page: pagination.perPage,
    }

    if (filters.role) {
      params.role = filters.role
    }
    if (filters.search.trim()) {
      params.search = filters.search.trim()
    }

    const res = await mitraStaffApi.getStaffList(params)

    if (Array.isArray(res.data)) {
      staffList.value = res.data
      pagination.total = res.data.length
      pagination.currentPage = 1
      pagination.lastPage = 1
    } else if (res.data && 'data' in res.data) {
      staffList.value = res.data.data
      pagination.total = res.data.total ?? res.data.data.length
      pagination.currentPage = res.data.current_page ?? 1
      pagination.lastPage = res.data.last_page ?? 1
      pagination.perPage = res.data.per_page ?? 15
    }

    recalculateMetrics()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat daftar staf operasional.')
  } finally {
    loading.value = false
  }
}

function recalculateMetrics() {
  let total = staffList.value.length
  let guidesAvail = 0
  let portersAvail = 0
  let onDuty = 0

  staffList.value.forEach((s) => {
    if (s.is_available) {
      if (s.role === 'guide') guidesAvail++
      if (s.role === 'porter') portersAvail++
    } else {
      onDuty++
    }
  })

  metrics.total = total
  metrics.guidesAvailable = guidesAvail
  metrics.portersAvailable = portersAvail
  metrics.onDutyCount = onDuty
}

function handleSearchInput() {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    pagination.currentPage = 1
    fetchStaffList(1)
  }, 400)
}

function handleRoleFilter(roleVal: StaffRole | '') {
  filters.role = roleVal
  pagination.currentPage = 1
  fetchStaffList(1)
}

function resetFilters() {
  filters.search = ''
  filters.role = ''
  pagination.currentPage = 1
  fetchStaffList(1)
}

function openCreateModal() {
  selectedStaff.value = null
  isFormModalOpen.value = true
}

function openEditModal(staff: MitraStaff) {
  selectedStaff.value = staff
  isFormModalOpen.value = true
}

function handleStaffSaved(savedStaff: MitraStaff) {
  const idx = staffList.value.findIndex((s) => s.id === savedStaff.id)
  if (idx !== -1) {
    staffList.value[idx] = savedStaff
  } else {
    staffList.value.unshift(savedStaff)
  }
  recalculateMetrics()
  fetchStaffList()
}

function openDeleteModal(staff: MitraStaff) {
  staffToDelete.value = staff
  isDeleteModalOpen.value = true
}

async function confirmDelete() {
  if (!staffToDelete.value) return
  isDeleting.value = true

  try {
    await mitraStaffApi.deleteStaff(staffToDelete.value.id)
    successFeedback.value = `Staf "${staffToDelete.value.nama}" berhasil dihapus.`
    staffList.value = staffList.value.filter((s) => s.id !== staffToDelete.value?.id)
    recalculateMetrics()
    isDeleteModalOpen.value = false
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal menghapus data staf.')
  } finally {
    isDeleting.value = false
  }
}

async function handleToggleAvailability(staff: MitraStaff) {
  const originalState = staff.is_available
  staff.is_available = !originalState

  try {
    await mitraStaffApi.toggleStaffAvailability(staff.id, staff.is_available)
    recalculateMetrics()
  } catch (err) {
    // Revert on error
    staff.is_available = originalState
    errorMessage.value = getApiErrorMessage(err, 'Gagal memperbarui status ketersediaan staf.')
  }
}

function formatWhatsAppUrl(phone: string): string {
  let clean = phone.replace(/[^0-9]/g, '')
  if (clean.startsWith('08')) {
    clean = '628' + clean.slice(2)
  }
  return `https://wa.me/${clean}`
}

function getRoleLabel(role: StaffRole): string {
  switch (role) {
    case 'guide':
      return 'Pemandu (Guide)'
    case 'porter':
      return 'Porter Logistik'
    case 'petugas':
      return 'Petugas Basecamp'
    default:
      return role
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight flex items-center gap-2.5">
          <Users class="w-7 h-7 text-[#1E3A2B]" />
          Manajemen Staf & Kru Operasional
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Kelola direktori pemandu (guide), porter logistik, dan petugas lapangan di basecamp
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
          @click="fetchStaffList()"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
          Refresh
        </Button>

        <Button
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px] px-4 font-bold shadow-sm"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4 mr-1.5" />
          Tambah Staf Baru
        </Button>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div
      v-if="successFeedback"
      class="flex items-center gap-3 p-4 bg-emerald-50 text-emerald-800 rounded-2xl border border-emerald-200 text-sm"
    >
      <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
      <span class="flex-1">{{ successFeedback }}</span>
      <Button
        variant="ghost"
        size="sm"
        class="text-emerald-700 hover:bg-emerald-100"
        @click="successFeedback = null"
      >
        <X class="w-4 h-4" />
      </Button>
    </div>

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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-stone-100 text-stone-700 flex items-center justify-center font-bold shrink-0">
          <Users class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Total Staf Terdaftar</span>
          <div class="text-xl font-extrabold text-stone-900 mt-0.5">
            {{ metrics.total }} Orang
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-[#1E3A2B] flex items-center justify-center font-bold shrink-0">
          <Compass class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Guide Siap Bertugas</span>
          <div class="text-xl font-extrabold text-[#1E3A2B] mt-0.5">
            {{ metrics.guidesAvailable }} Pemandu
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-orange-50 text-[#E65100] flex items-center justify-center font-bold shrink-0">
          <Package class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Porter Siap Bertugas</span>
          <div class="text-xl font-extrabold text-[#E65100] mt-0.5">
            {{ metrics.portersAvailable }} Orang
          </div>
        </div>
      </div>

      <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
          <Calendar class="w-6 h-6" />
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-stone-400">Sedang Bertugas / Off</span>
          <div class="text-xl font-extrabold text-amber-700 mt-0.5">
            {{ metrics.onDutyCount }} Staf
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="p-4 bg-white rounded-2xl border border-stone-200 shadow-xs space-y-3">
      <!-- Role Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
        <button
          v-for="tab in roleTabs"
          :key="tab.value"
          type="button"
          :class="[
            'px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all cursor-pointer whitespace-nowrap min-h-[36px] flex items-center gap-1.5',
            filters.role === tab.value
              ? 'bg-[#1E3A2B] text-white shadow-xs'
              : 'bg-stone-100 text-stone-600 hover:bg-stone-200/80',
          ]"
          @click="handleRoleFilter(tab.value)"
        >
          <component :is="tab.icon" class="w-3.5 h-3.5" />
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- Search Query Bar -->
      <div class="flex items-center gap-3 pt-2 border-t border-stone-100">
        <div class="relative flex-1 w-full">
          <Search class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <Input
            v-model="filters.search"
            type="text"
            placeholder="Cari nama staf, nomor WhatsApp, atau jadwal penugasan..."
            class="pl-9 h-10 rounded-xl text-xs sm:text-sm border-stone-200"
            @input="handleSearchInput"
          />
        </div>

        <Button
          v-if="filters.search || filters.role"
          variant="ghost"
          class="h-10 px-3 rounded-xl text-xs text-stone-600 hover:bg-stone-100 shrink-0"
          @click="resetFilters"
        >
          <X class="w-4 h-4 mr-1" />
          Reset
        </Button>
      </div>
    </div>

    <!-- Staff Table Content Section -->
    <div class="space-y-4">
      <!-- Loading Skeleton -->
      <div v-if="loading" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="h-12 bg-stone-100 rounded-xl animate-pulse" />
      </div>

      <!-- Empty State -->
      <div
        v-else-if="staffList.length === 0"
        class="bg-white rounded-2xl border border-stone-200 p-12 text-center space-y-3 shadow-xs"
      >
        <div class="w-16 h-16 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto">
          <Users class="w-8 h-8" />
        </div>
        <h3 class="text-base font-bold text-stone-800">Tidak ada staf operasional ditemukan</h3>
        <p class="text-xs text-stone-500 max-w-sm mx-auto">
          Belum ada data staf guide atau porter yang terdaftar untuk filter ini.
        </p>
        <div class="flex items-center justify-center gap-2 pt-2">
          <Button
            v-if="filters.search || filters.role"
            variant="outline"
            size="sm"
            class="rounded-xl border-stone-200"
            @click="resetFilters"
          >
            Reset Filter
          </Button>

          <Button
            size="sm"
            class="rounded-xl bg-[#1E3A2B] text-white"
            @click="openCreateModal"
          >
            <Plus class="w-4 h-4 mr-1" />
            Tambah Staf Baru
          </Button>
        </div>
      </div>

      <!-- Table View -->
      <div v-else class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700 uppercase text-[10px] tracking-wider">
              <tr>
                <th class="p-3.5">Nama & Kontak</th>
                <th class="p-3.5">Posisi / Peran</th>
                <th class="p-3.5">Jadwal Tugas</th>
                <th class="p-3.5 text-center">Status Ketersediaan</th>
                <th class="p-3.5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-200">
              <tr
                v-for="item in staffList"
                :key="item.id"
                class="hover:bg-stone-50/70 transition-colors"
              >
                <!-- Nama & Telepon WhatsApp -->
                <td class="p-3.5">
                  <div class="font-bold text-stone-900 text-sm">{{ item.nama }}</div>
                  <div class="flex items-center gap-2 text-[11px] text-stone-500 mt-0.5">
                    <a
                      :href="formatWhatsAppUrl(item.telepon)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-800 hover:underline"
                      title="Hubungi via WhatsApp"
                    >
                      <Phone class="w-3 h-3 text-emerald-600 shrink-0" />
                      <span>{{ item.telepon }}</span>
                      <ExternalLink class="w-2.5 h-2.5 text-emerald-500" />
                    </a>
                  </div>
                </td>

                <!-- Role Badge -->
                <td class="p-3.5">
                  <span
                    :class="[
                      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold',
                      item.role === 'guide'
                        ? 'bg-emerald-50 text-[#1E3A2B] border border-emerald-200'
                        : item.role === 'porter'
                          ? 'bg-orange-50 text-[#E65100] border border-orange-200'
                          : 'bg-stone-100 text-stone-700 border border-stone-200',
                    ]"
                  >
                    <Compass v-if="item.role === 'guide'" class="w-3.5 h-3.5" />
                    <Package v-else-if="item.role === 'porter'" class="w-3.5 h-3.5" />
                    <ShieldCheck v-else class="w-3.5 h-3.5" />
                    <span>{{ getRoleLabel(item.role) }}</span>
                  </span>
                </td>

                <!-- Jadwal Tugas -->
                <td class="p-3.5">
                  <div class="flex items-center gap-1.5 text-stone-700 font-medium">
                    <Calendar class="w-3.5 h-3.5 text-stone-400 shrink-0" />
                    <span>{{ item.jadwal_tugas || 'Fleksibel / On Call' }}</span>
                  </div>
                </td>

                <!-- Quick Availability Toggle -->
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="item.is_available"
                      :class="[
                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                        item.is_available ? 'bg-[#1E3A2B]' : 'bg-stone-300',
                      ]"
                      @click="handleToggleAvailability(item)"
                    >
                      <span
                        :class="[
                          'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                          item.is_available ? 'translate-x-4' : 'translate-x-0',
                        ]"
                      />
                    </button>
                    <span
                      class="text-[11px] font-bold"
                      :class="item.is_available ? 'text-emerald-700' : 'text-stone-400'"
                    >
                      {{ item.is_available ? 'Tersedia' : 'Off / Bertugas' }}
                    </span>
                  </div>
                </td>

                <!-- Action Buttons -->
                <td class="p-3.5 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <Button
                      size="sm"
                      variant="outline"
                      class="h-8 px-2.5 rounded-lg border-stone-200 text-stone-700 hover:bg-stone-100 text-xs font-medium"
                      @click="openEditModal(item)"
                    >
                      <Edit class="w-3.5 h-3.5 mr-1" />
                      Edit
                    </Button>

                    <Button
                      size="sm"
                      variant="ghost"
                      class="h-8 px-2.5 rounded-lg text-red-600 hover:bg-red-50 text-xs font-medium"
                      @click="openDeleteModal(item)"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </Button>
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
          Menampilkan <strong class="text-stone-800">{{ staffList.length }}</strong> dari <strong class="text-stone-800">{{ pagination.total }}</strong> staf
        </div>

        <div class="flex items-center gap-1.5">
          <Button
            variant="outline"
            size="sm"
            :disabled="pagination.currentPage <= 1 || loading"
            class="h-8 rounded-lg border-stone-200"
            @click="fetchStaffList(pagination.currentPage - 1)"
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
            @click="fetchStaffList(pagination.currentPage + 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal Form Create / Edit -->
    <StaffFormModal
      v-model:is-open="isFormModalOpen"
      :staff="selectedStaff"
      @saved="handleStaffSaved"
    />

    <!-- Modal Confirm Delete -->
    <ConfirmModal
      :open="isDeleteModalOpen"
      title="Hapus Staf Lapangan"
      :description="`Apakah Anda yakin ingin menghapus '${staffToDelete?.nama}' dari daftar staf basecamp? Tindakan ini tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus Staf"
      cancel-label="Batal"
      variant="danger"
      :loading="isDeleting"
      @confirm="confirmDelete"
      @cancel="isDeleteModalOpen = false"
      @update:open="isDeleteModalOpen = $event"
    />
  </div>
</template>
