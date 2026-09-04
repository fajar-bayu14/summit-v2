<script setup lang="ts">
import { computed } from 'vue'
import {
  Ticket,
  Tent,
  Compass,
  UserCheck,
  Package,
  Building2,
  MapPin,
  Clock,
  CheckCircle2,
  AlertCircle,
  User,
  Phone,
  CreditCard,
  Mountain,
} from 'lucide-vue-next'
import type { Product } from '@/types/product'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  open: boolean
  product: Product | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const categoryConfig = computed(() => {
  if (!props.product) return { label: 'Produk', bg: '', icon: Package }
  switch (props.product.kategori) {
    case 'ticket':
      return {
        label: 'Tiket Pendakian',
        bg: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300',
        icon: Ticket,
      }
    case 'rental':
      return {
        label: 'Sewa Alat',
        bg: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300',
        icon: Tent,
      }
    case 'opentrip':
      return {
        label: 'Open Trip',
        bg: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300',
        icon: Compass,
      }
    case 'guide':
    case 'porter':
      return {
        label: props.product.kategori === 'guide' ? 'Guide Pendakian' : 'Porter',
        bg: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300',
        icon: UserCheck,
      }
    default:
      return {
        label: props.product.kategori.toUpperCase(),
        bg: 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900/40 dark:text-slate-300',
        icon: Package,
      }
  }
})

