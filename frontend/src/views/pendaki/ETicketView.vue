<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  QrCode,
  Building2,
  Users,
  ShieldCheck,
  Printer,
  Tent,
  AlertCircle,
  Loader2,
  MessageCircle,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import { formatRupiah, formatDateIndonesia, formatOrderStatus } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { PesananDetail } from '@/types/pendakiOrder'

const route = useRoute()
const router = useRouter()

const invoice = computed(() => String(route.params.invoice || ''))
const order = ref<PesananDetail | null>(null)
const isLoading = ref(true)
const errorMessage = ref('')

async function fetchOrderDetail() {
  if (!invoice.value) return
  isLoading.value = true
  errorMessage.value = ''

  try {
    const res = await pendakiOrdersApi.getOrderByInvoice(invoice.value)
    order.value = res.data || null
  } catch (err: unknown) {
    errorMessage.value = extractApiError(err).message
  } finally {
    isLoading.value = false
  }
}

function handlePrintTicket() {
  window.print()
}

const qrCodeUrl = computed(() => {
  const code = order.value?.invoice || 'SUMMIT'
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(code)}&margin=10`
})

const rentalItems = computed(() => {
  if (!order.value?.details) return []
  return order.value.details.filter(
    d => d.produk?.kategori === 'rental' || d.produk?.kategori === 'merchandise' || d.produk?.kategori === 'konsumsi'
  )
})

const serviceItems = computed(() => {
  if (!order.value?.details) return []
  return order.value.details.filter(d => d.produk?.kategori === 'jasa')
})

onMounted(() => {
  fetchOrderDetail()
})
</script>

<template>
  <div class="max-w-4xl mx-auto pb-24 space-y-6">
    <!-- Non-printable back navigation & actions -->
    <div class="print:hidden flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
        @click="router.push('/pendaki/orders')"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Riwayat Pesanan</span>
      </button>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="rounded-xl text-xs font-bold gap-1.5 shadow-xs"
          @click="handlePrintTicket"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Cetak / Simpan PDF</span>
        </Button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="py-20 text-center space-y-3">
      <Loader2 class="w-8 h-8 animate-spin text-emerald-600 mx-auto" />
      <p class="text-xs text-slate-500">Memuat data tiket digital SIMAKSI...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="errorMessage || !order"
      class="p-6 rounded-3xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-3"
    >
      <AlertCircle class="w-5 h-5 shrink-0" />
      <div>
        <strong class="font-bold text-sm block">Gagal Memuat Tiket</strong>
        <p>{{ errorMessage || 'Data pesanan tidak ditemukan.' }}</p>
      </div>
    </div>

    <!-- Main Boarding Pass / E-Ticket Card -->
    <div
      v-else
      class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl overflow-hidden print:shadow-none print:border-slate-300 print:m-0"
    >
      <!-- Ticket Header Banner -->
      <div class="bg-gradient-to-r from-emerald-950 via-forest-900 to-emerald-900 text-white p-6 sm:p-8 relative overflow-hidden">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-black tracking-wider uppercase text-emerald-300">
                SUMMIT DIGITAL PASS
              </span>
              <span class="text-xs text-emerald-400/80">•</span>
              <span class="text-xs text-emerald-200">Surat Izin Masuk Kawasan Konservasi (SIMAKSI)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black font-display tracking-tight text-white">
              {{ order.jalur?.nama_jalur || 'Jalur Pendakian Resmi' }}
            </h1>
            <p class="text-xs sm:text-sm text-emerald-200/90 flex items-center gap-1.5">
              <Building2 class="w-4 h-4 text-orange-400" />
              <span>Pengelola: {{ order.basecamp?.nama_basecamp || 'Basecamp Mitra' }}</span>
            </p>
          </div>

          <div class="shrink-0 flex sm:flex-col items-start sm:items-end gap-2">
            <span
              class="px-3 py-1 rounded-full text-xs font-bold"
              :class="[formatOrderStatus(order.status).bgClass, formatOrderStatus(order.status).textClass]"
            >
              {{ formatOrderStatus(order.status).label }}
            </span>
            <span class="font-mono text-xs text-emerald-300 font-bold">
              {{ order.invoice }}
            </span>
          </div>
        </div>

        <!-- Watermark Icon Background -->
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
          <QrCode class="w-48 h-48 text-white" />
        </div>
      </div>

      <!-- Ticket Body (QR Code + Core Data) -->
      <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-8 items-center border-b border-dashed border-slate-200 dark:border-slate-800">
        <!-- QR Code Section (1 Col) -->
        <div class="text-center space-y-3 bg-slate-50 dark:bg-slate-800/60 p-5 rounded-2xl border border-slate-100 dark:border-slate-800">
          <div class="bg-white p-3 rounded-xl inline-block shadow-xs border border-slate-200/60">
            <img
              :src="qrCodeUrl"
              :alt="`QR Code ${order.invoice}`"
              class="w-40 h-40 object-contain mx-auto"
            />
          </div>
          <div>
            <span class="font-mono font-black text-sm text-slate-900 dark:text-slate-100 block">
              {{ order.invoice }}
            </span>
            <p class="text-[11px] text-slate-500 mt-0.5">
              Pindai QR ini di Pos Registrasi Basecamp
            </p>
          </div>
        </div>

        <!-- Metadata Section (2 Cols) -->
        <div class="md:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-5 text-xs">
          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Ketua Rombongan</span>
            <strong class="text-slate-900 dark:text-slate-100 text-sm font-bold block">
              {{ order.anggotas?.[0]?.nama_anggota || order.user?.name || '-' }}
            </strong>
            <span class="text-[11px] text-slate-500 font-mono">
              NIK: {{ order.anggotas?.[0]?.nik_identitas || '-' }}
            </span>
          </div>

          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Tanggal Booking</span>
            <strong class="text-slate-900 dark:text-slate-100 text-sm font-bold block">
              {{ formatDateIndonesia(order.tanggal_booking) }}
            </strong>
            <span class="text-[11px] text-emerald-600 font-semibold">
              Izin Aktif Resmi
            </span>
          </div>

          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Jumlah Anggota</span>
            <strong class="text-slate-900 dark:text-slate-100 text-sm font-bold block">
              {{ order.anggotas?.length || 1 }} Orang Pendaki
            </strong>
            <span class="text-[11px] text-slate-500">
              Asuransi Terproteksi
            </span>
          </div>

          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Total Pembayaran</span>
            <strong class="text-emerald-700 dark:text-emerald-400 text-sm font-black font-display block">
              {{ formatRupiah(order.total_bayar) }}
            </strong>
            <span class="text-[11px] text-slate-500">
              Metode: {{ order.pembayaran?.provider || 'Xendit Gateway' }}
            </span>
          </div>

          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Jam Buka Pos</span>
            <strong class="text-slate-900 dark:text-slate-100 text-sm font-bold block">
              {{ order.basecamp?.jam_operasional || '06:00 - 18:00 WIB' }}
            </strong>
          </div>

          <div class="space-y-1">
            <span class="text-slate-400 block font-medium">Status Check-In</span>
            <div class="flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">
              <ShieldCheck class="w-4 h-4" />
              <span>Valid &amp; Siap Masuk</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Manifest Members Table -->
      <div class="p-6 sm:p-8 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
          <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
            <Users class="w-4 h-4 text-emerald-600" />
            <span>Daftar Manifes Anggota Rombongan ({{ order.anggotas?.length || 0 }})</span>
          </h3>
          <span class="text-xs text-slate-500">Wajib menunjukkan KTP saat check-in</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-semibold">
                <th class="pb-2 w-10">No</th>
                <th class="pb-2">Nama Lengkap</th>
                <th class="pb-2">NIK Identitas</th>
                <th class="pb-2">No. HP</th>
                <th class="pb-2">Kontak Darurat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
              <tr v-for="(anggota, idx) in order.anggotas" :key="anggota.id" class="py-2.5">
                <td class="py-2.5 font-bold text-slate-400">{{ idx + 1 }}</td>
                <td class="py-2.5 font-bold text-slate-900 dark:text-slate-100">
                  {{ anggota.nama_anggota }}
                  <span v-if="idx === 0" class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-semibold">
                    Ketua
                  </span>
                </td>
                <td class="py-2.5 font-mono">{{ anggota.nik_identitas }}</td>
                <td class="py-2.5">{{ anggota.telepon || '-' }}</td>
                <td class="py-2.5">
                  {{ anggota.telepon_darurat ? `${anggota.telepon_darurat} (${anggota.hubungan_darurat || 'Kerabat'})` : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Equipment Pickup & Services Checklist (if any) -->
      <div
        v-if="rentalItems.length > 0 || serviceItems.length > 0"
        class="p-6 sm:p-8 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 space-y-4"
      >
        <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <Tent class="w-4 h-4 text-amber-600" />
          <span>Pengambilan Perlengkapan Sewa &amp; Staf di Basecamp</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="item in [...rentalItems, ...serviceItems]"
            :key="item.id"
            class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700 flex items-center justify-between"
          >
            <div>
              <h4 class="font-bold text-xs text-slate-900 dark:text-slate-100">
                {{ item.produk?.nama_produk }}
              </h4>
              <p class="text-[11px] text-slate-500">
                Jumlah: {{ item.qty }} {{ item.produk?.satuan || 'unit' }}
              </p>
            </div>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300">
              Ambil di Pos
            </span>
          </div>
        </div>
      </div>

      <!-- Basecamp Location & Support Footer -->
      <div class="p-6 sm:p-8 bg-emerald-950 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
          <h4 class="font-bold text-sm text-white flex items-center gap-1.5">
            <Building2 class="w-4 h-4 text-emerald-400" />
            <span>{{ order.basecamp?.nama_basecamp || 'Pos Pelayanan Basecamp' }}</span>
          </h4>
          <p class="text-xs text-emerald-200/80">
            Pastikan rombongan tiba 30 menit sebelum jadwal pendakian untuk pemeriksaan kesehatan dan perlengkapan.
          </p>
        </div>

        <div class="flex items-center gap-2 print:hidden">
          <a
            v-if="order.basecamp?.mitra?.telepon"
            :href="`https://wa.me/${order.basecamp.mitra.telepon.replace(/\D/g, '')}`"
            target="_blank"
            rel="noopener noreferrer"
            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-800 hover:bg-emerald-700 text-white flex items-center gap-1.5 transition-colors"
          >
            <MessageCircle class="w-3.5 h-3.5" />
            <span>WhatsApp Basecamp</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
