<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMitraStore } from '@/stores/mitra'
import { navigationConfig } from '@/config/navigation'
import BasecampSwitcher from '@/components/mitra/BasecampSwitcher.vue'
import type { NavGroup, NavItem } from '@/types/navigation'
import {
  Mountain,
  Menu,
  X,
  Search,
  Bell,
  ChevronDown,
  LogOut,
  User as UserIcon,
  Shield,
  ChevronRight,
  PanelLeftClose,
  PanelLeft,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const authStore = useAuthStore()
const mitraStore = useMitraStore()
const route = useRoute()
const router = useRouter()

// State sidebar mobile & desktop
const isMobileMenuOpen = ref(false)
const isSidebarCollapsed = ref(false)
const searchQuery = ref('')

// Filter navigasi secara reaktif berdasarkan role user saat ini
const filteredNavigation = computed<NavGroup[]>(() => {
  const currentRole = authStore.userRole
  if (!currentRole) return []

  return navigationConfig
    .filter(group => !group.roles || group.roles.includes(currentRole))
    .map(group => ({
      ...group,
      items: group.items
        .filter(item => !item.roles || item.roles.includes(currentRole))
        .map(item => {
          if (item.to === '/mitra/orders' && mitraStore.incomingOrdersCount > 0) {
            return {
              ...item,
              badge: String(mitraStore.incomingOrdersCount),
              badgeVariant: 'destructive' as const,
            }
          }
          return item
        }),
    }))
    .filter(group => group.items.length > 0)
})

onMounted(() => {
  if (authStore.isMitra) {
    mitraStore.fetchIncomingOrdersCount()
  }
})

watch(
  () => mitraStore.activeBasecampId,
  (newVal, oldVal) => {
    if (authStore.isMitra && newVal !== oldVal) {
      mitraStore.fetchIncomingOrdersCount()
    }
  }
)

watch(
  () => route.path,
  (path) => {
    if (authStore.isMitra && (path === '/mitra/orders' || path === '/mitra')) {
      mitraStore.fetchIncomingOrdersCount()
    }
  }
)

// Role badge visual styling
const roleBadgeInfo = computed(() => {
  if (authStore.isAdmin) {
    return {
      label: 'Admin Central',
      class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    }
  }
  if (authStore.isMitra) {
    return {
      label: 'Mitra Basecamp',
      class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    }
  }
  return {
    label: 'Portal User',
    class: 'bg-slate-500/10 text-slate-600 border-slate-500/20',
  }
})

// Active route checking helper
function isItemActive(item: NavItem): boolean {
  if (typeof item.to === 'string') {
    return (
      route.path === item.to ||
      (item.to !== '/admin' && item.to !== '/mitra' && route.path.startsWith(item.to))
    )
  }
  return route.name === item.to.name
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50/50 dark:bg-slate-950 flex">
    <!-- Desktop Sidebar (Persistent Left Frame) -->
    <aside
      class="hidden md:flex flex-col border-r bg-card transition-all duration-300 z-30 sticky top-0 h-screen shrink-0 select-none"
      :class="isSidebarCollapsed ? 'w-18' : 'w-64'"
    >
      <!-- Brand & Collapse Toggle -->
      <div class="h-16 flex items-center justify-between px-4 border-b">
        <router-link
          to="/"
          class="flex items-center gap-2.5 font-bold tracking-tight text-foreground overflow-hidden"
        >
          <div class="h-9 w-9 rounded-lg bg-emerald-700 text-white flex items-center justify-center shrink-0 shadow-xs">
            <Mountain class="h-5 w-5" />
          </div>
          <div v-if="!isSidebarCollapsed" class="flex flex-col">
            <span class="text-base font-extrabold tracking-wider leading-none">SUMMIT</span>
            <span class="text-[10px] text-muted-foreground font-medium uppercase tracking-widest mt-0.5">App Panel</span>
          </div>
        </router-link>

        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8 text-muted-foreground hover:text-foreground hidden lg:flex"
          :title="isSidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
          @click="isSidebarCollapsed = !isSidebarCollapsed"
        >
          <PanelLeftClose v-if="!isSidebarCollapsed" class="h-4 w-4" />
          <PanelLeft v-else class="h-4 w-4" />
        </Button>
      </div>

      <!-- Active Role Badge Indicator -->
      <div
        v-if="!isSidebarCollapsed"
        class="px-4 py-2.5 border-b bg-muted/20 flex items-center justify-between"
      >
        <div class="flex items-center gap-2">
          <Shield class="h-3.5 w-3.5 text-muted-foreground" />
          <span class="text-xs font-medium text-muted-foreground">Peran:</span>
        </div>
        <Badge variant="outline" :class="roleBadgeInfo.class" class="text-[11px] font-semibold">
          {{ roleBadgeInfo.label }}
        </Badge>
      </div>

      <!-- Dynamic Role-Filtered Navigation Links -->
      <div class="flex-1 overflow-y-auto py-3 px-3 space-y-4">
        <div v-for="(group, gIdx) in filteredNavigation" :key="gIdx" class="space-y-0.5">
          <div
            v-if="group.heading && !isSidebarCollapsed"
            class="px-3 pt-2.5 pb-1 flex items-center"
          >
            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70 dark:text-muted-foreground/60 select-none">
              {{ group.heading }}
            </span>
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group.items"
              :key="item.title"
              :to="item.to"
              class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors relative group"
              :class="[
                isItemActive(item)
                  ? 'bg-primary text-primary-foreground font-semibold shadow-xs'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
              ]"
              :title="isSidebarCollapsed ? item.title : undefined"
            >
              <component :is="item.icon" class="h-4 w-4 shrink-0" />
              <span v-if="!isSidebarCollapsed" class="truncate flex-1">{{ item.title }}</span>
              <Badge
                v-if="item.badge && !isSidebarCollapsed"
                :variant="item.badgeVariant || 'secondary'"
                class="text-[10px] px-1.5 py-0 ml-auto font-semibold"
                role="status"
                :aria-label="`${item.badge} pesanan masuk`"
              >
                {{ item.badge }}
              </Badge>
              <span
                v-if="item.badge && isSidebarCollapsed"
                class="absolute top-2 right-2 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-card animate-pulse"
                :title="`${item.badge} pesanan masuk`"
              />
            </router-link>
          </div>
        </div>
      </div>

      <!-- Sidebar Footer / User Profile & Logout -->
      <div class="p-3 border-t bg-card">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              class="w-full flex items-center justify-start gap-2.5 p-2 h-auto hover:bg-accent rounded-lg"
            >
              <Avatar class="h-8 w-8 rounded-lg shrink-0">
                <AvatarImage src="" />
                <AvatarFallback class="bg-primary/10 text-primary text-xs font-semibold">
                  {{ authStore.user?.name?.slice(0, 2).toUpperCase() || 'AD' }}
                </AvatarFallback>
              </Avatar>
              <div v-if="!isSidebarCollapsed" class="grid flex-1 text-left text-xs leading-tight">
                <span class="truncate font-semibold text-foreground">{{ authStore.user?.name || 'Administrator' }}</span>
                <span class="truncate text-muted-foreground text-[11px]">{{ authStore.user?.email || 'admin@summit.id' }}</span>
              </div>
              <ChevronDown v-if="!isSidebarCollapsed" class="ml-auto h-4 w-4 text-muted-foreground" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent class="w-56" align="end" side="right" :side-offset="8">
            <DropdownMenuLabel>Akun Pengguna</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="router.push('/settings')">
              <UserIcon class="mr-2 h-4 w-4" /> Profil & Pengaturan
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="handleLogout" class="text-destructive focus:text-destructive">
              <LogOut class="mr-2 h-4 w-4" /> Keluar Sistem
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </aside>

    <!-- Mobile Navigation Drawer (Backdrop + Slide Panel) -->
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-50 bg-black/60 md:hidden backdrop-blur-xs transition-opacity"
      @click="isMobileMenuOpen = false"
    />
    <div
      class="fixed inset-y-0 left-0 z-50 w-72 bg-card border-r flex flex-col transform transition-transform duration-300 md:hidden"
      :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="h-16 flex items-center justify-between px-4 border-b">
        <div class="flex items-center gap-2.5 font-bold tracking-tight">
          <div class="h-8 w-8 rounded-lg bg-emerald-700 text-white flex items-center justify-center">
            <Mountain class="h-4 w-4" />
          </div>
          <span class="text-base font-extrabold tracking-wider">SUMMIT</span>
        </div>
        <Button variant="ghost" size="icon" @click="isMobileMenuOpen = false">
          <X class="h-5 w-5" />
        </Button>
      </div>

      <div class="px-4 py-2.5 border-b bg-muted/20 flex items-center justify-between">
        <span class="text-xs font-medium text-muted-foreground">Peran:</span>
        <Badge variant="outline" :class="roleBadgeInfo.class" class="text-[11px] font-semibold">
          {{ roleBadgeInfo.label }}
        </Badge>
      </div>

      <!-- Mobile Basecamp Switcher for Mitra -->
      <div v-if="authStore.isMitra" class="px-3 py-2 border-b bg-card">
        <BasecampSwitcher />
      </div>

      <!-- Mobile Navigation Links -->
      <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <div v-for="(group, gIdx) in filteredNavigation" :key="gIdx" class="space-y-0.5">
          <div
            v-if="group.heading"
            class="px-3 pt-2.5 pb-1 flex items-center"
          >
            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70 dark:text-muted-foreground/60 select-none">
              {{ group.heading }}
            </span>
          </div>
          <div class="space-y-0.5">
            <router-link
              v-for="item in group.items"
              :key="item.title"
              :to="item.to"
              @click="isMobileMenuOpen = false"
              class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors"
              :class="[
                isItemActive(item)
                  ? 'bg-primary text-primary-foreground font-semibold'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
              ]"
            >
              <component :is="item.icon" class="h-4 w-4" />
              <span class="flex-1">{{ item.title }}</span>
              <Badge
                v-if="item.badge"
                :variant="item.badgeVariant || 'secondary'"
                class="text-[10px] px-1.5 py-0 font-semibold"
                role="status"
                :aria-label="`${item.badge} pesanan masuk`"
              >
                {{ item.badge }}
              </Badge>
            </router-link>
          </div>
        </div>
      </div>

      <div class="p-4 border-t">
        <Button
          variant="outline"
          class="w-full justify-start text-destructive gap-2"
          @click="handleLogout"
        >
          <LogOut class="h-4 w-4" /> Keluar
        </Button>
      </div>
    </div>

    <!-- Main Content Area (Header + Page Slot) -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Navbar / Header Frame -->
      <header
        class="sticky top-0 z-20 h-16 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 flex items-center justify-between px-4 sm:px-6 lg:px-8"
      >
        <div class="flex items-center gap-3">
          <Button
            variant="ghost"
            size="icon"
            class="md:hidden"
            @click="isMobileMenuOpen = true"
          >
            <Menu class="h-5 w-5" />
          </Button>

          <!-- Breadcrumbs / Page context -->
          <div class="hidden sm:flex items-center gap-2 text-sm text-muted-foreground">
            <router-link to="/" class="hover:text-foreground font-medium">Summit</router-link>
            <ChevronRight class="h-3.5 w-3.5" />
            <span class="text-foreground font-semibold capitalize">
              {{ route.meta.title?.toString().replace(' - Summit', '') || 'Dashboard' }}
            </span>
          </div>

          <!-- Basecamp Context Switcher for Mitra -->
          <div v-if="authStore.isMitra" class="hidden md:flex items-center ml-2">
            <BasecampSwitcher />
          </div>
        </div>

        <!-- Global Search & Header Actions -->
        <div class="flex items-center gap-3">
          <div class="relative w-48 lg:w-72 hidden md:block">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              type="search"
              placeholder="Cari data, tiket, jalur..."
              class="pl-9 h-9 text-xs rounded-lg bg-muted/40 border-muted"
            />
          </div>

          <!-- Notification Trigger / Link to orders for Mitra -->
          <Button
            variant="ghost"
            size="icon"
            class="relative rounded-full h-9 w-9"
            :title="authStore.isMitra && mitraStore.incomingOrdersCount > 0 ? `${mitraStore.incomingOrdersCount} pesanan masuk perlu diproses` : 'Notifikasi'"
            @click="authStore.isMitra ? router.push('/mitra/orders') : null"
          >
            <Bell class="h-4 w-4 text-muted-foreground" />
            <span
              v-if="authStore.isMitra && mitraStore.incomingOrdersCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-background animate-pulse"
              role="status"
              :aria-label="`${mitraStore.incomingOrdersCount} pesanan masuk`"
            >
              {{ mitraStore.incomingOrdersCount > 99 ? '99+' : mitraStore.incomingOrdersCount }}
            </span>
            <span
              v-else
              class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-emerald-600 animate-pulse"
            />
          </Button>

          <!-- User Dropdown in Header -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" class="relative h-9 flex items-center gap-2 px-2 rounded-lg">
                <Avatar class="h-7 w-7 rounded-full">
                  <AvatarFallback class="bg-primary/10 text-primary text-xs font-semibold">
                    {{ authStore.user?.name?.slice(0, 2).toUpperCase() || 'AD' }}
                  </AvatarFallback>
                </Avatar>
                <span class="text-xs font-medium hidden sm:inline-block">{{ authStore.user?.name || 'Admin' }}</span>
                <ChevronDown class="h-3.5 w-3.5 text-muted-foreground hidden sm:inline-block" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel>
                <div class="flex flex-col">
                  <span class="font-medium text-sm">{{ authStore.user?.name || 'Administrator' }}</span>
                  <span class="text-xs text-muted-foreground">{{ authStore.user?.email || 'admin@summit.id' }}</span>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="router.push('/settings')">
                <UserIcon class="mr-2 h-4 w-4" /> Pengaturan Profil
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleLogout" class="text-destructive focus:text-destructive">
                <LogOut class="mr-2 h-4 w-4" /> Keluar
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <!-- Dynamic Page View Slot -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        <!--
          Dukungan ganda:
          1. Menggunakan <slot /> jika komponen ini diimpor langsung dalam view
          2. Fallback otomatis ke <router-view /> untuk konfigurasi nested route
        -->
        <slot>
          <router-view />
        </slot>
      </main>
    </div>
  </div>
</template>
