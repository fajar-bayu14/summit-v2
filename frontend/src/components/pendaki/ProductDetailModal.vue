<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Tent,
  Plus,
  Minus,
  ShoppingCart,
  Loader2,
} from 'lucide-vue-next'
import { formatRupiah } from '@/lib/formatters'
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
  (e: 'add-to-cart', product: ProdukCatalogItem, quantity: number): void
}>()

const quantity = ref(1)
const isAdding = ref(false)

const maxStock = computed(() => props.product?.stok || 99)
const isOutOfStock = computed(() => (props.product?.stok ?? 1) <= 0)

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

function handleAdd() {
  if (!props.product || isOutOfStock.value || isAdding.value) return
  isAdding.value = true
  emit('add-to-cart', props.product, quantity.value)
  emit('update:isOpen', false)
  isAdding.value = false
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-xl p-0 overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
      <div v-if="product" class="space-y-0">
        <!-- Photo Container -->
        <div class="relative h-56 sm:h-64 w-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
          <img
            v-if="product.gambar"
            :src="product.gambar"
            :alt="product.nama_produk"
            class="h-full w-full object-cover object-center"
          />
          <div v-else class="flex flex-col items-center justify-center text-slate-400">
            <Tent class="w-12 h-12 mb-2 opacity-50" />
            <span class="text-xs uppercase font-bold tracking-wider">Perlengkapan Outdoor</span>
          </div>

          <Badge class="absolute top-4 left-4 bg-slate-900/80 text-white border-0 text-xs font-bold uppercase backdrop-blur-xs">
            {{ product.kategori }}
          </Badge>
        </div>

        <!-- Body Info -->
        <div class="p-6 space-y-4">
          <div class="space-y-1">
            <DialogTitle class="text-xl font-extrabold text-slate-900 dark:text-slate-100">
              {{ product.nama_produk }}
            </DialogTitle>
            <DialogDescription class="text-xs text-slate-500 flex items-center gap-2">
              <span>Mitra Basecamp: {{ product.basecamp?.nama_basecamp || 'Basecamp Resmi' }}</span>
              <span>•</span>
              <span :class="isOutOfStock ? 'text-rose-600 font-bold' : 'text-emerald-700 dark:text-emerald-400 font-bold'">
                {{ isOutOfStock ? 'Stok Habis' : `Tersedia ${product.stok ?? 'Banyak'} unit` }}
              </span>
            </DialogDescription>
          </div>

          <!-- Price Highlight -->
          <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
            <span class="text-xs text-emerald-900 dark:text-emerald-200 font-medium">Harga Sewa / Pemesanan</span>
            <p class="text-xl font-extrabold text-emerald-800 dark:text-emerald-300 font-display">
              {{ formatRupiah(product.harga) }}
              <span class="text-xs font-normal text-slate-500">/ {{ product.satuan || 'hari' }}</span>
            </p>
          </div>

          <!-- Description -->
          <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            <h4 class="font-bold text-slate-900 dark:text-slate-100">Deskripsi &amp; Spesifikasi Produk:</h4>
            <p>{{ product.deskripsi || 'Peralatan resmi berstandar SAR dan APGI, bersih dan siap pakai untuk aktivitas outdoor mendaki gunung.' }}</p>
          </div>

          <!-- Quantity Stepper & Add Action -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Jumlah:</span>
              <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-1">
                <button
                  type="button"
                  class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
                  :disabled="quantity <= 1"
                  @click="decrement"
                >
                  <Minus class="w-3.5 h-3.5" />
                </button>
                <span class="w-8 text-center font-bold text-xs text-slate-900 dark:text-slate-100">
                  {{ quantity }}
                </span>
                <button
                  type="button"
                  class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30 transition-colors"
                  :disabled="quantity >= maxStock"
                  @click="increment"
                >
                  <Plus class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <Button
              class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs gap-2 shadow-xs"
              :disabled="isOutOfStock || isAdding"
              @click="handleAdd"
            >
              <Loader2 v-if="isAdding" class="w-4 h-4 animate-spin" />
              <ShoppingCart v-else class="w-4 h-4" />
              <span>{{ isAdding ? 'Menambahkan...' : '+ Masukkan Keranjang' }}</span>
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
