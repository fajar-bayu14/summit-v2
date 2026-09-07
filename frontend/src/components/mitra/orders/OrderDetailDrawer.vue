<script setup lang="ts">
import { ref, watch } from 'vue'
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
  (e: 'itemStatusUpdated', payload: { item: DetailPesananItem; status: ItemOperationalStatus }): void
  (e: 'close'): void
}>()

const activeTab = ref<'manifest' | 'items' | 'payment'>('manifest')
const updatingItemId = ref<number | null>(null)
const itemActionMessage = ref<string | null>(null)
const itemActionError = ref<string | null>(null)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      activeTab.value = 'manifest'
      itemActionMessage.value = null
      itemActionError.value = null
    }
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

function handleTriggerCheckIn() {
  if (props.order) {
    emit('checkIn', props.order)
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
        <!-- Ketua Rombongan Info Box -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 bg-stone-50 rounded-xl border border-stone-200">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 text-stone-600 flex items-center justify-center shrink-0">
              <User class="w-4 h-4" />
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Ketua Rombongan</span>
              <p class="text-xs font-bold text-stone-900">{{ props.order.user?.name || '-' }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 text-stone-600 flex items-center justify-center shrink-0">
              <Phone class="w-4 h-4" />
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Kontak Telepon</span>
              <p class="text-xs font-bold text-stone-900">{{ props.order.user?.telepon || '-' }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 text-stone-600 flex items-center justify-center shrink-0">
              <Users class="w-4 h-4" />
            </div>
            <div>
              <span class="text-[10px] uppercase font-bold text-stone-400">Jumlah Pendaki</span>
              <p class="text-xs font-bold text-stone-900">
                {{ (props.order.anggotas?.length ?? 0) + 1 }} Orang (1 Ketua + {{ props.order.anggotas?.length ?? 0 }} Anggota)
              </p>
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
          <div v-if="!props.order.anggotas || props.order.anggotas.length === 0" class="text-center py-6 text-stone-400 text-xs">
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
                    {{ anggota.nama_anggota }}
                  </td>
                  <td class="p-3 text-stone-600">
                    <span class="uppercase text-[10px] font-bold text-stone-500 mr-1">
                      {{ anggota.identitas_tipe || 'KTP' }}:
                    </span>
                    {{ anggota.identitas_nomor || '-' }}
                  </td>
                  <td class="p-3 text-stone-600 capitalize">
                    {{ anggota.jenis_kelamin || '-' }}
                  </td>
                  <td class="p-3 text-stone-600">
                    {{ anggota.telepon_darurat || '-' }}
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
                    {{ item.nama_produk }}
                  </div>
                  <div class="text-[11px] text-stone-500">
                    {{ item.kuantitas }}x @ {{ formatRupiah(item.harga_satuan) }} =
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
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
