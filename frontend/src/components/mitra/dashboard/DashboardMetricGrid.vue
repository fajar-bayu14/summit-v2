<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import type { MitraAnalyticsSummary } from '@/types/dashboard'
import {
  Wallet,
  Users,
  Compass,
  Mountain,
  AlertTriangle,
  CheckCircle2,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    metrics: MitraAnalyticsSummary | null
    loading?: boolean
  }>(),
  {
    loading: false,
  }
)

function formatRupiah(value: number | undefined | null): string {
  if (value === undefined || value === null) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

const financial = computed(() => props.metrics?.financial)
const operations = computed(() => props.metrics?.operations_today)
const resources = computed(() => props.metrics?.resources)

const isTrailOpen = computed(() => operations.value?.status_jalur === 'open')
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Card 1: Saldo Siap Tarik & Finansial -->
    <Card class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs hover:shadow-md transition-all duration-200">
      <CardContent class="p-5">
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Saldo Siap Tarik</span>
          <div class="h-10 w-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <Wallet class="h-5 w-5 stroke-[2]" />
          </div>
        </div>

        <div v-if="loading" class="space-y-2">
          <div class="h-8 bg-muted animate-pulse rounded-md w-3/4"></div>
          <div class="h-4 bg-muted animate-pulse rounded-md w-1/2"></div>
        </div>
        <div v-else>
          <h3 class="text-2xl font-bold tracking-tight text-foreground font-mono">
            {{ formatRupiah(financial?.saldo_available) }}
          </h3>
          <p class="text-xs text-muted-foreground mt-1.5 flex items-center gap-1">
            <span class="font-medium text-emerald-600 dark:text-emerald-400">
              {{ formatRupiah(financial?.total_pendapatan_bersih) }}
            </span>
            <span>total omzet bersih</span>
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Card 2: Pendaki Hari Ini -->
    <Card class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs hover:shadow-md transition-all duration-200">
      <CardContent class="p-5">
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Pendaki Hari Ini</span>
          <div class="h-10 w-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
            <Users class="h-5 w-5 stroke-[2]" />
          </div>
        </div>

        <div v-if="loading" class="space-y-2">
          <div class="h-8 bg-muted animate-pulse rounded-md w-3/4"></div>
          <div class="h-4 bg-muted animate-pulse rounded-md w-1/2"></div>
        </div>
        <div v-else>
          <h3 class="text-2xl font-bold tracking-tight text-foreground font-mono">
            {{ operations?.pendaki_berangkat_hari_ini || 0 }} <span class="text-sm font-sans font-medium text-muted-foreground">Orang</span>
          </h3>
          <p class="text-xs text-muted-foreground mt-1.5 flex items-center gap-1">
            <span class="font-semibold text-blue-600 dark:text-blue-400">
              {{ operations?.pendaki_sedang_mendaki || 0 }}
            </span>
            <span>sedang di atas gunung</span>
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Card 3: Sisa Kuota Pendakian -->
    <Card class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs hover:shadow-md transition-all duration-200">
      <CardContent class="p-5">
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sisa Kuota Hari Ini</span>
          <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <Compass class="h-5 w-5 stroke-[2]" />
          </div>
        </div>

        <div v-if="loading" class="space-y-2">
          <div class="h-8 bg-muted animate-pulse rounded-md w-3/4"></div>
          <div class="h-4 bg-muted animate-pulse rounded-md w-1/2"></div>
        </div>
        <div v-else>
          <h3 class="text-2xl font-bold tracking-tight text-foreground font-mono">
            {{ operations?.sisa_kuota_hari_ini ?? 0 }}
            <span class="text-sm font-sans font-normal text-muted-foreground">/ {{ operations?.total_kuota_hari_ini ?? 0 }}</span>
          </h3>
          <p class="text-xs text-muted-foreground mt-1.5 flex items-center gap-1">
            <span class="font-semibold text-amber-600 dark:text-amber-400">
              {{ operations?.kuota_terpakai_hari_ini || 0 }}
            </span>
            <span>kuota telah terpesan</span>
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Card 4: Status Jalur & Operasional -->
    <Card class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs hover:shadow-md transition-all duration-200">
      <CardContent class="p-5">
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status Jalur Pendakian</span>
          <div
            class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0"
            :class="isTrailOpen ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'"
          >
            <Mountain class="h-5 w-5 stroke-[2]" />
          </div>
        </div>

        <div v-if="loading" class="space-y-2">
          <div class="h-8 bg-muted animate-pulse rounded-md w-3/4"></div>
          <div class="h-4 bg-muted animate-pulse rounded-md w-1/2"></div>
        </div>
        <div v-else>
          <div class="flex items-center gap-2">
            <h3
              class="text-2xl font-bold tracking-tight capitalize"
              :class="isTrailOpen ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'"
            >
              {{ isTrailOpen ? 'Jalur Buka' : 'Jalur Tutup' }}
            </h3>
            <CheckCircle2 v-if="isTrailOpen" class="h-5 w-5 text-emerald-600" />
            <AlertTriangle v-else class="h-5 w-5 text-rose-600 animate-bounce" />
          </div>
          <p class="text-xs text-muted-foreground mt-1.5 flex items-center gap-1">
            <span>{{ resources?.total_staf_bertugas || 0 }} staf bertugas</span>
            <span>·</span>
            <span>{{ resources?.total_staf_tersedia || 0 }} siap</span>
          </p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
