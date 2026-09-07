<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMitraStore } from '@/stores/mitra'
import mitraQuotasApi from '@/api/mitraQuotas'
import mitraProductsApi from '@/api/mitraProducts'
import type { Produk } from '@/types/product'
import type { KuotaHarian, BatchQuotaPayload } from '@/types/quota'
import { getApiErrorMessage } from '@/lib/axios'
import {
  CalendarDays,
  CalendarRange,
  Ticket,
  TrendingUp,
  Users,
  RefreshCw,
  CheckCircle2,
  AlertTriangle,
  Plus,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { EmptyState } from '@/components/common'
import QuotaCalendarGrid from '@/components/mitra/quota/QuotaCalendarGrid.vue'
import SingleQuotaEditModal from '@/components/mitra/quota/SingleQuotaEditModal.vue'
import BatchQuotaModal from '@/components/mitra/quota/BatchQuotaModal.vue'

let router: ReturnType<typeof useRouter> | null = null
try {
  router = useRouter()
} catch {
  router = null
}

const mitraStore = useMitraStore()

function handleNavigateToProducts() {
  if (router) {
    router.push('/mitra/products')
  }
}

// State
const ticketProducts = ref<Produk[]>([])
const selectedProductId = ref<number | null>(null)
const quotas = ref<KuotaHarian[]>([])
const isLoadingProducts = ref<boolean>(false)
const isLoadingQuotas = ref<boolean>(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Current Calendar Navigation State (1-indexed month)
const now = new Date()
const currentYear = ref<number>(now.getFullYear())
const currentMonth = ref<number>(now.getMonth() + 1)

// Modals State
const isSingleModalOpen = ref<boolean>(false)
const selectedDate = ref<string>('')
const selectedQuota = ref<KuotaHarian | null>(null)
const isUpdatingSingle = ref<boolean>(false)

const isBatchModalOpen = ref<boolean>(false)
const isSubmittingBatch = ref<boolean>(false)

const selectedProduct = computed<Produk | null>(() => {
  if (!selectedProductId.value) return null
  return ticketProducts.value.find((p) => p.id === selectedProductId.value) || null
})

// Load Ticket Products for Active Basecamp
async function fetchTicketProducts() {
  isLoadingProducts.value = true
  errorMessage.value = null
  try {
    const params: any = { kategori: 'ticket' }
    if (mitraStore.activeBasecampId) {
      params.basecamp_id = mitraStore.activeBasecampId
    }

    const res = await mitraProductsApi.getProducts(params)
    const rawData = res.data
    let items: Produk[] = []
    if (rawData && Array.isArray(rawData)) {
      items = rawData
    } else if (rawData && 'data' in rawData && Array.isArray(rawData.data)) {
      items = rawData.data
    }

    // Filter only ticket products
    ticketProducts.value = items.filter((p) => p.kategori === 'ticket')

    if (ticketProducts.value.length > 0) {
      // Keep selected or pick first
      if (
        !selectedProductId.value ||
        !ticketProducts.value.some((p) => p.id === selectedProductId.value)
      ) {
        selectedProductId.value = ticketProducts.value[0].id
      } else {
        fetchQuotas()
      }
    } else {
      selectedProductId.value = null
      quotas.value = []
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(
      err,
      'Gagal memuat produk tiket basecamp.'
    )
  } finally {
    isLoadingProducts.value = false
  }
}

// Load Quotas for Selected Product & Navigated Month
async function fetchQuotas() {
  if (!selectedProductId.value) return

  isLoadingQuotas.value = true
  errorMessage.value = null

  try {
    const startDate = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-01`
    const lastDayOfMonth = new Date(currentYear.value, currentMonth.value, 0).getDate()
    const endDate = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(lastDayOfMonth).padStart(2, '0')}`

    const res = await mitraQuotasApi.getProductQuotas(selectedProductId.value, {
      start_date: startDate,
      end_date: endDate,
    })

    quotas.value = res.data || []
  } catch (err) {
    errorMessage.value = getApiErrorMessage(
      err,
      'Gagal memuat ketersediaan kuota kalender.'
    )
  } finally {
    isLoadingQuotas.value = false
  }
}

// Month Navigation Handlers
function handlePrevMonth() {
  if (currentMonth.value === 1) {
    currentMonth.value = 12
    currentYear.value--
  } else {
    currentMonth.value--
  }
  fetchQuotas()
}

function handleNextMonth() {
  if (currentMonth.value === 12) {
    currentMonth.value = 1
    currentYear.value++
  } else {
    currentMonth.value++
  }
  fetchQuotas()
}

function handleGoToToday() {
  const d = new Date()
  currentYear.value = d.getFullYear()
  currentMonth.value = d.getMonth() + 1
  fetchQuotas()
}

// Watchers
watch(
  () => mitraStore.activeBasecampId,
  () => {
    fetchTicketProducts()
  }
)

watch(
  () => selectedProductId.value,
  () => {
    if (selectedProductId.value) {
      fetchQuotas()
    }
  }
)

onMounted(() => {
  fetchTicketProducts()
})

// KPI Metrics Calculations
const todayStr = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

const todayQuota = computed(() => {
  return quotas.value.find((q) => q.tanggal === todayStr.value) || null
})

const totalMonthBooked = computed(() => {
  return quotas.value.reduce((acc, q) => {
    return acc + Math.max(0, q.kuota_total - q.kuota_tersisa)
  }, 0)
})

const totalMonthCapacity = computed(() => {
  return quotas.value.reduce((acc, q) => acc + q.kuota_total, 0)
})

const monthOccupancyRate = computed(() => {
  if (totalMonthCapacity.value === 0) return 0
  return Math.round((totalMonthBooked.value / totalMonthCapacity.value) * 100)
})

// Modal Handlers
function handleSelectDateCell(payload: { dateStr: string; quota?: KuotaHarian | null }) {
  selectedDate.value = payload.dateStr
  selectedQuota.value = payload.quota || null
  isSingleModalOpen.value = true
}

async function handleSingleQuotaSubmit(payload: {
  quotaId?: number
  date: string
  kuota_total: number
}) {
  if (!selectedProductId.value) return
  isUpdatingSingle.value = true
  errorMessage.value = null

  try {
    if (payload.quotaId) {
      // Update existing quota via PUT /mitra/quotas/{quota_id}
      await mitraQuotasApi.updateSingleQuota(payload.quotaId, {
        kuota_total: payload.kuota_total,
      })
    } else {
      // Create new quota entry via batch endpoint for single date
      await mitraQuotasApi.batchSetQuotas(selectedProductId.value, {
        start_date: payload.date,
        end_date: payload.date,
        kuota_total: payload.kuota_total,
      })
    }

    isSingleModalOpen.value = false
    showToast(`Kuota tanggal ${payload.date} berhasil diperbarui menjadi ${payload.kuota_total} kuota.`)
    fetchQuotas()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memperbarui kuota tanggal terpilih.')
  } finally {
    isUpdatingSingle.value = false
  }
}

async function handleBatchQuotaSubmit(payload: BatchQuotaPayload) {
  if (!selectedProductId.value) return
  isSubmittingBatch.value = true
  errorMessage.value = null

  try {
    await mitraQuotasApi.batchSetQuotas(selectedProductId.value, payload)
    isBatchModalOpen.value = false
    showToast(
      `Kuota massal (${payload.kuota_total}/hari) berhasil diterapkan untuk rentang ${payload.start_date} s/d ${payload.end_date}.`
    )
    fetchQuotas()
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal menerapkan kuota massal.')
  } finally {
    isSubmittingBatch.value = false
  }
}

function showToast(msg: string) {
  successMessage.value = msg
  setTimeout(() => {
    if (successMessage.value === msg) {
      successMessage.value = null
    }
  }, 4500)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight">
          Kalender & Kuota Harian Tiket
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Pantau kapasitas pendakian dan atur kuota registrasi harian jalur
          <span v-if="mitraStore.activeBasecamp" class="font-semibold text-stone-800">
            • {{ mitraStore.activeBasecamp.nama_basecamp }}
          </span>
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl border-stone-200 text-stone-700 hover:bg-stone-100 min-h-[44px]"
          :disabled="isLoadingQuotas"
          @click="fetchQuotas"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', isLoadingQuotas && 'animate-spin']" />
          Segarkan
        </Button>
        <Button
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white shadow-sm min-h-[44px]"
          :disabled="!selectedProductId"
          @click="isBatchModalOpen = true"
        >
          <CalendarRange class="w-4 h-4 mr-2" />
          Set Kuota Massal
        </Button>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div
      v-if="successMessage"
      class="flex items-center gap-3 p-4 bg-emerald-50 text-emerald-800 rounded-2xl border border-emerald-200 text-sm font-medium animate-fadeIn"
    >
      <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
      <span>{{ successMessage }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="flex items-center gap-3 p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm font-medium animate-fadeIn"
    >
      <AlertTriangle class="w-5 h-5 text-red-600 shrink-0" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Ticket Product Selector Bar -->
    <div
      v-if="ticketProducts.length > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-2xl border border-stone-200/80 shadow-xs"
    >
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
          <Ticket class="w-4 h-4" />
        </div>
        <div>
          <span class="text-xs font-bold text-stone-800">Pilih Tiket Jalur:</span>
          <p class="text-[11px] text-stone-500">
            Tampilkan kalender kuota untuk tiket jalur yang dipilih
          </p>
        </div>
      </div>

      <div class="w-full sm:w-72">
        <select
          v-model="selectedProductId"
          class="w-full h-10 px-3 bg-stone-50 border border-stone-200 rounded-xl text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
        >
          <option
            v-for="ticket in ticketProducts"
            :key="ticket.id"
            :value="ticket.id"
          >
            {{ ticket.nama_produk }} (Rp {{ ticket.harga.toLocaleString('id-ID') }})
          </option>
        </select>
      </div>
    </div>

    <!-- Empty State if No Ticket Product Registered -->
    <div
      v-if="!isLoadingProducts && ticketProducts.length === 0"
      class="bg-white rounded-2xl border border-stone-200 p-8 shadow-xs"
    >
      <EmptyState
        title="Belum Ada Tiket Pendakian"
        description="Basecamp ini belum memiliki produk tiket registrasi simaksi. Tambahkan produk tiket terlebih dahulu melalui menu Katalog Produk agar dapat mengatur kalender kuota."
      >
        <template #action>
          <Button
            class="rounded-xl bg-[#1E3A2B] text-white min-h-[44px]"
            @click="handleNavigateToProducts"
          >
            <Plus class="w-4 h-4 mr-2" />
            Buka Katalog Produk
          </Button>
        </template>
      </EmptyState>
    </div>

    <!-- Main Content when Ticket Product Exists -->
    <template v-else>
      <!-- KPI Metric Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Kuota Hari Ini -->
        <Card class="rounded-2xl border-stone-200/80 shadow-xs">
          <CardContent class="p-4 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
              <CalendarDays class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-semibold text-stone-500">Kuota Hari Ini</span>
              <div class="text-lg font-bold text-stone-900 mt-0.5">
                <template v-if="todayQuota">
                  {{ todayQuota.kuota_tersisa }}
                  <span class="text-xs font-normal text-stone-500">
                    / {{ todayQuota.kuota_total }} tersisa
                  </span>
                </template>
                <span v-else class="text-xs text-stone-400">Belum diset</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Card 2: Terpesan Bulan Ini -->
        <Card class="rounded-2xl border-stone-200/80 shadow-xs">
          <CardContent class="p-4 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
              <Users class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-semibold text-stone-500">Terdaftar Bulan Ini</span>
              <div class="text-lg font-bold text-stone-900 mt-0.5">
                {{ totalMonthBooked.toLocaleString('id-ID') }}
                <span class="text-xs font-normal text-stone-500">Pendaki</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Card 3: Okupansi Rata-rata -->
        <Card class="rounded-2xl border-stone-200/80 shadow-xs">
          <CardContent class="p-4 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
              <TrendingUp class="w-6 h-6" />
            </div>
            <div>
              <span class="text-xs font-semibold text-stone-500">Okupansi Bulan Ini</span>
              <div class="text-lg font-bold text-stone-900 mt-0.5">
                {{ monthOccupancyRate }}%
                <span class="text-xs font-normal text-stone-500">
                  ({{ totalMonthBooked }} / {{ totalMonthCapacity }})
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Interactive Calendar Component -->
      <QuotaCalendarGrid
        :year="currentYear"
        :month="currentMonth"
        :quotas="quotas"
        :loading="isLoadingQuotas"
        @prev-month="handlePrevMonth"
        @next-month="handleNextMonth"
        @today="handleGoToToday"
        @select-date="handleSelectDateCell"
      />
    </template>

    <!-- Single Quota Edit Modal -->
    <SingleQuotaEditModal
      v-model:is-open="isSingleModalOpen"
      :date="selectedDate"
      :quota="selectedQuota"
      :product-name="selectedProduct?.nama_produk || 'Tiket Pendakian'"
      :loading="isUpdatingSingle"
      @submit="handleSingleQuotaSubmit"
    />

    <!-- Batch Quota Setup Modal -->
    <BatchQuotaModal
      v-model:is-open="isBatchModalOpen"
      :product-id="selectedProductId"
      :product-name="selectedProduct?.nama_produk || 'Tiket Pendakian'"
      :loading="isSubmittingBatch"
      @submit="handleBatchQuotaSubmit"
    />
  </div>
</template>
