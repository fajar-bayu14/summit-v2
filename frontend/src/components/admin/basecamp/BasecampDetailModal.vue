<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Building2,
  Users,
  MapPin,
  Clock,
  ExternalLink,
  Mountain,
  ShoppingBag,
} from 'lucide-vue-next'
import type { Basecamp } from '@/types/basecamp'

defineProps<{
  open: boolean
  basecamp: Basecamp | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'edit', basecamp: Basecamp): void
}>()

function handleClose() {
  emit('update:open', false)
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent v-if="basecamp" class="sm:max-w-lg max-h-[90vh] flex flex-col p-0 gap-0 overflow-hidden">
      <!-- Header -->
      <DialogHeader class="p-5 border-b bg-card shrink-0">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <div class="h-10 w-10 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
              <Building2 class="h-5 w-5" />
            </div>
            <div>
              <DialogTitle class="text-base font-bold tracking-tight text-foreground">
                {{ basecamp.nama_basecamp }}
              </DialogTitle>
              <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                Pos Registrasi Resmi Pendakian
              </DialogDescription>
            </div>
          </div>

          <Badge variant="secondary" class="text-xs font-semibold shrink-0">
            <Clock class="h-3 w-3 mr-1 text-emerald-600" />
            <span>{{ basecamp.jam_operasional }}</span>
          </Badge>
        </div>
      </DialogHeader>

      <!-- Body Content -->
      <div class="flex-1 overflow-y-auto p-5 space-y-4 text-xs">
        <!-- Mountain & Trail Info -->
        <div class="p-3.5 bg-muted/20 rounded-xl border border-border/60 space-y-2.5">
          <span class="font-bold text-foreground flex items-center gap-1.5">
            <Mountain class="h-3.5 w-3.5 text-emerald-600" />
            <span>Destinasi & Jalur Pendakian</span>
          </span>

          <div class="grid grid-cols-2 gap-2 pt-1 border-t border-border/40">
            <div>
              <span class="text-[11px] text-muted-foreground">Gunung:</span>
              <p class="font-semibold text-foreground">
                {{ basecamp.jalur?.gunung?.nama_gunung || '-' }}
              </p>
            </div>
            <div>
              <span class="text-[11px] text-muted-foreground">Jalur:</span>
              <p class="font-semibold text-foreground">
                {{ basecamp.jalur?.nama_jalur || '-' }}
              </p>
            </div>
          </div>

          <div v-if="basecamp.jalur?.titik_awal_mdpl || basecamp.jalur?.titik_akhir_mdpl" class="pt-2 border-t border-border/40 flex items-center justify-between text-[11px]">
            <span class="text-muted-foreground">Elevasi Rute:</span>
            <span class="font-medium text-foreground">
              {{ basecamp.jalur?.titik_awal_mdpl || '-' }} → {{ basecamp.jalur?.titik_akhir_mdpl || '-' }}
            </span>
          </div>
        </div>

        <!-- Mitra Penanggung Jawab -->
        <div class="p-3.5 bg-muted/20 rounded-xl border border-border/60 space-y-2.5">
          <span class="font-bold text-foreground flex items-center gap-1.5">
            <Users class="h-3.5 w-3.5 text-emerald-600" />
            <span>Mitra Penanggung Jawab</span>
          </span>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-border/40">
            <div>
              <span class="text-[11px] text-muted-foreground">Nama Pemilik:</span>
              <p class="font-semibold text-foreground">
                {{ basecamp.mitra?.nama_pemilik || '-' }}
              </p>
            </div>
            <div>
              <span class="text-[11px] text-muted-foreground">Nomor Telepon:</span>
              <p class="font-medium text-foreground">
                {{ basecamp.mitra?.telepon || '-' }}
              </p>
            </div>
          </div>

          <div v-if="basecamp.mitra?.bank" class="pt-2 border-t border-border/40 flex items-center justify-between text-[11px]">
            <span class="text-muted-foreground">Rekening Payout:</span>
            <span class="font-mono text-foreground font-medium">
              {{ basecamp.mitra?.bank }} — {{ basecamp.mitra?.rekening_bank }}
            </span>
          </div>
        </div>

        <!-- Coordinates & Location -->
        <div class="p-3.5 bg-muted/20 rounded-xl border border-border/60 space-y-2.5">
          <div class="flex items-center justify-between">
            <span class="font-bold text-foreground flex items-center gap-1.5">
              <MapPin class="h-3.5 w-3.5 text-emerald-600" />
              <span>Koordinat Geolokasi GPS</span>
            </span>

            <a
              :href="`https://www.google.com/maps?q=${basecamp.latitude},${basecamp.longitude}`"
              target="_blank"
              class="text-emerald-600 hover:underline font-medium text-[11px] flex items-center gap-1"
            >
              <span>Buka di Google Maps</span>
              <ExternalLink class="h-3 w-3" />
            </a>
          </div>

          <div class="p-2.5 bg-card rounded-lg border border-border/50 font-mono text-xs flex items-center justify-between">
            <span class="text-muted-foreground">Lat / Long:</span>
            <span class="font-bold text-foreground">{{ basecamp.latitude }}, {{ basecamp.longitude }}</span>
          </div>
        </div>

        <!-- Products / Services (if loaded) -->
        <div v-if="basecamp.produks && basecamp.produks.length > 0" class="space-y-2">
          <span class="font-bold text-foreground flex items-center gap-1.5">
            <ShoppingBag class="h-3.5 w-3.5 text-emerald-600" />
            <span>Katalog Produk & Tiket Aktif ({{ basecamp.produks.length }})</span>
          </span>
          <div class="divide-y border border-border/50 rounded-lg overflow-hidden">
            <div
              v-for="p in basecamp.produks"
              :key="p.id"
              class="p-2 bg-card flex items-center justify-between text-xs"
            >
              <span class="font-medium text-foreground">{{ p.nama_produk }}</span>
              <Badge variant="outline" class="text-[10px]">
                {{ p.kategori }}
              </Badge>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <DialogFooter class="p-4 border-t bg-card shrink-0 flex items-center justify-between">
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="text-xs"
          @click="handleClose"
        >
          Tutup
        </Button>

        <Button
          type="button"
          size="sm"
          class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white"
          @click="emit('edit', basecamp)"
        >
          Edit Basecamp
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