const formatCurrency = (val?: number) => {
  if (val === undefined || val === null) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val)
}

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent
      class="max-h-[90vh] w-full max-w-3xl overflow-y-auto p-0 rounded-2xl bg-white dark:bg-slate-900"
    >
      <div v-if="product" class="flex flex-col">
        <!-- Header Banner -->
        <DialogHeader class="border-b border-slate-100 bg-slate-50/70 p-6 dark:border-slate-800 dark:bg-slate-900/50">
          <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <Badge
                variant="outline"
                :class="['flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold', categoryConfig.bg]"
              >
                <component :is="categoryConfig.icon" class="h-3.5 w-3.5" />
                <span>{{ categoryConfig.label }}</span>
              </Badge>

              <Badge
                :variant="product.is_active ? 'default' : 'destructive'"
                class="gap-1 px-2.5 py-0.5 text-xs font-semibold"
                :class="product.is_active ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
              >
                <CheckCircle2 v-if="product.is_active" class="h-3 w-3" />
                <AlertCircle v-else class="h-3 w-3" />
                <span>{{ product.is_active ? 'Produk Aktif / Tersedia' : 'Nonaktif' }}</span>
              </Badge>
            </div>

            <div>
              <DialogTitle class="text-xl font-bold text-slate-900 dark:text-slate-100">
                {{ product.nama_produk }}
              </DialogTitle>
              <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                <MapPin class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                <span>
                  {{ product.basecamp?.nama_basecamp || 'Basecamp' }}
                  <template v-if="product.basecamp?.jalur?.gunung?.nama_gunung">
                    • {{ product.basecamp.jalur.gunung.nama_gunung }} ({{ product.basecamp.jalur.nama_jalur }})
                  </template>
                </span>
              </p>
            </div>
          </div>
        </DialogHeader>

        <!-- Body Content -->
        <div class="space-y-6 p-6">
          <!-- Overview Cards -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Price Box -->
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 dark:border-emerald-950 dark:bg-emerald-950/20">
              <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Tarif / Harga</span>
              <div class="mt-1 text-lg font-bold text-emerald-900 dark:text-emerald-200">
                {{ formatCurrency(product.harga) }}
                <span v-if="product.satuan" class="text-xs font-normal text-emerald-600 dark:text-emerald-400">
                  / {{ product.satuan }}
                </span>
              </div>
            </div>

            <!-- Stock / Capacity Box -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Ketersediaan / Stok</span>
              <div class="mt-1 text-lg font-bold text-slate-800 dark:text-slate-200">
                <template v-if="product.kategori === 'ticket'">
                  {{ product.tiket?.kuotas?.[0]?.kuota_tersisa ?? 'Fleksibel' }}
                  <span class="text-xs font-normal text-slate-500">kuota</span>
                </template>
                <template v-else-if="product.kategori === 'opentrip'">
                  {{ product.opentrip?.sisa_kursi ?? 0 }}
                  <span class="text-xs font-normal text-slate-500">sisa kursi</span>
                </template>
                <template v-else-if="product.stok !== null">
                  {{ product.stok }}
                  <span class="text-xs font-normal text-slate-500">unit fisik</span>
                </template>
                <template v-else>
                  Tersedia
                </template>
              </div>
            </div>

            <!-- ID & Category Code -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">ID & Kode Referensi</span>
              <div class="mt-1 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">
                PRD-{{ String(product.id).padStart(5, '0') }}
              </div>
            </div>
          </div>

          <!-- Deskripsi & Gambar -->
          <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div v-if="product.gambar" class="md:col-span-1">
              <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 h-44 w-full">
                <img :src="product.gambar" :alt="product.nama_produk" class="h-full w-full object-cover" />
              </div>
            </div>

            <div :class="product.gambar ? 'md:col-span-2' : 'md:col-span-3'">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi & Syarat Produk</h4>
              <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300 whitespace-pre-line">
                {{ product.deskripsi || 'Tidak ada deskripsi detail tambahan untuk produk ini.' }}
              </p>
            </div>
          </div>

          <!-- Deep Inspection: Ticket Daily Quotas -->
          <div
            v-if="product.kategori === 'ticket' && product.tiket"
            class="rounded-xl border border-emerald-200 bg-emerald-50/20 p-4.5 dark:border-emerald-900/60 dark:bg-emerald-950/20"
          >
            <div class="mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Ticket class="h-4 w-4 text-emerald-600" />
                <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                  Inspeksi Kuota Harian Tiket Pendakian
                </h4>
              </div>

              <div v-if="product.tiket.jam_buka || product.tiket.jam_tutup" class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                <Clock class="h-3.5 w-3.5 text-emerald-600" />
                <span>Jam Buka: {{ product.tiket.jam_buka || '07:00' }} - {{ product.tiket.jam_tutup || '17:00' }}</span>
              </div>
            </div>

            <div v-if="product.tiket.kuotas && product.tiket.kuotas.length > 0" class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-emerald-100 bg-emerald-100/50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <th class="p-2.5 font-semibold">Tanggal</th>
                    <th class="p-2.5 font-semibold text-center">Kuota Total</th>
                    <th class="p-2.5 font-semibold text-center">Kuota Tersisa</th>
                    <th class="p-2.5 font-semibold text-center">Terpakai</th>
                    <th class="p-2.5 font-semibold text-right">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="kuota in product.tiket.kuotas" :key="kuota.id" class="hover:bg-white/80 dark:hover:bg-slate-900/80">
                    <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200">
                      {{ formatDate(kuota.tanggal) }}
                    </td>
                    <td class="p-2.5 text-center font-semibold text-slate-700 dark:text-slate-300">
                      {{ kuota.kuota_total }}
                    </td>
                    <td class="p-2.5 text-center font-bold text-emerald-600 dark:text-emerald-400">
                      {{ kuota.kuota_tersisa }}
                    </td>
                    <td class="p-2.5 text-center text-slate-500">
                      {{ Math.max(0, kuota.kuota_total - kuota.kuota_tersisa) }}
                    </td>
                    <td class="p-2.5 text-right">
                      <span
                        v-if="kuota.kuota_tersisa > 10"
                        class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                      >
                        Tersedia
                      </span>
                      <span
                        v-else-if="kuota.kuota_tersisa > 0"
                        class="inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                      >
                        Hampir Habis
                      </span>
                      <span
                        v-else
                        class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                      >
                        Habis
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="py-4 text-center text-xs text-slate-400">
              Belum ada data alokasi kuota harian spesifik yang diinput.
            </div>
          </div>

          <!-- Deep Inspection: Open Trip Details -->
          <div
            v-if="product.kategori === 'opentrip' && product.opentrip"
            class="rounded-xl border border-purple-200 bg-purple-50/20 p-4.5 dark:border-purple-900/60 dark:bg-purple-950/20"
          >
            <div class="mb-3 flex items-center gap-2">
              <Compass class="h-4 w-4 text-purple-600" />
              <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                Detail Jadwal & Kuota Open Trip
              </h4>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs">
              <div class="rounded-lg bg-white p-3 shadow-xs dark:bg-slate-800">
                <span class="text-slate-400">Tanggal Berangkat</span>
                <p class="mt-1 font-semibold text-slate-800 dark:text-slate-200">
                  {{ formatDate(product.opentrip.tanggal_berangkat) }}
                </p>
              </div>

              <div class="rounded-lg bg-white p-3 shadow-xs dark:bg-slate-800">
                <span class="text-slate-400">Tanggal Pulang</span>
                <p class="mt-1 font-semibold text-slate-800 dark:text-slate-200">
                  {{ formatDate(product.opentrip.tanggal_pulang) }}
                </p>
              </div>

              <div class="rounded-lg bg-white p-3 shadow-xs dark:bg-slate-800">
                <span class="text-slate-400">Kapasitas Peserta</span>
                <p class="mt-1 font-semibold text-slate-800 dark:text-slate-200">
                  {{ product.opentrip.minimal_peserta }} - {{ product.opentrip.maksimal_peserta }} Peserta
                </p>
              </div>

              <div class="rounded-lg bg-white p-3 shadow-xs dark:bg-slate-800">
                <span class="text-slate-400">Sisa Kursi / Kuota</span>
                <p class="mt-1 font-bold text-purple-700 dark:text-purple-300">
                  {{ product.opentrip.sisa_kursi }} Kursi Tersedia
                </p>
              </div>
            </div>

            <div v-if="product.opentrip.meeting_point" class="mt-3 flex items-center gap-2 rounded-lg bg-white p-3 text-xs shadow-xs dark:bg-slate-800">
              <MapPin class="h-4 w-4 text-purple-600 shrink-0" />
              <span class="font-medium text-slate-700 dark:text-slate-300">
                Meeting Point: <strong class="text-slate-900 dark:text-slate-100">{{ product.opentrip.meeting_point }}</strong>
              </span>
            </div>
          </div>

          <!-- Partner & Basecamp Profile Box -->
          <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <h4 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">
              Informasi Mitra & Basecamp Pengelola
            </h4>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                  <User class="h-3.5 w-3.5 text-emerald-600" />
                  <span>{{ product.basecamp?.mitra?.nama_pemilik || 'Nama Mitra' }}</span>
                </div>
                <div v-if="product.basecamp?.mitra?.telepon" class="flex items-center gap-2 text-slate-500">
                  <Phone class="h-3.5 w-3.5 text-slate-400" />
                  <span>{{ product.basecamp.mitra.telepon }}</span>
                </div>
                <div v-if="product.basecamp?.mitra?.bank" class="flex items-center gap-2 text-slate-500">
                  <CreditCard class="h-3.5 w-3.5 text-slate-400" />
                  <span>{{ product.basecamp.mitra.bank }} ({{ product.basecamp.mitra.rekening_bank }})</span>
                </div>
              </div>

              <div class="space-y-1.5">
                <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                  <Building2 class="h-3.5 w-3.5 text-emerald-600" />
                  <span>{{ product.basecamp?.nama_basecamp }}</span>
                </div>
                <div v-if="product.basecamp?.jalur" class="flex items-center gap-2 text-slate-500">
                  <Mountain class="h-3.5 w-3.5 text-slate-400" />
                  <span>Jalur {{ product.basecamp.jalur.nama_jalur }}</span>
                </div>
                <div v-if="product.basecamp?.jam_operasional" class="flex items-center gap-2 text-slate-500">
                  <Clock class="h-3.5 w-3.5 text-slate-400" />
                  <span>Operasional: {{ product.basecamp.jam_operasional }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/50">
          <Button
            variant="outline"
            class="rounded-xl px-5 text-xs font-semibold"
            @click="emit('update:open', false)"
          >
            Tutup
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
