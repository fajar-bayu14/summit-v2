<script setup lang="ts">
import {
  Ticket,
  Tent,
  Compass,
  UserCheck,
  Package,
  Eye,
  CheckCircle2,
  AlertCircle,
} from 'lucide-vue-next'
import type { Product } from '@/types/product'
import DataTable from '@/components/common/DataTable.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  products: Product[]
  loading?: boolean
  currentPage: number
  lastPage: number
  total: number
}>()

const emit = defineEmits<{
  (e: 'inspect', product: Product): void
  (e: 'page-change', page: number): void
}>()

const columns = [
  { key: 'nama_produk', label: 'Produk' },
  { key: 'mitra', label: 'Mitra Pengelola' },
  { key: 'basecamp', label: 'Basecamp & Jalur' },
  { key: 'kategori', label: 'Kategori' },
  { key: 'harga', label: 'Harga / Satuan' },
  { key: 'stok', label: 'Stok / Kuota' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Aksi', align: 'right' as const },
]

const getCategoryConfig = (kategori: string) => {
  switch (kategori) {
    case 'ticket':
      return {
        label: 'Tiket',
        bg: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300',
        icon: Ticket,
      }
    case 'rental':
      return {
        label: 'Sewa Alat',
        bg: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300',
        icon: Tent,
      }
    case 'opentrip':
      return {
        label: 'Open Trip',
        bg: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300',
        icon: Compass,
      }
    case 'guide':
    case 'porter':
      return {
        label: kategori === 'guide' ? 'Guide' : 'Porter',
        bg: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300',
        icon: UserCheck,
      }
    default:
      return {
        label: kategori.toUpperCase(),
        bg: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/40 dark:text-slate-300',
        icon: Package,
      }
  }
}

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val)
}
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
    <DataTable
      :columns="columns"
      :data="products"
      :loading="loading"
      :current-page="currentPage"
      :last-page="lastPage"
      :total="total"
      @page-change="emit('page-change', $event)"
    >
      <!-- Nama Produk Slot -->
      <template #cell(nama_produk)="{ row }">
        <div class="flex items-center gap-3 py-1">
          <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
            <img
              v-if="row.gambar"
              :src="row.gambar"
              :alt="row.nama_produk"
              class="h-full w-full object-cover"
            />
            <component
              :is="getCategoryConfig(row.kategori).icon"
              v-else
              class="h-5 w-5 text-slate-400"
            />
          </div>
          <div>
            <div class="font-semibold text-slate-900 dark:text-slate-100">
              {{ row.nama_produk }}
            </div>
            <div class="font-mono text-[11px] text-slate-400">
              PRD-{{ String(row.id).padStart(5, '0') }}
            </div>
          </div>
        </div>
      </template>

      <!-- Mitra Slot -->
      <template #cell(mitra)="{ row }">
        <div>
          <div class="font-medium text-slate-800 dark:text-slate-200">
            {{ row.basecamp?.mitra?.nama_pemilik || '-' }}
          </div>
          <div class="text-[11px] text-slate-400">
            {{ row.basecamp?.mitra?.telepon || '-' }}
          </div>
        </div>
      </template>

      <!-- Basecamp Slot -->
      <template #cell(basecamp)="{ row }">
        <div>
          <div class="font-medium text-slate-800 dark:text-slate-200">
            {{ row.basecamp?.nama_basecamp || '-' }}
          </div>
          <div class="text-[11px] text-slate-400">
            {{ row.basecamp?.jalur?.gunung?.nama_gunung || '-' }}
          </div>
        </div>
      </template>

      <!-- Kategori Slot -->
      <template #cell(kategori)="{ row }">
        <Badge
          variant="outline"
          :class="['gap-1 px-2 py-0.5 text-xs font-medium', getCategoryConfig(row.kategori).bg]"
        >
          <component :is="getCategoryConfig(row.kategori).icon" class="h-3 w-3" />
          <span>{{ getCategoryConfig(row.kategori).label }}</span>
        </Badge>
      </template>

      <!-- Harga Slot -->
      <template #cell(harga)="{ row }">
        <div>
          <div class="font-bold text-emerald-700 dark:text-emerald-400">
            {{ formatCurrency(row.harga) }}
          </div>
          <div v-if="row.satuan" class="text-[11px] text-slate-400">
            / {{ row.satuan }}
          </div>
        </div>
      </template>

      <!-- Stok / Kuota Slot -->
      <template #cell(stok)="{ row }">
        <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">
          <template v-if="row.kategori === 'ticket'">
            {{ row.tiket?.kuotas?.[0]?.kuota_tersisa ?? 'Fleksibel' }}
            <span class="text-[11px] font-normal text-slate-400">kuota</span>
          </template>
          <template v-else-if="row.kategori === 'opentrip'">
            {{ row.opentrip?.sisa_kursi ?? 0 }}
            <span class="text-[11px] font-normal text-slate-400">kursi</span>
          </template>
          <template v-else-if="row.stok !== null">
            {{ row.stok }}
            <span class="text-[11px] font-normal text-slate-400">unit</span>
          </template>
          <template v-else>
            -
          </template>
        </div>
      </template>

      <!-- Status Slot -->
      <template #cell(status)="{ row }">
        <Badge
          :variant="row.is_active ? 'default' : 'destructive'"
          class="gap-1 px-2 py-0.5 text-xs font-medium"
          :class="row.is_active ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
        >
          <CheckCircle2 v-if="row.is_active" class="h-3 w-3" />
          <AlertCircle v-else class="h-3 w-3" />
          <span>{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </Badge>
      </template>

      <!-- Actions Slot -->
      <template #cell(actions)="{ row }">
        <div class="flex items-center justify-end">
          <Button
            size="sm"
            variant="outline"
            class="h-8 gap-1 rounded-lg text-xs font-medium text-slate-700 hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-200"
            @click="emit('inspect', row)"
          >
            <Eye class="h-3.5 w-3.5" />
            <span>Detail</span>
          </Button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
