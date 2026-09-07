<script setup lang="ts">
import {
  Building2,
  Clock,
  MapPin,
  ShieldCheck,
  ArrowRight,
  ExternalLink,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { BasecampMitraSummary, JalurDetail } from '@/types/pendakiMountain'

const props = defineProps<{
  trail: JalurDetail
  selectedBasecampId?: number | null
}>()

const emit = defineEmits<{
  (e: 'select-basecamp', basecamp: BasecampMitraSummary): void
}>()

const basecamps = props.trail.basecamps || []
</script>

<template>
  <div class="space-y-4">
    <!-- Header Summary -->
    <div class="flex items-center justify-between">
      <div class="space-y-0.5">
        <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <Building2 class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
          <span>Mitra Basecamp Pengelola Resmi di {{ trail.nama_jalur }}</span>
        </h4>
        <p class="text-xs text-slate-500">
          Pilih salah satu basecamp pengelola untuk check-in SIMAKSI dan memesan logistik/jasa lokal.
        </p>
      </div>
      <Badge variant="outline" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">
        {{ basecamps.length }} Mitra Tersedia
      </Badge>
    </div>

    <!-- Empty State -->
    <div
      v-if="basecamps.length === 0"
      class="p-8 text-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-2"
    >
      <Building2 class="w-8 h-8 mx-auto text-slate-400" />
      <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Belum Ada Mitra Basecamp Aktif</p>
      <p class="text-[11px] text-slate-500 max-w-sm mx-auto">
        Jalur ini sedang dalam proses integrasi mitra basecamp resmi. Silakan hubungi admin atau pilih jalur alternatif.
      </p>
    </div>

    <!-- Basecamp Cards List -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="bc in basecamps"
        :key="bc.id"
        class="rounded-2xl border p-4 bg-white dark:bg-slate-900 transition-all duration-200 flex flex-col justify-between space-y-4 shadow-xs"
        :class="[
          selectedBasecampId === bc.id
            ? 'border-emerald-600 ring-2 ring-emerald-600/20 bg-emerald-50/20 dark:bg-emerald-950/20'
            : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700',
        ]"
      >
        <div class="space-y-3">
          <!-- Title & Verification -->
          <div class="flex items-start justify-between gap-2">
            <div>
              <h5 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>{{ bc.nama_basecamp }}</span>
                <span
                  v-if="bc.mitra?.is_verified"
                  title="Mitra Resmi Terverifikasi"
                  class="text-emerald-600"
                >
                  <ShieldCheck class="w-4 h-4 inline-block" />
                </span>
              </h5>
              <p v-if="bc.mitra?.nama_mitra" class="text-xs text-slate-500">
                Pengelola: {{ bc.mitra.nama_mitra }}
              </p>
            </div>

            <Badge
              v-if="selectedBasecampId === bc.id"
              class="bg-emerald-600 text-white font-bold text-[10px] px-2 py-0.5 shrink-0"
            >
              Terpilih
            </Badge>
          </div>

          <!-- Basecamp Info Details -->
          <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
              <span>Jam Operasional: <strong class="text-slate-800 dark:text-slate-200">{{ bc.jam_operasional || '24 Jam' }}</strong></span>
            </div>

            <div v-if="bc.latitude && bc.longitude" class="flex items-center gap-1.5">
              <MapPin class="w-3.5 h-3.5 text-orange-600 shrink-0" />
              <a
                :href="`https://maps.google.com/?q=${bc.latitude},${bc.longitude}`"
                target="_blank"
                rel="noopener noreferrer"
                class="text-emerald-700 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 font-medium"
              >
                <span>Lihat di Google Maps</span>
                <ExternalLink class="w-3 h-3" />
              </a>
            </div>
          </div>

          <!-- Basecamp Facilities Pills -->
          <div class="flex flex-wrap gap-1.5 pt-1">
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium">
              🅿️ Parkir
            </span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium">
              🕌 Musholla
            </span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium">
              🚻 Toilet
            </span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium">
              ⚡ Charging
            </span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium">
              🍲 Logistik
            </span>
          </div>
        </div>

        <!-- Action Button -->
        <Button
          class="w-full rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs gap-1.5 shadow-xs"
          @click="emit('select-basecamp', bc)"
        >
          <span>Pilih Basecamp & Belanja</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </Button>
      </div>
    </div>
  </div>
</template>
