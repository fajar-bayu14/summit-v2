<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { StatusBadge } from '@/components/common'
import {
  FileText,
  User,
  Users,
  Phone,
  Package,
  QrCode,
  CheckCircle,
  AlertCircle,
  IdCard,
  ShieldCheck,
  Loader2,
} from 'lucide-vue-next'
import type { MitraPesanan, DetailPesananItem, ItemOperationalStatus } from '@/types/order'
import mitraOrdersApi from '@/api/mitraOrders'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    order: MitraPesanan | null
    loading?: boolean
  }>(),
  {
    isOpen: false,
    order: null,
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'checkIn', order: MitraPesanan): void
  (e: 'checkOut', order: MitraPesanan): void
  (e: 'itemStatusUpdated', payload: { item: DetailPesananItem; status: ItemOperationalStatus }): void
  (e: 'close'): void
}>()

const activeTab = ref<'manifest' | 'items' | 'payment'>('manifest')
const updatingItemId = ref<number | null>(null)
const itemActionMessage = ref<string | null>(null)
const itemActionError = ref<string | null>(null)

// KTP preview modal state
const isKtpModalOpen = ref(false)
const isLoadingKtp = ref(false)
const ktpImageUrl = ref<string | null>(null)
const ktpError = ref<string | null>(null)

const climberName = computed(() => props.order?.user?.name || '-')
const climberPhone = computed(() => {
  return (
    props.order?.user?.pendaki?.telepon ||
    props.order?.anggotas?.[0]?.telepon ||
    props.order?.user?.telepon ||
    '-'
  )
})
const climberNik = computed(() => {
  return (
    props.order?.user?.pendaki?.nomor_identitas ||
    props.order?.user?.pendaki?.nik ||
    props.order?.anggotas?.[0]?.nik_identitas ||
    props.order?.anggotas?.[0]?.identitas_nomor ||
    '-'
  )
})
const climberEmergencyContact = computed(() => {
  const p = props.order?.user?.pendaki
  const emergencyName = p?.nama_kontak_darurat || p?.kontak_darurat_nama
  const emergencyHub = p?.hubungan_darurat || p?.kontak_darurat_hubungan
  const emergencyPhone = p?.telepon_darurat || p?.kontak_darurat_no
  if (emergencyName) {
    const hub = emergencyHub ? ` (${emergencyHub})` : ''
    const no = emergencyPhone ? ` • ${emergencyPhone}` : ''
    return `${emergencyName}${hub}${no}`
  }
  const a = props.order?.anggotas?.[0]
  if (a?.telepon_darurat) {
    const hub = a.hubungan_darurat ? ` (${a.hubungan_darurat})` : ''
    return `${a.telepon_darurat}${hub}`
  }
  return '-'
})
const climberKycStatus = computed(() => {
  return props.order?.user?.pendaki?.status_verifikasi || props.order?.user?.pendaki?.status_kyc || null
})

const totalPendakiSummary = computed(() => {
  const count = props.order?.anggotas?.length ?? 0
  if (count <= 0) {
    return '1 Orang (Ketua)'
  }
  if (count === 1) {
    return '1 Orang (Ketua Rombongan)'
  }
  return `${count} Orang (1 Ketua + ${count - 1} Anggota)`
})

async function handleViewKtp() {
  if (!props.order) return
  isKtpModalOpen.value = true
  ktpError.value = null

  if (ktpImageUrl.value) return

  isLoadingKtp.value = true
  try {
    const blob = await mitraOrdersApi.downloadClimberKtp(props.order.id)
    ktpImageUrl.value = URL.createObjectURL(blob)
  } catch (err) {
    ktpError.value = getApiErrorMessage(err, 'Foto identitas/KTP tidak dapat dimuat atau belum diunggah oleh pendaki.')
  } finally {
    isLoadingKtp.value = false
  }
}

function handleCloseKtpModal() {
  isKtpModalOpen.value = false
}

function handleTriggerCheckIn() {
  if (!props.order) return
  emit('checkIn', props.order)
}

