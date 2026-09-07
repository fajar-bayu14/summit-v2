<script setup lang="ts">
import { ref } from 'vue'
import {
  Users,
  ShieldCheck,
  Plus,
  Minus,
  ShoppingCart,
  MessageSquare,
} from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { formatRupiah } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

const props = defineProps<{
  services: ProdukCatalogItem[]
}>()

const emit = defineEmits<{
  (
    e: 'add-service',
    payload: {
      product: ProdukCatalogItem
      quantity: number
      specialNotes: string
    }
  ): void
}>()

const quantities = ref<Record<number, number>>({})
const specialNotes = ref<Record<number, string>>({})

function getQty(productId: number): number {
  return quantities.value[productId] || 1
}

function setQty(productId: number, val: number) {
  quantities.value[productId] = Math.max(1, val)
}

function handleAdd(service: ProdukCatalogItem) {
  emit('add-service', {
    product: service,
    quantity: getQty(service.id),
    specialNotes: specialNotes.value[service.id] || '',
  })
}
</script>

<template>
  <div class="space-y-6">
    <div class="space-y-1">
      <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
        <Users class="w-5 h-5 text-emerald-700 dark:text-emerald-400" />
        <span>Jasa Porter Angkut &amp; Pemandu Guide Resmi</span>
      </h3>
      <p class="text-xs text-slate-500">
        Ringankan beban bawaanmu dan daki gunung lebih aman didampingi porter dan guide lokal berlisensi APGI.
      </p>
    </div>

    <!-- Empty State -->
    <div
      v-if="services.length === 0"
      class="p-10 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 space-y-2"
    >
      <Users class="w-8 h-8 mx-auto text-slate-400" />
      <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Belum Ada Layanan Porter/Guide</p>
      <p class="text-[11px] text-slate-500">Basecamp ini belum mendaftarkan layanan porter/guide online.</p>
    </div>

    <!-- Services Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div
        v-for="srv in services"
        :key="srv.id"
        class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs flex flex-col justify-between space-y-4 hover:border-emerald-500/50 transition-colors"
      >
        <div class="space-y-3">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <Badge class="bg-blue-600 text-white font-bold text-[10px] uppercase border-0">
                  {{ srv.kategori }}
                </Badge>
                <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                  <ShieldCheck class="w-3.5 h-3.5" />
                  Berlisensi
                </span>
              </div>
              <h4 class="font-bold text-base text-slate-900 dark:text-slate-100">
                {{ srv.nama_produk }}
              </h4>
            </div>

            <div class="text-right shrink-0">
              <span class="text-[10px] text-slate-400">Tarif Jasa</span>
              <p class="font-extrabold text-base text-emerald-700 dark:text-emerald-400 font-display">
                {{ formatRupiah(srv.harga) }}
                <span class="text-[10px] font-normal text-slate-400">/ {{ srv.satuan || 'orang' }}</span>
              </p>
            </div>
          </div>

          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            {{ srv.deskripsi || 'Layanan porter/guide terpercaya. Standar beban maksimal 20 kg per porter, bertanggung jawab atas keselamatan dan kenyamanan pendaki.' }}
          </p>

          <!-- Specifications Pill List -->
          <div class="flex flex-wrap gap-2 text-[11px] text-slate-600 dark:text-slate-400">
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-medium">
              ⚖️ Max Beban: 20 Kg
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-medium">
              🧭 Pemandu Berpengalaman
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-medium">
              ⛺ Termasuk Pasang Tenda
            </span>
          </div>

          <!-- Special Request Note Input -->
          <div class="pt-2">
            <label class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-1 mb-1">
              <MessageSquare class="w-3 h-3 text-slate-400" />
              <span>Catatan / Kebutuhan Khusus (Opsional):</span>
            </label>
            <input
              type="text"
              v-model="specialNotes[srv.id]"
              placeholder="Contoh: Bawa tenda kapasitas 4 & logistik pos 3"
              class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500"
            />
          </div>
        </div>

        <!-- Action & Quantity -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
          <div class="inline-flex items-center border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 p-0.5">
            <button
              type="button"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 disabled:opacity-30"
              :disabled="getQty(srv.id) <= 1"
              @click="setQty(srv.id, getQty(srv.id) - 1)"
            >
              <Minus class="w-3 h-3" />
            </button>
            <span class="w-6 text-center font-bold text-xs text-slate-900 dark:text-slate-100">
              {{ getQty(srv.id) }}
            </span>
            <button
              type="button"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700"
              @click="setQty(srv.id, getQty(srv.id) + 1)"
            >
              <Plus class="w-3 h-3" />
            </button>
          </div>

          <Button
            size="sm"
            class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs gap-1.5 shadow-xs"
            @click="handleAdd(srv)"
          >
            <ShoppingCart class="w-3.5 h-3.5" />
            <span>Pesan Jasa Ini</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
