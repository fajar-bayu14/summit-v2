<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import heroImage from '@/assets/hero.jpg'

// Preloader state
const isLoading = ref(true)
const showLoader = ref(true)
const loadingProgress = ref(0)

// Scroll-aware navbar + active section tracking
const isScrolled = ref(false)
const activeSection = ref('hero')

const navLinks = [
  { id: 'hero',       label: 'Home' },
  { id: 'gunung',     label: 'Mendaki Kemana?' },
  { id: 'cara-kerja', label: 'Menuju Puncak' },
]

let observer: IntersectionObserver | null = null
let scrollRevealObserver: IntersectionObserver | null = null

const handleScroll = () => {
  isScrolled.value = window.scrollY > 40
}

onMounted(() => {
  // 1. Preloader progress animation
  const startTime = performance.now()
  const duration = 650 // ms
  const updateProgress = (currentTime: number) => {
    const elapsed = currentTime - startTime
    const progress = Math.min(100, (elapsed / duration) * 100)
    loadingProgress.value = progress

    if (progress < 100) {
      requestAnimationFrame(updateProgress)
    } else {
      setTimeout(() => {
        isLoading.value = false
        setTimeout(() => {
          showLoader.value = false
        }, 700) // remove from DOM after fade-out transition
      }, 120)
    }
  }
  requestAnimationFrame(updateProgress)

  // 2. Navbar scroll listener
  window.addEventListener('scroll', handleScroll, { passive: true })

  // 3. Active section observer
  observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          activeSection.value = entry.target.id
        }
      }
    },
    { rootMargin: '-10% 0px -60% 0px', threshold: 0 },
  )

  navLinks.forEach(({ id }) => {
    const el = document.getElementById(id)
    if (el) observer!.observe(el)
  })

  // 4. Scroll Reveal Motion Observer (triggers once per page load)
  nextTick(() => {
    const revealElements = document.querySelectorAll(
      '.reveal-init, .reveal-left, .reveal-right, .reveal-scale'
    )
    scrollRevealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in-view')
            scrollRevealObserver?.unobserve(entry.target)
          }
        })
      },
      { rootMargin: '0px 0px -6% 0px', threshold: 0.08 }
    )

    revealElements.forEach((el) => scrollRevealObserver!.observe(el))
  })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  observer?.disconnect()
  scrollRevealObserver?.disconnect()
})
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import {
  Mountain,
  Compass,
  Tent,
  ShieldCheck,
  CheckCircle2,
  ArrowRight,
  QrCode,
  Lock,
  Mail,
  User,
  Eye,
  EyeOff,
  Phone,
  Backpack,
  AlertCircle,
  Route,
  Users,
  RefreshCw,
  CalendarCheck,
} from 'lucide-vue-next'

import { useAuthStore } from '@/stores/auth'
import { getDashboardRouteByRole } from '@/router/guards'

const router = useRouter()
const authStore = useAuthStore()


// Auth Modal state
const isAuthModalOpen = ref(false)
const authMode = ref<'login' | 'register'>('login')
const selectedRole = ref<'pendaki' | 'mitra' | 'admin'>('pendaki')

// Quick Booking Dialog state
const isBookingModalOpen = ref(false)

// Form states
const loginForm = ref({
  email: '',
  password: '',
  rememberMe: false,
})

const registerForm = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  agreeTerms: false,
})

const showPassword = ref(false)
const isSubmitting = ref(false)
const authSuccessMessage = ref<string | null>(null)
const authErrorMessage = ref<string | null>(null)

// Realistic Mountain Data with High-Res Stitch Photography
export interface MountainItem {
  id: string
  name: string
  elevation: string
  elevationNum: number
  location: string
  province: string
  trails: string
  defaultTrail: string
  price: number
  quotaLeft: number
  maxQuota: number
  status: string
  statusType: 'available' | 'limited' | 'warning'
  weather: string
  basecamp: string
  imageUrl: string
  categories: string[]
}

const mountainsData: MountainItem[] = [
  {
    id: 'merbabu',
    name: 'Gunung Merbabu',
    elevation: '3.142 MDPL',
    elevationNum: 3142,
    location: 'Magelang & Boyolali',
    province: 'Jawa Tengah',
    trails: 'Selo, Suwanting, Wekas',
    defaultTrail: 'Selo',
    price: 150000,
    quotaLeft: 42,
    maxQuota: 150,
    status: 'Kuota Tersedia',
    statusType: 'available',
    weather: '18°C',
    basecamp: 'Selo',
    imageUrl: '/images/merbabu.jpg',
    categories: ['semua', 'pemula', 'weekend', 'sunrise'],
  },
  {
    id: 'rinjani',
    name: 'Gunung Rinjani',
    elevation: '3.726 MDPL',
    elevationNum: 3726,
    location: 'Lombok Timur & Utara',
    province: 'Nusa Tenggara Barat',
    trails: 'Senaru, Sembalun, Torean',
    defaultTrail: 'Senaru',
    price: 450000,
    quotaLeft: 96,
    maxQuota: 250,
    status: 'Kuota Tersedia',
    statusType: 'available',
    weather: '12°C',
    basecamp: 'Senaru',
    imageUrl: '/images/rinjani.jpg',
    categories: ['semua', 'tantangan', 'sunrise'],
  },
  {
    id: 'prau',
    name: 'Gunung Prau',
    elevation: '2.565 MDPL',
    elevationNum: 2565,
    location: 'Dataran Tinggi Dieng',
    province: 'Jawa Tengah',
    trails: 'Patakbanteng, Dieng Kulon',
    defaultTrail: 'Patakbanteng',
    price: 50000,
    quotaLeft: 14,
    maxQuota: 200,
    status: 'Kuota Terbatas',
    statusType: 'limited',
    weather: '9°C',
    basecamp: 'Patakbanteng',
    imageUrl: '/images/prau.jpg',
    categories: ['semua', 'pemula', 'weekend', 'sunrise'],
  },
  {
    id: 'gede',
    name: 'Gunung Gede Pangrango',
    elevation: '2.958 MDPL',
    elevationNum: 2958,
    location: 'Cianjur & Bogor',
    province: 'Jawa Barat',
    trails: 'Cibodas, Gunung Putri, Selabintana',
    defaultTrail: 'Cibodas',
    price: 35000,
    quotaLeft: 82,
    maxQuota: 300,
    status: 'Kuota Tersedia',
    statusType: 'available',
    weather: '16°C',
    basecamp: 'Cibodas',
    imageUrl: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1000&q=80',
    categories: ['semua', 'pemula', 'weekend'],
  },
  {
    id: 'semeru',
    name: 'Gunung Semeru',
    elevation: '3.676 MDPL',
    elevationNum: 3676,
    location: 'Lumajang & Malang',
    province: 'Jawa Timur',
    trails: 'Ranu Pane, Oro-oro Ombo',
    defaultTrail: 'Ranu Pane',
    price: 45000,
    quotaLeft: 32,
    maxQuota: 200,
    status: 'Kuota Terbatas',
    statusType: 'limited',
    weather: '10°C',
    basecamp: 'Ranu Pane',
    imageUrl: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1000&q=80',
    categories: ['semua', 'tantangan', 'sunrise'],
  },
  {
    id: 'kerinci',
    name: 'Gunung Kerinci',
    elevation: '3.805 MDPL',
    elevationNum: 3805,
    location: 'Kerinci & Solok Selatan',
    province: 'Jambi & Sumatera Barat',
    trails: 'Kersik Tuo, Solok Selatan',
    defaultTrail: 'Kersik Tuo',
    price: 40000,
    quotaLeft: 58,
    maxQuota: 100,
    status: 'Kuota Tersedia',
    statusType: 'available',
    weather: '11°C',
    basecamp: 'Kersik Tuo',
    imageUrl: 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?auto=format&fit=crop&w=1000&q=80',
    categories: ['semua', 'tantangan'],
  },
]

