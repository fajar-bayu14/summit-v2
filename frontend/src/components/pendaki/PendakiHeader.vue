<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Mountain,
  Search,
  ShoppingCart,
  User as UserIcon,
  LogOut,
  FileText,
  Settings,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import KycSecurityShield from './KycSecurityShield.vue'
import { useAuthStore } from '@/stores/auth'

const props = withDefaults(
  defineProps<{
    cartItemCount?: number
  }>(),
  {
    cartItemCount: 0,
  }
)

const emit = defineEmits<{
  (e: 'search', query: string): void
  (e: 'open-mobile-menu'): void
}>()

const router = useRouter()
const authStore = useAuthStore()

const searchQuery = ref('')
const isUserDropdownOpen = ref(false)

function handleSearchSubmit() {
  if (searchQuery.value.trim()) {
    emit('search', searchQuery.value.trim())
    router.push({ path: '/pendaki', query: { q: searchQuery.value.trim() } })
  }
}

async function handleLogout() {
  isUserDropdownOpen.value = false
  await authStore.logout()
  router.push('/')
}
</script>

<template>
  <header class="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/95 dark:border-slate-800 dark:bg-slate-950/95 backdrop-blur-md transition-all shadow-xs">
    <div class="max-w-7xl mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8 gap-4">
      <!-- Left: Brand Logo & Title -->
      <div class="flex items-center gap-6 shrink-0">
        <router-link to="/pendaki" class="flex items-center gap-2.5 group">
          <div class="w-9 h-9 rounded-xl bg-emerald-700 text-white flex items-center justify-center shadow-sm group-hover:bg-emerald-800 transition-colors">
            <Mountain class="h-5 w-5" />
          </div>
          <div class="flex flex-col">
            <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-slate-100 font-display">SUMMIT</span>
            <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest -mt-1">MARKETPLACE</span>
          </div>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden lg:flex items-center gap-5 text-sm font-medium text-slate-600 dark:text-slate-300">
          <router-link to="/pendaki" class="hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
            Jelajah Gunung
          </router-link>
          <router-link to="/pendaki" class="hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
            Sewa Alat
          </router-link>
          <router-link to="/pendaki" class="hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors">
            Porter &amp; Guide
          </router-link>
          <router-link to="/" class="hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors text-slate-400">
            Portal Utama
          </router-link>
        </nav>
      </div>

      <!-- Center: Global Search Bar -->
      <div class="flex-1 max-w-md hidden md:block">
        <form @submit.prevent="handleSearchSubmit" class="relative">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <Input
            v-model="searchQuery"
            placeholder="Cari gunung, jalur (Merbabu, Rinjani, Selo...)"
            class="w-full pl-9 pr-4 h-9 rounded-full border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-xs sm:text-sm focus-visible:ring-emerald-600"
          />
        </form>
      </div>

      <!-- Right: Cart & User Account Actions -->
      <div class="flex items-center gap-3">
        <!-- Shopping Cart Icon Button -->
        <router-link
          to="/pendaki/cart"
          class="relative p-2 rounded-xl text-slate-700 hover:text-emerald-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 transition-colors"
          title="Keranjang Belanja"
        >
          <ShoppingCart class="w-5 h-5" />
          <span
            v-if="cartItemCount > 0"
            class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-orange-600 text-white text-[11px] font-bold flex items-center justify-center shadow-sm"
          >
            {{ cartItemCount > 9 ? '9+' : cartItemCount }}
          </span>
        </router-link>

        <!-- Authenticated User Menu -->
        <div v-if="authStore.isAuthenticated" class="relative">
          <button
            type="button"
            class="flex items-center gap-2 p-1.5 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer border border-transparent hover:border-slate-200 dark:hover:border-slate-700"
            @click="isUserDropdownOpen = !isUserDropdownOpen"
          >
            <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 font-bold text-xs flex items-center justify-center border border-emerald-300 dark:border-emerald-700">
              {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'P' }}
            </div>
            <span class="hidden sm:inline-block text-xs font-semibold text-slate-800 dark:text-slate-200 max-w-[100px] truncate">
              {{ authStore.user?.name || 'Pendaki' }}
            </span>
          </button>

          <!-- Dropdown Menu -->
          <div
            v-if="isUserDropdownOpen"
            class="absolute right-0 mt-2 w-64 rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50 text-xs sm:text-sm animate-in fade-in-50 zoom-in-95"
            @mouseleave="isUserDropdownOpen = false"
          >
            <!-- User Info Header -->
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
              <p class="font-bold text-slate-900 dark:text-slate-100 truncate">{{ authStore.user?.name }}</p>
              <p class="text-xs text-slate-500 truncate mb-2">{{ authStore.user?.email }}</p>
              <!-- KYC Shield Badge -->
              <KycSecurityShield :status="authStore.kycStatus" compact />
            </div>

            <!-- Menu Links -->
            <div class="py-1">
              <router-link
                to="/pendaki/profile"
                class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors"
                @click="isUserDropdownOpen = false"
              >
                <UserIcon class="w-4 h-4 text-slate-400" />
                <span>Profil &amp; Identitas KYC</span>
              </router-link>

              <router-link
                to="/pendaki/orders"
                class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors"
                @click="isUserDropdownOpen = false"
              >
                <FileText class="w-4 h-4 text-slate-400" />
                <span>Pesanan Saya (E-Tiket)</span>
              </router-link>

              <router-link
                to="/pendaki/profile"
                class="flex items-center gap-2.5 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors"
                @click="isUserDropdownOpen = false"
              >
                <Settings class="w-4 h-4 text-slate-400" />
                <span>Pengaturan Akun</span>
              </router-link>
            </div>

            <!-- Logout Button -->
            <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                class="w-full flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors text-left"
                @click="handleLogout"
              >
                <LogOut class="w-4 h-4" />
                <span>Keluar</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Guest Actions -->
        <div v-else class="flex items-center gap-2">
          <router-link to="/login">
            <Button variant="ghost" size="sm" class="rounded-full text-xs font-semibold">
              Masuk
            </Button>
          </router-link>
          <router-link to="/login">
            <Button size="sm" class="rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs">
              Daftar
            </Button>
          </router-link>
        </div>
      </div>
    </div>
  </header>
</template>
