<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMitraStore } from '@/stores/mitra'
import mitraDashboardApi from '@/api/mitraDashboard'
import type { MitraAnalyticsSummary } from '@/types/dashboard'
import { getApiErrorMessage } from '@/lib/axios'
import {
  Calendar,
  RefreshCw,
  QrCode,
  Building2,
  AlertCircle,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import DashboardMetricGrid from '@/components/mitra/dashboard/DashboardMetricGrid.vue'
import ActionQueuesBar from '@/components/mitra/dashboard/ActionQueuesBar.vue'
import EmergencyTrailBanner from '@/components/mitra/dashboard/EmergencyTrailBanner.vue'
import EmergencyClosureModal from '@/components/mitra/dashboard/EmergencyClosureModal.vue'
import TodayArrivalTable from '@/components/mitra/dashboard/TodayArrivalTable.vue'

const router = useRouter()
const mitraStore = useMitraStore()

const metrics = ref<MitraAnalyticsSummary | null>(null)
const todayOrders = ref<any[]>([])
const isLoading = ref<boolean>(false)
const errorMessage = ref<string | null>(null)
const isEmergencyModalOpen = ref<boolean>(false)

const activeBasecamp = computed(() => mitraStore.activeBasecamp)
const activeJalur = computed(() => mitraStore.activeJalur)
const activeGunung = computed(() => mitraStore.activeGunung)

const todayFormatted = computed(() => {
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'full',
  }).format(new Date())
})

async function fetchDashboardData() {
  isLoading.value = true
  errorMessage.value = null

  try {
    const basecampId = mitraStore.activeBasecampId

    const [summaryRes, ordersRes] = await Promise.all([
      mitraDashboardApi.getSummary(basecampId),
      mitraDashboardApi.getTodayOrders(basecampId),
    ])

    if (summaryRes.data) {
      metrics.value = summaryRes.data
    }

    if (ordersRes.data) {
      todayOrders.value = Array.isArray(ordersRes.data) ? ordersRes.data : ordersRes.data.data || []
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memuat data dashboard operasional mitra.')
  } finally {
    isLoading.value = false
  }
}

// Watch for active basecamp changes and reload data
watch(
  () => mitraStore.activeBasecampId,
  () => {
    fetchDashboardData()
  }
)

onMounted(() => {
  fetchDashboardData()
})

function handleEmergencySuccess(newStatus: 'open' | 'close') {
  if (metrics.value?.operations_today) {
    metrics.value.operations_today.status_jalur = newStatus
  }
  fetchDashboardData()
}

function handleViewOrderDetail(order: any) {
  router.push(`/mitra/orders?search=${encodeURIComponent(order.invoice)}`)
}

function handleProcessCheckIn(order: any) {
  router.push(`/mitra/orders?search=${encodeURIComponent(order.invoice)}&checkin=true`)
}
</script>

<template>
  <div class="space-y-6 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
          <Building2 class="h-3.5 w-3.5" />
          <span>{{ activeBasecamp?.nama_basecamp || 'Pos Utama Basecamp' }}</span>
          <span v-if="activeGunung">· {{ activeGunung.nama_gunung }}</span>
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight text-foreground mt-0.5">
          Pusat Operasional Basecamp
        </h1>
        <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
          Monitoring kuota harian, validasi kedatangan pendaki, dan status jalur pendakian secara real-time.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border bg-muted/30 text-xs font-medium text-muted-foreground">
          <Calendar class="h-3.5 w-3.5 text-emerald-600" />
          <span>{{ todayFormatted }}</span>
        </div>

        <Button
          variant="outline"
          size="sm"
          class="h-9 px-3 text-xs gap-1.5 rounded-xl"
          :disabled="isLoading"
          @click="fetchDashboardData"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
          <span class="hidden sm:inline">Segarkan</span>
        </Button>

        <Button
          size="sm"
          class="h-9 px-3.5 text-xs gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold shadow-xs"
          @click="router.push('/mitra/orders')"
        >
          <QrCode class="h-3.5 w-3.5" />
          <span>Pindai E-Ticket</span>
        </Button>
      </div>
    </div>

    <!-- Error Alert if any -->
    <div
      v-if="errorMessage"
      class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 text-xs flex items-center justify-between gap-3"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="h-4 w-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>
      <Button variant="ghost" size="sm" class="h-7 text-xs" @click="fetchDashboardData">
        Coba Lagi
      </Button>
    </div>

    <!-- Section 1: Emergency Trail Closure Banner -->
    <EmergencyTrailBanner
      :status="metrics?.operations_today?.status_jalur || 'open'"
      :trail-name="activeJalur?.nama_jalur || 'Jalur Pendakian'"
      :mountain-name="activeGunung?.nama_gunung || 'Gunung'"
      @open-modal="isEmergencyModalOpen = true"
    />

    <!-- Section 2: Metric Cards Grid -->
    <DashboardMetricGrid :metrics="metrics" :loading="isLoading" />

    <!-- Section 3: Action Queues Bar -->
    <ActionQueuesBar
      :action-queues="metrics?.action_queues"
      :resources="metrics?.resources"
    />

    <!-- Section 4: Today's Arrival & Check-In Queue Table -->
    <TodayArrivalTable
      :orders="todayOrders"
      :loading="isLoading"
      @view-detail="handleViewOrderDetail"
      @process-check-in="handleProcessCheckIn"
      @refresh="fetchDashboardData"
    />

    <!-- Emergency Closure Modal -->
    <EmergencyClosureModal
      v-model:open="isEmergencyModalOpen"
      :current-status="metrics?.operations_today?.status_jalur || 'open'"
      :trail-id="activeJalur?.id || activeBasecamp?.jalur_id"
      :trail-name="activeJalur?.nama_jalur || 'Jalur Pendakian'"
      @success="handleEmergencySuccess"
    />
  </div>
</template>
