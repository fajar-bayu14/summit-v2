<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import {
  Package,
  Users,
  Ticket,
  Tent,
  LayoutGrid,
  List,
  Search,
  RefreshCw,
  X,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { MetricCard, EmptyState } from '@/components/common'
import PartnerCatalogSection from '@/components/admin/product/PartnerCatalogSection.vue'
import ProductTableList from '@/components/admin/product/ProductTableList.vue'
import ProductDetailInspectorModal from '@/components/admin/product/ProductDetailInspectorModal.vue'
import type { Product, ProductFilterParams, PartnerGroupedCatalog } from '@/types/product'
import type { Mitra } from '@/types/partner'
import type { PaginationMeta } from '@/types/api'
import { getProducts } from '@/api/product'
import { getPartners } from '@/api/partner'
import { useToast } from '@/composables/useToast'
import { extractApiError } from '@/lib/normalizer'

const toast = useToast()

// View Mode: 'catalog' (Grouped by Partner) or 'table'
const viewMode = ref<'catalog' | 'table'>('catalog')

// State
const products = ref<Product[]>([])
const partnersList = ref<Mitra[]>([])
const isLoading = ref(false)
const isPartnersLoading = ref(false)

// Filters
const searchQuery = ref('')
const selectedMitraId = ref<string>('all')
const selectedCategory = ref<string>('all')
const selectedStatus = ref<'all' | 'active' | 'inactive'>('all')

const paginationMeta = ref<PaginationMeta>({
  current_page: 1,
  from: 1,
  last_page: 1,
  per_page: 50,
  to: 1,
  total: 0,
})

// Inspector Modal State
const isInspectorOpen = ref(false)
const selectedProduct = ref<Product | null>(null)

// Metrics
const totalProductsCount = computed(() => paginationMeta.value.total || products.value.length)
const totalTicketsCount = computed(() => products.value.filter(p => p.kategori === 'ticket').length)
const totalRentalsCount = computed(() => products.value.filter(p => p.kategori === 'rental').length)
const totalTripsCount = computed(() => products.value.filter(p => p.kategori === 'opentrip').length)

// Compute Grouped Catalog by Partner & Basecamp
const partnerGroups = computed<PartnerGroupedCatalog[]>(() => {
  const map = new Map<number, PartnerGroupedCatalog>()

  for (const product of products.value) {
    const mitra = product.basecamp?.mitra
    const basecamp = product.basecamp

    if (!mitra || !basecamp) continue

    if (!map.has(mitra.id)) {
      map.set(mitra.id, {
        mitra,
        totalProducts: 0,
        totalTickets: 0,
        totalRentals: 0,
        totalTrips: 0,
        basecampGroups: [],
      })
    }

    const pGroup = map.get(mitra.id)!
    pGroup.totalProducts++
    if (product.kategori === 'ticket') pGroup.totalTickets++
    else if (product.kategori === 'rental') pGroup.totalRentals++
    else if (product.kategori === 'opentrip') pGroup.totalTrips++

    let bGroup = pGroup.basecampGroups.find(bg => bg.basecamp.id === basecamp.id)
    if (!bGroup) {
      bGroup = {
        basecamp,
        products: [],
      }
      pGroup.basecampGroups.push(bGroup)
    }

    bGroup.products.push(product)
  }

  return Array.from(map.values())
})

// Fetch Products from Backend
async function fetchProducts(page = 1) {
  isLoading.value = true
  try {
    const params: ProductFilterParams = {
      page,
      per_page: viewMode.value === 'catalog' ? 100 : 15,
    }

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim()
    }
    if (selectedMitraId.value !== 'all') {
      params.mitra_id = Number(selectedMitraId.value)
    }
    if (selectedCategory.value !== 'all') {
      params.kategori = selectedCategory.value
    }
    if (selectedStatus.value !== 'all') {
      params.is_active = selectedStatus.value === 'active'
    }

    const result = await getProducts(params)
    products.value = result.items
    paginationMeta.value = result.meta
  } catch (err) {
    const msg = extractApiError(err)
    toast.error(msg.message)
  } finally {
    isLoading.value = false
  }
}

// Fetch Partners for the Filter Dropdown
async function fetchPartnersDropdown() {
  isPartnersLoading.value = true
  try {
    const result = await getPartners({ per_page: 100 })
    partnersList.value = result.items
  } catch {
    // Silently fail or ignore dropdown error
  } finally {
    isPartnersLoading.value = false
  }
}

// Inspect Product
function handleInspectProduct(product: Product) {
  selectedProduct.value = product
  isInspectorOpen.value = true
}

// Reset Filters
function resetFilters() {
  searchQuery.value = ''
  selectedMitraId.value = 'all'
  selectedCategory.value = 'all'
  selectedStatus.value = 'all'
  fetchProducts(1)
}

// Watch filters
let searchDebounce: any = null
watch([searchQuery, selectedMitraId, selectedCategory, selectedStatus], () => {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    fetchProducts(1)
  }, 300)
})

watch(viewMode, () => {
  fetchProducts(1)
})

