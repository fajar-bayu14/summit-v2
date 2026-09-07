<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { ShoppingCart, ArrowRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { formatRupiah } from '@/lib/formatters'
import { useCartStore } from '@/stores/cart'

const props = withDefaults(
  defineProps<{
    customSubtotal?: number
    customItemCount?: number
  }>(),
  {
    customSubtotal: undefined,
    customItemCount: undefined,
  }
)

const emit = defineEmits<{
  (e: 'open-drawer'): void
  (e: 'go-to-checkout'): void
}>()

const router = useRouter()
const cartStore = useCartStore()

const itemCount = computed(() => {
  return props.customItemCount !== undefined ? props.customItemCount : cartStore.totalItems
})

const currentSubtotal = computed(() => {
  return props.customSubtotal !== undefined ? props.customSubtotal : cartStore.subtotal
})

const isVisible = computed(() => itemCount.value > 0)

function handleNavigateCart() {
  emit('open-drawer')
  router.push('/pendaki/cart')
}

function handleCheckout() {
  emit('go-to-checkout')
  router.push('/pendaki/checkout')
}
</script>

<template>
  <div
    v-if="isVisible"
    class="fixed bottom-0 left-0 right-0 z-40 p-3 sm:p-4 pointer-events-none animate-in slide-in-from-bottom-6 duration-300"
  >
    <div class="max-w-4xl mx-auto pointer-events-auto">
      <div class="rounded-2xl bg-slate-900/95 text-white p-3.5 sm:p-4 shadow-2xl backdrop-blur-md border border-slate-800 flex items-center justify-between gap-4">
        <!-- Left: Item Count Badge & Subtotal -->
        <div class="flex items-center gap-3">
          <div class="relative p-2.5 rounded-xl bg-emerald-600/30 border border-emerald-500/40 text-emerald-400">
            <ShoppingCart class="w-5 h-5" />
            <span class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-orange-600 text-white font-bold text-[10px] flex items-center justify-center shadow-xs">
              {{ itemCount }}
            </span>
          </div>

          <div>
            <span class="text-[10px] sm:text-xs text-slate-400 block font-medium">Estimasi Subtotal:</span>
            <p class="text-sm sm:text-base font-extrabold text-white font-display">
              {{ formatRupiah(currentSubtotal) }}
            </p>
          </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            class="hidden sm:inline-flex rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800"
            @click="handleNavigateCart"
          >
            Lihat Keranjang
          </Button>

          <Button
            size="sm"
            class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs gap-1.5 shadow-md"
            @click="handleCheckout"
          >
            <span>Checkout</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
