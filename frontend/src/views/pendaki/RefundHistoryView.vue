<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import type { PesananItem } from '@/types/pendakiOrder'
import type { RefundItem } from '@/types/pendakiRefund'
import RefundTimelineCard from '@/components/pendaki/RefundTimelineCard.vue'
import DisputeEscalationModal from '@/components/pendaki/DisputeEscalationModal.vue'
import RefundRequestModal from '@/components/pendaki/RefundRequestModal.vue'
import { extractApiError } from '@/lib/normalizer'
import {
  AlertCircle,
  HelpCircle,
  Loader2,
  RefreshCw,
  RotateCcw,
  ShieldCheck
} from 'lucide-vue-next'

const orders = ref<PesananItem[]>([])
const loading = ref(true)
const errorMessage = ref('')
const activeTab = ref<'all' | 'pending' | 'resolved' | 'dispute'>('all')

// Dispute Modal State
const disputeModal = ref({
  isOpen: false,
  refundId: 0,
  invoice: '',
  mitraAlasanPenolakan: ''
})

// Refund Request Modal State
const refundModal = ref({
  isOpen: false,
  invoice: '',
  totalBayar: 0,
  tanggalBooking: ''
})

async function fetchOrders() {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await pendakiOrdersApi.getMyOrders()
    orders.value = (res.data as any)?.items || res.data || []
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOrders()
})

// Extract all refunds from orders or build refund representations
const allRefunds = computed<RefundItem[]>(() => {
  const refunds: RefundItem[] = []
  orders.value.forEach((order: any) => {
    const orderRefunds = Array.isArray(order.refunds) ? order.refunds : (order.refund ? [order.refund] : [])
    if (orderRefunds.length > 0) {
      orderRefunds.forEach((rf: any) => {
        refunds.push({
          ...rf,
          pesanan: rf.pesanan || {
            id: order.id,
            invoice: order.invoice,
            total_bayar: order.total_bayar,
            tanggal_booking: order.tanggal_booking,
            status: order.status,
            basecamp: order.basecamp,
            jalur: order.jalur
          }
        })
      })
    } else if (order.status === 'cancelled') {
      refunds.push({
        id: order.id,
        pesanan_id: order.id,
        nominal: order.total_bayar,
        status: 'pending',
        alasan: 'Pengajuan pembatalan / refund',
        bank_tujuan: 'Transfer Bank',
        rekening_tujuan: '-',
        nama_tujuan: order.user?.name || 'Pendaki',
        created_at: order.created_at,
        pesanan: {
          id: order.id,
          invoice: order.invoice,
          total_bayar: order.total_bayar,
          tanggal_booking: order.tanggal_booking,
          status: order.status,
          basecamp: order.basecamp,
          jalur: order.jalur
        }
      })
    }
  })
  return refunds
})

const filteredRefunds = computed(() => {
  if (activeTab.value === 'pending') {
    return allRefunds.value.filter((r) => r.status === 'pending')
  }
  if (activeTab.value === 'resolved') {
    return allRefunds.value.filter((r) => r.status === 'success' || r.status === 'approved_by_mitra')
  }
  if (activeTab.value === 'dispute') {
    return allRefunds.value.filter((r) => r.status === 'disputed' || r.status === 'rejected_by_mitra')
  }
  return allRefunds.value
})

function handleOpenDispute(refund: RefundItem) {
  disputeModal.value = {
    isOpen: true,
    refundId: refund.id,
    invoice: refund.pesanan?.invoice || '',
    mitraAlasanPenolakan: refund.mitra_alasan_penolakan || ''
  }
}

