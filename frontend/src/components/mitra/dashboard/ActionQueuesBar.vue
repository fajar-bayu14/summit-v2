<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { ActionQueuesSummary, ResourcesSummary } from '@/types/dashboard'
import {
  Ticket,
  ShieldCheck,
  RefreshCw,
  AlertTriangle,
  ChevronRight,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    actionQueues?: ActionQueuesSummary | null
    resources?: ResourcesSummary | null
  }>(),
  {
    actionQueues: null,
    resources: null,
  }
)

let router: any = null
try {
  router = useRouter()
} catch {
  router = null
}

function navigateTo(path: string) {
  if (router) {
    router.push(path)
  }
}
</script>

<template>
  <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card p-4 sm:p-5 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
      <div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
          <span>Antrean Aksi Operasional Segera</span>
        </h4>
      </div>
      <span class="text-[11px] text-muted-foreground">Tindakan mendesak hari ini</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Queue 1: Pesanan Menunggu Check-in -->
      <button
        type="button"
        class="flex items-center justify-between p-3 rounded-xl border border-slate-200/60 dark:border-slate-800 bg-muted/20 hover:bg-muted/60 transition-all text-left group cursor-pointer"
        @click="navigateTo('/mitra/orders?status=paid')"
      >
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
            <Ticket class="h-4 w-4" />
          </div>
          <div>
            <div class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">
              Siap Check-in
            </div>
            <div class="text-[11px] text-muted-foreground">
              {{ props.actionQueues?.pesanan_paid_count ?? 0 }} pesanan lunas
            </div>
          </div>
        </div>
        <ChevronRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
      </button>

      <!-- Queue 2: Verifikasi Logbook Summit -->
      <button
        type="button"
        class="flex items-center justify-between p-3 rounded-xl border border-slate-200/60 dark:border-slate-800 bg-muted/20 hover:bg-muted/60 transition-all text-left group cursor-pointer"
        @click="navigateTo('/mitra/logbooks?status_validasi=pending')"
      >
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <ShieldCheck class="h-4 w-4" />
          </div>
          <div>
            <div class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">
              Bukti Summit
            </div>
            <div class="text-[11px] text-muted-foreground">
              {{ props.actionQueues?.logbook_pending_count ?? 0 }} pending validasi
            </div>
          </div>
        </div>
        <ChevronRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
      </button>

      <!-- Queue 3: Review Refund -->
      <button
        type="button"
        class="flex items-center justify-between p-3 rounded-xl border border-slate-200/60 dark:border-slate-800 bg-muted/20 hover:bg-muted/60 transition-all text-left group cursor-pointer"
        @click="navigateTo('/mitra/refunds?status=pending')"
      >
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
            <RefreshCw class="h-4 w-4" />
          </div>
          <div>
            <div class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">
              Klaim Refund
            </div>
            <div class="text-[11px] text-muted-foreground">
              {{ props.actionQueues?.refund_pending_count ?? 0 }} permohonan
            </div>
          </div>
        </div>
        <ChevronRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
      </button>

      <!-- Queue 4: Stok Alat Menipis -->
      <button
        type="button"
        class="flex items-center justify-between p-3 rounded-xl border border-slate-200/60 dark:border-slate-800 bg-muted/20 hover:bg-muted/60 transition-all text-left group cursor-pointer"
        @click="navigateTo('/mitra/products')"
      >
        <div class="flex items-center gap-2.5">
          <div class="h-8 w-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <AlertTriangle class="h-4 w-4" />
          </div>
          <div>
            <div class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors">
              Stok Rental Menipis
            </div>
            <div class="text-[11px] text-muted-foreground">
              {{ props.resources?.produk_stok_menipis_count ?? 0 }} alat &lt; 3 unit
            </div>
          </div>
        </div>
        <ChevronRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
      </button>
    </div>
  </div>
</template>
