<script setup lang="ts">
import { computed } from 'vue'
import {
  Building2,
  Phone,
  CreditCard,
  CheckCircle2,
  AlertCircle,
  Package,
  Ticket,
  Tent,
  Compass,
  MapPin,
  Clock,
} from 'lucide-vue-next'
import type { PartnerGroupedCatalog, Product } from '@/types/product'
import { Badge } from '@/components/ui/badge'
import ProductCatalogCard from './ProductCatalogCard.vue'

const props = defineProps<{
  partnerGroup: PartnerGroupedCatalog
}>()

const emit = defineEmits<{
  (e: 'inspect', product: Product): void
}>()

const initials = computed(() => {
  const name = props.partnerGroup.mitra.nama_pemilik || 'Mitra'
  return name
    .split(' ')
    .map((w: string) => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
})

const isPartnerActive = computed(() => {
  const st = (props.partnerGroup.mitra as any).status
  return st !== 'suspend'
})
</script>

<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm transition-all duration-200 dark:border-slate-800 dark:bg-slate-900"
  >
    <!-- Partner Header -->
    <div
      class="border-b border-slate-100 bg-slate-50/70 p-5 dark:border-slate-800/80 dark:bg-slate-900/50"
    >
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <!-- Partner Identity Info -->
        <div class="flex items-center gap-3.5">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-sm font-bold text-white shadow-sm ring-2 ring-emerald-500/20"
          >
            {{ initials }}
          </div>

          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                {{ partnerGroup.mitra.nama_pemilik }}
              </h3>

              <Badge
                :variant="isPartnerActive ? 'default' : 'destructive'"
                class="gap-1 px-2 py-0.5 text-xs font-semibold"
                :class="
                  isPartnerActive
                    ? 'bg-emerald-600 hover:bg-emerald-700'
                    : 'bg-rose-600 hover:bg-rose-700'
                "
              >
                <CheckCircle2 v-if="isPartnerActive" class="h-3 w-3" />
                <AlertCircle v-else class="h-3 w-3" />
                <span>{{ isPartnerActive ? 'Mitra Aktif' : 'Suspend' }}</span>
              </Badge>
            </div>

            <!-- Contact & Bank details -->
            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <span v-if="partnerGroup.mitra.telepon" class="flex items-center gap-1">
                <Phone class="h-3.5 w-3.5 text-slate-400" />
                {{ partnerGroup.mitra.telepon }}
              </span>

              <span v-if="partnerGroup.mitra.bank" class="flex items-center gap-1">
                <CreditCard class="h-3.5 w-3.5 text-slate-400" />
                {{ partnerGroup.mitra.bank }} ({{ partnerGroup.mitra.rekening_bank || '-' }})
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Partner Metrics -->
        <div class="flex flex-wrap items-center gap-2">
          <div
            class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <Building2 class="h-3.5 w-3.5 text-emerald-600" />
            <span>{{ partnerGroup.basecampGroups.length }} Basecamp</span>
          </div>

          <div
            class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
          >
            <Package class="h-3.5 w-3.5 text-blue-600" />
            <span>{{ partnerGroup.totalProducts }} Total Produk</span>
          </div>

          <div
            v-if="partnerGroup.totalTickets > 0"
            class="flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50/70 px-2.5 py-1.5 text-xs font-medium text-emerald-800 shadow-xs dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
          >
            <Ticket class="h-3.5 w-3.5 text-emerald-600" />
            <span>{{ partnerGroup.totalTickets }} Tiket</span>
          </div>

          <div
            v-if="partnerGroup.totalRentals > 0"
            class="flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50/70 px-2.5 py-1.5 text-xs font-medium text-sky-800 shadow-xs dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-300"
          >
            <Tent class="h-3.5 w-3.5 text-sky-600" />
            <span>{{ partnerGroup.totalRentals }} Sewa Alat</span>
          </div>

          <div
            v-if="partnerGroup.totalTrips > 0"
            class="flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50/70 px-2.5 py-1.5 text-xs font-medium text-purple-800 shadow-xs dark:border-purple-800 dark:bg-purple-950/40 dark:text-purple-300"
          >
            <Compass class="h-3.5 w-3.5 text-purple-600" />
            <span>{{ partnerGroup.totalTrips }} Open Trip</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Partner Basecamps & Products Body -->
    <div class="space-y-6 p-5">
      <div
        v-for="bg in partnerGroup.basecampGroups"
        :key="bg.basecamp.id"
        class="rounded-xl border border-slate-100 bg-slate-50/40 p-4 dark:border-slate-800/60 dark:bg-slate-900/40"
      >
        <!-- Basecamp Subheader -->
        <div class="mb-3.5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200/60 pb-2.5 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
              <Building2 class="h-4 w-4" />
            </div>
            <div>
              <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ bg.basecamp.nama_basecamp }}
              </h4>
              <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                <span v-if="bg.basecamp.jalur?.gunung?.nama_gunung" class="flex items-center gap-1">
                  <MapPin class="h-3 w-3 text-slate-400" />
                  {{ bg.basecamp.jalur.gunung.nama_gunung }} ({{ bg.basecamp.jalur.nama_jalur }})
                </span>
                <span v-if="bg.basecamp.jam_operasional" class="flex items-center gap-1">
                  <Clock class="h-3 w-3 text-slate-400" />
                  {{ bg.basecamp.jam_operasional }}
                </span>
              </div>
            </div>
          </div>

          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            {{ bg.products.length }} Katalog Produk
          </span>
        </div>

        <!-- Basecamp Products Grid -->
        <div
          v-if="bg.products.length > 0"
          class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4"
        >
          <ProductCatalogCard
            v-for="prod in bg.products"
            :key="prod.id"
            :product="prod"
            @inspect="emit('inspect', $event)"
          />
        </div>

        <!-- Empty state inside basecamp -->
        <div
          v-else
          class="flex items-center justify-center rounded-lg border border-dashed border-slate-200 py-6 text-center text-xs text-slate-400 dark:border-slate-800"
        >
          Belum ada produk aktif yang terdaftar pada basecamp ini.
        </div>
      </div>
    </div>
  </div>
</template>