function handleDisputeSuccess() {
  fetchOrders()
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAF8] py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
      <!-- Breadcrumb & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
            <router-link to="/pendaki/orders" class="hover:text-[#1E3A2B] transition">Pesanan Saya</router-link>
            <span>/</span>
            <span class="text-[#1E3A2B]">Pusat Bantuan & Refund</span>
          </div>
          <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-2">
            <RotateCcw class="h-6 w-6 text-[#1E3A2B]" />
            <span>Pusat Bantuan & Pengajuan Refund</span>
          </h1>
          <p class="text-xs text-gray-500 mt-1">
            Pantau status pengembalian dana, garansi SOP H-3/H-1, dan eskalasi sengketa resmi Summit.
          </p>
        </div>

        <button
          @click="fetchOrders"
          :disabled="loading"
          class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition"
        >
          <RefreshCw :class="['h-3.5 w-3.5', loading && 'animate-spin']" />
          <span>Segarkan</span>
        </button>
      </div>

      <!-- Policy Guarantees Banner -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 space-y-1">
          <div class="flex items-center gap-2 text-emerald-800 font-bold text-xs">
            <ShieldCheck class="h-4 w-4" />
            <span>Garansi 100% (H-3)</span>
          </div>
          <p class="text-[11px] text-emerald-700">
            Pembatalan 3 hari sebelum tanggal pendakian berhak mendapatkan pengembalian dana 100%.
          </p>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 space-y-1">
          <div class="flex items-center gap-2 text-amber-800 font-bold text-xs">
            <ShieldCheck class="h-4 w-4" />
            <span>Garansi 50% (H-1 s/d H-2)</span>
          </div>
          <p class="text-[11px] text-amber-700">
            Pembatalan 1-2 hari sebelum pendakian berhak mendapatkan pengembalian dana parsial 50%.
          </p>
        </div>

        <div class="rounded-2xl border border-purple-100 bg-purple-50/70 p-4 space-y-1">
          <div class="flex items-center gap-2 text-purple-800 font-bold text-xs">
            <HelpCircle class="h-4 w-4" />
            <span>Pusat Sengketa Admin</span>
          </div>
          <p class="text-[11px] text-purple-700">
            Bila pengajuan ditolak sepihak oleh mitra, eskalasikan langsung ke tim mediasi Summit.
          </p>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto border-b border-gray-200 pb-2">
        <button
          v-for="tab in [
            { id: 'all', label: 'Semua Status' },
            { id: 'pending', label: 'Menunggu Review' },
            { id: 'resolved', label: 'Disetujui / Selesai' },
            { id: 'dispute', label: 'Sengketa & Ditolak' }
          ]"
          :key="tab.id"
          :class="[
            'px-3.5 py-1.5 text-xs font-bold rounded-lg transition whitespace-nowrap',
            activeTab === tab.id
              ? 'bg-[#1E3A2B] text-white shadow-xs'
              : 'text-gray-600 hover:bg-gray-100'
          ]"
          @click="activeTab = tab.id as any"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-16 text-gray-400">
        <Loader2 class="h-8 w-8 animate-spin text-[#1E3A2B] mb-2" />
        <p class="text-xs">Memuat data pengajuan refund...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="rounded-2xl bg-red-50 p-6 text-center text-sm text-red-700 border border-red-200">
        <AlertCircle class="h-6 w-6 mx-auto mb-2 text-red-600" />
        <p>{{ errorMessage }}</p>
        <button
          @click="fetchOrders"
          class="mt-3 inline-flex items-center gap-1 rounded-xl bg-red-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-red-700 transition"
        >
          Coba Lagi
        </button>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredRefunds.length === 0"
        class="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center space-y-3"
      >
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
          <RotateCcw class="h-6 w-6" />
        </div>
        <h3 class="text-base font-bold text-gray-800">Belum Ada Pengajuan Refund</h3>
        <p class="text-xs text-gray-500 max-w-md mx-auto">
          Jika Anda memiliki pesanan yang perlu dibatalkan, buka halaman Pesanan Saya dan pilih opsi Batalkan / Ajukan Refund.
        </p>
        <router-link
          to="/pendaki/orders"
          class="inline-flex items-center gap-1.5 rounded-xl bg-[#1E3A2B] px-4 py-2 text-xs font-bold text-white hover:bg-[#15281e] transition"
        >
          Buka Pesanan Saya
        </router-link>
      </div>

      <!-- Refunds List -->
      <div v-else class="space-y-4">
        <RefundTimelineCard
          v-for="refund in filteredRefunds"
          :key="refund.id"
          :refund="refund"
          @dispute="handleOpenDispute"
        />
      </div>

      <!-- Dispute Modal -->
      <DisputeEscalationModal
        :is-open="disputeModal.isOpen"
        :refund-id="disputeModal.refundId"
        :invoice="disputeModal.invoice"
        :mitra-alasan-penolakan="disputeModal.mitraAlasanPenolakan"
        @close="disputeModal.isOpen = false"
        @success="handleDisputeSuccess"
      />

      <!-- Refund Request Modal (if triggered) -->
      <RefundRequestModal
        :is-open="refundModal.isOpen"
        :invoice="refundModal.invoice"
        :total-bayar="refundModal.totalBayar"
        :tanggal-booking="refundModal.tanggalBooking"
        @close="refundModal.isOpen = false"
        @success="fetchOrders"
      />
    </div>
  </div>
</template>
