<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { pendakiRefundApi } from '@/api/pendakiRefund'
import { formatRupiah, formatDate } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { RefundCategory, RefundItem } from '@/types/pendakiRefund'
import {
  AlertCircle,
  CheckCircle2,
  HelpCircle,
  Info,
  Loader2,
  X
} from 'lucide-vue-next'

const props = defineProps<{
  isOpen: boolean
  invoice: string
  totalBayar: number
  tanggalBooking: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', refund: RefundItem): void
}>()

const bankList = [
  'BCA',
  'Mandiri',
  'BRI',
  'BNI',
  'BSI',
  'GoPay',
  'OVO',
  'DANA',
  'ShopeePay'
]

const form = ref({
  category: 'pre_trip' as RefundCategory,
  bankTujuan: 'BCA',
  rekeningTujuan: '',
  namaTujuan: '',
  alasan: '',
  nominalCustom: 0
})

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Calculate difference in days between now and trip date
const daysDifference = computed(() => {
  if (!props.tanggalBooking) return 0
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const tripDate = new Date(props.tanggalBooking)
  tripDate.setHours(0, 0, 0, 0)
  const diffTime = tripDate.getTime() - today.getTime()
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

// SOP Refund rate calculation
const calculatedRefund = computed(() => {
  const diff = daysDifference.value
  if (form.value.category === 'force_majeure') {
    return {
      percentage: 100,
      nominal: props.totalBayar,
      label: '100% Pengembalian Penuh (Force Majeure / Jalur Tutup)',
      badgeClass: 'bg-emerald-100 text-emerald-800'
    }
  }

  if (diff >= 3) {
    return {
      percentage: 100,
      nominal: props.totalBayar,
      label: '100% Pengembalian Penuh (Pembatalan >= H-3)',
      badgeClass: 'bg-emerald-100 text-emerald-800'
    }
  } else if (diff >= 1) {
    return {
      percentage: 50,
      nominal: Math.round(props.totalBayar * 0.5),
      label: '50% Pengembalian Parsial (Pembatalan H-1 s/d H-2)',
      badgeClass: 'bg-amber-100 text-amber-800'
    }
  } else {
    return {
      percentage: 0,
      nominal: form.value.nominalCustom || props.totalBayar,
      label: 'Pengajuan Khusus / Insiden Lapangan (Hari H)',
      badgeClass: 'bg-blue-100 text-blue-800'
    }
  }
})

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      errorMessage.value = ''
      successMessage.value = ''
      form.value = {
        category: 'pre_trip',
        bankTujuan: 'BCA',
        rekeningTujuan: '',
        namaTujuan: '',
        alasan: '',
        nominalCustom: props.totalBayar
      }
    }
  }
)