onMounted(() => {
  fetchProducts(1)
  fetchPartnersDropdown()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section with Dual View Toggle -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
          Monitoring Katalog Produk & Kuota
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Supervisi katalog tiket pendakian, sewa alat, paket trip, dan ketersediaan kuota harian lintas mitra basecamp.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <!-- View Mode Switcher -->
        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100/80 p-1 dark:border-slate-800 dark:bg-slate-900">
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
            :class="
              viewMode === 'catalog'
                ? 'bg-white text-emerald-800 shadow-xs dark:bg-slate-800 dark:text-emerald-300'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'
            "
            @click="viewMode = 'catalog'"
          >
            <LayoutGrid class="h-3.5 w-3.5" />
            <span>Katalog Mitra</span>
          </button>
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all cursor-pointer"
            :class="
              viewMode === 'table'
                ? 'bg-white text-emerald-800 shadow-xs dark:bg-slate-800 dark:text-emerald-300'
                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200'
            "
            @click="viewMode = 'table'"
          >
            <List class="h-3.5 w-3.5" />
            <span>Tabel Data</span>
          </button>
        </div>

        <Button
          variant="outline"
          class="h-9 gap-1.5 rounded-xl border-slate-200 bg-white text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          :disabled="isLoading"
          @click="fetchProducts(paginationMeta.current_page)"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Segarkan</span>
        </Button>
      </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <MetricCard
        title="Total Katalog Produk"
        :value="totalProductsCount"
        :icon="Package"
        variant="default"
        description="Semua produk terdaftar"
        icon-class="bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
      />

      <MetricCard
        title="Mitra Pengelola"
        :value="partnersList.length"
        :icon="Users"
        variant="default"
        description="Mitra bisnis aktif"
        icon-class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
      />

      <MetricCard
        title="Tiket Pendakian"
        :value="totalTicketsCount"
        :icon="Ticket"
        variant="success"
        description="Produk kuota resmi"
        icon-class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
      />

      <MetricCard
        title="Logistik & Layanan"
        :value="totalRentalsCount + totalTripsCount"
        :icon="Tent"
        variant="default"
        description="Sewa alat & open trip"
        icon-class="bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300"
      />
    </div>

    <!-- Filter & Search Controls Bar -->
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <!-- Search Input -->
        <div class="relative flex-1">
          <Search class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Cari nama produk, nama mitra, atau basecamp..."
            class="h-10 pl-10 pr-4 text-xs rounded-xl bg-slate-50/50 border-slate-200 dark:bg-slate-800 dark:border-slate-700"
          />
        </div>

        <!-- Dropdown Filters -->
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Filter Mitra -->
          <div class="w-full sm:w-auto">
            <select
              v-model="selectedMitraId"
              class="h-10 w-full sm:w-44 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            >
              <option value="all">Semua Mitra</option>
              <option
                v-for="partner in partnersList"
                :key="partner.id"
                :value="String(partner.id)"
              >
                {{ partner.nama_pemilik }}
              </option>
            </select>
          </div>

          <!-- Filter Kategori -->
          <div class="w-full sm:w-auto">
            <select
              v-model="selectedCategory"
              class="h-10 w-full sm:w-40 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            >
              <option value="all">Semua Kategori</option>
              <option value="ticket">Tiket Pendakian</option>
              <option value="rental">Sewa Alat</option>
              <option value="opentrip">Open Trip</option>
              <option value="guide">Guide</option>
              <option value="porter">Porter</option>
              <option value="transport">Transport</option>
              <option value="merchandise">Merchandise</option>
              <option value="kuliner">Kuliner</option>
            </select>
          </div>

          <!-- Filter Status -->
          <div class="w-full sm:w-auto">
            <select
              v-model="selectedStatus"
              class="h-10 w-full sm:w-36 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
            >
              <option value="all">Semua Status</option>
              <option value="active">Aktif / Tersedia</option>
              <option value="inactive">Nonaktif</option>
            </select>
          </div>

          <!-- Reset Filter Button -->
          <Button
            v-if="searchQuery || selectedMitraId !== 'all' || selectedCategory !== 'all' || selectedStatus !== 'all'"
            variant="ghost"
            size="sm"
            class="h-10 gap-1 rounded-xl text-xs text-slate-500 hover:text-slate-900"
            @click="resetFilters"
          >
            <X class="h-3.5 w-3.5" />
            <span>Reset</span>
          </Button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-4 py-8">
      <div class="flex flex-col items-center justify-center gap-3">
        <RefreshCw class="h-8 w-8 animate-spin text-emerald-600" />
        <span class="text-sm font-medium text-slate-500">Memuat katalog produk mitra...</span>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="products.length === 0" class="rounded-2xl border border-slate-200 bg-white p-12 dark:border-slate-800 dark:bg-slate-900">
      <EmptyState
        title="Tidak Ada Produk Ditemukan"
        description="Belum ada produk yang terdaftar atau sesuai dengan kriteria filter pencarian Anda."
        action-label="Reset Filter"
        @action="resetFilters"
      />
    </div>

    <!-- Main Content Area: Grouped Catalog View vs Table View -->
    <div v-else>
      <!-- MODE 1: Partner-Grouped Catalog -->
      <div v-if="viewMode === 'catalog'" class="space-y-6">
        <PartnerCatalogSection
          v-for="pGroup in partnerGroups"
          :key="pGroup.mitra.id"
          :partner-group="pGroup"
          @inspect="handleInspectProduct"
        />
      </div>

      <!-- MODE 2: Flat Data Table -->
      <div v-else>
        <ProductTableList
          :products="products"
          :loading="isLoading"
          :current-page="paginationMeta.current_page"
          :last-page="paginationMeta.last_page"
          :total="paginationMeta.total"
          @inspect="handleInspectProduct"
          @page-change="fetchProducts($event)"
        />
      </div>
    </div>

    <!-- Inspector Modal -->
    <ProductDetailInspectorModal
      :open="isInspectorOpen"
      :product="selectedProduct"
      @update:open="isInspectorOpen = $event"
    />
  </div>
</template>
