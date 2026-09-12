<script setup lang="ts">
import { computed, onMounted } from 'vue'
import PendakiHeader from '@/components/pendaki/PendakiHeader.vue'
import PendakiMobileNav from '@/components/pendaki/PendakiMobileNav.vue'
import { Mountain, ShieldCheck, HeartHandshake, PhoneCall } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()

const cartCount = computed(() => cartStore.totalItems)

onMounted(async () => {
  if (authStore.isAuthenticated && !cartStore.cart) {
    await cartStore.fetchCart()
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col font-sans selection:bg-emerald-100 selection:text-emerald-900">
    <!-- E-Commerce Sticky Header -->
    <PendakiHeader :cart-item-count="cartCount" class="print:hidden" />

    <!-- Main Storefront & Portal Viewport -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-20 md:pb-8 print:p-0 print:m-0 print:max-w-none print:w-full">
      <router-view />
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <PendakiMobileNav :cart-item-count="cartCount" class="print:hidden" />

    <!-- E-Commerce Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 py-10 text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-auto hidden md:block print:hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          <!-- Col 1: Brand Info -->
          <div class="space-y-3">
            <div class="flex items-center gap-2 text-slate-900 dark:text-slate-100 font-bold text-base">
              <Mountain class="w-5 h-5 text-emerald-700" />
              <span>SUMMIT MARKETPLACE</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              Platform terpadu pemesanan tiket SIMAKSI gunung, sewa peralatan outdoor, dan jasa porter/guide berlisensi di Indonesia.
            </p>
          </div>

          <!-- Col 2: Navigasi Pendaki -->
          <div class="space-y-2">
            <p class="font-bold text-slate-900 dark:text-slate-100 text-xs uppercase tracking-wider">Layanan Pendaki</p>
            <ul class="space-y-1.5 text-xs">
              <li><router-link to="/pendaki" class="hover:text-emerald-700 transition-colors">Booking Tiket SIMAKSI</router-link></li>
              <li><router-link to="/pendaki" class="hover:text-emerald-700 transition-colors">Rental Peralatan Camping</router-link></li>
              <li><router-link to="/pendaki" class="hover:text-emerald-700 transition-colors">Jasa Porter &amp; Guide APGI</router-link></li>
              <li><router-link to="/pendaki" class="hover:text-emerald-700 transition-colors">Digital Logbook &amp; E-Sertifikat</router-link></li>
            </ul>
          </div>

          <!-- Col 3: Keamanan & Escrow -->
          <div class="space-y-2">
            <p class="font-bold text-slate-900 dark:text-slate-100 text-xs uppercase tracking-wider">Keamanan Transaksi</p>
            <div class="space-y-2 text-xs text-slate-500">
              <div class="flex items-center gap-2">
                <ShieldCheck class="w-4 h-4 text-emerald-700 shrink-0" />
                <span>Dana aman via Escrow Xendit</span>
              </div>
              <div class="flex items-center gap-2">
                <HeartHandshake class="w-4 h-4 text-emerald-700 shrink-0" />
                <span>Garansi Refund SOP H-3 / Jalur Tutup</span>
              </div>
            </div>
          </div>

          <!-- Col 4: Bantuan & Kontak -->
          <div class="space-y-2">
            <p class="font-bold text-slate-900 dark:text-slate-100 text-xs uppercase tracking-wider">Pusat Bantuan</p>
            <p class="text-xs text-slate-500">
              Butuh bantuan atau koordinasi darurat jalur?
            </p>
            <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-semibold text-xs">
              <PhoneCall class="w-4 h-4" />
              <span>Call Center: 0812-9999-SUMMIT</span>
            </div>
          </div>
        </div>

        <!-- Copyright -->
        <div class="pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
          <p>&copy; {{ new Date().getFullYear() }} Summit Marketplace. Hak Cipta Dilindungi.</p>
          <div class="flex items-center gap-4">
            <router-link to="/" class="hover:underline">Syarat &amp; Ketentuan</router-link>
            <router-link to="/" class="hover:underline">Kebijakan Privasi</router-link>
            <router-link to="/mitra" class="hover:underline">Portal Mitra</router-link>
            <router-link to="/admin" class="hover:underline">Portal Admin</router-link>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
