<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Bell, RefreshCw, CheckCircle2, ArrowRight } from 'lucide-vue-next'
import { useNotificationCenter } from '@/composables/useNotificationCenter'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const { notifications, totalCount, isLoading, fetchNotifications } = useNotificationCenter()

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchNotifications()
  }
})

function handleNavigate(link: string) {
  if (link) {
    router.push(link)
  }
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        class="relative rounded-full h-9 w-9 hover:bg-muted/70 focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 transition-colors"
        :aria-label="totalCount > 0 ? `Notifikasi, ${totalCount} tugas menunggu tindakan` : 'Pusat Notifikasi'"
        :title="totalCount > 0 ? `${totalCount} antrean perlu diproses` : 'Pusat Notifikasi'"
      >
        <Bell class="h-4 w-4 text-muted-foreground transition-transform hover:rotate-6" />

        <!-- Red pulsing badge only when there are actionable pending items -->
        <span
          v-if="totalCount > 0"
          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-background animate-pulse select-none shadow-sm"
          role="status"
          aria-atomic="true"
        >
          {{ totalCount > 99 ? '99+' : totalCount }}
        </span>
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent
      align="end"
      class="w-80 sm:w-96 p-0 overflow-hidden shadow-xl border border-border/80 bg-popover rounded-xl z-50 animate-in fade-in-50 zoom-in-95 duration-100"
    >
      <!-- Header -->
      <div class="px-4 py-3 flex items-center justify-between bg-muted/30 border-b border-border/60">
        <div class="flex items-center gap-2">
          <h4 class="text-xs font-bold text-foreground uppercase tracking-wider">Notifikasi</h4>
          <Badge
            v-if="totalCount > 0"
            variant="destructive"
            class="h-5 px-1.5 text-[10px] font-semibold rounded-full"
          >
            {{ totalCount }} antrean
          </Badge>
          <Badge
            v-else
            variant="secondary"
            class="h-5 px-1.5 text-[10px] font-medium rounded-full text-muted-foreground"
          >
            0
          </Badge>
        </div>

        <Button
          variant="ghost"
          size="icon"
          class="h-7 w-7 text-muted-foreground hover:text-foreground rounded-md"
          :disabled="isLoading"
          title="Perbarui notifikasi"
          aria-label="Perbarui notifikasi"
          @click.stop="fetchNotifications"
        >
          <RefreshCw
            class="h-3.5 w-3.5 transition-transform"
            :class="{ 'animate-spin text-primary': isLoading }"
          />
        </Button>
      </div>

      <!-- Notifications List -->
      <div v-if="notifications.length > 0" class="max-h-[360px] overflow-y-auto divide-y divide-border/40">
        <DropdownMenuItem
          v-for="item in notifications"
          :key="item.id"
          class="flex items-start gap-3 p-3.5 cursor-pointer rounded-none focus:bg-accent/60 transition-colors group"
          @click="handleNavigate(item.link)"
        >
          <div
            class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
            :class="item.iconBg"
          >
            <component
              :is="item.icon"
              class="h-4 w-4"
              :class="item.iconColor"
            />
          </div>

          <div class="flex-1 min-w-0 pr-1">
            <div class="flex items-center justify-between gap-1">
              <span class="text-xs font-semibold text-foreground group-hover:text-primary transition-colors truncate">
                {{ item.title }}
              </span>
              <span
                class="shrink-0 text-[10px] font-bold px-1.5 py-0.2 rounded-full"
                :class="item.type === 'critical' ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400' : 'bg-amber-500/15 text-amber-700 dark:text-amber-400'"
              >
                {{ item.count }}
              </span>
            </div>
            <p class="text-[11px] text-muted-foreground line-clamp-2 mt-0.5 leading-snug">
              {{ item.description }}
            </p>
          </div>

          <ArrowRight class="h-3.5 w-3.5 text-muted-foreground/50 shrink-0 self-center group-hover:text-primary group-hover:translate-x-0.5 transition-all" />
        </DropdownMenuItem>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="py-8 px-4 flex flex-col items-center justify-center text-center space-y-2 bg-background/50"
      >
        <div class="h-10 w-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-1">
          <CheckCircle2 class="h-5 w-5" />
        </div>
        <p class="text-xs font-semibold text-foreground">Semua Antrean Bersih</p>
        <p class="text-[11px] text-muted-foreground max-w-[220px]">
          Tidak ada antrean tertunda atau tindakan darurat yang memerlukan perhatian saat ini.
        </p>
      </div>

      <DropdownMenuSeparator class="my-0" />

      <!-- Footer Info -->
      <div class="px-4 py-2 bg-muted/20 flex items-center justify-between text-[10px] text-muted-foreground">
        <span>Peran: <strong class="text-foreground capitalize">{{ authStore.userRole || 'Pengguna' }}</strong></span>
        <span>Auto-sync aktif</span>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
