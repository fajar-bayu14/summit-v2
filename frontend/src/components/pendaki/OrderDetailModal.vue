<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  CreditCard,
  Building2,
  Calendar,
  Users,
  Ticket,
  Copy,
  Check,
  RefreshCw,
  ShieldCheck,
  QrCode,
  X,
} from 'lucide-vue-next'
import { formatRupiah, formatDateIndonesia, formatOrderStatus } from '@/lib/formatters'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import { extractApiError } from '@/lib/normalizer'
import { useToast } from '@/composables/useToast'
import type { PesananDetail } from '@/types/pendakiOrder'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    order: PesananDetail | null
  }>(),
  {
    isOpen: false,
    order: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
  (e: 'pay-order', order: PesananDetail): void
  (e: 'order-updated', order: PesananDetail): void
  (e: 'order-cancelled', order: PesananDetail): void
}>()

const router = useRouter()
const toast = useToast()

const currentOrder = ref<PesananDetail | null>(props.order)
const isCheckingStatus = ref(false)
const isCopied = ref(false)
const errorMessage = ref<string | null>(null)

watch(
  () => props.order,
  newVal => {
    currentOrder.value = newVal
  },
  { immediate: true }
)

const paymentStatusLabel = computed(() => {
  const status = currentOrder.value?.pembayaran?.status || currentOrder.value?.status
  if (status === 'paid') return { text: 'Lunas (Berhasil)', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }
  if (status === 'pending') return { text: 'Menunggu Pembayaran', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }
  if (status === 'expired') return { text: 'Pembayaran Kadaluarsa', class: 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }
  if (status === 'failed') return { text: 'Pembayaran Gagal', class: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }
  return { text: status || 'Unknown', class: 'bg-slate-100 text-slate-700' }
})

async function handleCheckPaymentStatus() {
  if (!currentOrder.value?.invoice) return
  isCheckingStatus.value = true
  errorMessage.value = null

  try {
    const res = await pendakiOrdersApi.getOrderByInvoice(currentOrder.value.invoice)
    if (res.data) {
      currentOrder.value = res.data
      emit('order-updated', res.data)
      toast.success('Status pembayaran berhasil diperbarui.', 'Status Terkini')
    }
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
    toast.error(errorMessage.value, 'Gagal Mengecek Status')
  } finally {
    isCheckingStatus.value = false
  }
}

function handleCopyReference() {
  const refId = currentOrder.value?.pembayaran?.reference_id || currentOrder.value?.invoice
  if (!refId) return
  navigator.clipboard.writeText(refId)
  isCopied.value = true
  toast.info('Nomor referensi disalin ke clipboard.')
  setTimeout(() => {
    isCopied.value = false
  }, 2000)
}

function handleNavigateTicket() {
  if (!currentOrder.value?.invoice) return
  emit('update:isOpen', false)
  router.push(`/pendaki/orders/${encodeURIComponent(currentOrder.value.invoice)}/ticket`)
}

