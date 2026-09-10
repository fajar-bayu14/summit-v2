<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  Compass,
  Search,
  SlidersHorizontal,
  Mountain,
  AlertCircle,
} from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import MountainCard from '@/components/pendaki/MountainCard.vue'
import MountainFilterSidebar from '@/components/pendaki/MountainFilterSidebar.vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'
import { useBookingStore } from '@/stores/booking'
import { extractApiError, normalizePaginatedResponse } from '@/lib/normalizer'
import type { GunungItem, MountainFilterParams } from '@/types/pendakiMountain'

const router = useRouter()
const route = useRoute()
const bookingStore = useBookingStore()

const mountains = ref<GunungItem[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)
const isMobileFilterOpen = ref(false)

const searchKeyword = ref<string>((route.query.q as string) || '')
const filterParams = reactive<MountainFilterParams>({
  page: 1,
  search: (route.query.q as string) || undefined,
  provinsi: undefined,
  min_mdpl: undefined,
  max_mdpl: undefined,
  kesulitan: undefined,
})

async function fetchMountains() {
  isLoading.value = true
  errorMessage.value = null
  try {
    const res = await pendakiMountainsApi.getMountains({
      ...filterParams,
      search: searchKeyword.value.trim() || undefined,
    })
    const normalized = normalizePaginatedResponse<GunungItem>(res)
    mountains.value = normalized.items
  } catch (err) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

function handleSearchSubmit() {
  filterParams.page = 1
  fetchMountains()
}

function handleFilterChange(newFilters: MountainFilterParams) {
  Object.assign(filterParams, newFilters)
  filterParams.page = 1
  fetchMountains()
}

function handleResetFilters() {
  searchKeyword.value = ''
  filterParams.search = undefined
  filterParams.provinsi = undefined
  filterParams.min_mdpl = undefined
  filterParams.max_mdpl = undefined
  filterParams.kesulitan = undefined
  filterParams.page = 1
  fetchMountains()
}

function handleSelectMountain(mountain: GunungItem) {
  bookingStore.selectMountain(mountain)
  router.push(`/pendaki/gunung/${mountain.id}`)
}

onMounted(() => {
  fetchMountains()
})

watch(
  () => route.query.q,
  newQuery => {
    if (newQuery !== undefined) {
      searchKeyword.value = (newQuery as string) || ''
      fetchMountains()
    }
  }
)
</script>

<template>
  <div class="space-y-8">
    <!-- Header Discovery Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-950 via-pine-900 to-slate-900 text-white p-6 sm:p-8 shadow-md border border-emerald-900/40">
      <div class="relative z-10 max-w-3xl space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">
          <Compass class="w-3.5 h-3.5" />
          <span>Katalog Destinasi Resmi SIMAKSI</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight font-display">
          Jelajahi Puncak Impianmu di Seluruh Nusantara
        </h1>
        <p class="text-xs sm:text-sm text-emerald-100/80 max-w-2xl leading-relaxed">
          Pilih gunung tujuan, temukan jalur pendakian resmi, cek status kuota harian tiket SIMAKSI, dan pilih basecamp mitra pengelola terpercaya.
        </p>

        <!-- Search Input Bar -->
        <form @submit.prevent="handleSearchSubmit" class="pt-2 flex items-center gap-2 max-w-lg">
          <div class="relative flex-1">
            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input
              v-model="searchKeyword"
              placeholder="Cari gunung atau wilayah (Merbabu, Rinjani, Lawu...)"
              class="w-full pl-9 pr-4 h-10 rounded-xl bg-white/95 text-slate-900 placeholder:text-slate-400 text-xs sm:text-sm border-0 focus-visible:ring-2 focus-visible:ring-emerald-500"
            />
          </div>
          <Button
            type="submit"
            class="h-10 px-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs shrink-0"
          >
            Cari
          </Button>
        </form>
      </div>
    </div>

    <!-- Main Content: Sidebar Filter & Grid Results -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
      <!-- Desktop Sidebar Filter -->
      <aside class="hidden lg:block lg:col-span-1 sticky top-24">
        <MountainFilterSidebar
          :initial-filters="filterParams"
          @filter-change="handleFilterChange"
          @reset="handleResetFilters"
        />
      </aside>

      <!-- Mobile Filter Toggle Bar -->
      <div class="lg:hidden flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
        <span class="text-xs font-medium text-slate-500">
          Ditemukan <strong class="text-slate-900 dark:text-slate-100">{{ mountains.length }}</strong> Gunung
        </span>
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl gap-2 text-xs font-semibold"
          @click="isMobileFilterOpen = !isMobileFilterOpen"
        >
          <SlidersHorizontal class="w-3.5 h-3.5 text-emerald-700" />
          <span>Filter</span>
        </Button>
      </div>

      <!-- Mobile Filter Drawer / Collapse -->
      <div v-if="isMobileFilterOpen" class="lg:hidden col-span-1">
        <MountainFilterSidebar
          :initial-filters="filterParams"
          @filter-change="handleFilterChange"
          @reset="handleResetFilters"
        />
      </div>

      <!-- Mountain Grid Listing -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Results Count on Desktop -->
        <div class="hidden lg:flex items-center justify-between">
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Menampilkan <strong class="text-slate-900 dark:text-slate-100 font-bold">{{ mountains.length }}</strong> gunung tujuan pendakian
          </p>
        </div>

        <!-- Error State -->
        <div
          v-if="errorMessage"
          class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-6 flex items-center gap-4 text-rose-700 dark:text-rose-300"
        >
          <AlertCircle class="w-6 h-6 shrink-0" />
          <div class="flex-1">
            <h4 class="font-bold text-sm">Gagal Memuat Destinasi</h4>
            <p class="text-xs opacity-90">{{ errorMessage }}</p>
          </div>
          <Button size="sm" variant="outline" class="rounded-xl text-xs" @click="fetchMountains">
            Coba Lagi
          </Button>
        </div>

        <!-- Loading Skeleton Grid -->
        <div
          v-else-if="isLoading"
          class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6"
        >
          <div
            v-for="i in 6"
            :key="i"
            class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden animate-pulse"
          >
            <div class="h-48 bg-slate-200 dark:bg-slate-800 w-full"></div>
            <div class="p-5 space-y-3">
              <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-1/3"></div>
              <div class="h-5 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div>
              <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-full"></div>
              <div class="pt-3 flex justify-between items-center">
                <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-1/4"></div>
                <div class="h-8 bg-slate-200 dark:bg-slate-800 rounded-xl w-24"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="mountains.length === 0"
          class="rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 p-12 text-center flex flex-col items-center justify-center space-y-4"
        >
          <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 flex items-center justify-center">
            <Mountain class="w-7 h-7" />
          </div>
          <div class="space-y-1 max-w-sm">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Destinasi Tidak Ditemukan</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Tidak ada gunung yang cocok dengan kriteria pencarian atau filter yang dipilih.
            </p>
          </div>
          <Button
            variant="outline"
            size="sm"
            class="rounded-xl text-xs font-semibold gap-1.5"
            @click="handleResetFilters"
          >
            <span>Reset Semua Filter</span>
          </Button>
        </div>

        <!-- Mountain Cards Grid -->
        <div
          v-else
          class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6"
        >
          <MountainCard
            v-for="m in mountains"
            :key="m.id"
            :mountain="m"
            @select="handleSelectMountain"
          />
        </div>
      </div>
    </div>
  </div>
</template>
