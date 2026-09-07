<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
  Wallet,
  Clock,
  ArrowUpRight,
  Building2,
  CheckCircle2,
  TrendingUp,
} from 'lucide-vue-next'
import type { MitraWallet } from '@/types/wallet'

const props = withDefaults(
  defineProps<{
    wallet: MitraWallet | null
    loading?: boolean
  }>(),
  {
    wallet: null,
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'requestWithdrawal'): void
}>()

function formatRupiah(amount?: number): string {
  if (typeof amount !== 'number') return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <!-- Card 1: Saldo Siap Ditarik (Available) -->
    <div
      class="p-5 bg-gradient-to-br from-[#1E3A2B] to-[#2D5A43] text-white rounded-2xl shadow-sm flex flex-col justify-between space-y-4"
    >
      <div class="flex items-start justify-between">
        <div class="space-y-1">
          <span class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">
            Saldo Siap Ditarik
          </span>
          <div class="text-2xl font-extrabold tracking-tight">
            {{ formatRupiah(props.wallet?.saldo_available) }}
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center">
          <Wallet class="w-5 h-5 text-emerald-200" />
        </div>
      </div>

      <div class="pt-2 border-t border-white/10 flex items-center justify-between">
        <span class="text-[11px] text-emerald-200">
          Dana bersih siap ditransfer
        </span>
        <Button
          size="sm"
          class="h-8 px-3 rounded-lg bg-white text-[#1E3A2B] hover:bg-emerald-50 text-xs font-bold shadow-xs cursor-pointer"
          :disabled="props.loading || (props.wallet?.saldo_available ?? 0) < 50000"
          @click="emit('requestWithdrawal')"
        >
          <ArrowUpRight class="w-3.5 h-3.5 mr-1" />
          Tarik Dana
        </Button>
      </div>
    </div>

    <!-- Card 2: Saldo Pending Escrow -->
    <div
      class="p-5 bg-white border border-stone-200 rounded-2xl shadow-xs flex flex-col justify-between space-y-4"
    >
      <div class="flex items-start justify-between">
        <div class="space-y-1">
          <span class="text-xs font-bold text-amber-700 uppercase tracking-wider">
            Saldo Pending Escrow
          </span>
          <div class="text-2xl font-extrabold text-stone-900 tracking-tight">
            {{ formatRupiah(props.wallet?.saldo_pending) }}
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
          <Clock class="w-5 h-5 text-amber-600" />
        </div>
      </div>

      <div class="pt-2 border-t border-stone-100 flex items-center gap-1.5 text-[11px] text-stone-500">
        <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
        <span>Tertahan aman pada transaksi on-going</span>
      </div>
    </div>

    <!-- Card 3: Total Telah Dicairkan -->
    <div
      class="p-5 bg-white border border-stone-200 rounded-2xl shadow-xs flex flex-col justify-between space-y-4"
    >
      <div class="flex items-start justify-between">
        <div class="space-y-1">
          <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">
            Total Telah Dicairkan
          </span>
          <div class="text-2xl font-extrabold text-stone-900 tracking-tight">
            {{ formatRupiah(props.wallet?.total_withdrawn) }}
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
          <TrendingUp class="w-5 h-5 text-blue-600" />
        </div>
      </div>

      <div class="pt-2 border-t border-stone-100 flex items-center gap-1.5 text-[11px] text-stone-500">
        <CheckCircle2 class="w-3.5 h-3.5 text-blue-600 shrink-0" />
        <span>Akumulasi dana berhasil masuk ke rekening</span>
      </div>
    </div>

    <!-- Card 4: Rekening Bank Tujuan -->
    <div
      class="p-5 bg-stone-50 border border-stone-200 rounded-2xl shadow-xs flex flex-col justify-between space-y-3"
    >
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-wider">
          Rekening Pencairan
        </span>
        <div class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
          <CheckCircle2 class="w-3 h-3 text-emerald-600" />
          Aktif
        </div>
      </div>

      <div>
        <div class="flex items-center gap-2">
          <Building2 class="w-4 h-4 text-stone-500 shrink-0" />
          <span class="font-bold text-stone-900 text-sm">
            {{ props.wallet?.rekening_tujuan?.bank || 'Bank Terdaftar' }}
          </span>
        </div>
        <div class="font-mono text-xs font-bold text-stone-800 tracking-wide mt-1">
          {{ props.wallet?.rekening_tujuan?.rekening_bank || '1234-5678-90' }}
        </div>
        <div class="text-[11px] text-stone-500 truncate mt-0.5">
          a.n {{ props.wallet?.rekening_tujuan?.nama_rekening || 'Mitra Basecamp' }}
        </div>
      </div>

      <div class="text-[10px] text-stone-400 border-t border-stone-200 pt-1.5">
        Hubungi Admin untuk perubahan rekening bank.
      </div>
    </div>
  </div>
</template>
