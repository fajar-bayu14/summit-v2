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
  CheckCircle2,
  Scissors,
  CreditCard,
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

const climberMembers = computed(() => {
  if (order.value?.anggotas && order.value.anggotas.length > 0) {
    return order.value.anggotas
  }
  return [
    {
      id: 1,
      pesanan_id: order.value?.id || 0,
      nama_anggota: order.value?.user?.name || 'Pendaki Terdaftar',
      nik_identitas: '-',
      telepon: order.value?.user?.telepon || '-',
      telepon_darurat: '-',
      hubungan_darurat: '-',
    },
  ]
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
  <div class="max-w-4xl mx-auto pb-24 space-y-6 print:p-0 print:m-0 print:max-w-none print:pb-0">
    <!-- Non-printable top navigation & actions -->
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
          class="rounded-xl text-xs font-bold gap-1.5 shadow-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800"
          @click="handlePrintTicket"
        >
          <Printer class="w-3.5 h-3.5" />
          <span>Cetak Kartu Identitas (PDF)</span>
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

    <!-- Main Content -->
    <div v-else class="space-y-8 print:space-y-6">
      <!-- Screen-only Hint Banner -->
      <div class="print:hidden p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-900/60 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2.5 shadow-xs">
        <CreditCard class="w-4 h-4 shrink-0 text-emerald-600" />
        <p class="leading-relaxed">
          <strong>Format Kartu Identitas:</strong> Tampilan cetak telah dioptimalkan agar pas menyerupai ukuran kartu tanda pengenal (ID Card / Lanyard Pass) resmi 1 lembar tanpa elemen website.
        </p>
      </div>

      <!-- PRINTABLE SECTION: CLIMBER ID CARDS (1 per member in manifest) -->
      <section class="space-y-8 print:space-y-6">
        <div
          v-for="(anggota, idx) in climberMembers"
          :key="anggota.id || idx"
          class="print-id-card-wrapper max-w-2xl mx-auto"
        >
          <!-- Cut guideline for printed sheet -->
          <div class="hidden print:flex items-center justify-between text-[10px] text-slate-400 mb-1 px-1 font-mono">
            <span class="flex items-center gap-1.5">
              <Scissors class="w-3 h-3" />
              <span>Gunting sepanjang garis putus-putus untuk kartu identitas lanyard pass</span>
            </span>
            <span>Kartu #{{ idx + 1 }} dari {{ climberMembers.length }}</span>
          </div>

          <!-- THE OFFICIAL CLIMBER ID CARD / DIGITAL PASS -->
          <div class="rounded-2xl border-2 border-emerald-900/50 dark:border-emerald-700/60 bg-white dark:bg-slate-900 shadow-lg overflow-hidden print:shadow-none print:border-2 print:border-emerald-950 print:rounded-2xl print:m-0">
            <!-- Card Header Strip -->
            <div class="bg-gradient-to-r from-emerald-950 via-forest-900 to-emerald-900 text-white p-4 sm:p-5 relative overflow-hidden">
              <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="space-y-1">
                  <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-emerald-300">
                    <ShieldCheck class="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                    <span>SUMMIT DIGITAL PASS</span>
                    <span class="text-emerald-500">•</span>
                    <span class="text-emerald-200/90 font-medium">SURAT IZIN MASUK KAWASAN KONSERVASI (SIMAKSI)</span>
                  </div>
                  <h2 class="text-lg sm:text-xl font-black font-display tracking-tight text-white leading-tight">
                    {{ order.jalur?.nama_jalur || 'Jalur Pendakian Resmi' }}
                  </h2>
                  <p class="text-xs text-emerald-200/90 flex items-center gap-1">
                    <Building2 class="w-3.5 h-3.5 text-orange-400 shrink-0" />
                    <span>Pengelola: {{ order.basecamp?.nama_basecamp || 'Basecamp Mitra' }}</span>
                  </p>
                </div>

                <div class="shrink-0 flex flex-col items-end gap-1">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs flex items-center gap-1"
                    :class="[formatOrderStatus(order.status).bgClass, formatOrderStatus(order.status).textClass]"
                  >
                    <CheckCircle2 class="w-3 h-3" />
                    <span>{{ formatOrderStatus(order.status).label }}</span>
                  </span>
                  <span class="font-mono text-[11px] text-emerald-300 font-bold tracking-wider">
                    {{ order.invoice }}
                  </span>
                </div>
              </div>

              <!-- Watermark QR Background -->
              <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                <QrCode class="w-32 h-32 text-white" />
              </div>
            </div>

            <!-- Card Body (Data Pendaki + QR Code) -->
            <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
              <!-- Left: Identitas Pendaki (2 Cols) -->
              <div class="sm:col-span-2 space-y-3">
                <div class="flex items-center gap-2">
                  <span
                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                    :class="idx === 0 ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                  >
                    {{ idx === 0 ? '★ Ketua Rombongan' : 'Anggota Pendaki' }}
                  </span>
                  <span class="text-[10px] text-slate-400 font-mono">PASS: {{ order.invoice }}-{{ idx + 1 }}</span>
                </div>

                <div>
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Nama Lengkap</span>
                  <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-50 tracking-tight leading-snug">
                    {{ anggota.nama_anggota }}
                  </h3>
                </div>

                <!-- Grid 2x2 Data Identitas -->
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                  <div class="space-y-0.5">
                    <span class="text-[10px] text-slate-400 block">NIK Identitas</span>
                    <strong class="font-mono font-bold text-xs text-slate-800 dark:text-slate-200 block">
                      {{ anggota.nik_identitas }}
                    </strong>
                  </div>

                  <div class="space-y-0.5">
                    <span class="text-[10px] text-slate-400 block">Tanggal Booking</span>
                    <strong class="font-bold text-xs text-slate-800 dark:text-slate-200 block">
                      {{ formatDateIndonesia(order.tanggal_booking) }}
                    </strong>
                  </div>

                  <div class="space-y-0.5">
                    <span class="text-[10px] text-slate-400 block">No. Handphone</span>
                    <span class="font-medium text-xs text-slate-700 dark:text-slate-300 block">
                      {{ anggota.telepon || order.user?.telepon || '-' }}
                    </span>
                  </div>

                  <div class="space-y-0.5">
                    <span class="text-[10px] text-slate-400 block">Kontak Darurat</span>
                    <span class="font-medium text-xs text-slate-700 dark:text-slate-300 block truncate">
                      {{ anggota.telepon_darurat ? `${anggota.telepon_darurat} (${anggota.hubungan_darurat || 'Kerabat'})` : '-' }}
                    </span>
                  </div>

                  <div class="space-y-0.5">
                    <span class="text-[10px] text-slate-400 block">Total Tagihan</span>
                    <strong class="font-bold text-xs text-emerald-700 dark:text-emerald-400 block">
                      {{ formatRupiah(order.total_bayar) }} (Lunas)
                    </strong>
                  </div>
                </div>
              </div>

              <!-- Right: QR Code Pos Registrasi (1 Col) -->
              <div class="sm:border-l sm:border-dashed sm:border-slate-200 dark:sm:border-slate-800 sm:pl-4 text-center flex flex-col items-center justify-center space-y-1.5">
                <div class="p-2 rounded-xl bg-white border border-slate-200 shadow-xs inline-block">
                  <img
                    :src="qrCodeUrl"
                    :alt="`QR Code ${order.invoice}`"
                    class="w-28 h-28 sm:w-32 sm:h-32 object-contain mx-auto"
                  />
                </div>
                <div class="space-y-0.5">
                  <span class="font-mono font-black text-[11px] text-slate-900 dark:text-slate-100 block tracking-tight">
                    {{ order.invoice }}
                  </span>
                  <span class="text-[9px] text-slate-500 font-medium block">
                    Pindai di Pos Registrasi
                  </span>
                </div>
              </div>
            </div>

            <!-- Card Footer: Security & Operational Strip -->
            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200/80 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-500 dark:text-slate-400">
              <div class="flex items-center gap-2">
                <span class="font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                  <ShieldCheck class="w-3.5 h-3.5" />
                  Asuransi Terproteksi
                </span>
                <span>•</span>
                <span>Pos Buka: {{ order.basecamp?.jam_operasional || '24 Jam' }}</span>
                <span>•</span>
                <span>Rombongan: {{ order.anggotas?.length || 1 }} Orang</span>
              </div>
              <span class="font-mono text-[9px] text-slate-400">
                Wajib membawa KTP/Identitas Asli saat Verifikasi
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- SCREEN-ONLY SECTION: Detailed Manifest & Orders Overview (Hidden on Print) -->
      <section class="print:hidden space-y-6">
        <!-- Manifest Members Table -->
        <div class="rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-4 shadow-sm">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <Users class="w-4 h-4 text-emerald-600" />
              <span>Daftar Manifes Lengkap Anggota Rombongan ({{ order.anggotas?.length || 0 }})</span>
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
          class="rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 sm:p-8 space-y-4 shadow-sm"
        >
          <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center gap-2">
            <Tent class="w-4 h-4 text-amber-600" />
            <span>Pengambilan Perlengkapan Sewa &amp; Staf di Basecamp</span>
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div
              v-for="item in [...rentalItems, ...serviceItems]"
              :key="item.id"
              class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 flex items-center justify-between"
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
        <div class="rounded-3xl bg-emerald-950 text-white p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
          <div class="space-y-1">
            <h4 class="font-bold text-sm text-white flex items-center gap-1.5">
              <Building2 class="w-4 h-4 text-emerald-400" />
              <span>{{ order.basecamp?.nama_basecamp || 'Pos Pelayanan Basecamp' }}</span>
            </h4>
            <p class="text-xs text-emerald-200/80">
              Pastikan rombongan tiba 30 menit sebelum jadwal pendakian untuk pemeriksaan kesehatan dan perlengkapan.
            </p>
          </div>

          <div class="flex items-center gap-2">
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
      </section>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    size: auto;
    margin: 10mm 12mm;
  }

  body {
    background-color: #ffffff !important;
    color: #000000 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print-id-card-wrapper {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
    margin-bottom: 20px !important;
  }

  /* Force background colors and gradients in print */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
