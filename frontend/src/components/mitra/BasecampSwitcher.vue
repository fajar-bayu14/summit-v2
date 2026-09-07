<script setup lang="ts">
import { computed } from 'vue'
import { useMitraStore } from '@/stores/mitra'
import {
  Building2,
  ChevronDown,
  Check,
  Mountain,
  MapPin,
  Clock,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const mitraStore = useMitraStore()

const activeBasecamp = computed(() => mitraStore.activeBasecamp)
const basecamps = computed(() => mitraStore.basecamps)
const hasMultiple = computed(() => mitraStore.hasMultipleBasecamps)

function handleSelectBasecamp(id: number) {
  mitraStore.setActiveBasecamp(id)
}
</script>

<template>
  <div class="flex items-center">
    <!-- Multiple Basecamps Dropdown Selector -->
    <DropdownMenu v-if="hasMultiple && activeBasecamp">
      <DropdownMenuTrigger as-child>
        <Button
          variant="outline"
          size="sm"
          class="h-9 px-3 gap-2 text-xs font-semibold bg-background hover:bg-muted/50 border-border/80 shadow-xs max-w-[240px] sm:max-w-[280px]"
          aria-label="Pilih Basecamp Operasional"
        >
          <Building2 class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
          <div class="flex items-center gap-1.5 truncate text-left">
            <span class="truncate font-semibold text-foreground">
              {{ activeBasecamp.nama_basecamp }}
            </span>
            <span
              v-if="activeBasecamp.jalur?.gunung"
              class="text-[10px] text-muted-foreground font-normal shrink-0"
            >
              ({{ activeBasecamp.jalur.gunung.nama_gunung.replace('Gunung ', 'Gn. ') }})
            </span>
          </div>
          <ChevronDown class="h-3.5 w-3.5 text-muted-foreground ml-auto shrink-0" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="start" class="w-72 p-1.5 shadow-md">
        <DropdownMenuLabel class="px-2.5 py-1.5 text-xs text-muted-foreground flex items-center justify-between">
          <span>Ganti Basecamp Aktif</span>
          <Badge variant="secondary" class="text-[10px] font-medium h-4 px-1">
            {{ basecamps.length }} Lokasi
          </Badge>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />

        <DropdownMenuItem
          v-for="b in basecamps"
          :key="b.id"
          class="flex items-start gap-2.5 p-2 rounded-md cursor-pointer transition-colors"
          :class="b.id === activeBasecamp.id ? 'bg-primary/10 text-primary font-semibold' : 'text-foreground hover:bg-muted'"
          @click="handleSelectBasecamp(b.id)"
        >
          <div class="mt-0.5 shrink-0">
            <Check v-if="b.id === activeBasecamp.id" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
            <Building2 v-else class="h-4 w-4 text-muted-foreground" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold truncate">{{ b.nama_basecamp }}</div>
            <div class="flex items-center gap-1 text-[11px] text-muted-foreground mt-0.5">
              <Mountain class="h-3 w-3 shrink-0" />
              <span class="truncate">{{ b.jalur?.gunung?.nama_gunung || 'Gunung' }} · {{ b.jalur?.nama_jalur || 'Jalur' }}</span>
            </div>
            <div v-if="b.jam_operasional" class="flex items-center gap-1 text-[10px] text-muted-foreground/80 mt-0.5">
              <Clock class="h-2.5 w-2.5 shrink-0" />
              <span>{{ b.jam_operasional }}</span>
            </div>
          </div>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>

    <!-- Single Basecamp Static Pill -->
    <div
      v-else-if="activeBasecamp"
      class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-medium"
      title="Basecamp Operasional Terhubung"
    >
      <Building2 class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
      <span class="font-semibold truncate max-w-[200px]">{{ activeBasecamp.nama_basecamp }}</span>
      <span v-if="activeBasecamp.jalur?.gunung" class="text-[10px] text-emerald-700/80 dark:text-emerald-300/80 font-normal">
        · {{ activeBasecamp.jalur.gunung.nama_gunung.replace('Gunung ', 'Gn. ') }}
      </span>
    </div>

    <!-- Fallback if no basecamp loaded yet -->
    <div
      v-else
      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-muted text-muted-foreground text-xs"
    >
      <MapPin class="h-3.5 w-3.5" />
      <span>Semua Basecamp</span>
    </div>
  </div>
</template>