// Interactive Floating Quota Checker State
const selectedMountainId = ref('merbabu')
const selectedTrail = ref('Selo')
const searchDate = ref('2026-09-18')
const climberCount = ref(4)

const currentMountain = computed(() => {
  return mountainsData.find(m => m.id === selectedMountainId.value) || mountainsData[0]
})

const availableTrails = computed(() => {
  return currentMountain.value.trails.split(',').map(t => t.trim())
})

// Sync default trail when mountain changes
watch(selectedMountainId, () => {
  const trails = availableTrails.value
  if (trails.length > 0) {
    selectedTrail.value = trails[0]
  }
})

const estimatedPrice = computed(() => {
  return (currentMountain.value.price * climberCount.value).toLocaleString('id-ID')
})

// Category filter for Mountain Discovery
const selectedCategory = ref('semua')
const filterCategories = [
  { id: 'semua', label: 'Semua' },
  { id: 'pemula', label: 'Pemula' },
  { id: 'weekend', label: 'Weekend' },
  { id: 'sunrise', label: 'Sunrise' },
  { id: 'tantangan', label: 'Tantangan' },
]

const filteredMountains = computed(() => {
  if (selectedCategory.value === 'semua') {
    return mountainsData.slice(0, 3)
  }
  const matches = mountainsData.filter(m => m.categories.includes(selectedCategory.value))
  return matches.length > 0 ? matches : mountainsData.slice(0, 3)
})

const selectMountainCard = (mountain: MountainItem) => {
  selectedMountainId.value = mountain.id
  selectedTrail.value = mountain.defaultTrail
  isBookingModalOpen.value = true
}

// Modal Trigger Helpers
const openAuthModal = (mode: 'login' | 'register', role: 'pendaki' | 'mitra' | 'admin' = 'pendaki') => {
  authMode.value = mode
  selectedRole.value = role
  authSuccessMessage.value = null
  authErrorMessage.value = null
  isAuthModalOpen.value = true
}

// Quick Demo Login Helper
const quickLogin = (role: 'pendaki' | 'mitra' | 'admin') => {
  selectedRole.value = role
  if (role === 'pendaki') {
    loginForm.value.email = 'pendaki@summit.id'
    loginForm.value.password = 'password123'
  } else if (role === 'mitra') {
    loginForm.value.email = 'basecamp.selo@summit.id'
    loginForm.value.password = 'password123'
  } else {
    loginForm.value.email = 'admin@example.com'
    loginForm.value.password = 'password'
  }
}

// Handle Form Submissions
const handleLoginSubmit = () => {
  authErrorMessage.value = null
  if (!loginForm.value.email || !loginForm.value.password) {
    authErrorMessage.value = 'Mohon lengkapi email dan password Anda.'
    return
  }

  isSubmitting.value = true
  setTimeout(() => {
    isSubmitting.value = false
    authSuccessMessage.value = `Login berhasil! Selamat datang kembali di portal ${selectedRole.value.toUpperCase()}.`
  }, 600)
}

const handleRegisterSubmit = () => {
  authErrorMessage.value = null
  if (!registerForm.value.name || !registerForm.value.email || !registerForm.value.password) {
    authErrorMessage.value = 'Mohon isi seluruh data yang diperlukan.'
    return
  }
  if (registerForm.value.password !== registerForm.value.passwordConfirmation) {
    authErrorMessage.value = 'Konfirmasi password tidak cocok.'
    return
  }
  if (!registerForm.value.agreeTerms) {
    authErrorMessage.value = 'Anda harus menyetujui Syarat & Ketentuan pendakian.'
    return
  }

  isSubmitting.value = true
  setTimeout(() => {
    isSubmitting.value = false
    authSuccessMessage.value = 'Pendaftaran berhasil! Kode OTP verifikasi telah dikirim ke email Anda.'
  }, 700)
}

const navigateToPortal = () => {
  isAuthModalOpen.value = false
  if (selectedRole.value === 'pendaki') {
    router.push('/pendaki')
  } else if (selectedRole.value === 'mitra') {
    router.push('/mitra')
  } else {
    router.push('/admin')
  }
}


