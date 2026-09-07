<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  MapPin,
  Compass,
  ArrowLeft,
  AlertTriangle,
  Info,
  CheckCircle2,
  Building2,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import TrailCard from '@/components/pendaki/TrailCard.vue'
import BasecampSelectorList from '@/components/pendaki/BasecampSelectorList.vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'
import { useBookingStore } from '@/stores/booking'
import { formatMdpl } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { GunungItem, JalurDetail, BasecampMitraSummary } from '@/types/pendakiMountain'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()

const mountainId = computed(() => Number(route.params.id))
const mountain = ref<GunungItem | null>(null)
const selectedTrail = ref<JalurDetail | null>(null)
const selectedBasecamp = ref<BasecampMitraSummary | null>(null)

const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

async function fetchMountainDetail() {
  if (!mountainId.value) return
  isLoading.value = true
  errorMessage.value = null
  try {
    const res = await pendakiMountainsApi.getMountainById(mountainId.value)
    mountain.value = res.data || null
    bookingStore.selectMountain(mountain.value)

    // Auto-select first open trail if available
    if (mountain.value?.jalurs && mountain.value.jalurs.length > 0) {
      const firstOpen = mountain.value.jalurs.find(j => j.status === 'open') || mountain.value.jalurs[0]
      selectedTrail.value = firstOpen
      bookingStore.selectTrail(firstOpen)
    }
  } catch (err) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

function handleSelectTrail(trail: JalurDetail) {
  selectedTrail.value = trail
  bookingStore.selectTrail(trail)
}

function handleSelectBasecamp(basecamp: BasecampMitraSummary) {
  selectedBasecamp.value = basecamp
  bookingStore.selectBasecamp(basecamp)
  router.push(`/pendaki/basecamp/${basecamp.id}/shop`)
}

onMounted(() => {
  fetchMountainDetail()
})
</script>

<template>
  <div class="space-y-8">
    <!-- Back & Breadcrumb Navigation -->
    <div class="flex items-center justify-between">
      <router-link
        to="/pendaki/gunung"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Katalog Gunung</span>
      </router-link>
    </div>

    <!-- Error State -->
    <div
      v-if="errorMessage"
      class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-8 text-center space-y-3 text-rose-700 dark:text-rose-300"
    >
      <AlertTriangle class="w-10 h-10 mx-auto" />
      <h3 class="font-bold text-base">Gagal Memuat Detail Gunung</h3>
      <p class="text-xs opacity-90">{{ errorMessage }}</p>
      <Button size="sm" variant="outline" class="rounded-xl text-xs" @click="fetchMountainDetail">
        Coba Lagi
      </Button>
    </div>

    <!-- Loading Skeleton -->
    <div v-else-if="isLoading" class="space-y-6 animate-pulse">
      <div class="h-64 sm:h-80 w-full rounded-3xl bg-slate-200 dark:bg-slate-800"></div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 h-40 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
        <div class="h-40 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
      </div>
    </div>

    <!-- Mountain Content Body -->
    <div v-else-if="mountain" class="space-y-8">
      <!-- Hero Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white min-h-[300px] sm:min-h-[360px] flex flex-col justify-end p-6 sm:p-10 shadow-lg border border-slate-800">
        <!-- Background Image / Overlay -->
        <img
          v-if="mountain.foto"
          :src="mountain.foto"
          :alt="mountain.nama_gunung"
          class="absolute inset-0 h-full w-full object-cover object-center opacity-45"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-transparent"></div>

        <!-- Banner Content -->
        <div class="relative z-10 space-y-3 max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <Badge class="bg-emerald-600 text-white font-bold text-xs px-3 py-1 border-0 shadow-md">
              {{ formatMdpl(mountain.tinggi_mdpl) }}
            </Badge>
            <Badge
              v-if="mountain.status === 'aktif' || mountain.status === 'open'"
              class="bg-emerald-500/20 text-emerald-300 border-emerald-400/40 text-xs"
            >
              Destinasi Terbuka
            </Badge>
            <Badge
              v-else
              class="bg-rose-500/20 text-rose-300 border-rose-400/40 text-xs"
            >
              Tutup Sementara
            </Badge>
          </div>

          <h1 class="text-3xl sm:text-4xl font-black tracking-tight font-display text-white">
            {{ mountain.nama_gunung }}
          </h1>

          <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-300">
            <MapPin class="w-4 h-4 text-orange-500 shrink-0" />
            <span>{{ mountain.lokasi }}</span>
          </div>

          <p class="text-xs sm:text-sm text-slate-200/90 leading-relaxed max-w-2xl pt-1">
            {{ mountain.deskripsi || 'Nikmati pesona keindahan lanskap alam puncak nusantara dengan rute resmi dan pengawasan basecamp profesional.' }}
          </p>
        </div>
      </div>

      <!-- Main Layout: 2 Columns (Trail & Basecamp Selection vs Rules & Info) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Interactive Trail Selection & Basecamps (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="space-y-1">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Compass class="w-5 h-5 text-emerald-700 dark:text-emerald-400" />
              <span>1. Pilih Jalur Pendakian Resmi</span>
            </h2>
            <p class="text-xs text-slate-500">
              Setiap jalur memiliki tingkat kesulitan, estimasi waktu tempuh, dan basecamp pengelola yang berbeda.
            </p>
          </div>

          <!-- Empty Trails -->
          <div
            v-if="!mountain.jalurs || mountain.jalurs.length === 0"
            class="p-8 text-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800"
          >
            <p class="text-xs text-slate-500">Belum ada data jalur pendakian resmi untuk gunung ini.</p>
          </div>

          <!-- Trails List -->
          <div v-else class="space-y-4">
            <TrailCard
              v-for="trail in mountain.jalurs"
              :key="trail.id"
              :trail="trail"
              :is-selected="selectedTrail?.id === trail.id"
              @select-trail="handleSelectTrail"
            />
          </div>

          <!-- Basecamp Selection Section (If Trail Selected) -->
          <div v-if="selectedTrail" class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
            <div class="space-y-1">
              <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <Building2 class="w-5 h-5 text-emerald-700 dark:text-emerald-400" />
                <span>2. Pilih Mitra Basecamp Pengelola ({{ selectedTrail.nama_jalur }})</span>
              </h2>
              <p class="text-xs text-slate-500">
                Pilih basecamp untuk memesan tiket SIMAKSI, peralatan sewa outdoor, dan jasa porter/guide.
              </p>
            </div>

            <BasecampSelectorList
              :trail="selectedTrail"
              :selected-basecamp-id="selectedBasecamp?.id"
              @select-basecamp="handleSelectBasecamp"
            />
          </div>
        </div>

        <!-- Right: SOP & Mountain Guidelines Card (1 Col) -->
        <div class="space-y-6">
          <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 font-bold text-sm text-slate-900 dark:text-slate-100">
              <Info class="w-4 h-4 text-blue-600" />
              <span>SOP &amp; Peraturan Pendakian</span>
            </div>

            <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-400">
              <li class="flex items-start gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>Wajib membawa identitas diri resmi (KTP/Paspor) terverifikasi KYC.</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>Wajib membawa perlengkapan standar keselamatan gunung (tenda double layer, sleeping bag, matras, jaket gunung).</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>Batas maksimal waktu pendakian standar adalah 2 hari 1 malam.</span>
              </li>
              <li class="flex items-start gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>Menerapkan prinsip <em>Zero Waste / Trash Bag</em> (Bawa kembali sampahmu).</span>
              </li>
            </ul>

            <div class="pt-2">
              <div class="p-3 rounded-xl bg-orange-50 dark:bg-orange-950/30 border border-orange-200/60 dark:border-orange-800/60 text-xs text-orange-800 dark:text-orange-300 flex items-start gap-2">
                <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                <span>Dilarang keras menyalakan api unggun di area rawan kebakaran dan membawa miras/narkoba.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
