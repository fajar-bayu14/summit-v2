<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import {
  X,
  CreditCard,
  Clock,
  ExternalLink,
  Copy,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Trash2,
  Check,
} from 'lucide-vue-next'
import { formatRupiah, formatDate } from '@/lib/formatters'
import { getOrderDetail, cancelOrder } from '@/api/pendakiCheckout'
import type { Pesanan } from '@/types/pendakiCheckout'
import { extractApiError } from '@/lib/normalizer'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    order: Pesanan | null
    checkoutUrl?: string | null
  }>(),
  {
    order: null,
    checkoutUrl: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'payment-success', order: Pesanan): void
  (e: 'order-cancelled', order: Pesanan): void
}>()

const isChecking = ref(false)
const isCancelling = ref(false)
const isCopied = ref(false)
const currentOrder = ref<Pesanan | null>(props.order)
const errorMessage = ref('')
const isSuccess = ref(false)

// Timer remaining in seconds
const remainingSeconds = ref(1800) // Default 30 min
let countdownTimer: ReturnType<typeof setInterval> | null = null
let pollingInterval: ReturnType<typeof setInterval> | null = null

function updateCountdown() {
  if (!currentOrder.value?.pembayaran?.expired_at) {
    if (remainingSeconds.value > 0) remainingSeconds.value--
    return
  }

  const expiryTime = new Date(currentOrder.value.pembayaran.expired_at).getTime()
  const now = new Date().getTime()
  const diff = Math.max(0, Math.floor((expiryTime - now) / 1000))
  remainingSeconds.value = diff
}

const formattedCountdown = computed(() => {
  const mins = Math.floor(remainingSeconds.value / 60)
  const secs = remainingSeconds.value % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
})

const effectiveCheckoutUrl = computed(() => {
  return props.checkoutUrl || currentOrder.value?.pembayaran?.checkout_url || null
})

async function checkPaymentStatus() {
  if (!currentOrder.value?.invoice) return
  isChecking.value = true
  errorMessage.value = ''

  try {
    const res = await getOrderDetail(currentOrder.value.invoice)
    if (res.data) {
      currentOrder.value = res.data
      if (
        res.data.status === 'paid' ||
        res.data.status === 'confirmed' ||
        res.data.pembayaran?.status === 'paid'
      ) {
        isSuccess.value = true
        stopPolling()
        emit('payment-success', res.data)
      }
    }
  } catch (err: unknown) {
    // Silent on background poll
  } finally {
    isChecking.value = false
  }
}

async function handleCancelOrder() {
  if (!currentOrder.value?.invoice) return
  if (!confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) return

  isCancelling.value = true
  errorMessage.value = ''

  try {
    const res = await cancelOrder(currentOrder.value.invoice)
    stopPolling()
    if (res.data) {
      emit('order-cancelled', res.data)
    }
    emit('update:isOpen', false)
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isCancelling.value = false
  }
}

function copyInvoice() {
  if (!currentOrder.value?.invoice) return
  navigator.clipboard.writeText(currentOrder.value.invoice)
  isCopied.value = true
  setTimeout(() => {
    isCopied.value = false
  }, 2000)
}

function openPaymentGateway() {
  if (effectiveCheckoutUrl.value) {
    window.open(effectiveCheckoutUrl.value, '_blank', 'noopener,noreferrer')
  }
}

function startPolling() {
  stopPolling()
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
  pollingInterval = setInterval(checkPaymentStatus, 5000)
}

