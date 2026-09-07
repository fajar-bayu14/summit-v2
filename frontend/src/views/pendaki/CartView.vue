<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Ticket,
  Tent,
  Users,
  Building2,
  Calendar,
  MapPin,
  Trash2,
  Plus,
  Minus,
  ArrowRight,
  PackageOpen,
  ArrowLeft,
  ShieldCheck,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { useCartStore } from '@/stores/cart'
import { formatRupiah, formatDateIndonesia } from '@/lib/formatters'

const router = useRouter()
const cartStore = useCartStore()

const SERVICE_FEE = 2500

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

function handleProceedToCheckout() {
  router.push('/pendaki/checkout')
}

onMounted(() => {
  cartStore.fetchCart()
})
</script>

<template>
  <div class="space-y-8 max-w-5xl mx-auto pb-16">
    <!-- Header Navigation -->
    <div class="flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
        @click="router.back()"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Lanjut Belanja</span>
      </button>

      <Button
        v-if="cartStore.hasItems"
        variant="ghost"
        size="sm"
        class="text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl gap-1.5"
        @click="cartStore.clearCart()"
      >
        <Trash2 class="w-3.5 h-3.5" />
        <span>Kosongkan Keranjang</span>
      </Button>
    </div>

    <!-- Empty State -->
    <div
      v-if="!cartStore.hasItems"
      class="p-16 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4"
    >
      <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
        <PackageOpen class="w-8 h-8" />
      </div>
      <div class="space-y-1 max-w-sm mx-auto">
        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Keranjang Belanja Kosong</h3>
        <p class="text-xs text-slate-500">
          Anda belum memilih tiket pendakian SIMAKSI ataupun perlengkapan sewa outdoor.
        </p>
      </div>
      <router-link to="/pendaki/gunung">
        <Button class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-2 shadow-xs">
          <span>Eksplorasi Gunung Sekarang</span>
          <ArrowRight class="w-4 h-4" />
        </Button>
      </router-link>
    </div>

    <!-- Main Cart Layout (2 Cols: Items vs Summary) -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
      <!-- Left: Grouped Items List (2 Cols) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Booking Context Card -->
        <div class="p-5 rounded-3xl bg-emerald-950 text-white border border-emerald-900/50 shadow-sm space-y-2">
          <div class="flex items-center gap-2 text-xs text-emerald-300 font-semibold">
            <Building2 class="w-4 h-4" />
            <span>Destinasi &amp; Mitra Basecamp</span>
          </div>
          <h2 class="text-lg font-bold">
            {{ cartStore.cart?.basecamp?.nama_basecamp || 'Basecamp Pengelola' }}
          </h2>
          <div class="flex flex-wrap items-center gap-4 text-xs text-emerald-200/80 pt-1">
            <span class="flex items-center gap-1.5">
              <MapPin class="w-3.5 h-3.5 text-orange-400" />
              Jalur: {{ cartStore.cart?.jalur?.nama_jalur || 'Jalur Resmi' }}
            </span>
            <span class="flex items-center gap-1.5">
              <Calendar class="w-3.5 h-3.5 text-emerald-400" />
              Tanggal: {{ cartStore.cart?.tanggal_booking ? formatDateIndonesia(cartStore.cart.tanggal_booking) : 'Sesuai Pilihan' }}
            </span>
          </div>
        </div>

        <!-- SECTION 1: TIKET SIMAKSI -->
        <div v-if="cartStore.ticketItem" class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Ticket class="w-4 h-4 text-emerald-600" />
              <span>Tiket SIMAKSI Resmi</span>
            </h3>
            <Badge class="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-0 text-[10px] font-bold">
              Wajib SIMAKSI
            </Badge>
          </div>

          <div class="flex items-center justify-between gap-4">
            <div class="space-y-1">
              <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                {{ cartStore.ticketItem.produk?.nama_produk }}
              </h4>
              <p class="text-xs text-slate-500">
                Tarif: {{ formatRupiah(cartStore.ticketItem.produk?.harga) }} x {{ cartStore.ticketItem.qty }} Orang
              </p>
            </div>

            <!-- Quantity Stepper -->
            <div class="flex items-center gap-3">
              <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 p-1">
                <button
                  type="button"
                  class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30"
                  :disabled="cartStore.ticketItem.qty <= 1"
                  @click="handleDecrement(cartStore.ticketItem.id, cartStore.ticketItem.qty)"
                >
                  <Minus class="w-3 h-3" />
                </button>
                <span class="w-7 text-center text-xs font-bold text-slate-900 dark:text-slate-100">
                  {{ cartStore.ticketItem.qty }}
                </span>
                <button
                  type="button"
                  class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700"
                  @click="handleIncrement(cartStore.ticketItem.id, cartStore.ticketItem.qty)"
                >
                  <Plus class="w-3 h-3" />
                </button>
              </div>

              <p class="font-extrabold text-sm text-slate-900 dark:text-slate-100 w-24 text-right font-display">
                {{ formatRupiah((cartStore.ticketItem.produk?.harga || 0) * cartStore.ticketItem.qty) }}
              </p>
            </div>
          </div>
        </div>

        <!-- SECTION 2: PERALATAN RENTAL -->
        <div v-if="cartStore.rentalItems.length > 0" class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Tent class="w-4 h-4 text-amber-600" />
              <span>Sewa Perlengkapan Outdoor ({{ cartStore.rentalItems.length }})</span>
            </h3>
          </div>

          <div class="divide-y divide-slate-100 dark:divide-slate-800 space-y-3">
            <div
              v-for="item in cartStore.rentalItems"
              :key="item.id"
              class="pt-3 first:pt-0 flex items-center justify-between gap-4"
            >
              <div class="space-y-0.5 min-w-0 flex-1">
                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 truncate">
                  {{ item.produk?.nama_produk }}
                </h4>
                <p class="text-[11px] text-slate-500">
                  {{ formatRupiah(item.produk?.harga) }} / {{ item.produk?.satuan || 'hari' }}
                </p>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 p-0.5">
                  <button
                    type="button"
                    class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-slate-200 disabled:opacity-30"
                    :disabled="item.qty <= 1"
                    @click="handleDecrement(item.id, item.qty)"
                  >
                    <Minus class="w-3 h-3" />
                  </button>
                  <span class="w-6 text-center text-xs font-bold text-slate-900 dark:text-slate-100">
                    {{ item.qty }}
                  </span>
                  <button
                    type="button"
                    class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-slate-200"
                    @click="handleIncrement(item.id, item.qty)"
                  >
                    <Plus class="w-3 h-3" />
                  </button>
                </div>

                <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 w-20 text-right">
                  {{ formatRupiah((item.produk?.harga || 0) * item.qty) }}
                </p>

                <button
                  type="button"
                  class="p-1 text-slate-400 hover:text-rose-600 transition-colors"
                  @click="handleRemove(item.id)"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 3: PORTER & GUIDE -->
        <div v-if="cartStore.serviceItems.length > 0" class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Users class="w-4 h-4 text-blue-600" />
              <span>Jasa Porter &amp; Guide APGI ({{ cartStore.serviceItems.length }})</span>
            </h3>
          </div>

          <div class="divide-y divide-slate-100 dark:divide-slate-800 space-y-3">
            <div
              v-for="item in cartStore.serviceItems"
              :key="item.id"
              class="pt-3 first:pt-0 flex items-center justify-between gap-4"
            >
              <div class="space-y-0.5 min-w-0 flex-1">
                <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 truncate">
                  {{ item.produk?.nama_produk }}
                </h4>
                <p class="text-[11px] text-slate-500">
                  {{ formatRupiah(item.produk?.harga) }} / {{ item.produk?.satuan || 'orang' }}
                </p>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-400">
                  {{ item.qty }} Personil
                </span>

                <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 w-20 text-right">
                  {{ formatRupiah((item.produk?.harga || 0) * item.qty) }}
                </p>

                <button
                  type="button"
                  class="p-1 text-slate-400 hover:text-rose-600 transition-colors"
                  @click="handleRemove(item.id)"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Summary Card (1 Col) -->
      <div class="space-y-4 sticky top-24">
        <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-5">
          <h3 class="font-bold text-base text-slate-900 dark:text-slate-100 pb-3 border-b border-slate-100 dark:border-slate-800">
            Ringkasan Belanja
          </h3>

          <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-400">
            <div class="flex items-center justify-between">
              <span>Total Item:</span>
              <strong class="text-slate-900 dark:text-slate-100">{{ cartStore.totalItems }} Item</strong>
            </div>

            <div class="flex items-center justify-between">
              <span>Subtotal Produk &amp; Jasa:</span>
              <strong class="text-slate-900 dark:text-slate-100">{{ formatRupiah(cartStore.subtotal) }}</strong>
            </div>

            <div class="flex items-center justify-between">
              <span>Biaya Layanan Platform:</span>
              <strong class="text-slate-900 dark:text-slate-100">{{ formatRupiah(SERVICE_FEE) }}</strong>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-sm">
              <span class="font-bold text-slate-900 dark:text-slate-100">Total Estimasi:</span>
              <span class="font-black text-lg text-emerald-700 dark:text-emerald-400 font-display">
                {{ formatRupiah(cartStore.subtotal + SERVICE_FEE) }}
              </span>
            </div>
          </div>

          <Button
            class="w-full h-11 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs sm:text-sm gap-2 shadow-md"
            @click="handleProceedToCheckout"
          >
            <span>Lanjut ke Manifes &amp; Checkout</span>
            <ArrowRight class="w-4 h-4" />
          </Button>

          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-[11px] text-slate-500 space-y-1">
            <div class="flex items-center gap-1.5 font-semibold text-emerald-700 dark:text-emerald-400">
              <ShieldCheck class="w-3.5 h-3.5" />
              <span>Jaminan Keamanan SIMAKSI</span>
            </div>
            <p>Kuota terkunci otomatis setelah Anda menyelesaikan pengisian manifes dan pembayaran.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
