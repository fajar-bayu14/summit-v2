<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Award,
  Mountain,
  Trophy,
  Calendar,
  MapPin,
  ExternalLink,
  Loader2,
  AlertCircle,
  ArrowRight,
  ShieldCheck,
  Flame,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'
import { formatMDPL, formatDateIndonesia } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { BadgeItem } from '@/types/pendakiLogbook'

const router = useRouter()

const badges = ref<BadgeItem[]>([])
const isLoading = ref(true)
const errorMessage = ref('')

async function fetchBadges() {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await pendakiLogbookApi.getBadges()
    badges.value = res.data || []
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

const totalSummits = computed(() => badges.value.length)

const totalElevationAccumulated = computed(() => {
  return badges.value.reduce((acc, b) => acc + (b.tinggi_mdpl || 0), 0)
})

const highestPeak = computed(() => {
  if (badges.value.length === 0) return 0
  return Math.max(...badges.value.map(b => b.tinggi_mdpl || 0))
})

const explorerRank = computed(() => {
  const count = totalSummits.value
  if (count >= 10) return 'Master Mountaineer (Legenda Puncak)'
  if (count >= 5) return 'Advanced Explorer (Penjelajah Mahir)'
  if (count >= 1) return 'Summit Conqueror (Pendaki Tangguh)'
  return 'Beginner Hiker (Pendaki Pemula)'
})

const sevenSummits = [
  { name: 'Puncak Jaya (Carstensz)', island: 'Papua', mdpl: 4884 },
  { name: 'Gunung Kerinci', island: 'Sumatera', mdpl: 3805 },
  { name: 'Gunung Rinjani', island: 'Lombok', mdpl: 3726 },
  { name: 'Gunung Semeru', island: 'Jawa', mdpl: 3676 },
  { name: 'Gunung Latimojong', island: 'Sulawesi', mdpl: 3430 },
  { name: 'Gunung Binaiya', island: 'Maluku', mdpl: 3027 },
  { name: 'Bukit Raya', island: 'Kalimantan', mdpl: 2278 },
]

function isSevenSummitConquered(name: string): boolean {
  return badges.value.some(b => b.gunung_nama.toLowerCase().includes(name.toLowerCase()))
}

onMounted(() => {
  fetchBadges()
})
</script>

<template>
  <div class="space-y-8 max-w-5xl mx-auto pb-20">
    <!-- Header Title -->
    <div>
      <div class="flex items-center gap-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">
        <Trophy class="w-4 h-4" />
        <span>PASPOR DIGITAL &amp; LENCANA PRESTASI</span>
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-slate-100 font-display mt-1">
        Paspor Pendakian Gunung Indonesia
      </h1>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">
        Koleksi lencana penaklukan puncak resmi terverifikasi dan sertifikat digital 7 Summits Indonesia.
      </p>
    </div>

    <!-- Metrics Stats Banner -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-1">
        <span class="text-xs text-slate-500 font-medium block">Puncak Ditaklukkan</span>
        <strong class="text-2xl sm:text-3xl font-black text-emerald-700 dark:text-emerald-400 font-display block">
          {{ totalSummits }}
        </strong>
        <span class="text-[11px] text-slate-400">Gunung Terverifikasi</span>
      </div>

      <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-1">
        <span class="text-xs text-slate-500 font-medium block">Akumulasi Elevasi</span>
        <strong class="text-2xl sm:text-3xl font-black text-emerald-700 dark:text-emerald-400 font-display block">
          {{ formatMDPL(totalElevationAccumulated) }}
        </strong>
        <span class="text-[11px] text-slate-400">Total Ketinggian Vertikal</span>
      </div>

      <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-1">
        <span class="text-xs text-slate-500 font-medium block">Puncak Tertinggi</span>
        <strong class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400 font-display block">
          {{ highestPeak > 0 ? formatMDPL(highestPeak) : '-' }}
        </strong>
        <span class="text-[11px] text-slate-400">Rekor Personal</span>
      </div>

      <div class="p-5 rounded-3xl bg-emerald-950 text-white shadow-xs space-y-1">
        <span class="text-[11px] text-emerald-300 font-medium block">Peringkat Penjelajah</span>
        <strong class="text-sm sm:text-base font-black text-white block leading-snug">
          {{ explorerRank }}
        </strong>
        <span class="text-[10px] text-emerald-400 flex items-center gap-1 pt-1">
          <Flame class="w-3 h-3 text-orange-400" />
          <span>Summit Rank #1</span>
        </span>
      </div>
    </div>

    <!-- 7 Summits Indonesia Tracker Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-xl bg-amber-100 text-amber-900 font-bold">
            <Trophy class="w-4 h-4" />
          </div>
          <div>
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
              The 7 Summits of Indonesia Challenge
            </h3>
            <p class="text-xs text-slate-500">Tujuh puncak tertinggi di 7 pulau/kepulauan kepulauan Indonesia</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
        <div
          v-for="peak in sevenSummits"
          :key="peak.name"
          class="p-3.5 rounded-2xl text-center space-y-1.5 transition-all border"
          :class="
            isSevenSummitConquered(peak.name)
              ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950 ring-1 ring-emerald-200'
              : 'bg-slate-50/60 dark:bg-slate-800/40 border-slate-200/60 text-slate-400 opacity-60'
          "
        >
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center mx-auto text-xs font-bold"
            :class="
              isSevenSummitConquered(peak.name)
                ? 'bg-emerald-600 text-white'
                : 'bg-slate-200 dark:bg-slate-700 text-slate-500'
            "
          >
            <ShieldCheck v-if="isSevenSummitConquered(peak.name)" class="w-4 h-4" />
            <Mountain v-else class="w-4 h-4" />
          </div>
          <strong class="text-xs font-bold block truncate" :title="peak.name">{{ peak.name }}</strong>
          <span class="text-[10px] block">{{ peak.island }}</span>
          <span class="text-[10px] font-mono font-semibold text-emerald-700 dark:text-emerald-400 block">
            {{ formatMDPL(peak.mdpl) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Badges List Grid -->
    <div class="space-y-4">
      <h3 class="font-bold text-base text-slate-900 dark:text-slate-100 flex items-center gap-2">
        <Award class="w-5 h-5 text-emerald-600" />
        <span>Koleksi Lencana Penakluk Puncak ({{ badges.length }})</span>
      </h3>

      <!-- Loading State -->
      <div v-if="isLoading" class="py-16 text-center space-y-3">
        <Loader2 class="w-8 h-8 animate-spin text-emerald-600 mx-auto" />
        <p class="text-xs text-slate-500">Memuat lencana paspor...</p>
      </div>

      <!-- Error State -->
      <div
        v-else-if="errorMessage"
        class="p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2"
      >
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="badges.length === 0"
        class="p-16 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4"
      >
        <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
          <Award class="w-8 h-8" />
        </div>
        <div class="space-y-1 max-w-sm mx-auto">
          <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Belum Ada Lencana Puncak</h3>
          <p class="text-xs text-slate-500">
            Selesaikan pendakian pertama Anda dan unggah bukti foto summit di pos basecamp untuk membuka lencana resmi.
          </p>
        </div>
        <router-link to="/pendaki/gunung">
          <Button class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold gap-2 shadow-xs">
            <span>Mulai Petualangan Mendaki</span>
            <ArrowRight class="w-4 h-4" />
          </Button>
        </router-link>
      </div>

      <!-- Badges Cards Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
        <div
          v-for="badge in badges"
          :key="badge.id"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-amber-300/80 dark:border-slate-800 p-5 shadow-sm hover:shadow-md hover:border-amber-400 transition-all duration-200 relative overflow-hidden flex flex-col justify-between"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-yellow-300 text-slate-950 flex items-center justify-center shadow-xs">
                <Award class="w-6 h-6" />
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300">
                Official Summit
              </span>
            </div>

            <div>
              <h4 class="font-black text-base text-slate-900 dark:text-slate-100 leading-snug">
                {{ badge.badge_title }}
              </h4>
              <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                {{ formatMDPL(badge.tinggi_mdpl) }}
              </p>
            </div>

            <div class="space-y-1 text-xs text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
              <div class="flex items-center gap-1.5 truncate">
                <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span class="truncate">{{ badge.lokasi }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span>{{ formatDateIndonesia(badge.tanggal_summit) }}</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800">
            <Button
              v-if="badge.certificate_url"
              class="w-full rounded-xl bg-forest-600 hover:bg-forest-700 text-white text-xs font-bold gap-1.5 shadow-xs"
              @click="router.push(`/pendaki/certificate/${encodeURIComponent(badge.certificate_url.split('/').pop() || '')}`)"
            >
              <span>Lihat E-Sertifikat</span>
              <ExternalLink class="w-3.5 h-3.5" />
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