function handlePay() {
  if (!currentOrder.value) return
  emit('pay-order', currentOrder.value)
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200"
    @click.self="emit('update:isOpen', false)"
  >
    <div class="sm:max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-6 shadow-2xl relative">
      <!-- Close button -->
      <button
        type="button"
        class="absolute top-5 right-5 p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        @click="emit('update:isOpen', false)"
      >
        <X class="w-5 h-5" />
      </button>

      <!-- Header -->
      <div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-4 pr-8">
        <div class="flex items-center justify-between gap-3">
          <span class="font-mono text-xs font-bold text-slate-500">
            Invoice: {{ currentOrder?.invoice }}
          </span>
          <span
            class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
            :class="[formatOrderStatus(currentOrder?.status).bgClass, formatOrderStatus(currentOrder?.status).textClass]"
          >
            {{ formatOrderStatus(currentOrder?.status).label }}
          </span>
        </div>
        <h2 class="text-xl font-black text-slate-900 dark:text-slate-100 font-display">
          Rincian Pesanan &amp; Pembayaran
        </h2>
        <p class="text-xs text-slate-500">
          Informasi lengkap transaksi, status verifikasi SIMAKSI, dan bukti pembayaran.
        </p>
      </div>

      <div v-if="currentOrder" class="space-y-6">
        <!-- 1. PAYMENT STATUS & AUDIT BOX -->
        <div
          class="rounded-2xl p-4 sm:p-5 border space-y-3"
          :class="[
            currentOrder.status === 'paid' ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800' :
            currentOrder.status === 'pending' ? 'bg-amber-50/70 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800' :
            'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700'
          ]"
        >
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
              <CreditCard class="w-4 h-4" :class="currentOrder.status === 'paid' ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'" />
              <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Status Pembayaran Gateway
              </h4>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="paymentStatusLabel.class">
              {{ paymentStatusLabel.text }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs">
            <div>
              <span class="text-slate-500 block">Metode Pembayaran:</span>
              <p class="font-semibold text-slate-900 dark:text-slate-100 mt-0.5 uppercase">
                {{ (currentOrder.pembayaran?.provider || currentOrder.pembayaran?.metode || 'Xendit Gateway / QRIS / VA').toUpperCase() }}
              </p>
            </div>

            <div>
              <span class="text-slate-500 block">Nomor Referensi Transaksi:</span>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="font-mono text-xs font-bold text-slate-900 dark:text-slate-100 truncate max-w-[180px]">
                  {{ currentOrder.pembayaran?.reference_id || currentOrder.invoice }}
                </span>
                <button
                  type="button"
                  class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-colors"
                  title="Salin Referensi"
                  @click="handleCopyReference"
                >
                  <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-600" />
                  <Copy v-else class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div v-if="currentOrder.pembayaran?.paid_at">
              <span class="text-slate-500 block">Waktu Pembayaran:</span>
              <p class="font-semibold text-emerald-700 dark:text-emerald-400 mt-0.5">
                {{ formatDateIndonesia(currentOrder.pembayaran.paid_at) }}
              </p>
            </div>

            <div v-else-if="currentOrder.pembayaran?.expired_at">
              <span class="text-slate-500 block">Batas Waktu Bayar:</span>
              <p class="font-semibold text-amber-700 dark:text-amber-400 mt-0.5">
                {{ formatDateIndonesia(currentOrder.pembayaran.expired_at) }}
              </p>
            </div>

            <div>
              <span class="text-slate-500 block">Jaminan Keamanan Escrow:</span>
              <div class="flex items-center gap-1.5 font-semibold text-emerald-700 dark:text-emerald-400 mt-0.5">
                <ShieldCheck class="w-3.5 h-3.5" />
                <span>Dana aman sampai check-in basecamp</span>
              </div>
            </div>
          </div>

          <!-- Pending status actions inside audit box -->
          <div v-if="currentOrder.status === 'pending'" class="pt-3 border-t border-amber-200 dark:border-amber-800/60 flex items-center justify-between gap-3 flex-wrap">
            <Button
              variant="outline"
              size="sm"
              class="rounded-xl text-xs gap-1.5"
              :disabled="isCheckingStatus"
              @click="handleCheckPaymentStatus"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isCheckingStatus }" />
              <span>Cek Status Pembayaran</span>
            </Button>

            <Button
              size="sm"
              class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs gap-1.5 shadow-xs"
              @click="handlePay"
            >
              <CreditCard class="w-3.5 h-3.5" />
              <span>Bayar Sekarang</span>
            </Button>
          </div>
        </div>

        <!-- 2. DESTINATION & BASECAMP INFO -->
        <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 space-y-2">
          <div class="flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-400">
            <Building2 class="w-4 h-4" />
            <span>Destinasi &amp; Mitra Basecamp</span>
          </div>
          <h3 class="font-black text-base text-slate-900 dark:text-slate-100">
            {{ currentOrder.jalur?.nama_jalur || 'Jalur Pendakian Resmi' }}
          </h3>
          <p class="text-xs text-slate-500">
            Mitra Pengelola: <strong class="text-slate-700 dark:text-slate-300">{{ currentOrder.basecamp?.nama_basecamp }}</strong>
          </p>
          <div class="flex items-center gap-4 text-xs text-slate-500 pt-1">
            <span class="flex items-center gap-1.5">
              <Calendar class="w-3.5 h-3.5 text-emerald-600" />
              Tanggal Naik: {{ formatDateIndonesia(currentOrder.tanggal_booking) }}
            </span>
          </div>
        </div>

        <!-- 3. CLIMBERS MANIFEST -->
        <div v-if="currentOrder.anggotas && currentOrder.anggotas.length > 0" class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <Users class="w-4 h-4 text-emerald-600" />
              <span>Manifes Rombongan ({{ currentOrder.anggotas.length }} Pendaki)</span>
            </h4>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
            <div
              v-for="(anggota, idx) in currentOrder.anggotas"
              :key="anggota.id"
              class="py-2 first:pt-0 flex items-center justify-between gap-2"
            >
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">
                  {{ idx + 1 }}. {{ anggota.nama_anggota }}
                </p>
                <p class="text-[11px] text-slate-400 font-mono">
                  NIK: {{ anggota.nik_identitas }}
                </p>
              </div>
              <span v-if="idx === 0" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-bold">
                Ketua Tim
              </span>
            </div>
          </div>
        </div>

        <!-- 4. ITEMIZED BILLING BREAKDOWN -->
        <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 space-y-3">
          <h4 class="font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
            <Ticket class="w-4 h-4 text-emerald-600" />
            <span>Rincian Item &amp; Biaya</span>
          </h4>

          <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs space-y-2">
            <div
              v-for="item in currentOrder.details"
              :key="item.id"
              class="pt-2 first:pt-0 flex items-center justify-between"
            >
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">
                  {{ item.produk?.nama_produk || 'Produk' }}
                </p>
                <p class="text-[11px] text-slate-400">
                  {{ formatRupiah(item.harga) }} x {{ item.qty }} unit
                </p>
              </div>
              <span class="font-bold text-slate-900 dark:text-slate-100">
                {{ formatRupiah(item.subtotal || item.harga * item.qty) }}
              </span>
            </div>
          </div>

          <!-- Total Calculation -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
            <div class="flex items-center justify-between">
              <span>Subtotal:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatRupiah(currentOrder.subtotal) }}</span>
            </div>
            <div v-if="currentOrder.diskon > 0" class="flex items-center justify-between text-emerald-600">
              <span>Diskon Promo:</span>
              <span>-{{ formatRupiah(currentOrder.diskon) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Biaya Layanan Platform:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatRupiah(currentOrder.biaya_layanan_user || 2500) }}</span>
            </div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-sm">
              <span class="font-bold text-slate-900 dark:text-slate-100">Total Tagihan:</span>
              <span class="font-black text-emerald-700 dark:text-emerald-400 text-base font-display">
                {{ formatRupiah(currentOrder.total_bayar) }}
              </span>
            </div>
          </div>
        </div>

        <!-- 5. FOOTER ACTIONS -->
        <div class="flex items-center justify-between gap-3 pt-2">
          <Button
            variant="ghost"
            size="sm"
            class="rounded-xl text-xs"
            @click="emit('update:isOpen', false)"
          >
            Tutup
          </Button>

          <div class="flex items-center gap-2">
            <Button
              v-if="currentOrder.status === 'paid' || currentOrder.status === 'confirmed' || currentOrder.status === 'on_going'"
              size="sm"
              class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-1.5 shadow-xs"
              @click="handleNavigateTicket"
            >
              <QrCode class="w-3.5 h-3.5" />
              <span>Lihat E-Tiket &amp; Barcode</span>
            </Button>

            <Button
              v-else-if="currentOrder.status === 'pending'"
              size="sm"
              class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold gap-1.5 shadow-xs"
              @click="handlePay"
            >
              <CreditCard class="w-3.5 h-3.5" />
              <span>Bayar Sekarang</span>
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
