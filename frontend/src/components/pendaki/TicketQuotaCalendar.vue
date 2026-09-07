<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Calendar as CalendarIcon,
  Clock,
  Users,
  Plus,
  Minus,
  ShoppingCart,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatRupiah, formatDateIndonesia } from '@/lib/formatters'
import type { ProdukCatalogItem, DailyQuotaItem } from '@/types/pendakiProduct'

const props = defineProps<{
  ticketProduct: ProdukCatalogItem
}>()

const emit = defineEmits<{
  (
    e: 'add-ticket',
    payload: {
      product: ProdukCatalogItem
      selectedDate: string
      climberCount: number
      totalAmount: number
    }
  ): void
}>()

const selectedDate = ref<string>('')
const climberCount = ref<number>(1)

const quotaList = computed<DailyQuotaItem[]>(() => {
  return props.ticketProduct.tiket?.kuotas || []
})

const selectedQuotaItem = computed(() => {
  if (!selectedDate.value) return null
  return quotaList.value.find(q => q.tanggal === selectedDate.value) || null
})

const maxAllowedClimbers = computed(() => {
  if (!selectedQuotaItem.value) return 10
  return Math.min(10, selectedQuotaItem.value.kuota_tersisa)
})

const isDateAvailable = computed(() => {
  if (!selectedQuotaItem.value) return true // Default open if unconstrained
  return selectedQuotaItem.value.kuota_tersisa > 0
})

const totalAmount = computed(() => {
  return props.ticketProduct.harga * climberCount.value
})

function handleDateSelect(tanggal: string, kuotaTersisa: number) {
  if (kuotaTersisa <= 0) return
  selectedDate.value = tanggal
  if (climberCount.value > kuotaTersisa) {
    climberCount.value = kuotaTersisa
  }
}

function incrementClimbers() {
  if (climberCount.value < maxAllowedClimbers.value) {
    climberCount.value++
  }
}

function decrementClimbers() {
  if (climberCount.value > 1) {
    climberCount.value--
  }
}

function handleAddToCart() {
  if (!selectedDate.value || !isDateAvailable.value) return

  emit('add-ticket', {
    product: props.ticketProduct,
    selectedDate: selectedDate.value,
    climberCount: climberCount.value,
    totalAmount: totalAmount.value,
  })
}
</script>

<template>
  <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xs space-y-6">
    <!-- Header Ticket Info -->
    <div class="flex flex-wrap items-start justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <Badge class="bg-emerald-600 text-white font-bold text-xs border-0">
            SIMAKSI Resmi
          </Badge>
          <span v-if="ticketProduct.tiket?.jam_buka" class="text-xs text-slate-500 flex items-center gap-1">
            <Clock class="w-3.5 h-3.5" />
            Buka: {{ ticketProduct.tiket.jam_buka }} - {{ ticketProduct.tiket.jam_tutup || '17:00' }}
          </span>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
          {{ ticketProduct.nama_produk }}
        </h3>
        <p class="text-xs text-slate-500 max-w-xl">
          {{ ticketProduct.deskripsi || 'Tiket masuk resmi kawasan konservasi dan registrasi pendakian per orang per hari.' }}
        </p>
      </div>

      <div class="text-right">
        <span class="text-xs text-slate-500">Harga per Pendaki</span>
        <p class="text-xl font-extrabold text-emerald-700 dark:text-emerald-400 font-display">
          {{ formatRupiah(ticketProduct.harga) }}
        </p>
      </div>
    </div>

    <!-- Daily Quota Selector Grid -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
          <CalendarIcon class="w-4 h-4 text-emerald-600" />
          <span>Pilih Tanggal Rencana Pendakian</span>
        </label>
        <span class="text-[11px] text-slate-500">
          Klik tanggal untuk cek kuota
        </span>
      </div>

      <!-- Quota Tiles Grid -->
      <div v-if="quotaList.length > 0" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
        <button
          v-for="q in quotaList"
          :key="q.id"
          type="button"
          :disabled="q.kuota_tersisa <= 0"
          class="p-2.5 rounded-2xl border text-left transition-all duration-200 flex flex-col justify-between space-y-1 relative"
          :class="[
            selectedDate === q.tanggal
              ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 ring-2 ring-emerald-600/30'
              : q.kuota_tersisa <= 0
              ? 'opacity-45 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 cursor-not-allowed'
              : 'border-slate-200/80 dark:border-slate-800 hover:border-emerald-500 bg-white dark:bg-slate-900',
          ]"
          @click="handleDateSelect(q.tanggal, q.kuota_tersisa)"
        >
          <span class="text-[10px] font-medium text-slate-500 uppercase truncate">
            {{ formatDateIndonesia(q.tanggal) }}
          </span>

          <div class="flex items-center justify-between gap-1">
            <span class="text-xs font-bold" :class="q.kuota_tersisa > 0 ? 'text-slate-900 dark:text-slate-100' : 'text-rose-500'">
              {{ q.kuota_tersisa > 0 ? `${q.kuota_tersisa} Kuota` : 'Habis' }}
            </span>
            <span
              class="w-2 h-2 rounded-full shrink-0"
              :class="[
                q.kuota_tersisa > q.kuota_total * 0.5
                  ? 'bg-emerald-500'
                  : q.kuota_tersisa > 0
                  ? 'bg-amber-500'
                  : 'bg-rose-500',
              ]"
            ></span>
          </div>
        </button>
      </div>

      <!-- Fallback Native Date Input if no pre-generated quotas -->
      <div v-else class="space-y-2">
        <input
          type="date"
          v-model="selectedDate"
          class="h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs sm:text-sm text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 w-full sm:w-64"
        />
      </div>
    </div>

    <!-- Climber Stepper & Summary Bar -->
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
      <!-- Climber Count Stepper -->
      <div class="flex items-center gap-3">
        <label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
          <Users class="w-4 h-4 text-emerald-600" />
          <span>Jumlah Anggota:</span>
        </label>
        <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-1">
          <button
            type="button"
            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
            :disabled="climberCount <= 1"
            @click="decrementClimbers"
          >
            <Minus class="w-3.5 h-3.5" />
          </button>
          <span class="w-10 text-center font-extrabold text-xs text-slate-900 dark:text-slate-100">
            {{ climberCount }}
          </span>
          <button
            type="button"
            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
            :disabled="climberCount >= maxAllowedClimbers"
            @click="incrementClimbers"
          >
            <Plus class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Price & CTA Action -->
      <div class="flex items-center gap-4">
        <div class="text-right">
          <span class="text-[11px] text-slate-500">Total Tiket SIMAKSI</span>
          <p class="text-base font-extrabold text-slate-900 dark:text-slate-100">
            {{ formatRupiah(totalAmount) }}
          </p>
        </div>

        <Button
          class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs gap-2 shadow-xs"
          :disabled="!selectedDate || !isDateAvailable"
          @click="handleAddToCart"
        >
          <ShoppingCart class="w-4 h-4" />
          <span>+ Masukkan Tiket</span>
        </Button>
      </div>
    </div>
  </div>
</template>
