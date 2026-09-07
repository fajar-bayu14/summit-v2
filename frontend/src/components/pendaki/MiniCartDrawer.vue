<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  ShoppingCart,
  Trash2,
  Plus,
  Minus,
  ArrowRight,
  PackageOpen,
} from 'lucide-vue-next'
import { formatRupiah } from '@/lib/formatters'
import { useCartStore } from '@/stores/cart'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
  }>(),
  {
    isOpen: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
}>()

const router = useRouter()
const cartStore = useCartStore()

const items = computed(() => cartStore.cart?.items || [])

function handleIncrement(itemId: number, currentQty: number) {
  cartStore.updateQuantity(itemId, currentQty + 1)
}

function handleDecrement(itemId: number, currentQty: number) {
  if (currentQty > 1) {
    cartStore.updateQuantity(itemId, currentQty - 1)
  }
}

function handleRemove(itemId: number) {
  cartStore.removeItem(itemId)
}

function handleGoToCheckout() {
  emit('update:isOpen', false)
  router.push('/pendaki/checkout')
}

function handleGoToFullCart() {
  emit('update:isOpen', false)
  router.push('/pendaki/cart')
}
</script>

<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-md p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4 max-h-[90vh] flex flex-col justify-between">
      <div class="space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2 font-extrabold text-base text-slate-900 dark:text-slate-100">
            <ShoppingCart class="w-5 h-5 text-emerald-700 dark:text-emerald-400" />
            <DialogTitle class="text-base font-bold">Keranjang Belanja</DialogTitle>
          </div>
          <Badge class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-0 text-xs font-bold">
            {{ cartStore.totalItems }} Item
          </Badge>
        </div>

        <DialogDescription class="text-xs text-slate-500">
          Ringkasan pesanan tiket SIMAKSI dan sewa logistik basecamp.
        </DialogDescription>

        <!-- Empty Cart -->
        <div
          v-if="items.length === 0"
          class="py-12 text-center space-y-3 text-slate-400"
        >
          <PackageOpen class="w-12 h-12 mx-auto opacity-50" />
          <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Keranjang Masih Kosong</p>
          <p class="text-[11px] text-slate-500">Pilih tiket SIMAKSI atau sewa alat untuk menambahkan item.</p>
        </div>

        <!-- Items List -->
        <div v-else class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
          <div
            v-for="item in items"
            :key="item.id"
            class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 gap-3"
          >
            <!-- Product Info -->
            <div class="space-y-0.5 flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <Badge variant="outline" class="text-[9px] uppercase font-bold py-0 px-1.5">
                  {{ item.produk?.kategori || 'Item' }}
                </Badge>
                <h5 class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                  {{ item.produk?.nama_produk }}
                </h5>
              </div>

              <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                {{ formatRupiah(item.produk?.harga || 0) }}
                <span class="text-[10px] font-normal text-slate-400">/ {{ item.produk?.satuan || 'unit' }}</span>
              </p>
            </div>

            <!-- Stepper Quantity & Delete -->
            <div class="flex items-center gap-2 shrink-0">
              <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 p-0.5">
                <button
                  type="button"
                  class="w-5 h-5 rounded flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30"
                  :disabled="item.qty <= 1"
                  @click="handleDecrement(item.id, item.qty)"
                >
                  <Minus class="w-2.5 h-2.5" />
                </button>
                <span class="w-5 text-center text-xs font-bold text-slate-900 dark:text-slate-100">
                  {{ item.qty }}
                </span>
                <button
                  type="button"
                  class="w-5 h-5 rounded flex items-center justify-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                  @click="handleIncrement(item.id, item.qty)"
                >
                  <Plus class="w-2.5 h-2.5" />
                </button>
              </div>

              <button
                type="button"
                class="p-1 text-slate-400 hover:text-rose-600 transition-colors"
                title="Hapus Item"
                @click="handleRemove(item.id)"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Cart Actions -->
      <div v-if="items.length > 0" class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
        <div class="flex items-center justify-between text-xs">
          <span class="font-medium text-slate-500">Estimasi Subtotal:</span>
          <span class="font-extrabold text-base text-slate-900 dark:text-slate-100">
            {{ formatRupiah(cartStore.subtotal) }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl text-xs font-semibold"
            @click="handleGoToFullCart"
          >
            Detail Keranjang
          </Button>

          <Button
            size="sm"
            class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs gap-1.5 shadow-xs"
            @click="handleGoToCheckout"
          >
            <span>Checkout</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
