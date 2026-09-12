<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  Compass,
  Tent,
  Users,
  Search,
  ArrowRight,
  Sparkles,
  ShieldCheck,
  CheckCircle2
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import KycStatusBanner from '@/components/pendaki/KycStatusBanner.vue'
import KycSubmissionModal from '@/components/pendaki/KycSubmissionModal.vue'
import HomeBannerCarousel from '@/components/pendaki/HomeBannerCarousel.vue'
import FlashDealsLogistics from '@/components/pendaki/FlashDealsLogistics.vue'
import MountainRecommendationGrid from '@/components/pendaki/MountainRecommendationGrid.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const searchQuery = ref('')
const isKycModalOpen = ref(false)

const kycStatus = computed(() => authStore.kycStatus)
const rejectionReason = computed(() => authStore.user?.pendaki?.alasan_penolakan || null)

onMounted(async () => {
  if (authStore.isAuthenticated && authStore.isPendaki) {
    await authStore.fetchKycStatus()
  }
})

const quickCategories = [
  {
    title: 'Booking SIMAKSI',
    subtitle: 'Tiket resmi 50+ gunung nasional',
    icon: Compass,
    color: 'from-[#1E3A2B] to-emerald-700',
    tag: 'Wajib',
    link: '/pendaki/gunung'
  },
  {
    title: 'Sewa Alat Outdoor',
    subtitle: 'Tenda, carrier, SB, kompor nesting',
    icon: Tent,
    color: 'from-[#E65100] to-amber-600',
    tag: 'Populer',
    link: '/pendaki/gunung'
  },
  {
    title: 'Porter & Guide APGI',
    subtitle: 'Jasa angkut & pemandu berlisensi',
    icon: Users,
    color: 'from-blue-600 to-indigo-700',
    tag: 'Terpercaya',
    link: '/pendaki/gunung'
  },
  {
    title: 'Paket Open Trip',
    subtitle: 'Jadwal open trip hemat all-in',
    icon: Sparkles,
    color: 'from-purple-600 to-pink-700',
    tag: 'Hemat',
    link: '/pendaki/gunung'
  }
]

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.push({
      path: '/pendaki/gunung',
      query: { search: searchQuery.value.trim() }
    })
  } else {
    router.push('/pendaki/gunung')
  }
}
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- KYC Banner Alert for Logged-in Pendaki -->
    <div v-if="authStore.isAuthenticated && authStore.isPendaki && !authStore.isKycVerified">
      <KycStatusBanner
        :status="kycStatus"
        :rejection-reason="rejectionReason"
        @verify="isKycModalOpen = true"
      />
    </div>

    <!-- Active Promo Banner Carousel -->
    <HomeBannerCarousel />

    <!-- Search Hero Bar -->
    <div class="rounded-3xl bg-white border border-gray-200 p-6 sm:p-8 shadow-sm space-y-4">
      <div class="max-w-3xl space-y-2">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-[#1E3A2B] text-xs font-extrabold border border-emerald-200">
          <Sparkles class="w-3.5 h-3.5" />
          <span>Marketplace Pendakian Terpadu #1 di Indonesia</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight leading-snug">
          Cari Gunung &amp; Booking Tiket SIMAKSI Basecamp Resmi
        </h1>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
          Pilih destinasi, sewa peralatan camping, dan gunakan jasa porter terpercaya dalam 1 transaksi aman via Escrow.
        </p>
      </div>

      <!-- Search Input Form -->
      <form @submit.prevent="handleSearch" class="flex flex-col sm:flex-row gap-3 pt-2">
        <div class="relative flex-1">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <Input
            v-model="searchQuery"
            placeholder="Cari Gunung Merbabu, Slamet, Rinjani, Prau, Semeru..."
            class="w-full pl-10 pr-4 h-12 rounded-2xl bg-gray-50 border-gray-200 text-gray-900 placeholder:text-gray-400 text-sm focus-visible:ring-[#1E3A2B]"
          />
        </div>
        <Button
          type="submit"
          class="h-12 px-7 rounded-2xl bg-[#1E3A2B] hover:bg-[#15281e] text-white font-bold text-sm shadow-md flex items-center justify-center gap-2 shrink-0 transition"
        >
          <span>Eksplorasi Gunung</span>
          <ArrowRight class="w-4 h-4" />
        </Button>
      </form>

      <!-- Value Proposition Badges -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-gray-100 text-xs text-gray-600">
        <div class="flex items-center gap-2">
          <ShieldCheck class="h-4 w-4 text-[#1E3A2B] shrink-0" />
          <span>SIMAKSI Terverifikasi</span>
        </div>
        <div class="flex items-center gap-2">
          <CheckCircle2 class="h-4 w-4 text-[#1E3A2B] shrink-0" />
          <span>Garansi Refund H-3</span>
        </div>
        <div class="flex items-center gap-2">
          <CheckCircle2 class="h-4 w-4 text-[#1E3A2B] shrink-0" />
          <span>Alat Camping Standar SOP</span>
        </div>
        <div class="flex items-center gap-2">
          <CheckCircle2 class="h-4 w-4 text-[#1E3A2B] shrink-0" />
          <span>Sertifikat Digital Resmi</span>
        </div>
      </div>
    </div>

    <!-- Quick Category Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <router-link
        v-for="cat in quickCategories"
        :key="cat.title"
        :to="cat.link"
        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-xs hover:border-emerald-300 hover:shadow-md transition-all flex flex-col justify-between"
      >
        <div class="flex items-start justify-between mb-4">
          <div :class="['w-11 h-11 rounded-xl bg-gradient-to-br text-white flex items-center justify-center shadow-xs', cat.color]">
            <component :is="cat.icon" class="w-5 h-5" />
          </div>
          <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
            {{ cat.tag }}
          </span>
        </div>

        <div>
          <h3 class="font-bold text-gray-900 text-sm sm:text-base group-hover:text-[#1E3A2B] transition-colors">
            {{ cat.title }}
          </h3>
          <p class="text-xs text-gray-500 mt-1 leading-relaxed">
            {{ cat.subtitle }}
          </p>
        </div>
      </router-link>
    </div>

    <!-- Flash Deals Section -->
    <FlashDealsLogistics />

    <!-- Mountain Recommendation Grid -->
    <MountainRecommendationGrid />

    <!-- KYC Modal -->
    <KycSubmissionModal
      :is-open="isKycModalOpen"
      @update:is-open="isKycModalOpen = $event"
      @submitted="authStore.updatePendakiProfile($event)"
    />
  </div>
</template>
