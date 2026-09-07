<script setup lang="ts">
import { ref, watch } from 'vue'
import { pendakiRefundApi } from '@/api/pendakiRefund'
import { extractApiError } from '@/lib/normalizer'
import type { RefundItem } from '@/types/pendakiRefund'
import {
  AlertTriangle,
  CheckCircle2,
  Loader2,
  Scale,
  ShieldAlert,
  X
} from 'lucide-vue-next'

const props = defineProps<{
  isOpen: boolean
  refundId: number
  invoice?: string
  mitraAlasanPenolakan?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', refund: RefundItem): void
}>()

const alasanDispute = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      alasanDispute.value = ''
      errorMessage.value = ''
      successMessage.value = ''
    }
  }
)

async function handleSubmit() {
  errorMessage.value = ''
  if (!alasanDispute.value.trim() || alasanDispute.value.trim().length < 15) {
    errorMessage.value = 'Alasan banding sengketa wajib diisi minimal 15 karakter.'
    return
  }

  loading.value = true
  try {
    const res = await pendakiRefundApi.disputeRefund(props.refundId, {
      alasan_dispute: alasanDispute.value.trim()
    })
    successMessage.value = 'Banding sengketa berhasil diteruskan ke Admin Platform Summit.'
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
      <div class="flex items-center justify-between border-b border-rose-100 bg-rose-50/75 px-6 py-4">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
            <Scale class="h-5 w-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900">Eskalasi Pusat Sengketa (Dispute)</h3>
            <p class="text-xs text-rose-700 font-medium">Mediasi Resmi Tim Admin Summit</p>
          </div>
        </div>
        <button
          class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
          @click="emit('close')"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Modal Body -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Error / Success Alert -->
        <div
          v-if="errorMessage"
          class="flex items-center gap-2 rounded-xl bg-red-50 p-3 text-sm text-red-700 border border-red-200"
        >
          <AlertTriangle class="h-4 w-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <div
          v-if="successMessage"
          class="flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700 border border-emerald-200"
        >
          <CheckCircle2 class="h-4 w-4 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- Rejection Context Card -->
        <div v-if="mitraAlasanPenolakan" class="rounded-xl border border-rose-200 bg-rose-50/50 p-3.5 space-y-1">
          <div class="flex items-center gap-1.5 text-xs font-bold text-rose-800">
            <ShieldAlert class="h-4 w-4" />
            <span>Alasan Penolakan Mitra:</span>
          </div>
          <p class="text-xs text-rose-700 italic">"{{ mitraAlasanPenolakan }}"</p>
        </div>

        <!-- Dispute Input Form -->
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
            Alasan Banding / Kronologi Kejadian
          </label>
          <textarea
            v-model="alasanDispute"
            rows="4"
            placeholder="Jelaskan bukti atau alasan mengapa penolakan refund oleh mitra tidak tepat (min. 15 karakter)..."
            class="w-full rounded-xl border border-gray-300 p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-rose-500 focus:outline-hidden focus:ring-1 focus:ring-rose-500"
            required
          ></textarea>
        </div>

        <!-- Notice Box -->
        <div class="rounded-xl bg-slate-50 p-3 text-xs text-gray-500 space-y-1">
          <p class="font-bold text-gray-700">Ketentuan Mediasi Admin Summit:</p>
          <p>
            1. Admin akan meninjau riwayat chat, bukti foto, dan kebijakan SOP operasional basecamp.
          </p>
          <p>
            2. Keputusan yang diterbitkan oleh Tim Admin Summit bersifat final dan mengikat kedua belah pihak.
          </p>
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
            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50 transition"
          >
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <span>Kirim Banding ke Admin</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
