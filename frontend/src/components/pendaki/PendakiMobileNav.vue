<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  Compass,
  Mountain,
  ShoppingCart,
  FileText,
  User,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    cartItemCount?: number
  }>(),
  {
    cartItemCount: 0,
  }
)

const route = useRoute()

const navItems = computed(() => [
  {
    name: 'Beranda',
    path: '/pendaki',
    icon: Mountain,
    isActive: route.path === '/pendaki' && !route.query.tab,
  },
  {
    name: 'Jelajah',
    path: '/pendaki',
    icon: Compass,
    isActive: route.path === '/pendaki' && !!route.query.q,
  },
  {
    name: 'Keranjang',
    path: '/pendaki/cart',
    icon: ShoppingCart,
    badge: props.cartItemCount,
    isActive: route.path === '/pendaki/cart',
  },
  {
    name: 'Pesanan',
    path: '/pendaki/orders',
    icon: FileText,
    isActive: route.path === '/pendaki/orders',
  },
  {
    name: 'Akun',
    path: '/pendaki/profile',
    icon: User,
    isActive: route.path.startsWith('/pendaki/profile') || route.path.startsWith('/pendaki/kyc'),
  },
])
</script>

<template>
  <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-slate-950/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 md:hidden pb-safe shadow-lg">
    <div class="grid grid-cols-5 h-16 max-w-lg mx-auto">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        class="flex flex-col items-center justify-center gap-1 transition-all relative group"
        :class="[
          item.isActive
            ? 'text-emerald-700 dark:text-emerald-400 font-bold'
            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-medium',
        ]"
      >
        <div class="relative">
          <component :is="item.icon" class="w-5 h-5 transition-transform group-hover:scale-110" />
          <span
            v-if="item.badge && item.badge > 0"
            class="absolute -top-1.5 -right-2.5 w-4 h-4 rounded-full bg-orange-600 text-white text-[9px] font-bold flex items-center justify-center shadow-xs"
          >
            {{ item.badge > 9 ? '9+' : item.badge }}
          </span>
        </div>
        <span class="text-[10px] tracking-tight leading-none">{{ item.name }}</span>
        <!-- Active indicator dot -->
        <span
          v-if="item.isActive"
          class="absolute bottom-1 w-1 h-1 rounded-full bg-emerald-700 dark:bg-emerald-400"
        ></span>
      </router-link>
    </div>
  </nav>
</template>