function handleTriggerCheckOut() {
  if (!props.order) return
  emit('checkOut', props.order)
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      activeTab.value = 'manifest'
      itemActionMessage.value = null
      itemActionError.value = null
    } else {
      if (ktpImageUrl.value) {
        URL.revokeObjectURL(ktpImageUrl.value)
        ktpImageUrl.value = null
      }
      isKtpModalOpen.value = false
      ktpError.value = null
    }
  }
)

watch(
  () => props.order?.id,
  () => {
    if (ktpImageUrl.value) {
      URL.revokeObjectURL(ktpImageUrl.value)
      ktpImageUrl.value = null
    }
    ktpError.value = null
  }
)

function formatRupiah(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDateIndo(dateStr?: string | null): string {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

async function handleUpdateItemStatus(item: DetailPesananItem, newStatus: ItemOperationalStatus) {
  if (!props.order) return
  updatingItemId.value = item.id
  itemActionMessage.value = null
  itemActionError.value = null

  try {
    await mitraOrdersApi.updateItemStatus(props.order.id, item.id, newStatus)
    item.status_operasional = newStatus
    itemActionMessage.value = `Status item "${item.nama_produk}" berhasil diubah ke "${newStatus}".`
    emit('itemStatusUpdated', { item, status: newStatus })
  } catch (err) {
    itemActionError.value = getApiErrorMessage(err, 'Gagal mengubah status item.')
  } finally {
    updatingItemId.value = null
  }
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}

function getItemStatusClass(status: ItemOperationalStatus): string {
  switch (status) {
    case 'ready':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200'
    case 'active':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'completed':
      return 'bg-blue-50 text-blue-700 border-blue-200'
    case 'cancelled':
      return 'bg-red-50 text-red-700 border-red-200'
    default:
      return 'bg-stone-100 text-stone-700 border-stone-200'
  }
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-3xl bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center font-bold">
              <FileText class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <DialogTitle class="text-lg font-bold text-stone-900">
                  {{ props.order?.invoice || 'Detail Pesanan' }}
                </DialogTitle>
                <StatusBadge v-if="props.order" :status="props.order.status" />
              </div>
              <DialogDescription class="text-xs text-stone-500 mt-0.5">
                Tgl Booking: {{ formatDateIndo(props.order?.tanggal_booking) }} •
                Tgl Naik: {{ formatDateIndo(props.order?.tanggal_pendakian || props.order?.tanggal_booking) }}
              </DialogDescription>
            </div>
          </div>

          <Button
            v-if="props.order?.status === 'paid'"
            class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white shadow-xs min-h-[40px]"
            @click="handleTriggerCheckIn"
          >
            <QrCode class="w-4 h-4 mr-2" />
            Check-In Rombongan
          </Button>
        </div>
      </DialogHeader>

      <!-- Feedback Alerts -->
      <div
        v-if="itemActionMessage"
        class="flex items-center gap-2.5 p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium border border-emerald-200"
      >
        <CheckCircle class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ itemActionMessage }}</span>
      </div>

      <div
        v-if="itemActionError"
        class="flex items-center gap-2.5 p-3 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200"
      >
        <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
        <span>{{ itemActionError }}</span>
      </div>

      <div v-if="props.order" class="space-y-4 py-2">
        <!-- Data Pemesan & Rombongan Info Box -->
        <div class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-stone-200/80">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold uppercase tracking-wider text-stone-600">Data Pemesan / Penanggung Jawab</span>
              <span
                v-if="climberKycStatus === 'verified' || climberKycStatus === 'approved'"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
              >
                <ShieldCheck class="w-3 h-3 text-emerald-700" />
                KYC Terverifikasi
              </span>
            </div>

            <!-- Tombol Lihat KTP -->
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="h-8 px-3 rounded-lg border-[#1E3A2B]/30 text-[#1E3A2B] hover:bg-[#1E3A2B]/10 font-bold text-xs gap-1.5 self-start sm:self-auto cursor-pointer"
              @click="handleViewKtp"
            >
              <IdCard class="w-3.5 h-3.5 text-[#1E3A2B]" />
              Lihat Foto KTP
            </Button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Nama & NIK -->
            <div class="flex items-start gap-2.5 bg-white p-2.5 rounded-xl border border-stone-200/80">
              <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-600 flex items-center justify-center shrink-0 mt-0.5">
                <User class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <span class="text-[10px] uppercase font-bold text-stone-400 block">Nama & NIK</span>
                <p class="text-xs font-bold text-stone-900 truncate">{{ climberName }}</p>
                <p class="text-[11px] font-mono text-stone-600 truncate">{{ climberNik }}</p>
              </div>
            </div>

            <!-- Telepon -->
            <div class="flex items-start gap-2.5 bg-white p-2.5 rounded-xl border border-stone-200/80">
              <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-600 flex items-center justify-center shrink-0 mt-0.5">
                <Phone class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <span class="text-[10px] uppercase font-bold text-stone-400 block">Kontak Telepon</span>
                <p class="text-xs font-bold text-stone-900 truncate">{{ climberPhone }}</p>
                <p class="text-[11px] text-stone-500 truncate">{{ props.order.user?.email || '-' }}</p>
              </div>
            </div>

            <!-- Kontak Darurat -->
            <div class="flex items-start gap-2.5 bg-white p-2.5 rounded-xl border border-stone-200/80">
              <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-600 flex items-center justify-center shrink-0 mt-0.5">
                <AlertCircle class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <span class="text-[10px] uppercase font-bold text-stone-400 block">Kontak Darurat</span>
                <p class="text-xs font-bold text-stone-900 truncate" :title="climberEmergencyContact">
                  {{ climberEmergencyContact }}
                </p>
              </div>
            </div>

            <!-- Total Pendaki -->
            <div class="flex items-start gap-2.5 bg-white p-2.5 rounded-xl border border-stone-200/80">
              <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-600 flex items-center justify-center shrink-0 mt-0.5">
                <Users class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <span class="text-[10px] uppercase font-bold text-stone-400 block">Jumlah Pendaki</span>
                <p class="text-xs font-bold text-stone-900">{{ totalPendakiSummary }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Controls -->
        <div class="flex items-center gap-2 border-b border-stone-200 pb-1">
          <button
            type="button"
            :class="[
              'px-4 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer min-h-[40px]',
              activeTab === 'manifest'
                ? 'bg-[#1E3A2B] text-white'
                : 'text-stone-600 hover:bg-stone-100',
            ]"
            @click="activeTab = 'manifest'"
          >
            Manifes Pendaki ({{ props.order.anggotas?.length ?? 0 }})
          </button>
          <button
            type="button"
            :class="[
              'px-4 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer min-h-[40px]',
              activeTab === 'items'
                ? 'bg-[#1E3A2B] text-white'
                : 'text-stone-600 hover:bg-stone-100',
            ]"
            @click="activeTab = 'items'"
          >
            Item & Perlengkapan ({{ props.order.details?.length ?? 0 }})
          </button>
          <button
            type="button"
            :class="[
              'px-4 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer min-h-[40px]',
              activeTab === 'payment'
                ? 'bg-[#1E3A2B] text-white'
                : 'text-stone-600 hover:bg-stone-100',
            ]"
            @click="activeTab = 'payment'"
          >
            Rincian Pembayaran
          </button>
        </div>

        <!-- Tab 1: Manifes Anggota Pendaki -->
        <div v-if="activeTab === 'manifest'" class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-stone-600">
              Daftar Peserta Rombongan ({{ props.order.anggotas?.length ?? 0 }} Terdaftar)
            </span>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="h-7 text-xs text-[#1E3A2B] hover:bg-[#1E3A2B]/10 gap-1.5 font-semibold"
              @click="handleViewKtp"
            >
              <IdCard class="w-3.5 h-3.5" />
              Lihat KTP Pemesan
            </Button>
          </div>

          <div v-if="!props.order.anggotas || props.order.anggotas.length === 0" class="text-center py-8 bg-stone-50 rounded-xl border border-stone-200 text-stone-400 text-xs">
            Tidak ada anggota rombongan tambahan (Hanya ketua rombongan).
          </div>
          <div v-else class="border border-stone-200 rounded-xl overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-stone-50 border-b border-stone-200 font-bold text-stone-700">
                <tr>
                  <th class="p-3">Nama Anggota</th>
                  <th class="p-3">Identitas (KTP/Paspor)</th>
                  <th class="p-3">Gender</th>
                  <th class="p-3">Kontak Darurat</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-stone-200">
                <tr
                  v-for="(anggota, idx) in props.order.anggotas"
                  :key="anggota.id || idx"
                  class="hover:bg-stone-50/50"
                >
                  <td class="p-3 font-semibold text-stone-900">
                    <div class="flex items-center gap-1.5">
                      <span>{{ anggota.nama_anggota }}</span>
                      <span
                        v-if="idx === 0"
                        class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                      >
                        Ketua
                      </span>
                    </div>
                  </td>
                  <td class="p-3 text-stone-600">
                    <span class="uppercase text-[10px] font-bold text-stone-500 mr-1">
                      {{ anggota.identitas_tipe || 'KTP' }}:
                    </span>
                    <span class="font-mono">{{ anggota.nik_identitas || anggota.identitas_nomor || '-' }}</span>
                  </td>
                  <td class="p-3 text-stone-600 capitalize">
                    {{ anggota.jenis_kelamin === 'L' ? 'Laki-laki' : (anggota.jenis_kelamin === 'P' ? 'Perempuan' : (anggota.jenis_kelamin || '-')) }}
                  </td>
                  <td class="p-3 text-stone-600">
                    <div>
                      <span>{{ anggota.telepon_darurat || '-' }}</span>
                      <span v-if="anggota.hubungan_darurat" class="block text-[11px] text-stone-400">
                        ({{ anggota.hubungan_darurat }})
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 2: Item & Perlengkapan Operasional -->
        <div v-if="activeTab === 'items'" class="space-y-3">
          <div v-if="!props.order.details || props.order.details.length === 0" class="text-center py-6 text-stone-400 text-xs">
            Tidak ada detail item pesanan.
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="item in props.order.details"
              :key="item.id"
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-stone-50/60 border border-stone-200 rounded-xl"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white border border-stone-200 flex items-center justify-center text-stone-600 shrink-0">
                  <Package class="w-5 h-5" />
                </div>
                <div>
                  <div class="font-bold text-stone-900 text-xs">
                    {{ item.nama_produk || item.produk?.nama_produk || 'Tiket / Perlengkapan' }}
                  </div>
                  <div class="text-[11px] text-stone-500">
                    {{ item.kuantitas ?? item.qty ?? 1 }}x @ {{ formatRupiah(item.harga_satuan ?? item.harga ?? (item.subtotal ? item.subtotal / (item.kuantitas ?? item.qty ?? 1) : 0)) }} =
                    <strong class="text-stone-800">{{ formatRupiah(item.subtotal) }}</strong>
                  </div>
                </div>
              </div>

              <!-- Operational Status Selector -->
              <div class="flex items-center gap-2">
                <span class="text-[11px] font-medium text-stone-500">Status Item:</span>
                <select
                  :value="item.status_operasional"
                  :disabled="updatingItemId === item.id"
                  class="h-9 px-2.5 bg-white border rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20"
                  :class="getItemStatusClass(item.status_operasional)"
                  @change="handleUpdateItemStatus(item, ($event.target as HTMLSelectElement).value as ItemOperationalStatus)"
                >
                  <option value="pending">Pending (Menunggu)</option>
                  <option value="ready">Ready (Siap)</option>
                  <option value="active">Active (Dipakai Mendaki)</option>
                  <option value="completed">Completed (Selesai/Kembali)</option>
                  <option value="cancelled">Cancelled (Batal)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Rincian Pembayaran -->
        <div v-if="activeTab === 'payment'" class="p-4 bg-stone-50 rounded-xl border border-stone-200 space-y-3">
          <div class="flex items-center justify-between text-xs text-stone-600">
            <span>Subtotal Produk & Layanan</span>
            <span class="font-semibold">{{ formatRupiah(props.order.subtotal) }}</span>
          </div>
          <div class="flex items-center justify-between text-xs text-stone-600 border-b border-stone-200 pb-2">
            <span>Biaya Layanan / Kode Unik</span>
            <span class="font-semibold">{{ formatRupiah(Math.max(0, props.order.total_bayar - props.order.subtotal)) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm font-bold text-stone-900 pt-1">
            <span>Total Pembayaran Pendaki</span>
            <span class="text-base text-emerald-800">{{ formatRupiah(props.order.total_bayar) }}</span>
          </div>
          <div
            v-if="props.order.pendapatan_mitra"
            class="flex items-center justify-between text-xs font-bold text-[#1E3A2B] bg-emerald-100/50 p-2.5 rounded-lg border border-emerald-200"
          >
            <span>Estimasi Pendapatan Bersih Mitra (Setelah Biaya Platform):</span>
            <span class="text-sm">{{ formatRupiah(props.order.pendapatan_mitra) }}</span>
          </div>
        </div>
      </div>

      <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3 border-t border-stone-200">
        <Button
          type="button"
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
          @click="handleClose"
        >
          Tutup
        </Button>

        <Button
          v-if="props.order?.status === 'paid'"
          type="button"
          class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold min-h-[44px] gap-2 shadow-sm"
          @click="handleTriggerCheckIn"
        >
          <QrCode class="w-4 h-4" />
          <span>Check-In Rombongan</span>
        </Button>

        <Button
          v-if="props.order?.status === 'on_going'"
          type="button"
          class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold min-h-[44px] gap-2 shadow-sm"
          @click="handleTriggerCheckOut"
        >
          <CheckCircle class="w-4 h-4" />
          <span>Selesaikan Pendakian (Check-Out)</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog Preview Foto KTP Pendaki -->
  <Dialog :open="isKtpModalOpen" @update:open="handleCloseKtpModal">
    <DialogContent class="sm:max-w-lg bg-white rounded-2xl p-6">
      <DialogHeader>
        <div class="flex items-center gap-2.5 border-b border-stone-200 pb-3">
          <div class="w-9 h-9 rounded-lg bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center shrink-0">
            <IdCard class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-base font-bold text-stone-900">
              Dokumen KTP Pendaki
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500">
              {{ climberName }} • NIK: {{ climberNik }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Loading state -->
      <div v-if="isLoadingKtp" class="flex flex-col items-center justify-center py-12 space-y-3">
        <Loader2 class="w-8 h-8 text-[#1E3A2B] animate-spin" />
        <span class="text-xs text-stone-500 font-medium">Memuat berkas KTP dari server...</span>
      </div>

      <!-- Error state -->
      <div
        v-else-if="ktpError"
        class="p-4 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200 flex items-center gap-3 my-4"
      >
        <AlertCircle class="w-5 h-5 text-red-600 shrink-0" />
        <span>{{ ktpError }}</span>
      </div>

      <!-- Image preview -->
      <div v-else-if="ktpImageUrl" class="mt-3 flex flex-col items-center">
        <div class="w-full bg-stone-100 rounded-xl overflow-hidden border border-stone-200 flex items-center justify-center max-h-[380px]">
          <img
            :src="ktpImageUrl"
            alt="Foto KTP Pendaki"
            class="w-full h-auto object-contain max-h-[380px]"
          />
        </div>
        <p class="text-[11px] text-stone-400 mt-2 text-center">
          Periksa kecocokan NIK, Nama, dan Foto fisik pemesan saat check-in di pos basecamp.
        </p>
      </div>

      <DialogFooter class="pt-3 border-t border-stone-200 flex justify-end">
        <Button
          type="button"
          variant="outline"
          class="rounded-xl border-stone-200 text-stone-700 min-h-[40px]"
          @click="handleCloseKtpModal"
        >
          Tutup
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
