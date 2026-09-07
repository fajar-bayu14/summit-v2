<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Building2,
  Ticket,
  Tent,
  Users,
  Clock,
  ArrowLeft,
  AlertCircle,
  CheckCircle2,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import TicketQuotaCalendar from '@/components/pendaki/TicketQuotaCalendar.vue'
import RentalProductGrid from '@/components/pendaki/RentalProductGrid.vue'
import PorterGuideServiceCards from '@/components/pendaki/PorterGuideServiceCards.vue'
import { pendakiProductsApi } from '@/api/pendakiProducts'
import { useBookingStore } from '@/stores/booking'
import { extractApiError } from '@/lib/normalizer'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()

const basecampId = computed(() => Number(route.params.id))
const activeTab = ref<'tiket' | 'rental' | 'jasa'>('tiket')

const products = ref<ProdukCatalogItem[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const cartToastMessage = ref<string | null>(null)

// Computed categories
const ticketProduct = computed(() => {
  return products.value.find(p => p.kategori === 'tiket') || null
})

const rentalProducts = computed(() => {
  return products.value.filter(p => p.kategori === 'rental' || p.kategori === 'merchandise' || p.kategori === 'konsumsi')
})

const serviceProducts = computed(() => {
  return products.value.filter(p => p.kategori === 'jasa')
})

const basecampInfo = computed(() => {
  if (products.value.length > 0 && products.value[0].basecamp) {
    return products.value[0].basecamp
  }
  return bookingStore.selectedBasecamp || null
})

async function fetchStorefrontProducts() {
  if (!basecampId.value) return
  isLoading.value = true
  errorMessage.value = null
  try {
    const res = await pendakiProductsApi.getProducts({
      basecamp_id: basecampId.value,
    })
    products.value = res.data || []
  } catch (err) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

function showCartToast(message: string) {
  cartToastMessage.value = message
  setTimeout(() => {
    cartToastMessage.value = null
  }, 4000)
}

function handleAddTicket(payload: {
  product: ProdukCatalogItem
  selectedDate: string
  climberCount: number
  totalAmount: number
}) {
  bookingStore.setBookingDates(payload.selectedDate)
  bookingStore.setClimberCount(payload.climberCount)
  showCartToast(`Tiket SIMAKSI (${payload.climberCount} orang) untuk tanggal ${payload.selectedDate} berhasil ditambahkan!`)
}

function handleAddRentalToCart(product: ProdukCatalogItem, quantity: number) {
  showCartToast(`${quantity}x ${product.nama_produk} berhasil dimasukkan ke keranjang.`)
}

function handleAddService(payload: {
  product: ProdukCatalogItem
  quantity: number
  specialNotes: string
}) {
  showCartToast(`Layanan ${payload.product.nama_produk} (${payload.quantity}x) berhasil dipesan.`)
}

onMounted(() => {
  fetchStorefrontProducts()
})
</script>

<template>
  <div class="space-y-8 pb-20">
    <!-- Top Navigation Breadcrumb -->
    <div class="flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
        @click="router.back()"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali</span>
      </button>

      <!-- Selection Indicator -->
      <div v-if="bookingStore.selectedMountain" class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
        <span>Gunung: <strong class="text-slate-900 dark:text-slate-100">{{ bookingStore.mountainName }}</strong></span>
        <span>•</span>
        <span>Jalur: <strong class="text-slate-900 dark:text-slate-100">{{ bookingStore.trailName }}</strong></span>
      </div>
    </div>

    <!-- Storefront Toast Alert -->
    <div
      v-if="cartToastMessage"
      class="rounded-2xl bg-emerald-700 text-white p-4 shadow-lg flex items-center justify-between gap-3 animate-in fade-in-50 slide-in-from-top-4"
    >
      <div class="flex items-center gap-2 text-xs font-semibold">
        <CheckCircle2 class="w-4 h-4 text-emerald-300" />
        <span>{{ cartToastMessage }}</span>
      </div>
      <router-link to="/pendaki" class="text-xs font-bold underline hover:text-emerald-200 shrink-0">
        Lihat Keranjang
      </router-link>
    </div>

    <!-- Storefront Basecamp Header Card -->
    <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 shadow-xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
              <Building2 class="w-5 h-5" />
            </span>
            <Badge class="bg-emerald-600 text-white font-bold text-xs border-0">
              Basecamp Mitra Resmi
            </Badge>
          </div>
          <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 font-display">
            {{ basecampInfo?.nama_basecamp || 'Basecamp Pengelola' }}
          </h1>
          <p class="text-xs text-slate-500 flex items-center gap-2">
            <span>Mitra: {{ basecampInfo?.mitra?.nama_mitra || 'Paguyuban Pengelola Basecamp' }}</span>
            <span>•</span>
            <span class="flex items-center gap-1">
              <Clock class="w-3.5 h-3.5 text-slate-400" />
              Operasional: {{ basecampInfo?.jam_operasional || '24 Jam' }}
            </span>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-800 text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400">Status Toko Basecamp</span>
            <p class="text-xs font-extrabold text-emerald-600 flex items-center justify-end gap-1 mt-0.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>Buka &amp; Melayani Online</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs for Storefront -->
      <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2 overflow-x-auto">
        <button
          type="button"
          class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 shrink-0"
          :class="[
            activeTab === 'tiket'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700',
          ]"
          @click="activeTab = 'tiket'"
        >
          <Ticket class="w-4 h-4" />
          <span>Tiket SIMAKSI &amp; Kuota</span>
        </button>

        <button
          type="button"
          class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 shrink-0"
          :class="[
            activeTab === 'rental'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700',
          ]"
          @click="activeTab = 'rental'"
        >
          <Tent class="w-4 h-4" />
          <span>Sewa Alat Outdoor ({{ rentalProducts.length }})</span>
        </button>

        <button
          type="button"
          class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 shrink-0"
          :class="[
            activeTab === 'jasa'
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700',
          ]"
          @click="activeTab = 'jasa'"
        >
          <Users class="w-4 h-4" />
          <span>Porter &amp; Guide ({{ serviceProducts.length }})</span>
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="errorMessage"
      class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-8 text-center space-y-3 text-rose-700 dark:text-rose-300"
    >
      <AlertCircle class="w-10 h-10 mx-auto" />
      <h3 class="font-bold text-base">Gagal Memuat Produk Basecamp</h3>
      <p class="text-xs opacity-90">{{ errorMessage }}</p>
      <Button size="sm" variant="outline" class="rounded-xl text-xs" @click="fetchStorefrontProducts">
        Coba Lagi
      </Button>
    </div>

    <!-- Loading Skeleton -->
    <div v-else-if="isLoading" class="space-y-4 animate-pulse">
      <div class="h-48 rounded-3xl bg-slate-200 dark:bg-slate-800"></div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="h-40 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
      </div>
    </div>

    <!-- Tab Contents -->
    <div v-else class="space-y-6">
      <!-- TAB 1: TIKET SIMAKSI & KUOTA -->
      <div v-if="activeTab === 'tiket'" class="space-y-6">
        <div v-if="ticketProduct">
          <TicketQuotaCalendar
            :ticket-product="ticketProduct"
            @add-ticket="handleAddTicket"
          />
        </div>
        <div
          v-else
          class="p-12 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-2"
        >
          <Ticket class="w-8 h-8 mx-auto text-slate-400" />
          <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Tiket SIMAKSI Belum Dikonfigurasi</p>
          <p class="text-[11px] text-slate-500">Basecamp ini belum menerbitkan produk tiket SIMAKSI resmi.</p>
        </div>
      </div>

      <!-- TAB 2: SEWA ALAT OUTDOOR -->
      <div v-else-if="activeTab === 'rental'">
        <RentalProductGrid
          :products="rentalProducts"
          @add-to-cart="handleAddRentalToCart"
        />
      </div>

      <!-- TAB 3: PORTER & GUIDE -->
      <div v-else-if="activeTab === 'jasa'">
        <PorterGuideServiceCards
          :services="serviceProducts"
          @add-service="handleAddService"
        />
      </div>
    </div>
  </div>
</template>
