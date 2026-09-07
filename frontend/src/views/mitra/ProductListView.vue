<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import mitraProductsApi from '@/api/mitraProducts'
import type { Produk, StoreProductPayload, UpdateProductPayload } from '@/types/product'
import { getApiErrorMessage } from '@/lib/axios'
import {
  Plus,
  RefreshCw,
  Search,
  Package,
  Ticket,
  Compass,
  Users,
  Utensils,
  Layers,
  Edit2,
  Trash2,
  AlertTriangle,
  CheckCircle2,
  SlidersHorizontal,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { EmptyState } from '@/components/common'
import ProductCategoryTabs from '@/components/mitra/products/ProductCategoryTabs.vue'
import ProductStockModal from '@/components/mitra/products/ProductStockModal.vue'
import ProductFormModal from '@/components/mitra/products/ProductFormModal.vue'

const mitraStore = useMitraStore()

const products = ref<Produk[]>([])
const isLoading = ref<boolean>(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Filters
const activeCategory = ref<string>('all')
const searchQuery = ref<string>('')
const statusFilter = ref<string>('all')

// Modals State
const isFormModalOpen = ref<boolean>(false)
const formMode = ref<'create' | 'edit'>('create')
const selectedProduct = ref<Produk | null>(null)
const isSubmittingForm = ref<boolean>(false)
const formServerErrors = ref<Record<string, string[]> | null>(null)

// Stock Modal State
const isStockModalOpen = ref<boolean>(false)
const stockTargetProduct = ref<Produk | null>(null)
const isUpdatingStock = ref<boolean>(false)

// Delete Dialog State
const isDeleteModalOpen = ref<boolean>(false)
const productToDelete = ref<Produk | null>(null)
const isDeleting = ref<boolean>(false)

async function fetchProducts() {
  isLoading.value = true
  errorMessage.value = null
  try {
    const params: any = {}
    if (mitraStore.activeBasecampId) {
      params.basecamp_id = mitraStore.activeBasecampId
    }

    const response = await mitraProductsApi.getProducts(params)
    const rawData = response.data
    if (rawData && Array.isArray(rawData)) {
      products.value = rawData
    } else if (rawData && 'data' in rawData && Array.isArray(rawData.data)) {
      products.value = rawData.data
    } else {
      products.value = []
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(
      err,
      'Gagal memuat daftar produk katalog basecamp.'
    )
  } finally {
    isLoading.value = false
  }
}

watch(
  () => mitraStore.activeBasecampId,
  () => {
    fetchProducts()
  }
)

onMounted(() => {
  fetchProducts()
})

// Filtered Products
const filteredProducts = computed(() => {
  return products.value.filter((item) => {
    // Category match
    if (activeCategory.value !== 'all') {
      if (activeCategory.value === 'guide') {
        if (item.kategori !== 'guide' && item.kategori !== 'porter') return false
      } else if (activeCategory.value === 'kuliner') {
        if (item.kategori !== 'kuliner' && item.kategori !== 'merchandise') return false
      } else if (item.kategori !== activeCategory.value) {
        return false
      }
    }

    // Status filter
    if (statusFilter.value === 'active' && !item.is_active) return false
    if (statusFilter.value === 'inactive' && item.is_active) return false

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim()
      const matchName = item.nama_produk.toLowerCase().includes(q)
      const matchDesc = item.deskripsi?.toLowerCase().includes(q)
      if (!matchName && !matchDesc) return false
    }

    return true
  })
})

// Category Counters
const categoryCounts = computed(() => {
  const counts: Record<string, number> = {
    all: products.value.length,
    ticket: 0,
    rental: 0,
    opentrip: 0,
    guide: 0,
    kuliner: 0,
  }

  products.value.forEach((p) => {
    if (p.kategori === 'ticket') counts.ticket++
    else if (p.kategori === 'rental') counts.rental++
    else if (p.kategori === 'opentrip') counts.opentrip++
    else if (p.kategori === 'guide' || p.kategori === 'porter') counts.guide++
    else counts.kuliner++
  })

  return counts
})

function formatRupiah(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function getCategoryIcon(cat: string) {
  switch (cat) {
    case 'ticket':
      return Ticket
    case 'rental':
      return Package
    case 'opentrip':
      return Compass
    case 'guide':
    case 'porter':
      return Users
    case 'kuliner':
      return Utensils
    default:
      return Layers
  }
}

function getCategoryBadge(cat: string): { label: string; class: string } {
  switch (cat) {
    case 'ticket':
      return { label: 'Tiket Pendakian', class: 'bg-emerald-100 text-emerald-800' }
    case 'rental':
      return { label: 'Sewa Alat', class: 'bg-amber-100 text-amber-800' }
    case 'opentrip':
      return { label: 'Open Trip', class: 'bg-sky-100 text-sky-800' }
    case 'guide':
      return { label: 'Guide', class: 'bg-indigo-100 text-indigo-800' }
    case 'porter':
      return { label: 'Porter', class: 'bg-purple-100 text-purple-800' }
    case 'kuliner':
      return { label: 'Kuliner', class: 'bg-orange-100 text-orange-800' }
    default:
      return { label: cat, class: 'bg-stone-100 text-stone-700' }
  }
}

// Quick Actions
function openCreateModal() {
  formMode.value = 'create'
  selectedProduct.value = null
  formServerErrors.value = null
  isFormModalOpen.value = true
}

function openEditModal(product: Produk) {
  formMode.value = 'edit'
  selectedProduct.value = product
  formServerErrors.value = null
  isFormModalOpen.value = true
}

function openStockModal(product: Produk) {
  stockTargetProduct.value = product
  isStockModalOpen.value = true
}

function promptDeleteProduct(product: Produk) {
  productToDelete.value = product
  isDeleteModalOpen.value = true
}

async function handleToggleStatus(product: Produk) {
  const newStatus = !product.is_active
  const originalStatus = product.is_active
  product.is_active = newStatus // Optimistic

  try {
    await mitraProductsApi.toggleProductStatus(product.id, newStatus)
    showToast(`Status produk "${product.nama_produk}" diperbarui.`)
  } catch (err) {
    product.is_active = originalStatus
    errorMessage.value = getApiErrorMessage(err, 'Gagal mengubah status aktif produk.')
  }
}

async function handleStockSubmit(newStock: number) {
  if (!stockTargetProduct.value) return
  isUpdatingStock.value = true
  const targetId = stockTargetProduct.value.id
  const targetName = stockTargetProduct.value.nama_produk

  try {
    await mitraProductsApi.updateProductStock(targetId, newStock)
    const match = products.value.find((p) => p.id === targetId)
    if (match) match.stok = newStock
    isStockModalOpen.value = false
    showToast(`Stok "${targetName}" berhasil diperbarui menjadi ${newStock} unit.`)
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memperbarui stok produk.')
  } finally {
    isUpdatingStock.value = false
  }
}

async function handleFormSubmit(payload: StoreProductPayload | UpdateProductPayload) {
  isSubmittingForm.value = true
  formServerErrors.value = null
  try {
    if (formMode.value === 'create') {
      const res = await mitraProductsApi.createProduct(payload as StoreProductPayload)
      if (res.data) {
        products.value.unshift(res.data)
      }
      showToast('Produk baru berhasil ditambahkan ke katalog.')
    } else if (formMode.value === 'edit' && selectedProduct.value) {
      const res = await mitraProductsApi.updateProduct(
        selectedProduct.value.id,
        payload as UpdateProductPayload
      )
      if (res.data) {
        const idx = products.value.findIndex((p) => p.id === selectedProduct.value?.id)
        if (idx !== -1) products.value[idx] = res.data
      }
      showToast('Perubahan produk berhasil disimpan.')
    }
    isFormModalOpen.value = false
  } catch (err: any) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      formServerErrors.value = err.response.data.errors
    } else {
      errorMessage.value = getApiErrorMessage(err, 'Gagal menyimpan data produk.')
    }
  } finally {
    isSubmittingForm.value = false
  }
}

async function confirmDeleteProduct() {
  if (!productToDelete.value) return
  isDeleting.value = true
  const targetId = productToDelete.value.id
  const targetName = productToDelete.value.nama_produk

  try {
    await mitraProductsApi.deleteProduct(targetId)
    products.value = products.value.filter((p) => p.id !== targetId)
    isDeleteModalOpen.value = false
    showToast(`Produk "${targetName}" berhasil dihapus.`)
  } catch (err) {
    errorMessage.value = getApiErrorMessage(
      err,
      'Gagal menghapus produk. Produk mungkin memiliki riwayat transaksi aktif.'
    )
  } finally {
    isDeleting.value = false
  }
}

function showToast(msg: string) {
  successMessage.value = msg
  setTimeout(() => {
    if (successMessage.value === msg) {
      successMessage.value = null
    }
  }, 4000)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-stone-900 tracking-tight">
          Katalog Produk & Inventaris
        </h1>
        <p class="text-sm text-stone-500 mt-1">
          Kelola inventaris tiket, perlengkapan rental, open trip, dan logistik basecamp
          <span v-if="mitraStore.activeBasecamp" class="font-semibold text-stone-800">
            • {{ mitraStore.activeBasecamp.nama_basecamp }}
          </span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl border-stone-200 text-stone-700 hover:bg-stone-100 min-h-[44px]"
          :disabled="isLoading"
          @click="fetchProducts"
        >
          <RefreshCw :class="['w-4 h-4 mr-2', isLoading && 'animate-spin']" />
          Segarkan
        </Button>
        <Button
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white shadow-sm min-h-[44px]"
          @click="openCreateModal"
        >
          <Plus class="w-4 h-4 mr-2" />
          Tambah Produk
        </Button>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div
      v-if="successMessage"
      class="flex items-center gap-3 p-4 bg-emerald-50 text-emerald-800 rounded-2xl border border-emerald-200 text-sm font-medium animate-fadeIn"
    >
      <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
      <span>{{ successMessage }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="flex items-center gap-3 p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm font-medium animate-fadeIn"
    >
      <AlertTriangle class="w-5 h-5 text-red-600 shrink-0" />
      <span>{{ errorMessage }}</span>
    </div>

    <!-- Category Tabs Filter -->
    <ProductCategoryTabs
      v-model="activeCategory"
      :counts="categoryCounts"
    />

    <!-- Search & Filter Controls -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-stone-200/80 shadow-xs">
      <div class="relative w-full sm:w-80">
        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
        <Input
          v-model="searchQuery"
          placeholder="Cari nama produk / deskripsi..."
          class="pl-10 h-10 rounded-xl border-stone-200 text-sm"
        />
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <div class="flex items-center gap-2 text-xs font-semibold text-stone-600">
          <SlidersHorizontal class="w-4 h-4 text-stone-400" />
          <span>Status:</span>
        </div>
        <select
          v-model="statusFilter"
          class="h-10 px-3 bg-white border border-stone-200 rounded-xl text-xs font-medium text-stone-700 focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
        >
          <option value="all">Semua Status</option>
          <option value="active">Hanya Aktif</option>
          <option value="inactive">Hanya Non-Aktif</option>
        </select>
      </div>
    </div>

    <!-- Products Data Table -->
    <div class="bg-white rounded-2xl border border-stone-200 shadow-xs overflow-hidden">
      <div v-if="isLoading" class="p-12 text-center text-stone-500 space-y-3">
        <RefreshCw class="w-8 h-8 animate-spin mx-auto text-[#1E3A2B]" />
        <p class="text-sm font-medium">Memuat katalog produk...</p>
      </div>

      <div v-else-if="filteredProducts.length === 0" class="p-6">
        <EmptyState
          title="Tidak Ada Produk Ditemukan"
          :description="
            searchQuery || activeCategory !== 'all' || statusFilter !== 'all'
              ? 'Tidak ada produk yang cocok dengan kriteria pencarian Anda.'
              : 'Basecamp Anda belum memiliki produk terdaftar. Silakan tambahkan produk baru.'
          "
        >
          <template #action>
            <Button
              v-if="!searchQuery && activeCategory === 'all'"
              class="rounded-xl bg-[#1E3A2B] text-white min-h-[44px]"
              @click="openCreateModal"
            >
              <Plus class="w-4 h-4 mr-2" />
              Tambah Produk Pertama
            </Button>
          </template>
        </EmptyState>
      </div>

      <div v-else class="overflow-x-auto">
        <Table>
          <TableHeader class="bg-stone-50/75">
            <TableRow>
              <TableHead class="font-semibold text-stone-700">Produk</TableHead>
              <TableHead class="font-semibold text-stone-700">Kategori</TableHead>
              <TableHead class="font-semibold text-stone-700">Harga Satuan</TableHead>
              <TableHead class="font-semibold text-stone-700">Stok / Kuota</TableHead>
              <TableHead class="font-semibold text-stone-700">Status</TableHead>
              <TableHead class="font-semibold text-stone-700 text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="item in filteredProducts"
              :key="item.id"
              class="hover:bg-stone-50/50 transition-colors"
            >
              <!-- Info Produk -->
              <TableCell class="py-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-stone-100 text-stone-600 flex items-center justify-center shrink-0">
                    <component :is="getCategoryIcon(item.kategori)" class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="font-bold text-stone-900 text-sm">
                      {{ item.nama_produk }}
                    </div>
                    <div class="text-xs text-stone-500 line-clamp-1">
                      {{ item.deskripsi || (item.basecamp as any)?.nama_basecamp || (item.basecamp as any)?.nama || 'Tanpa deskripsi' }}
                    </div>
                  </div>
                </div>
              </TableCell>

              <!-- Kategori Badge -->
              <TableCell>
                <span
                  :class="[
                    'inline-block px-2.5 py-1 rounded-lg text-xs font-semibold',
                    getCategoryBadge(item.kategori).class,
                  ]"
                >
                  {{ getCategoryBadge(item.kategori).label }}
                </span>
              </TableCell>

              <!-- Harga Satuan -->
              <TableCell>
                <span class="font-bold text-stone-900 text-sm">
                  {{ formatRupiah(item.harga) }}
                </span>
                <span v-if="item.satuan" class="text-xs text-stone-500">
                  / {{ item.satuan }}
                </span>
              </TableCell>

              <!-- Stok / Kuota Detail -->
              <TableCell>
                <div v-if="item.kategori === 'rental' || item.kategori === 'kuliner' || item.kategori === 'merchandise'" class="flex items-center gap-2">
                  <span
                    :class="[
                      'font-bold text-sm px-2 py-0.5 rounded-md',
                      (item.stok ?? 0) === 0
                        ? 'bg-red-100 text-red-700'
                        : (item.stok ?? 0) <= 5
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-emerald-100 text-emerald-700',
                    ]"
                  >
                    {{ item.stok ?? 0 }} {{ item.satuan || 'unit' }}
                  </span>
                  <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-xs text-stone-600 hover:text-stone-900 hover:bg-stone-100 rounded-lg"
                    @click="openStockModal(item)"
                  >
                    Ubah
                  </Button>
                </div>

                <div v-else-if="item.kategori === 'ticket'" class="text-xs text-stone-600">
                  <span>Operasional: {{ item.tiket?.jam_buka || '06:00' }} - {{ item.tiket?.jam_tutup || '17:00' }}</span>
                </div>

                <div v-else-if="item.kategori === 'opentrip'" class="text-xs text-stone-600">
                  <span>Kursi: {{ item.opentrip?.sisa_kursi ?? item.opentrip?.maksimal_peserta ?? '-' }} pax</span>
                </div>

                <div v-else class="text-xs text-stone-500">
                  <span>Sesuai Pesanan</span>
                </div>
              </TableCell>

              <!-- Status Toggle Switch -->
              <TableCell>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="item.is_active"
                    class="sr-only peer"
                    @change="handleToggleStatus(item)"
                  />
                  <div
                    class="w-10 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1E3A2B]"
                  ></div>
                  <span class="ml-2 text-xs font-semibold" :class="item.is_active ? 'text-emerald-700' : 'text-stone-500'">
                    {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </label>
              </TableCell>

              <!-- Aksi Menu -->
              <TableCell class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 text-stone-600 hover:text-blue-700 hover:bg-blue-50 rounded-xl"
                    title="Edit Produk"
                    @click="openEditModal(item)"
                  >
                    <Edit2 class="w-4 h-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 text-stone-600 hover:text-red-700 hover:bg-red-50 rounded-xl"
                    title="Hapus Produk"
                    @click="promptDeleteProduct(item)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- Form Modal (Create / Edit) -->
    <ProductFormModal
      v-model:is-open="isFormModalOpen"
      :mode="formMode"
      :product="selectedProduct"
      :basecamps="mitraStore.basecamps"
      :default-basecamp-id="mitraStore.activeBasecampId"
      :loading="isSubmittingForm"
      :server-errors="formServerErrors"
      @submit="handleFormSubmit"
    />

    <!-- Stock Adjust Modal -->
    <ProductStockModal
      v-model:is-open="isStockModalOpen"
      :product="stockTargetProduct"
      :loading="isUpdatingStock"
      @submit="handleStockSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
      <DialogContent class="sm:max-w-md bg-white rounded-2xl p-6">
        <DialogHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-700 flex items-center justify-center">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div>
              <DialogTitle class="text-lg font-bold text-stone-900">
                Hapus Produk Katalog
              </DialogTitle>
              <DialogDescription class="text-sm text-stone-500">
                Konfirmasi penghapusan produk dari katalog
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <div class="py-3 text-sm text-stone-600">
          Apakah Anda yakin ingin menghapus produk
          <strong class="text-stone-900">"{{ productToDelete?.nama_produk }}"</strong>?
          Tindakan ini tidak dapat dibatalkan.
        </div>

        <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
            :disabled="isDeleting"
            @click="isDeleteModalOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="rounded-xl bg-red-600 hover:bg-red-700 text-white min-h-[44px]"
            :disabled="isDeleting"
            @click="confirmDeleteProduct"
          >
            {{ isDeleting ? 'Menghapus...' : 'Hapus Sekarang' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