function stopPolling() {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

watch(
  () => props.order,
  newVal => {
    currentOrder.value = newVal
    if (newVal) {
      if (
        newVal.status === 'paid' ||
        newVal.status === 'confirmed' ||
        newVal.pembayaran?.status === 'paid'
      ) {
        isSuccess.value = true
      } else {
        isSuccess.value = false
      }
    }
  },
  { immediate: true }
)

watch(
  () => props.isOpen,
  open => {
    if (open && currentOrder.value && !isSuccess.value) {
      startPolling()
    } else {
      stopPolling()
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]"
    >
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-lg bg-forest-600 text-white shadow-xs">
            <CreditCard class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-base">Pembayaran Xendit Gateway</h3>
            <p class="text-xs text-gray-500">Selesaikan transaksi tiket pendakian Anda</p>
          </div>
        </div>
        <button
          type="button"
          class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
          @click="emit('update:isOpen', false)"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto space-y-6">
        <!-- Error alert -->
        <div
          v-if="errorMessage"
          class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2"
        >
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Success state banner -->
        <div
          v-if="isSuccess"
          class="p-5 rounded-2xl bg-forest-50 border border-forest-200 text-center space-y-3"
        >
          <div class="w-12 h-12 rounded-full bg-forest-600 text-white flex items-center justify-center mx-auto shadow-md">
            <CheckCircle2 class="w-7 h-7" />
          </div>
          <div>
            <h4 class="font-bold text-forest-900 text-base">Pembayaran Berhasil Diverifikasi!</h4>
            <p class="text-xs text-forest-700 mt-1">
              Tiket SIMAKSI dan kode QR check-in Anda telah diterbitkan secara otomatis.
            </p>
          </div>
          <div class="pt-2">
            <button
              type="button"
              class="w-full py-2.5 rounded-xl font-bold text-sm bg-forest-600 hover:bg-forest-700 text-white transition-colors shadow-xs"
              @click="
                () => {
                  if (currentOrder) emit('payment-success', currentOrder)
                  emit('update:isOpen', false)
                }
              "
            >
              Lihat E-Tiket & QR Check-in
            </button>
          </div>
        </div>

        <!-- Pending state content -->
        <template v-else>
          <!-- Countdown bar -->
          <div class="p-4 rounded-xl bg-amber-50/80 border border-amber-200 flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-800">
              <Clock class="w-4 h-4 shrink-0 animate-pulse" />
              <span class="text-xs font-semibold">Sisa Waktu Pembayaran</span>
            </div>
            <div class="font-mono font-bold text-amber-900 text-base">
              {{ formattedCountdown }}
            </div>
          </div>

          <!-- Total bill and Invoice ID card -->
          <div class="bg-gray-50/80 border border-gray-200/80 rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500">Nomor Invoice</span>
              <div class="flex items-center gap-1.5">
                <span class="font-mono font-bold text-xs text-gray-900">{{
                  currentOrder?.invoice || '-'
                }}</span>
                <button
                  type="button"
                  class="p-1 text-gray-400 hover:text-forest-700 transition-colors"
                  title="Salin nomor invoice"
                  @click="copyInvoice"
                >
                  <Check v-if="isCopied" class="w-3.5 h-3.5 text-forest-600" />
                  <Copy v-else class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-200/60">
              <span class="text-xs font-medium text-gray-500">Tanggal Transaksi</span>
              <span class="text-xs font-medium text-gray-900">
                {{ formatDate(currentOrder?.created_at || new Date().toISOString()) }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-gray-200">
              <div>
                <span class="text-xs text-gray-500 block">Total Tagihan</span>
                <span class="text-xs text-forest-600 font-semibold">Termasuk biaya layanan</span>
              </div>
              <div class="text-right">
                <span class="text-xl font-extrabold text-forest-700">
                  {{ formatRupiah(currentOrder?.total_bayar || 0) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Gateway options note -->
          <div class="text-xs text-gray-500 space-y-1 bg-white p-3 rounded-xl border border-gray-100">
            <p class="font-semibold text-gray-700">Didukung oleh Xendit Secure Gateway:</p>
            <p>QRIS (BCA, Mandiri, GoPay, OVO, ShopeePay), Virtual Account Bank, dan Kartu Kredit.</p>
          </div>

          <!-- Action CTA -->
          <div class="space-y-2.5 pt-2">
            <button
              type="button"
              class="w-full py-3 px-4 rounded-xl font-bold text-sm bg-safety-600 hover:bg-safety-700 text-white flex items-center justify-center gap-2 shadow-md transition-all active:scale-[0.99]"
              @click="openPaymentGateway"
            >
              <span>Bayar via Xendit</span>
              <ExternalLink class="w-4 h-4" />
            </button>

            <button
              type="button"
              class="w-full py-2.5 px-4 rounded-xl font-semibold text-xs text-forest-700 bg-forest-50 hover:bg-forest-100 border border-forest-200 flex items-center justify-center gap-2 transition-colors disabled:opacity-50"
              :disabled="isChecking"
              @click="checkPaymentStatus"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isChecking }" />
              <span>{{ isChecking ? 'Mengecek Status...' : 'Saya Sudah Membayar (Cek Status)' }}</span>
            </button>

            <button
              type="button"
              class="w-full py-2 px-4 rounded-xl text-xs font-semibold text-gray-500 hover:text-red-600 hover:bg-red-50 flex items-center justify-center gap-1.5 transition-colors disabled:opacity-50"
              :disabled="isCancelling"
              @click="handleCancelOrder"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ isCancelling ? 'Membatalkan...' : 'Batalkan Pesanan Ini' }}</span>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
