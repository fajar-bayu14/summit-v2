<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { publicAdsApi } from '@/api/publicAds'
import type { BannerAdItem } from '@/types/bannerAd'
import { ChevronLeft, ChevronRight, ExternalLink, Sparkles } from 'lucide-vue-next'

const banners = ref<BannerAdItem[]>([])
const currentIndex = ref(0)
const isHovered = ref(false)
let timer: ReturnType<typeof setInterval> | null = null

// Fallback high-quality promotional banners if backend has no active banners yet
const defaultBanners: BannerAdItem[] = [
  {
    id: 101,
    judul: 'Festival 7 Summits Indonesia 2026',
    gambar_url: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1400&q=80',
    target_url: '/pendaki/gunung',
    posisi: 'home_top',
    tipe: 'promo',
    tanggal_mulai: '2026-01-01',
    tanggal_selesai: '2026-12-31',
    is_active: true
  },
  {
    id: 102,
    judul: 'Diskon Sewa Alat Outdoor Basecamp Hingga 30%',
    gambar_url: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1400&q=80',
    target_url: '/pendaki/gunung',
    posisi: 'home_top',
    tipe: 'promo',
    tanggal_mulai: '2026-01-01',
    tanggal_selesai: '2026-12-31',
    is_active: true
  }
]

async function loadBanners() {
  try {
    const res = await publicAdsApi.getBanners('home_top')
    const list = res.data || []
    if (list.length > 0) {
      banners.value = list
    } else {
      banners.value = defaultBanners
    }
  } catch {
    banners.value = defaultBanners
  }
}

function nextSlide() {
  if (banners.value.length <= 1) return
  currentIndex.value = (currentIndex.value + 1) % banners.value.length
}

function prevSlide() {
  if (banners.value.length <= 1) return
  currentIndex.value = (currentIndex.value - 1 + banners.value.length) % banners.value.length
}

function startAutoSlide() {
  stopAutoSlide()
  timer = setInterval(() => {
    if (!isHovered.value) {
      nextSlide()
    }
  }, 5000)
}

function stopAutoSlide() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

async function handleBannerClick(banner: BannerAdItem) {
  if (banner.id && banner.id < 100) {
    try {
      await publicAdsApi.recordClick(banner.id)
    } catch {
      // silent
    }
  }
  if (banner.target_url) {
    if (banner.target_url.startsWith('http')) {
      window.open(banner.target_url, '_blank')
    }
  }
}

onMounted(() => {
  loadBanners()
  startAutoSlide()
})

onUnmounted(() => {
  stopAutoSlide()
})
</script>

<template>
  <div
    class="relative w-full overflow-hidden rounded-3xl bg-slate-900 shadow-xl"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Slides Container -->
    <div
      class="flex transition-transform duration-500 ease-out"
      :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
    >
      <div
        v-for="banner in banners"
        :key="banner.id"
        class="relative min-w-full aspect-[21/9] sm:aspect-[24/9] md:aspect-[28/9] max-h-[360px] cursor-pointer overflow-hidden group"
        @click="handleBannerClick(banner)"
      >
        <!-- Background Banner Image -->
        <img
          :src="banner.gambar_url"
          :alt="banner.judul"
          class="h-full w-full object-cover brightness-75 transition duration-700 group-hover:scale-105"
        />

        <!-- Overlay Gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent flex flex-col justify-end p-6 sm:p-8">
          <div class="max-w-2xl space-y-2">
            <span
              v-if="banner.tipe === 'promo'"
              class="inline-flex items-center gap-1 rounded-full bg-[#E65100] px-3 py-1 text-[11px] font-extrabold text-white shadow-md uppercase tracking-wider"
            >
              <Sparkles class="h-3 w-3" />
              Promo Spesial
            </span>
            <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-white tracking-tight leading-snug drop-shadow-md">
              {{ banner.judul }}
            </h2>
            <div class="flex items-center gap-2 pt-1 text-xs font-semibold text-emerald-300 group-hover:text-emerald-200 transition">
              <span>Jelajahi Sekarang</span>
              <ExternalLink class="h-3.5 w-3.5" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Arrows -->
    <button
      v-if="banners.length > 1"
      type="button"
      class="absolute left-3 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-xs hover:bg-black/70 transition"
      @click.stop="prevSlide"
    >
      <ChevronLeft class="h-5 w-5" />
    </button>
    <button
      v-if="banners.length > 1"
      type="button"
      class="absolute right-3 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-xs hover:bg-black/70 transition"
      @click.stop="nextSlide"
    >
      <ChevronRight class="h-5 w-5" />
    </button>

    <!-- Slide Indicator Dots -->
    <div
      v-if="banners.length > 1"
      class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
    >
      <button
        v-for="(_, idx) in banners"
        :key="idx"
        type="button"
        :class="[
          'h-2 rounded-full transition-all duration-300',
          idx === currentIndex ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/75'
        ]"
        @click.stop="currentIndex = idx"
      ></button>
    </div>
  </div>
</template>
