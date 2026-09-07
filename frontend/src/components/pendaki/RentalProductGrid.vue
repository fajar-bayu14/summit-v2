<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Search,
  PackageOpen,
} from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import RentalProductCard from './RentalProductCard.vue'
import ProductDetailModal from './ProductDetailModal.vue'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

const props = defineProps<{
  products: ProdukCatalogItem[]
}>()

const emit = defineEmits<{
  (e: 'add-to-cart', product: ProdukCatalogItem, quantity: number): void
}>()

const activeCategoryTab = ref<string>('all')
const searchKeyword = ref<string>('')
const selectedDetailProduct = ref<ProdukCatalogItem | null>(null)
const isDetailModalOpen = ref(false)

const categoryTabs = [
  { id: 'all', label: 'Semua Alat' },
  { id: 'tenda', label: 'Tenda & Shelter', keyword: 'tenda' },
  { id: 'sleeping', label: 'Sleeping Bag & Matras', keyword: 'sleeping' },
  { id: 'cooking', label: 'Cooking & Nesting', keyword: 'nesting' },
  { id: 'lamp', label: 'Penerangan & Alat', keyword: 'headlamp' },
  { id: 'logistik', label: 'Logistik & Ransum', keyword: 'gas' },
]

const filteredProducts = computed(() => {
  return props.products.filter(item => {
    // Only show rental / non-ticket products in this grid
    if (item.kategori === 'tiket') return false

    // Tab filter
    if (activeCategoryTab.value !== 'all') {
      const currentTab = categoryTabs.find(t => t.id === activeCategoryTab.value)
      if (currentTab?.keyword) {
        const matchesName = item.nama_produk.toLowerCase().includes(currentTab.keyword.toLowerCase())
        const matchesDesc = item.deskripsi?.toLowerCase().includes(currentTab.keyword.toLowerCase()) || false
        if (!matchesName && !matchesDesc) return false
      }
    }

    // Search keyword filter
    if (searchKeyword.value.trim()) {
      const q = searchKeyword.value.trim().toLowerCase()
      const matchesName = item.nama_produk.toLowerCase().includes(q)
      const matchesDesc = item.deskripsi?.toLowerCase().includes(q) || false
      if (!matchesName && !matchesDesc) return false
    }

    return true
  })
})

function handleOpenDetail(product: ProdukCatalogItem) {
  selectedDetailProduct.value = product
  isDetailModalOpen.value = true
}

function handleAddToCart(product: ProdukCatalogItem, quantity: number) {
  emit('add-to-cart', product, quantity)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <!-- Category Tabs (Pills) -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
        <button
          v-for="tab in categoryTabs"
          :key="tab.id"
          type="button"
          class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200 shrink-0"
          :class="[
            activeCategoryTab === tab.id
              ? 'bg-emerald-700 text-white shadow-xs'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
          ]"
          @click="activeCategoryTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Search in Storefront -->
      <div class="relative w-full sm:w-64 shrink-0">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
        <Input
          v-model="searchKeyword"
          placeholder="Cari alat (tenda, nesting...)"
          class="h-9 pl-8 pr-3 text-xs rounded-xl bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="filteredProducts.length === 0"
      class="p-12 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-3"
    >
      <PackageOpen class="w-10 h-10 mx-auto text-slate-400" />
      <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200">Tidak Ada Peralatan yang Sesuai</h4>
      <p class="text-xs text-slate-500 max-w-sm mx-auto">
        Tidak menemukan alat outdoor dengan kata kunci atau filter ini di basecamp terpilih.
      </p>
    </div>

    <!-- Product Grid -->
    <div
      v-else
      class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5"
    >
      <RentalProductCard
        v-for="p in filteredProducts"
        :key="p.id"
        :product="p"
        @open-detail="handleOpenDetail"
        @add-to-cart="handleAddToCart"
      />
    </div>

    <!-- Quick View Detail Modal -->
    <ProductDetailModal
      v-model:is-open="isDetailModalOpen"
      :product="selectedDetailProduct"
      @add-to-cart="handleAddToCart"
    />
  </div>
</template>