// 4 Easy Steps
const steps = [
  {
    number: '01',
    title: 'Cari Jalur & Cek Kuota',
    desc: 'Tentukan gunung impian Anda, pilih jalur favorit, dan pantau ketersediaan kuota harian secara transparan.',
    icon: Compass,
  },
  {
    number: '02',
    title: 'Lengkapi Anggota & Logistik',
    desc: 'Isi data identitas rombongan, tambahkan sewa perlengkapan outdoor dan porter berlisensi bila dibutuhkan.',
    icon: Backpack,
  },
  {
    number: '03',
    title: 'Bayar Aman via Escrow',
    desc: 'Selesaikan pembayaran instan (QRIS, VA, E-Wallet). Dana terlindungi 100% di akun penampung resmi Xendit.',
    icon: ShieldCheck,
  },
  {
    number: '04',
    title: 'Scan QR di Basecamp & Daki',
    desc: 'Tunjukkan e-tiket QR Code kepada petugas di pos basecamp untuk check-in instan dan mulailah mendaki!',
    icon: QrCode,
  },
]
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-on-surface font-body-md antialiased selection:bg-primary-container selection:text-on-primary-container flex flex-col">
    <!-- Preloader / Loading Screen -->
    <div
      v-if="showLoader"
      :class="[
        'fixed inset-0 z-[100] flex flex-col items-center justify-center bg-pine-950 text-white transition-all duration-700 ease-out',
        isLoading ? 'opacity-100 scale-100' : 'opacity-0 scale-105 pointer-events-none'
      ]"
    >
      <!-- Atmospheric Ambient Glow -->
      <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary-fixed/15 rounded-full blur-3xl pointer-events-none"></div>

      <div class="relative flex flex-col items-center text-center px-6">
        <!-- Mountain Icon with Pulse Ring -->
        <div class="relative w-20 h-20 flex items-center justify-center mb-6">
          <div class="absolute inset-0 rounded-full bg-primary/25 animate-ping"></div>
          <div class="relative w-16 h-16 rounded-2xl bg-surface-container-low/10 border border-primary-fixed/30 backdrop-blur-md flex items-center justify-center text-primary-fixed shadow-lg shadow-primary/20">
            <Mountain class="w-8 h-8 text-primary-fixed animate-pulse" />
          </div>
        </div>

        <!-- Brand Name -->
        <h1 class="font-display-lg text-4xl tracking-tighter shimmer-text mb-2">
          SUMMIT
        </h1>
        <p class="font-label-md text-xs uppercase tracking-[0.25em] text-white/70 mb-8 font-semibold">
          Platform Pendakian Gunung Indonesia
        </p>

        <!-- Minimalist Progress Bar -->
        <div class="w-52 sm:w-64 h-1.5 bg-white/10 rounded-full overflow-hidden mb-3 border border-white/10">
          <div
            class="h-full bg-gradient-to-r from-emerald-500 via-primary-fixed to-teal-300 rounded-full transition-all duration-300 ease-out shadow-sm shadow-primary"
            :style="{ width: `${loadingProgress}%` }"
          ></div>
        </div>

        <!-- Loading percentage & status text -->
        <div class="flex items-center justify-between w-52 sm:w-64 text-[11px] font-mono text-white/50">
          <span>Menyiapkan Jalur...</span>
          <span>{{ Math.round(loadingProgress) }}%</span>
        </div>
      </div>
    </div>

    <!-- TopNavBar (Desktop) -->
    <nav
      :class="[
        'fixed top-0 w-full z-50 transition-all duration-300 hidden md:flex',
        isScrolled
          ? 'bg-surface/90 backdrop-blur-xl border-b border-outline-variant/30 shadow-sm'
          : 'bg-black/25 border-b border-transparent backdrop-blur-sm',
      ]"
    >
      <div class="flex justify-between items-center max-w-7xl mx-auto px-margin-desktop py-4 w-full">
        <router-link
          to="/"
          :class="['font-display-lg text-2xl tracking-tighter flex items-center gap-2 transition-colors duration-300', isScrolled ? 'text-pine-900' : 'text-white drop-shadow-md']"
        >
          SUMMIT
        </router-link>

        <!-- Nav Links -->
        <div class="flex items-center gap-8">
          <a
            v-for="link in navLinks"
            :key="link.id"
            :href="`#${link.id}`"
            :class="[
              'font-label-lg text-label-lg transition-all duration-200 cursor-pointer relative py-1',
              isScrolled
                ? activeSection === link.id
                  ? 'text-primary font-bold after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-primary after:rounded-full'
                  : 'text-on-surface-variant hover:text-primary'
                : activeSection === link.id
                  ? 'text-white font-bold drop-shadow-sm after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-white after:rounded-full'
                  : 'text-white/75 hover:text-white drop-shadow-sm',
            ]"
          >{{ link.label }}</a>
        </div>

        <!-- Desktop Auth Action -->
        <div v-if="!authStore.isAuthenticated" class="flex items-center gap-4">
          <router-link
            to="/login"
            :class="['font-label-lg text-label-lg transition-colors duration-300 cursor-pointer drop-shadow-sm', isScrolled ? 'text-on-surface-variant hover:text-primary' : 'text-white hover:text-white/80']"
          >
            Log In
          </router-link>
          <router-link
            to="/login"
            :class="[
              'font-label-lg text-label-lg px-6 py-3 rounded-full transition-all duration-300 flex items-center gap-2 cursor-pointer',
              isScrolled
                ? 'bg-primary-container text-on-primary-container hover:bg-primary hover:text-white shadow-sm'
                : 'bg-white/25 backdrop-blur-md border border-white/50 text-white hover:bg-white/35 shadow-md',
            ]"
          >
            <span>Mulai Mendaki</span>
          </router-link>
        </div>
        <div v-else class="flex items-center gap-3">
          <router-link
            :to="getDashboardRouteByRole(authStore.userRole)"
            :class="[
              'font-label-lg text-label-lg px-5 py-2.5 rounded-full transition-all duration-300 flex items-center gap-2 cursor-pointer font-semibold',
              isScrolled
                ? 'bg-primary text-white shadow-sm'
                : 'bg-white/25 backdrop-blur-md border border-white/50 text-white hover:bg-white/35 shadow-md',
            ]"
          >
            <User class="w-4 h-4" />
            <span>{{ authStore.user?.name || 'Dashboard' }}</span>
            <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-600 text-white font-bold">{{ authStore.userRole }}</span>
          </router-link>
          <button
            type="button"
            :class="['font-label-md text-label-md transition-colors duration-300 cursor-pointer drop-shadow-sm text-xs px-3 py-1.5 rounded-lg border border-slate-300/40 hover:bg-white/20', isScrolled ? 'text-slate-700' : 'text-white']"
            @click="authStore.logout()"
          >
            Keluar
          </button>
        </div>
      </div>
    </nav>

    <!-- Mobile Nav -->
    <nav
      :class="[
        'fixed top-0 w-full z-50 transition-all duration-300 flex md:hidden flex-col px-margin-mobile',
        isScrolled
          ? 'bg-surface/95 backdrop-blur-xl border-b border-outline-variant/30 shadow-sm'
          : 'bg-black/30 border-b border-transparent backdrop-blur-sm',
      ]"
    >
      <!-- Row 1: Logo + Auth -->
      <div class="flex items-center justify-between py-3">
        <router-link
          to="/"
          :class="['font-display-lg-mobile text-2xl tracking-tighter flex items-center gap-2 transition-colors duration-300', isScrolled ? 'text-pine-900' : 'text-white drop-shadow-md']"
        >
          SUMMIT
        </router-link>
        <div v-if="!authStore.isAuthenticated" class="flex items-center gap-3">
          <router-link
            to="/login"
            :class="['font-label-md text-label-md transition-colors duration-300 cursor-pointer drop-shadow-sm', isScrolled ? 'text-on-surface-variant hover:text-primary' : 'text-white']"
          >
            Log In
          </router-link>
          <router-link
            to="/login"
            :class="[
              'font-label-md text-label-md px-4 py-2 rounded-full transition-all duration-300 cursor-pointer',
              isScrolled
                ? 'bg-primary-container text-on-primary-container hover:bg-primary hover:text-white shadow-sm'
                : 'bg-white/25 border border-white/50 text-white hover:bg-white/35 shadow-md',
            ]"
          >
            Daftar
          </router-link>
        </div>
        <div v-else class="flex items-center gap-2">
          <router-link
            :to="getDashboardRouteByRole(authStore.userRole)"
            class="text-xs font-semibold px-3 py-1.5 rounded-full bg-primary text-white"
          >
            {{ authStore.user?.name || 'Dashboard' }}
          </router-link>
          <button
            type="button"
            class="text-xs text-white/80 hover:text-white px-2 py-1"
            @click="authStore.logout()"
          >
            Keluar
          </button>
        </div>
      </div>

      <!-- Row 2: Nav Links -->
      <div class="flex items-center gap-6 pb-2.5">
        <a
          v-for="link in navLinks"
          :key="link.id"
          :href="`#${link.id}`"
          :class="[
            'text-sm font-semibold transition-all duration-200 cursor-pointer relative pb-1.5',
            isScrolled
              ? activeSection === link.id
                ? 'text-primary after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-primary after:rounded-full'
                : 'text-on-surface-variant hover:text-primary'
              : activeSection === link.id
                ? 'text-white after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-white after:rounded-full'
                : 'text-white/65 hover:text-white',
          ]"
        >{{ link.label }}</a>
      </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="pb-20">
      <!-- 1. Hero Section (full viewport height) -->
      <section id="hero" class="relative w-full h-screen min-h-[600px] mb-20">
        <!-- Hero Background -->
        <div
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${heroImage})` }"
        ></div>
        <!-- Bottom gradient for text readability -->
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/35 to-transparent"></div>
        <!-- Top vignette so navbar text is readable -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-transparent to-transparent h-48"></div>

        <div class="absolute bottom-14 sm:bottom-20 left-0 w-full px-margin-mobile md:px-margin-desktop text-white">
          <div class="max-w-7xl mx-auto reveal-init">
            <h1 class="text-shadow font-display-lg text-4xl sm:text-5xl md:text-6xl lg:text-7xl mb-4 max-w-3xl leading-tight text-white">
              Siapkan Pendakianmu.<br />Daki dengan Tenang.
            </h1>
            <p class="text-shadow font-body-lg text-base md:text-lg max-w-xl text-white/90 leading-relaxed mb-8">
              Temukan gunung, pesan simaksi resmi, dan siapkan kebutuhan perjalananmu dalam satu tempat.
            </p>
            <div class="flex flex-wrap items-center gap-4">
              <a
                href="#gunung"
                class="bg-primary-container text-on-primary-container font-label-lg text-label-lg px-7 py-3.5 rounded-full hover:bg-primary hover:text-white transition-all duration-300 flex items-center gap-2 font-semibold cursor-pointer"
              >
                <span>Jelajahi Gunung</span>
                <ArrowRight class="w-4 h-4" />
              </a>
              <button
                type="button"
                class="bg-white/20 backdrop-blur-md border border-white/30 text-white hover:bg-white/30 font-label-lg text-label-lg px-7 py-3.5 rounded-full transition-all duration-300 flex items-center gap-2 font-semibold cursor-pointer"
                @click="openAuthModal('register')"
              >
                <span>Mulai Mendaki</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- 2. Trust Bar -->
      <section class="border-y border-slate-200 bg-white py-8 mb-24 reveal-init">
        <div class="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop flex flex-wrap justify-center md:justify-between items-center gap-8 opacity-80">
          <div class="flex items-center gap-3 font-label-lg text-label-lg text-pine-900 reveal-scale delay-100">
            <Mountain class="w-5 h-5 text-primary" />
            <span>50+ Gunung &amp; Jalur Resmi</span>
          </div>
          <div class="hidden md:block w-px h-6 bg-slate-300"></div>
          <div class="flex items-center gap-3 font-label-lg text-label-lg text-pine-900 reveal-scale delay-200">
            <Users class="w-5 h-5 text-primary" />
            <span>15K+ Pendaki Terverifikasi</span>
          </div>
          <div class="hidden md:block w-px h-6 bg-slate-300"></div>
          <div class="flex items-center gap-3 font-label-lg text-label-lg text-pine-900 reveal-scale delay-300">
            <RefreshCw class="w-5 h-5 text-primary" />
            <span>Real-Time Informasi Kuota</span>
          </div>
          <div class="hidden md:block w-px h-6 bg-slate-300"></div>
          <div class="flex items-center gap-3 font-label-lg text-label-lg text-pine-900 reveal-scale delay-400">
            <CalendarCheck class="w-5 h-5 text-primary" />
            <span>24/7 Booking Online</span>
          </div>
        </div>
      </section>

      <!-- 3. Mountain Discovery ("Mau Mendaki Gunung Mana?") -->
      <section id="gunung" class="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop mb-32">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
          <div class="max-w-2xl reveal-left">
            <h2 class="font-headline-lg text-headline-lg text-pine-950 mb-4">Mau Mendaki Gunung Mana?</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
              Temukan jalur pendakian terbaik sesuai dengan tingkat pengalamanmu dan kondisi terkini.
            </p>
          </div>
          <!-- Category Filter Buttons -->
          <div class="flex flex-wrap gap-2 reveal-right delay-100">
            <button
              v-for="cat in filterCategories"
              :key="cat.id"
              type="button"
              :class="selectedCategory === cat.id
                ? 'bg-primary text-white shadow-sm'
                : 'bg-white border border-slate-200 text-on-surface hover:bg-slate-50'"
              class="px-5 py-2 rounded-full font-label-md text-label-md transition-colors cursor-pointer"
              @click="selectedCategory = cat.id"
            >
              {{ cat.label }}
            </button>
          </div>
        </div>

        <!-- 3 Feature Mountain Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(mountain, index) in filteredMountains"
            :key="mountain.id"
            :class="[
              'reveal-init',
              index === 0 ? 'delay-100' : index === 1 ? 'delay-200' : 'delay-300'
            ]"
            class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group border border-slate-100 flex flex-col h-full cursor-pointer"
            @click="selectMountainCard(mountain)"
          >
            <!-- Card Image -->
            <div class="relative h-64 overflow-hidden">
              <img
                :src="mountain.imageUrl"
                :alt="mountain.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
              />
              <!-- Quota Status Badge -->
              <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-lg font-label-md text-label-md text-pine-900 shadow-sm flex items-center gap-1.5">
                <span
                  :class="mountain.statusType === 'available' ? 'bg-green-500' : 'bg-yellow-400'"
                  class="w-2 h-2 rounded-full"
                ></span>
                {{ mountain.status }}
              </div>
              <!-- Elevation Badge -->
              <div class="absolute bottom-4 right-4 bg-pine-950/80 backdrop-blur-sm text-white px-3 py-1 rounded-lg font-label-md text-label-md shadow-sm">
                {{ mountain.elevation }}
              </div>
            </div>

            <!-- Card Body -->
            <div class="p-6 flex flex-col flex-grow">
              <h3 class="font-headline-md text-headline-md text-pine-950 mb-1 group-hover:text-primary transition-colors">
                {{ mountain.name }}
              </h3>
              <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-1.5 mb-4">
                <Route class="w-4 h-4 text-outline" />
                <span>Via {{ mountain.defaultTrail }}</span>
              </p>

              <!-- Card Footer -->
              <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between">
                <div>
                  <span class="font-label-md text-label-md text-on-surface-variant block mb-0.5">Mulai dari</span>
                  <span class="font-label-lg text-label-lg text-pine-900 font-bold">
                    Rp {{ mountain.price.toLocaleString('id-ID') }}
                  </span>
                </div>
                <button
                  type="button"
                  class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-pine-900 group-hover:bg-primary-container group-hover:border-primary-container group-hover:text-on-primary-container transition-colors"
                  aria-label="Pilih gunung"
                >
                  <ArrowRight class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>


      <!-- 5. Cara Kerja (4 Langkah Mudah) -->
      <section id="cara-kerja" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop">
          <div class="max-w-3xl mx-auto text-center space-y-3 mb-16 reveal-init">
            <span class="inline-block px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-label-md text-label-md font-bold uppercase tracking-wider">
              ALUR MUDAH
            </span>
            <h2 class="font-headline-lg text-headline-lg text-pine-950">
              4 Langkah Menuju Puncak
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
              Dari persiapan di rumah hingga melangkah di jalur pendakian secara pasti dan tanpa ribet.
            </p>
          </div>

          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
            <div
              v-for="(step, index) in steps"
              :key="step.number"
              :class="[
                'reveal-init',
                index === 0 ? 'delay-100' : index === 1 ? 'delay-200' : index === 2 ? 'delay-300' : 'delay-400'
              ]"
              class="p-7 rounded-3xl bg-white border border-slate-200 space-y-5 hover:border-primary/40 hover:shadow-lg transition-all duration-300 group"
            >
              <div class="flex items-center justify-between">
                <span class="text-4xl font-black text-primary/25 font-mono group-hover:text-primary transition-colors">
                  {{ step.number }}
                </span>
                <div class="w-11 h-11 rounded-2xl bg-surface-container-low text-primary flex items-center justify-center">
                  <component :is="step.icon" class="w-5 h-5" />
                </div>
              </div>
              <h3 class="font-headline-md text-lg font-bold text-pine-950">
                {{ step.title }}
              </h3>
              <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                {{ step.desc }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- 6. Keamanan & Ekosistem Multi-Role -->
      <section id="keamanan" class="py-24 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-margin-mobile md:px-margin-desktop">
          <div class="max-w-3xl mx-auto text-center space-y-3 mb-16 reveal-init">
            <span class="inline-block px-3 py-1 rounded-full bg-surface-container-low text-primary font-label-md text-label-md font-bold uppercase tracking-wider">
              PORTAL PERAN RESMI
            </span>
            <h2 class="font-headline-lg text-headline-lg text-pine-950">
              Solusi Khusus untuk Setiap Pihak
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant">
              Satu ekosistem terpadu untuk Pendaki, Mitra Operator Basecamp, dan Admin Pengawas Platform.
            </p>
          </div>

          <div class="grid md:grid-cols-3 gap-7 max-w-6xl mx-auto">
            <!-- Role 1: Pendaki -->
            <Card class="reveal-left delay-100 border-slate-200 hover:border-primary/40 hover:shadow-xl transition-all duration-300 rounded-3xl bg-white flex flex-col justify-between p-7">
              <CardHeader class="p-0 pb-4">
                <div class="w-13 h-13 rounded-2xl bg-surface-container-low text-primary flex items-center justify-center mb-4 border border-primary/20">
                  <Compass class="w-7 h-7" />
                </div>
                <div class="flex items-center justify-between">
                  <CardTitle class="text-2xl font-bold text-pine-950">Pendaki</CardTitle>
                  <Badge class="bg-surface-container-low text-primary border border-primary/30 font-bold text-xs">
                    End-User
                  </Badge>
                </div>
                <CardDescription class="text-sm text-slate-600 pt-2 leading-relaxed">
                  Eksplorasi katalog gunung se-Indonesia, reservasi tiket simaksi resmi, sewa alat outdoor, dan simpan riwayat digital logbook.
                </CardDescription>
              </CardHeader>
              <CardContent class="p-0 space-y-3 text-sm text-slate-700 pb-6">
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-primary shrink-0" />
                  <span>Katalog Gunung & Jalur Lengkap</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-primary shrink-0" />
                  <span>Reservasi Tiket & Sewa Perlengkapan</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-primary shrink-0" />
                  <span>Digital Logbook & Proteksi Escrow</span>
                </div>
              </CardContent>
              <div class="space-y-2.5">
                <Button
                  class="w-full bg-primary hover:bg-pine-900 text-white font-bold h-11 rounded-full cursor-pointer shadow-sm"
                  @click="openAuthModal('register', 'pendaki')"
                >
                  Daftar Sebagai Pendaki
                </Button>
                <router-link to="/pendaki" class="block w-full">
                  <Button variant="ghost" class="w-full text-xs text-slate-600 hover:text-primary rounded-full cursor-pointer">
                    Jelajahi Portal Pendaki →
                  </Button>
                </router-link>
              </div>
            </Card>

            <!-- Role 2: Mitra Basecamp -->
            <Card class="reveal-scale delay-200 border-slate-200 hover:border-amber-500/40 hover:shadow-xl transition-all duration-300 rounded-3xl bg-white flex flex-col justify-between p-7">
              <CardHeader class="p-0 pb-4">
                <div class="w-13 h-13 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center mb-4 border border-amber-200">
                  <Tent class="w-7 h-7" />
                </div>
                <div class="flex items-center justify-between">
                  <CardTitle class="text-2xl font-bold text-pine-950">Mitra Basecamp</CardTitle>
                  <Badge class="bg-amber-50 text-amber-700 border border-amber-200 font-bold text-xs">
                    Operator Pos
                  </Badge>
                </div>
                <CardDescription class="text-sm text-slate-600 pt-2 leading-relaxed">
                  Kelola kuota jalur harian, validasi QR tiket di pos masuk, kelola inventaris alat, dan penarikan dana otomatis via Xendit.
                </CardDescription>
              </CardHeader>
              <CardContent class="p-0 space-y-3 text-sm text-slate-700 pb-6">
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-amber-600 shrink-0" />
                  <span>Kalender Kuota & Buka/Tutup Jalur</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-amber-600 shrink-0" />
                  <span>Validasi Cepat Scan Tiket QR Pos</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-amber-600 shrink-0" />
                  <span>Penarikan Dana Otomatis & Sewa Alat</span>
                </div>
              </CardContent>
              <div class="space-y-2.5">
                <Button
                  variant="outline"
                  class="w-full border-amber-300 text-amber-800 hover:bg-amber-50 font-bold h-11 rounded-full cursor-pointer"
                  @click="openAuthModal('register', 'mitra')"
                >
                  Daftar Sebagai Mitra
                </Button>
                <router-link to="/mitra" class="block w-full">
                  <Button variant="ghost" class="w-full text-xs text-slate-600 hover:text-amber-800 rounded-full cursor-pointer">
                    Buka Dashboard Mitra →
                  </Button>
                </router-link>
              </div>
            </Card>

            <!-- Role 3: Admin Central -->
            <Card class="reveal-right delay-300 border-slate-200 hover:border-indigo-500/40 hover:shadow-xl transition-all duration-300 rounded-3xl bg-white flex flex-col justify-between p-7">
              <CardHeader class="p-0 pb-4">
                <div class="w-13 h-13 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center mb-4 border border-indigo-200">
                  <ShieldCheck class="w-7 h-7" />
                </div>
                <div class="flex items-center justify-between">
                  <CardTitle class="text-2xl font-bold text-pine-950">Admin Central</CardTitle>
                  <Badge class="bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-xs">
                    Superadmin
                  </Badge>
                </div>
                <CardDescription class="text-sm text-slate-600 pt-2 leading-relaxed">
                  Verifikasi dokumen KYC pendaki & mitra, kelola master data gunung se-Indonesia, dan audit transaksi platform.
                </CardDescription>
              </CardHeader>
              <CardContent class="p-0 space-y-3 text-sm text-slate-700 pb-6">
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-indigo-600 shrink-0" />
                  <span>Verifikasi Cepat Dokumen KYC Identitas</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-indigo-600 shrink-0" />
                  <span>Master Data Gunung & Jalur Nasional</span>
                </div>
                <div class="flex items-center gap-2.5">
                  <CheckCircle2 class="w-4 h-4 text-indigo-600 shrink-0" />
                  <span>Audit Transaksi Escrow & Kepatuhan SOP</span>
                </div>
              </CardContent>
              <div class="space-y-2.5">
                <Button
                  variant="outline"
                  class="w-full border-indigo-300 text-indigo-800 hover:bg-indigo-50 font-bold h-11 rounded-full cursor-pointer"
                  @click="openAuthModal('login', 'admin')"
                >
                  Login Petugas Admin
                </Button>
                <router-link to="/admin" class="block w-full">
                  <Button variant="ghost" class="w-full text-xs text-slate-600 hover:text-indigo-800 rounded-full cursor-pointer">
                    Buka Portal Admin →
                  </Button>
                </router-link>
              </div>
            </Card>
          </div>
        </div>
      </section>
    </main>

    <!-- FOOTER (Exact match with Stitch desktop design) -->
    <footer class="w-full py-stack-lg bg-pine-950 text-white mt-auto reveal-init">
      <div class="max-w-7xl mx-auto px-margin-desktop grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <!-- Col 1: SUMMIT Brand & Description -->
        <div class="col-span-1 md:col-span-2 lg:col-span-1 flex flex-col gap-4">
          <router-link to="/" class="font-display-lg text-3xl text-primary-fixed flex items-center gap-2 tracking-tighter">
            SUMMIT
          </router-link>
          <p class="font-body-md text-body-md text-surface-variant/70 mt-4 leading-relaxed">
            Platform resmi untuk cek kuota dan pemesanan simaksi pendakian gunung di seluruh Indonesia.
          </p>
        </div>

        <!-- Col 2: Legal -->
        <div class="flex flex-col gap-3 lg:ml-auto">
          <h4 class="font-label-lg text-label-lg text-white mb-2">Legal</h4>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Privacy Policy</a>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Terms of Service</a>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Park Permits</a>
        </div>

        <!-- Col 3: Panduan -->
        <div class="flex flex-col gap-3">
          <h4 class="font-label-lg text-label-lg text-white mb-2">Panduan</h4>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Safety Guide</a>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Persiapan Alat</a>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">FAQ</a>
        </div>

        <!-- Col 4: Bantuan -->
        <div class="flex flex-col gap-3">
          <h4 class="font-label-lg text-label-lg text-white mb-2">Bantuan</h4>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Contact Support</a>
          <a class="font-body-md text-body-md text-surface-variant/70 hover:text-primary-fixed transition-colors" href="#">Laporkan Masalah</a>
        </div>
      </div>

      <!-- Copyright Bottom Bar -->
      <div class="max-w-7xl mx-auto px-margin-desktop mt-12 pt-8 border-t border-white/10 flex justify-between items-center flex-wrap gap-4 text-xs sm:text-sm">
        <p class="font-body-md text-body-md text-surface-variant/50">
          © 2024 SUMMIT Expedition Services. All rights reserved.
        </p>
        <div class="flex items-center gap-4 text-surface-variant/70">
          <button class="hover:text-primary-fixed transition cursor-pointer" @click="openAuthModal('login')">Masuk Akun</button>
          <span>•</span>
          <button class="hover:text-primary-fixed transition cursor-pointer" @click="openAuthModal('register')">Daftar Akun</button>
        </div>
      </div>
    </footer>

    <!-- QUICK BOOKING CONFIRMATION DIALOG -->
    <Dialog v-model:open="isBookingModalOpen">
      <DialogContent class="sm:max-w-[480px] p-0 overflow-hidden bg-white border-slate-200 text-slate-900 rounded-3xl shadow-2xl">
        <div class="relative h-44 overflow-hidden">
          <img
            :src="currentMountain.imageUrl"
            :alt="currentMountain.name"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent"></div>
          <div class="absolute bottom-4 left-6 right-6 text-white">
            <span class="text-xs uppercase tracking-wider font-semibold text-emerald-300">Ringkasan Pemesanan</span>
            <h3 class="text-2xl font-bold font-display-lg">{{ currentMountain.name }}</h3>
            <p class="text-xs text-white/80">Jalur Resmi: Via {{ selectedTrail }} ({{ currentMountain.elevation }})</p>
          </div>
        </div>

        <div class="p-6 space-y-4">
          <div class="space-y-2.5 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Tanggal Pendakian:</span>
              <span class="font-semibold text-pine-950">{{ searchDate }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Jumlah Pendaki:</span>
              <span class="font-semibold text-pine-950">{{ climberCount }} Orang</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Status Kuota Pos:</span>
              <span class="text-emerald-700 font-semibold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Tersedia ({{ currentMountain.quotaLeft }} Slot)
              </span>
            </div>
            <div class="border-t border-slate-200 pt-2 flex justify-between items-center">
              <span class="font-semibold text-slate-800">Total Estimasi Simaksi:</span>
              <span class="font-bold text-lg text-primary">Rp {{ estimatedPrice }}</span>
            </div>
          </div>

          <div class="space-y-2 pt-2">
            <Button
              class="w-full h-12 bg-primary-container text-on-primary-container hover:bg-primary hover:text-white font-bold rounded-full transition-all duration-300 cursor-pointer shadow-sm"
              @click="router.push('/pendaki')"
            >
              <span>Lanjut ke Pemesanan Simaksi</span>
              <ArrowRight class="w-4 h-4 ml-1" />
            </Button>
            <Button
              variant="ghost"
              class="w-full text-slate-500 hover:text-slate-900 rounded-full cursor-pointer text-xs"
              @click="isBookingModalOpen = false"
            >
              Ubah Pilihan
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- AUTH MODAL DIALOG (LOGIN & REGISTER) -->
    <Dialog v-model:open="isAuthModalOpen">
      <DialogContent class="sm:max-w-[490px] p-0 overflow-hidden bg-white border-slate-200 text-slate-900 rounded-3xl shadow-2xl">
        <!-- Dialog Header Banner -->
        <DialogHeader class="bg-pine-950 p-7 relative text-left border-b border-emerald-900 text-white">
          <div class="flex items-center gap-2 mb-2">
            <Mountain class="w-5 h-5 text-primary-fixed" />
            <span class="text-xs font-bold uppercase tracking-widest text-primary-fixed">Summit Multi-Role</span>
          </div>
          <DialogTitle class="text-2xl font-bold tracking-tight text-white font-display-lg">
            {{ authMode === 'login' ? 'Masuk ke Akun Anda' : 'Daftar Akun Summit' }}
          </DialogTitle>
          <DialogDescription class="text-emerald-100 text-xs mt-1">
            {{ authMode === 'login' ? 'Pilih peran akun dan akses dashboard Anda sekarang.' : 'Bergabunglah dalam ekosistem pendaki & pengelola jalur resmi.' }}
          </DialogDescription>

          <!-- Tab Switcher: Masuk vs Daftar Baru -->
          <div class="mt-5 grid grid-cols-2 p-1 rounded-2xl bg-black/25 border border-white/10 text-xs font-bold">
            <button
              type="button"
              :class="authMode === 'login' ? 'bg-white text-slate-950 shadow-md font-bold' : 'text-white/80 hover:text-white'"
              class="py-2.5 rounded-xl transition-all cursor-pointer"
              @click="authMode = 'login'; authSuccessMessage = null; authErrorMessage = null"
            >
              Masuk (Login)
            </button>
            <button
              type="button"
              :class="authMode === 'register' ? 'bg-white text-slate-950 shadow-md font-bold' : 'text-white/80 hover:text-white'"
              class="py-2.5 rounded-xl transition-all cursor-pointer"
              @click="authMode = 'register'; authSuccessMessage = null; authErrorMessage = null"
            >
              Daftar Baru
            </button>
          </div>
        </DialogHeader>

        <div class="p-7 space-y-5">
          <!-- Role Selector -->
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
              Pilih Peran Akun
            </label>
            <div class="grid grid-cols-3 gap-2.5">
              <button
                type="button"
                :class="selectedRole === 'pendaki' ? 'border-primary bg-surface-container-low text-primary font-bold shadow-xs' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                class="flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-semibold transition cursor-pointer"
                @click="selectedRole = 'pendaki'"
              >
                <Compass class="w-4 h-4 mb-1" />
                <span>Pendaki</span>
              </button>

              <button
                type="button"
                :class="selectedRole === 'mitra' ? 'border-amber-500 bg-amber-50 text-amber-800 font-bold shadow-xs' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                class="flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-semibold transition cursor-pointer"
                @click="selectedRole = 'mitra'"
              >
                <Tent class="w-4 h-4 mb-1" />
                <span>Mitra Basecamp</span>
              </button>

              <button
                type="button"
                :class="selectedRole === 'admin' ? 'border-indigo-500 bg-indigo-50 text-indigo-800 font-bold shadow-xs' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                class="flex flex-col items-center justify-center p-3 rounded-2xl border text-xs font-semibold transition cursor-pointer"
                @click="selectedRole = 'admin'"
              >
                <ShieldCheck class="w-4 h-4 mb-1" />
                <span>Admin</span>
              </button>
            </div>
          </div>

          <!-- Alert Messages -->
          <div
            v-if="authErrorMessage"
            class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5 animate-in fade-in"
          >
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{{ authErrorMessage }}</span>
          </div>

          <div
            v-if="authSuccessMessage"
            class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 text-xs space-y-3 animate-in fade-in"
          >
            <div class="flex items-center gap-2.5">
              <CheckCircle2 class="w-5 h-5 text-primary shrink-0" />
              <span class="font-bold text-sm">{{ authSuccessMessage }}</span>
            </div>
            <Button
              size="sm"
              class="w-full bg-primary hover:bg-pine-900 text-white font-bold h-11 rounded-xl cursor-pointer shadow-md"
              @click="navigateToPortal"
            >
              Lanjut ke Portal {{ selectedRole.toUpperCase() }} →
            </Button>
          </div>

          <!-- LOGIN FORM -->
          <form v-if="authMode === 'login' && !authSuccessMessage" class="space-y-4" @submit.prevent="handleLoginSubmit">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <Mail class="w-3.5 h-3.5 text-primary" />
                <span>Alamat Email</span>
              </label>
              <Input
                v-model="loginForm.email"
                type="email"
                placeholder="nama@email.com"
                required
                class="h-11 text-sm rounded-2xl border-slate-200 focus:border-primary"
              />
            </div>

            <div class="space-y-1.5">
              <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <Lock class="w-3.5 h-3.5 text-primary" />
                  <span>Kata Sandi</span>
                </label>
                <a href="#" class="text-[11px] text-primary font-semibold hover:underline">
                  Lupa kata sandi?
                </a>
              </div>
              <div class="relative">
                <Input
                  v-model="loginForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Masukkan kata sandi"
                  required
                  class="h-11 pr-10 text-sm rounded-2xl border-slate-200 focus:border-primary"
                />
                <button
                  type="button"
                  class="absolute right-3 top-3 text-slate-400 hover:text-slate-700 cursor-pointer"
                  @click="showPassword = !showPassword"
                >
                  <Eye v-if="!showPassword" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
              <input
                id="remember"
                v-model="loginForm.rememberMe"
                type="checkbox"
                class="rounded border-slate-300 text-primary focus:ring-primary cursor-pointer"
              />
              <label for="remember" class="text-xs text-slate-700 cursor-pointer select-none">
                Ingat saya di perangkat ini
              </label>
            </div>

            <Button
              type="submit"
              class="w-full h-12 bg-primary hover:bg-pine-900 text-white font-bold text-sm rounded-2xl cursor-pointer shadow-md shadow-primary/20"
              :disabled="isSubmitting"
            >
              <span v-if="!isSubmitting">Masuk Sebagai {{ selectedRole.toUpperCase() }}</span>
              <span v-else class="flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Memproses...</span>
              </span>
            </Button>

            <!-- Quick Demo Login Buttons -->
            <div class="pt-4 border-t border-slate-100">
              <p class="text-[11px] text-center text-slate-500 mb-2.5 font-medium">Akses Cepat Demo Akun:</p>
              <div class="grid grid-cols-3 gap-2 text-xs">
                <button
                  type="button"
                  class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-surface-container-low text-slate-700 hover:text-primary font-bold cursor-pointer transition border border-slate-200"
                  @click="quickLogin('pendaki')"
                >
                  Pendaki
                </button>
                <button
                  type="button"
                  class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-amber-50 text-slate-700 hover:text-amber-800 font-bold cursor-pointer transition border border-slate-200"
                  @click="quickLogin('mitra')"
                >
                  Mitra Pos
                </button>
                <button
                  type="button"
                  class="py-2 px-2 rounded-xl bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-800 font-bold cursor-pointer transition border border-slate-200"
                  @click="quickLogin('admin')"
                >
                  Admin
                </button>
              </div>
            </div>
          </form>

          <!-- REGISTER FORM -->
          <form v-if="authMode === 'register' && !authSuccessMessage" class="space-y-3.5" @submit.prevent="handleRegisterSubmit">
            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <User class="w-3.5 h-3.5 text-primary" />
                <span>{{ selectedRole === 'mitra' ? 'Nama Pengelola / Organisasi' : 'Nama Lengkap (Sesuai KTP)' }}</span>
              </label>
              <Input
                v-model="registerForm.name"
                type="text"
                :placeholder="selectedRole === 'mitra' ? 'Basecamp Selo Merbabu' : 'Budi Santoso'"
                required
                class="h-10 text-sm rounded-xl border-slate-200 focus:border-primary"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <Mail class="w-3.5 h-3.5 text-primary" />
                <span>Alamat Email</span>
              </label>
              <Input
                v-model="registerForm.email"
                type="email"
                placeholder="nama@email.com"
                required
                class="h-10 text-sm rounded-xl border-slate-200 focus:border-primary"
              />
            </div>

            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <Phone class="w-3.5 h-3.5 text-primary" />
                <span>Nomor WhatsApp / HP</span>
              </label>
              <Input
                v-model="registerForm.phone"
                type="tel"
                placeholder="08123456789"
                required
                class="h-10 text-sm rounded-xl border-slate-200 focus:border-primary"
              />
            </div>

            <div class="grid grid-cols-2 gap-2.5">
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <Lock class="w-3.5 h-3.5 text-primary" />
                  <span>Kata Sandi</span>
                </label>
                <Input
                  v-model="registerForm.password"
                  type="password"
                  placeholder="Min 8 karakter"
                  required
                  class="h-10 text-sm rounded-xl border-slate-200 focus:border-primary"
                />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <Lock class="w-3.5 h-3.5 text-primary" />
                  <span>Ulangi Sandi</span>
                </label>
                <Input
                  v-model="registerForm.passwordConfirmation"
                  type="password"
                  placeholder="Ulangi sandi"
                  required
                  class="h-10 text-sm rounded-xl border-slate-200 focus:border-primary"
                />
              </div>
            </div>

            <div class="flex items-start gap-2 pt-1">
              <input
                id="terms"
                v-model="registerForm.agreeTerms"
                type="checkbox"
                class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary cursor-pointer"
              />
              <label for="terms" class="text-[11px] text-slate-600 cursor-pointer leading-tight select-none">
                Saya menyetujui <a href="#" class="text-primary underline font-semibold">Ketentuan Layanan</a>, SOP Pendakian, dan Kebijakan Privasi Summit.
              </label>
            </div>

            <Button
              type="submit"
              class="w-full h-12 bg-primary hover:bg-pine-900 text-white font-bold text-sm rounded-2xl cursor-pointer shadow-md shadow-primary/20"
              :disabled="isSubmitting"
            >
              <span v-if="!isSubmitting">Daftar Akun {{ selectedRole.toUpperCase() }}</span>
              <span v-else class="flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Mendaftarkan...</span>
              </span>
            </Button>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
