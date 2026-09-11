<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Tent,
  Plus,
  Minus,
  ShoppingCart,
  Eye,
  Package,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatRupiah } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

const props = defineProps<{
  product: ProdukCatalogItem
}>()

const emit = defineEmits<{
  (e: 'add-to-cart', product: ProdukCatalogItem, quantity: number): void
  (e: 'open-detail', product: ProdukCatalogItem): void
}>()

const quantity = ref(1)

const maxStock = computed(() => props.product.stok || 99)
const isOutOfStock = computed(() => (props.product.stok ?? 1) <= 0)

function increment() {
  if (quantity.value < maxStock.value) {
    quantity.value++
  }
}

function decrement() {
  if (quantity.value > 1) {
    quantity.value--
  }
}

function handleAddToCart() {
  if (isOutOfStock.value) return
  emit('add-to-cart', props.product, quantity.value)
}
</script>

<template>
  <div
    class="group relative flex flex-col justify-between rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5"
  >
    <!-- Product Image / Thumbnail -->
    <div class="relative h-40 sm:h-44 w-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
      <img
        v-if="product.gambar"
        :src="product.gambar"
        :alt="product.nama_produk"
        class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
        loading="lazy"
        @error="e => ((e.target as HTMLImageElement).src = '/images/hero-summit.jpg')"
      />
      <div v-else class="flex flex-col items-center justify-center text-slate-400">
        <Tent class="w-10 h-10 mb-1 opacity-60" />
        <span class="text-[10px] uppercase font-semibold">Outdoor Gear</span>
      </div>

      <!-- Quick View Button Overlay -->
      <button
        type="button"
        class="absolute bottom-2 right-2 p-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 text-slate-700 dark:text-slate-300 shadow-md backdrop-blur-xs opacity-0 group-hover:opacity-100 transition-opacity hover:text-emerald-700"
        title="Lihat Detail Produk"
        @click="emit('open-detail', product)"
      >
        <Eye class="w-4 h-4" />
      </button>

      <!-- Category Tag Overlay -->
      <div class="absolute top-2.5 left-2.5">
        <Badge variant="outline" class="bg-white/90 dark:bg-slate-900/90 text-[10px] font-bold uppercase backdrop-blur-xs border-slate-200">
          {{ product.kategori }}
        </Badge>
      </div>

      <!-- Out of stock badge -->
      <div v-if="isOutOfStock" class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px] flex items-center justify-center">
        <Badge class="bg-rose-600 text-white font-bold text-xs shadow-md">
          Stok Habis
        </Badge>
      </div>
    </div>

    <!-- Product Info Body -->
    <div class="flex-1 p-4 flex flex-col justify-between space-y-3">
      <div class="space-y-1.5">
        <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100 line-clamp-1 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">
          {{ product.nama_produk }}
        </h4>

        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
          {{ product.deskripsi || 'Peralatan standar pendakian resmi terawat dan steril siap pakai.' }}
        </p>

        <!-- Stock Status -->
        <div class="flex items-center gap-1.5 text-[11px] pt-1">
          <Package class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span
            :class="[
              isOutOfStock
                ? 'text-rose-600 font-semibold'
                : (product.stok ?? 10) <= 3
                ? 'text-amber-600 font-semibold'
                : 'text-slate-500',
            ]"
          >
            {{ isOutOfStock ? 'Habis' : `Tersedia ${product.stok ?? 'Banyak'} unit` }}
          </span>
        </div>
      </div>

      <!-- Price & Quantity Cart Action -->
      <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2">
        <div class="flex items-baseline justify-between">
          <div>
            <span class="text-[10px] text-slate-400 uppercase">Tarif Sewa</span>
            <p class="font-black text-sm text-slate-900 dark:text-slate-100 font-display">
              {{ formatRupiah(product.harga) }}
              <span class="text-[10px] font-normal text-slate-400">/ {{ product.satuan || 'hari' }}</span>
            </p>
          </div>

          <!-- Quantity Stepper -->
          <div v-if="!isOutOfStock" class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 p-0.5">
            <button
              type="button"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
              :disabled="quantity <= 1"
              @click="decrement"
            >
              <Minus class="w-3 h-3" />
            </button>
            <span class="w-6 text-center font-bold text-xs text-slate-900 dark:text-slate-100">
              {{ quantity }}
            </span>
            <button
              type="button"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
              :disabled="quantity >= maxStock"
              @click="increment"
            >
              <Plus class="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Add To Cart Button -->
        <Button
          size="sm"
          class="w-full rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs gap-1.5 shadow-xs"
          :disabled="isOutOfStock"
          @click="handleAddToCart"
        >
          <ShoppingCart class="w-3.5 h-3.5" />
          <span>+ Keranjang</span>
        </Button>
      </div>
    </div>
  </div>
</template>
