<script setup lang="ts">
import { computed } from 'vue'
import {
  Ticket,
  Tent,
  Compass,
  UserCheck,
  Package,
  Eye,
  MapPin,
  Clock,
  Layers,
} from 'lucide-vue-next'
import type { Product } from '@/types/product'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  (e: 'inspect', product: Product): void
}>()

const categoryConfig = computed(() => {
  switch (props.product.kategori) {
    case 'ticket':
      return {
        label: 'Tiket Pendakian',
        bg: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        icon: Ticket,
      }
    case 'rental':
      return {
        label: 'Sewa Alat',
        bg: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800',
        icon: Tent,
      }
    case 'opentrip':
      return {
        label: 'Open Trip',
        bg: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800',
        icon: Compass,
      }
    case 'guide':
    case 'porter':
      return {
        label: props.product.kategori === 'guide' ? 'Guide Pendakian' : 'Porter',
        bg: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        icon: UserCheck,
      }
    default:
      return {
        label: props.product.kategori.toUpperCase(),
        bg: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-800',
        icon: Package,
      }
  }
})

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val)
}

const mountainName = computed(() => {
  return props.product.basecamp?.jalur?.gunung?.nama_gunung || ''
})
</script>

<template>
  <div
    class="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-500/40 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
  >
    <div>
      <!-- Image or Category Placeholder Thumbnail -->
      <div
        class="relative mb-3.5 flex h-36 w-full items-center justify-center overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800/80"
      >
        <img
          v-if="product.gambar"
          :src="product.gambar"
          :alt="product.nama_produk"
          class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        <div v-else class="flex flex-col items-center gap-1.5 text-slate-400 dark:text-slate-500">
          <component :is="categoryConfig.icon" class="h-9 w-9 stroke-[1.5]" />
          <span class="text-xs font-medium">{{ categoryConfig.label }}</span>
        </div>

        <!-- Category Badge floating on image -->
        <div class="absolute left-2.5 top-2.5">
          <Badge
            variant="outline"
            :class="['flex items-center gap-1 px-2 py-0.5 text-xs font-medium backdrop-blur-sm', categoryConfig.bg]"
          >
            <component :is="categoryConfig.icon" class="h-3 w-3" />
            <span>{{ categoryConfig.label }}</span>
          </Badge>
        </div>

        <!-- Active / Inactive Status pill -->
        <div class="absolute right-2.5 top-2.5">
          <span
            v-if="product.is_active"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse" />
            Tersedia
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 rounded-full bg-rose-500/90 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm"
          >
            Nonaktif
          </span>
        </div>
      </div>

      <!-- Location / Basecamp details -->
      <div class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
        <MapPin class="h-3.5 w-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
        <span class="truncate font-medium">
          {{ product.basecamp?.nama_basecamp || 'Basecamp' }}
          <template v-if="mountainName">
            • <span class="text-slate-700 dark:text-slate-300">{{ mountainName }}</span>
          </template>
        </span>
      </div>

      <!-- Title -->
      <h4
        class="line-clamp-2 text-sm font-semibold text-slate-900 transition-colors group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-400"
        :title="product.nama_produk"
      >
        {{ product.nama_produk }}
      </h4>

      <!-- Description Snippet -->
      <p
        v-if="product.deskripsi"
        class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400 leading-relaxed"
      >
        {{ product.deskripsi }}
      </p>

      <!-- Category Extra Indicators -->
      <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
        <!-- Ticket: Daily Quota snippet -->
        <div
          v-if="product.kategori === 'ticket' && product.tiket?.kuotas?.length"
          class="flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-medium"
        >
          <Clock class="h-3 w-3 text-emerald-600" />
          <span>{{ product.tiket.kuotas[0].kuota_tersisa }} sisa kuota hari ini</span>
        </div>

        <!-- Opentrip: Trip date snippet -->
        <div
          v-else-if="product.kategori === 'opentrip' && product.opentrip?.tanggal_berangkat"
          class="flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 font-medium"
        >
          <Compass class="h-3 w-3 text-purple-600" />
          <span>Trip: {{ product.opentrip.tanggal_berangkat }}</span>
        </div>

        <!-- Rental / Physical stock -->
        <div
          v-else-if="product.stok !== null"
          class="flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-300 font-medium"
        >
          <Layers class="h-3 w-3 text-slate-500" />
          <span>Stok: {{ product.stok }} unit</span>
        </div>
      </div>
    </div>

    <!-- Card Footer: Price & Inspector Button -->
    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800/80">
      <div>
        <span class="block text-[11px] font-medium text-slate-400">Tarif / Harga</span>
        <div class="flex items-baseline gap-1">
          <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
            {{ formatCurrency(product.harga) }}
          </span>
          <span v-if="product.satuan" class="text-[11px] text-slate-400">
            / {{ product.satuan }}
          </span>
        </div>
      </div>

      <Button
        size="sm"
        variant="outline"
        class="h-8 gap-1.5 rounded-lg border-slate-200 text-xs font-medium text-slate-700 hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-emerald-950/30"
        @click="emit('inspect', product)"
      >
        <Eye class="h-3.5 w-3.5" />
        <span>Detail</span>
      </Button>
    </div>
  </div>
</template>
