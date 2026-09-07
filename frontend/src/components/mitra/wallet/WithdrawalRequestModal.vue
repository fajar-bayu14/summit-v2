<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Wallet,
  Building2,
  CheckCircle2,
  AlertCircle,
  ArrowUpRight,
  Loader2,
} from 'lucide-vue-next'
import type { MitraWallet, WithdrawalRequest, StoreWithdrawalPayload } from '@/types/wallet'
import mitraWalletApi from '@/api/mitraWallet'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    wallet: MitraWallet | null
  }>(),
  {
    isOpen: false,
    wallet: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submitted', withdrawal: WithdrawalRequest): void
  (e: 'close'): void
}>()

const amount = ref<number | ''>('')
const notes = ref<string>('')
const isSubmitting = ref<boolean>(false)
const formError = ref<string | null>(null)
const serverError = ref<string | null>(null)
const serverSuccess = ref<string | null>(null)

const maxAvailable = computed(() => props.wallet?.saldo_available ?? 0)

const quickAmounts = [100000, 250000, 500000, 1000000]

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      amount.value = ''
      notes.value = ''
      formError.value = null
      serverError.value = null
      serverSuccess.value = null
      isSubmitting.value = false
    }
  }
)

function formatRupiah(val?: number): string {
  if (typeof val !== 'number') return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val)
}

function setQuickAmount(preset: number) {
  if (preset <= maxAvailable.value) {
    amount.value = preset
    formError.value = null
  }
}

function setMaxAmount() {
  if (maxAvailable.value >= 50000) {
    amount.value = maxAvailable.value
    formError.value = null
  }
}

async function handleSubmit() {
  formError.value = null
  serverError.value = null
  serverSuccess.value = null

  const nominalVal = Number(amount.value)

  if (!nominalVal || isNaN(nominalVal) || nominalVal <= 0) {
    formError.value = 'Silakan masukkan nominal penarikan yang valid.'
    return
  }

  if (nominalVal < 50000) {
    formError.value = 'Nominal penarikan minimal adalah Rp 50.000.'
    return
  }

  if (nominalVal > maxAvailable.value) {
    formError.value = `Nominal penarikan melebihi saldo tersedia (${formatRupiah(maxAvailable.value)}).`
    return
  }

  isSubmitting.value = true

  try {
    const payload: StoreWithdrawalPayload = {
      nominal: nominalVal,
      catatan: notes.value.trim() || undefined,
    }

    const res = await mitraWalletApi.requestWithdrawal(payload)
    serverSuccess.value = 'Pengajuan penarikan dana berhasil dibuat dan segera diproses admin.'
    if (res.data) {
      emit('submitted', res.data)
    }
  } catch (err) {
    serverError.value = getApiErrorMessage(err, 'Gagal mengajukan penarikan dana.')
  } finally {
    isSubmitting.value = false
  }
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-xl bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex items-center gap-3 border-b border-stone-200 pb-3">
          <div class="w-11 h-11 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center font-bold">
            <ArrowUpRight class="w-6 h-6" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              Ajukan Penarikan Dana (Payout)
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500 mt-0.5">
              Cairkan pendapatan operasional basecamp ke rekening bank terdaftar Anda.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Feedback Alerts -->
      <div
        v-if="serverSuccess"
        class="flex items-center gap-2.5 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium border border-emerald-200 mt-3"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ serverSuccess }}</span>
      </div>

      <div
        v-if="serverError"
        class="flex items-center gap-2.5 p-3.5 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200 mt-3"
      >
        <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
        <span>{{ serverError }}</span>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-3">
        <!-- Saldo Tersedia Banner -->
        <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <Wallet class="w-5 h-5 text-[#1E3A2B]" />
            <div>
              <span class="text-[10px] uppercase font-bold text-emerald-800">Saldo Siap Ditarik</span>
              <div class="text-base font-extrabold text-[#1E3A2B]">
                {{ formatRupiah(maxAvailable) }}
              </div>
            </div>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="h-8 text-xs font-bold text-[#1E3A2B] border-emerald-300 hover:bg-emerald-100"
            :disabled="maxAvailable < 50000"
            @click="setMaxAmount"
          >
            Tarik Semua
          </Button>
        </div>

        <!-- Rekening Penerima Preview -->
        <div class="p-3.5 bg-stone-50 border border-stone-200 rounded-xl space-y-1">
          <div class="flex items-center justify-between text-[11px] text-stone-500 font-medium">
            <span>Rekening Tujuan Penerima:</span>
            <span class="flex items-center gap-1 text-emerald-700 font-bold text-[10px]">
              <CheckCircle2 class="w-3 h-3 text-emerald-600" /> Terverifikasi
            </span>
          </div>
          <div class="flex items-center gap-2 text-xs font-bold text-stone-900 pt-1">
            <Building2 class="w-4 h-4 text-stone-500" />
            <span>{{ props.wallet?.rekening_tujuan?.bank || 'Bank Terdaftar' }}</span>
            <span>—</span>
            <span class="font-mono">{{ props.wallet?.rekening_tujuan?.rekening_bank || '1234-5678-90' }}</span>
          </div>
          <div class="text-[11px] text-stone-600 pl-6">
            a.n {{ props.wallet?.rekening_tujuan?.nama_rekening || 'Mitra Basecamp' }}
          </div>
        </div>

        <!-- Input Nominal Penarikan -->
        <div class="space-y-2">
          <label class="text-xs font-bold text-stone-700">
            Nominal Penarikan (IDR) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-stone-400">
              Rp
            </span>
            <Input
              v-model.number="amount"
              type="number"
              placeholder="Contoh: 500000"
              class="pl-10 h-11 rounded-xl text-sm font-bold border-stone-300 focus:border-[#1E3A2B]"
              min="50000"
              :max="maxAvailable"
            />
          </div>
          <p v-if="formError" class="text-xs text-red-600 font-medium">{{ formError }}</p>

          <!-- Quick Preset Buttons -->
          <div class="flex flex-wrap items-center gap-1.5 pt-1">
            <button
              v-for="preset in quickAmounts"
              :key="preset"
              type="button"
              :disabled="preset > maxAvailable"
              :class="[
                'px-2.5 py-1 text-[11px] font-bold rounded-lg border transition-all cursor-pointer',
                amount === preset
                  ? 'bg-[#1E3A2B] text-white border-[#1E3A2B]'
                  : 'bg-stone-50 text-stone-600 border-stone-200 hover:bg-stone-100 disabled:opacity-40 disabled:cursor-not-allowed',
              ]"
              @click="setQuickAmount(preset)"
            >
              {{ formatRupiah(preset) }}
            </button>
          </div>
        </div>

        <!-- Input Catatan Opsional -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-stone-700">
            Catatan Penarikan <span class="text-stone-400 font-normal">(Opsional)</span>:
          </label>
          <Input
            v-model="notes"
            type="text"
            placeholder="Contoh: Payout mingguan operasional pos..."
            class="h-10 rounded-xl text-xs border-stone-300 focus:border-[#1E3A2B]"
          />
        </div>

        <!-- Submit Button -->
        <Button
          type="submit"
          :disabled="isSubmitting || maxAvailable < 50000"
          class="w-full h-11 rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white font-bold text-xs shadow-sm min-h-[44px]"
        >
          <Loader2 v-if="isSubmitting" class="w-4 h-4 mr-2 animate-spin" />
          <ArrowUpRight v-else class="w-4 h-4 mr-2" />
          Konfirmasi & Ajukan Penarikan
        </Button>
      </form>

      <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3 border-t border-stone-200">
        <Button
          type="button"
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          @click="handleClose"
        >
          Tutup
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