async function handleSubmit() {
  errorMessage.value = ''
  if (!form.value.rekeningTujuan.trim()) {
    errorMessage.value = 'Nomor rekening atau nomor e-wallet tujuan wajib diisi.'
    return
  }
  if (!form.value.namaTujuan.trim()) {
    errorMessage.value = 'Nama pemilik rekening wajib diisi.'
    return
  }
  if (!form.value.alasan.trim() || form.value.alasan.trim().length < 10) {
    errorMessage.value = 'Alasan refund wajib diisi minimal 10 karakter.'
    return
  }

  loading.value = true
  try {
    const payload = {
      alasan: form.value.alasan,
      bank_tujuan: form.value.bankTujuan,
      rekening_tujuan: form.value.rekeningTujuan,
      nama_tujuan: form.value.namaTujuan,
      nominal: calculatedRefund.value.nominal,
      refund_category: form.value.category
    }

    const res = await pendakiRefundApi.requestRefund(props.invoice, payload)
    successMessage.value = 'Pengajuan refund berhasil dikirim. Menunggu peninjauan oleh Mitra.'
    if (res.data) {
      emit('success', res.data)
    }
    setTimeout(() => {
      emit('close')
    }, 1500)
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
  >
    <div
      class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200"
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between border-b border-gray-100 bg-slate-50/75 px-6 py-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900">Pengajuan Refund & Pembatalan</h3>
          <p class="text-xs text-gray-500">Invoice: {{ invoice }}</p>
        </div>
        <button
          class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
          @click="emit('close')"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
        <!-- Error / Success Alert -->
        <div
          v-if="errorMessage"
          class="flex items-center gap-2 rounded-xl bg-red-50 p-3 text-sm text-red-700 border border-red-200"
        >
          <AlertCircle class="h-4 w-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <div
          v-if="successMessage"
          class="flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700 border border-emerald-200"
        >
          <CheckCircle2 class="h-4 w-4 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Order & SOP Summary Card -->
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 space-y-2">
          <div class="flex justify-between text-xs text-gray-600">
            <span>Tanggal Pendakian:</span>
            <span class="font-semibold text-gray-900">{{ formatDate(tanggalBooking) }}</span>
          </div>
          <div class="flex justify-between text-xs text-gray-600">
            <span>Total Pembayaran Asli:</span>
            <span class="font-bold text-gray-900">{{ formatRupiah(totalBayar) }}</span>
          </div>

          <!-- Refund Category Policy Badge -->
          <div class="pt-2 border-t border-emerald-100">
            <span
              :class="[
                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold',
                calculatedRefund.badgeClass
              ]"
            >
              <Info class="h-3.5 w-3.5" />
              {{ calculatedRefund.label }}
            </span>
            <div class="mt-2 flex justify-between items-center">
              <span class="text-xs text-gray-600 font-medium">Estimasi Dana Dikembalikan:</span>
              <span class="text-base font-extrabold text-[#1E3A2B]">
                {{ formatRupiah(calculatedRefund.nominal) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Refund Category Selector -->
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
            Kategori Alasan Refund
          </label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              :class="[
                'px-3 py-2 text-xs font-semibold rounded-lg border text-left transition',
                form.category === 'pre_trip'
                  ? 'border-[#1E3A2B] bg-[#1E3A2B]/10 text-[#1E3A2B]'
                  : 'border-gray-200 text-gray-600 hover:bg-gray-50'
              ]"
              @click="form.category = 'pre_trip'"
            >
              🗓️ Pembatalan Pribadi
            </button>
            <button
              type="button"
              :class="[
                'px-3 py-2 text-xs font-semibold rounded-lg border text-left transition',
                form.category === 'force_majeure'
                  ? 'border-[#1E3A2B] bg-[#1E3A2B]/10 text-[#1E3A2B]'
                  : 'border-gray-200 text-gray-600 hover:bg-gray-50'
              ]"
              @click="form.category = 'force_majeure'"
            >
              🌪️ Force Majeure / Jalur Tutup
            </button>
          </div>
        </div>

        <!-- Bank / E-Wallet Destination Form -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
              Bank / E-Wallet
            </label>
            <select
              v-model="form.bankTujuan"
              class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E3A2B] focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
            >
              <option v-for="bank in bankList" :key="bank" :value="bank">
                {{ bank }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
              Nomor Rekening / No HP
            </label>
            <input
              v-model="form.rekeningTujuan"
              type="text"
              placeholder="Contoh: 1234567890"
              class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#1E3A2B] focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
              required
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
            Nama Pemilik Rekening
          </label>
          <input
            v-model="form.namaTujuan"
            type="text"
            placeholder="Sesuai buku tabungan / akun e-wallet"
            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#1E3A2B] focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
            required
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
            Penjelasan Alasan Pembatalan
          </label>
          <textarea
            v-model="form.alasan"
            rows="3"
            placeholder="Jelaskan alasan pengajuan refund secara lengkap..."
            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#1E3A2B] focus:outline-hidden focus:ring-1 focus:ring-[#1E3A2B]"
            required
          ></textarea>
        </div>

        <!-- Help Info Note -->
        <div class="flex items-start gap-2 rounded-xl bg-gray-50 p-3 text-xs text-gray-500">
          <HelpCircle class="h-4 w-4 shrink-0 text-gray-400 mt-0.5" />
          <span>
            Proses persetujuan refund akan ditinjau oleh Mitra pengelola basecamp maksimal 1x24 jam. Jika ditolak tidak beralasan, Anda dapat mengajukan sengketa ke Admin Platform.
          </span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            type="button"
            class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
            @click="emit('close')"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="inline-flex items-center gap-2 rounded-xl bg-[#E65100] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#d84a00] disabled:opacity-50 transition"
          >
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <span>Kirim Pengajuan Refund</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
