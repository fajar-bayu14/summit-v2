<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Calendar,
  MapPin,
  Users,
  ShoppingCart,
} from 'lucide-vue-next'
import { formatRupiah, formatDateIndonesia } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    product?: ProdukCatalogItem | null
  }>(),
  {
    isOpen: false,
    product: null,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
  (e: 'book-opentrip', product: ProdukCatalogItem): void
}>()

function handleBook() {
  if (!props.product) return
  emit('book-opentrip', props.product)
  emit('update:isOpen', false)
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-xl p-0 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
      <div v-if="product" class="space-y-0">
        <!-- Header Banner -->
        <div class="relative h-44 w-full bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 text-white p-6 flex flex-col justify-end">
          <Badge class="absolute top-4 left-4 bg-purple-500/30 text-purple-200 border-purple-400/40 text-xs font-bold uppercase backdrop-blur-xs">
            Paket Open Trip All-In
          </Badge>

          <div class="relative z-10 space-y-1">
            <DialogTitle class="text-xl font-extrabold text-white">
              {{ product.nama_produk }}
            </DialogTitle>
            <DialogDescription class="text-xs text-purple-200/80">
              Mitra Basecamp: {{ product.basecamp?.nama_basecamp || 'Penyelenggara Resmi' }}
            </DialogDescription>
          </div>
        </div>

        <!-- Body Details -->
        <div class="p-6 space-y-5">
          <!-- Key Trip Highlights -->
          <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-xs">
            <div class="space-y-0.5">
              <span class="text-slate-400 flex items-center gap-1">
                <Calendar class="w-3.5 h-3.5 text-purple-500" />
                Jadwal Berangkat
              </span>
              <p class="font-bold text-slate-800 dark:text-slate-200">
                {{ product.opentrip?.tanggal_mulai ? formatDateIndonesia(product.opentrip.tanggal_mulai) : 'Sesuai Jadwal' }}
              </p>
            </div>

            <div class="space-y-0.5">
              <span class="text-slate-400 flex items-center gap-1">
                <MapPin class="w-3.5 h-3.5 text-orange-500" />
                Meeting Point
              </span>
              <p class="font-bold text-slate-800 dark:text-slate-200 truncate">
                {{ product.opentrip?.meeting_point || 'Basecamp Utama' }}
              </p>
            </div>

            <div class="space-y-0.5">
              <span class="text-slate-400 flex items-center gap-1">
                <Users class="w-3.5 h-3.5 text-emerald-500" />
                Kuota Peserta
              </span>
              <p class="font-bold text-slate-800 dark:text-slate-200">
                {{ product.opentrip?.kuota_terisi || 0 }} / {{ product.opentrip?.kuota_maksimal || 15 }} Peserta
              </p>
            </div>

            <div class="space-y-0.5">
              <span class="text-slate-400">Tarif All-in</span>
              <p class="font-extrabold text-purple-700 dark:text-purple-400 font-display">
                {{ formatRupiah(product.harga) }}
                <span class="text-[10px] font-normal text-slate-400">/ orang</span>
              </p>
            </div>
          </div>

          <!-- Description & Facilities -->
          <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
            <div class="space-y-1">
              <h4 class="font-bold text-slate-900 dark:text-slate-100">Fasilitas Termasuk (Include):</h4>
              <p class="leading-relaxed">
                {{ product.opentrip?.fasilitas_include || 'Tiket SIMAKSI, tenda & alat masak, guide APGI, porter tim, makan 3x selama di gunung, P3K standar.' }}
              </p>
            </div>

            <div v-if="product.opentrip?.fasilitas_exclude" class="space-y-1">
              <h4 class="font-bold text-slate-900 dark:text-slate-100">Tidak Termasuk (Exclude):</h4>
              <p class="leading-relaxed text-slate-500">
                {{ product.opentrip.fasilitas_exclude }}
              </p>
            </div>
          </div>

          <!-- Booking CTA Action -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
            <div>
              <span class="text-[11px] text-slate-500">Total Biaya Open Trip</span>
              <p class="text-base font-extrabold text-slate-900 dark:text-slate-100">
                {{ formatRupiah(product.harga) }}
              </p>
            </div>

            <Button
              class="rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs gap-2 shadow-xs"
              @click="handleBook"
            >
              <ShoppingCart class="w-4 h-4" />
              <span>Daftar Open Trip</span>
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
