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
import { Input } from '@/components/ui/input'
import { StatusBadge } from '@/components/common'
import {
  QrCode,
  Search,
  CheckCircle2,
  AlertCircle,
  User,
  Phone,
  Users,
  Package,
  Loader2,
} from 'lucide-vue-next'
import type { MitraPesanan } from '@/types/order'
import mitraOrdersApi from '@/api/mitraOrders'
import { getApiErrorMessage } from '@/lib/axios'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
  }>(),
  {
    isOpen: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'checkedIn', order: MitraPesanan): void
  (e: 'close'): void
}>()

const scanInput = ref('')
const isSearching = ref(false)
const isCheckingIn = ref(false)
const autoCheckIn = ref(false)

const matchedOrder = ref<MitraPesanan | null>(null)
const successMessage = ref<string | null>(null)
const errorMessage = ref<string | null>(null)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      resetState()
    }
  }
)

function resetState() {
  scanInput.value = ''
  matchedOrder.value = null
  successMessage.value = null
  errorMessage.value = null
  isSearching.value = false
  isCheckingIn.value = false
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

async function handleSearch() {
  const code = scanInput.value.trim()
  if (!code) {
    errorMessage.value = 'Silakan masukkan nomor invoice atau scan barcode/QR.'
    return
  }

  isSearching.value = true
  errorMessage.value = null
  successMessage.value = null
  matchedOrder.value = null

  try {
    const res = await mitraOrdersApi.getOrders({ search: code, per_page: 5 })
    const items = Array.isArray(res.data) ? res.data : (res.data?.data ?? [])

    if (items.length === 0) {
      errorMessage.value = `Pesanan dengan invoice atau kata kunci "${code}" tidak ditemukan.`
      return
    }

    // Ambil pesanan yang persis cocok atau pertama
    const exactMatch = items.find(
      (o) => o.invoice.toLowerCase() === code.toLowerCase()
    ) || items[0]

    // Fetch detail lengkapnya
    const detailRes = await mitraOrdersApi.getOrderById(exactMatch.id)
    if (detailRes.data) {
      matchedOrder.value = detailRes.data
    } else {
      matchedOrder.value = exactMatch
    }

    // Jika auto check-in aktif dan status 'paid', langsung eksekusi
    if (autoCheckIn.value && matchedOrder.value && matchedOrder.value.status === 'paid') {
      await executeCheckIn()
    }
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal mencari data pesanan.')
  } finally {
    isSearching.value = false
  }
}

async function executeCheckIn() {
  const currentOrder = matchedOrder.value
  if (!currentOrder) return

  if (currentOrder.status === 'on_going') {
    errorMessage.value = 'Rombongan ini sudah melakukan check-in sebelumnya (Status: On-Going).'
    return
  }

  if (currentOrder.status !== 'paid') {
    errorMessage.value = `Tidak dapat check-in: Status pesanan adalah "${currentOrder.status}". Hanya pesanan Lunas (Paid) yang dapat di-check-in.`
    return
  }

  isCheckingIn.value = true
  errorMessage.value = null
  successMessage.value = null

  try {
    const res = await mitraOrdersApi.checkInOrder(currentOrder.id)
    const updated = res.data ?? { ...currentOrder, status: 'on_going' as const }
    matchedOrder.value = updated
    successMessage.value = `Check-In Berhasil! Rombongan "${updated.user?.name || updated.invoice}" telah resmi tercatat aktif mendaki.`
    emit('checkedIn', updated)
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal melakukan check-in rombongan.')
  } finally {
    isCheckingIn.value = false
  }
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-2xl bg-white rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <div class="flex items-center gap-3 border-b border-stone-200 pb-3">
          <div class="w-11 h-11 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center font-bold">
            <QrCode class="w-6 h-6" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              Validasi & Check-In Tiket Rombongan
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500 mt-0.5">
              Masukkan nomor invoice atau scan kode QR tiket rombongan pendaki di basecamp.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <div class="space-y-4 py-3">
        <!-- Input Scanner Form -->
        <form @submit.prevent="handleSearch" class="space-y-3">
          <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
              <QrCode class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <Input
                v-model="scanInput"
                type="text"
                placeholder="Contoh: INV-20260906-001..."
                class="pl-10 h-11 rounded-xl text-xs sm:text-sm font-medium border-stone-300 focus:border-[#1E3A2B] focus:ring-[#1E3A2B]"
                autofocus
              />
            </div>
            <Button
              type="submit"
              :disabled="isSearching || !scanInput.trim()"
              class="h-11 px-5 rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white text-xs font-bold shrink-0 min-h-[44px]"
            >
              <Loader2 v-if="isSearching" class="w-4 h-4 mr-2 animate-spin" />
              <Search v-else class="w-4 h-4 mr-2" />
              Cari / Scan
            </Button>
          </div>

          <!-- Quick Auto Check-in Toggle -->
          <div class="flex items-center gap-2 text-xs text-stone-600">
            <input
              id="auto-checkin"
              v-model="autoCheckIn"
              type="checkbox"
              class="w-4 h-4 rounded text-[#1E3A2B] focus:ring-[#1E3A2B] border-stone-300 cursor-pointer"
            />
            <label for="auto-checkin" class="cursor-pointer select-none">
              Otomatis Check-In langsung saat kode invoice valid & berstatus <strong>Lunas (Paid)</strong>
            </label>
          </div>
        </form>

        <!-- Feedback Alert Messages -->
        <div
          v-if="errorMessage"
          class="flex items-center gap-2.5 p-3.5 bg-red-50 text-red-800 rounded-xl text-xs font-medium border border-red-200"
        >
          <AlertCircle class="w-4 h-4 text-red-600 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <div
          v-if="successMessage"
          class="flex items-center gap-2.5 p-3.5 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-medium border border-emerald-200"
        >
          <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
          <div class="flex-1">
            <div class="font-bold">Berhasil!</div>
            <div>{{ successMessage }}</div>
          </div>
        </div>

        <!-- Matched Order Detail Card -->
        <div
          v-if="matchedOrder"
          class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-4"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-stone-200 pb-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-stone-900">{{ matchedOrder.invoice }}</span>
                <StatusBadge :status="matchedOrder.status" />
              </div>
              <p class="text-xs text-stone-500 mt-0.5">
                Tanggal Naik: {{ formatDateIndo(matchedOrder.tanggal_pendakian || matchedOrder.tanggal_booking) }}
              </p>
            </div>

            <div class="text-xs text-stone-600">
              Jalur: <strong class="text-stone-800">{{ matchedOrder.jalur?.nama_jalur || 'Jalur Utama' }}</strong>
            </div>
          </div>

          <!-- Ketua & Anggota Info -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3 bg-white rounded-xl border border-stone-200">
              <div class="flex items-center gap-2 text-stone-400 text-[10px] uppercase font-bold mb-1">
                <User class="w-3.5 h-3.5" />
                Ketua Rombongan
              </div>
              <div class="text-xs font-bold text-stone-900">{{ matchedOrder.user?.name || '-' }}</div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-stone-200">
              <div class="flex items-center gap-2 text-stone-400 text-[10px] uppercase font-bold mb-1">
                <Phone class="w-3.5 h-3.5" />
                No. Telepon
              </div>
              <div class="text-xs font-bold text-stone-900">{{ matchedOrder.user?.telepon || '-' }}</div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-stone-200">
              <div class="flex items-center gap-2 text-stone-400 text-[10px] uppercase font-bold mb-1">
                <Users class="w-3.5 h-3.5" />
                Total Pendaki
              </div>
              <div class="text-xs font-bold text-stone-900">
                {{ (matchedOrder.anggotas?.length ?? 0) + 1 }} Orang
              </div>
            </div>
          </div>

          <!-- Manifest Mini List -->
          <div v-if="matchedOrder.anggotas && matchedOrder.anggotas.length > 0" class="space-y-1.5">
            <span class="text-[11px] font-bold text-stone-600">Anggota Rombongan:</span>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="anggota in matchedOrder.anggotas"
                :key="anggota.id"
                class="px-2.5 py-1 bg-white border border-stone-200 rounded-lg text-[11px] font-medium text-stone-700"
              >
                {{ anggota.nama_anggota }} ({{ anggota.identitas_tipe?.toUpperCase() || 'KTP' }}: {{ anggota.identitas_nomor || '-' }})
              </span>
            </div>
          </div>

          <!-- Items Summary -->
          <div v-if="matchedOrder.details && matchedOrder.details.length > 0" class="space-y-1.5">
            <span class="text-[11px] font-bold text-stone-600">Layanan & Item Tambahan:</span>
            <div class="space-y-1">
              <div
                v-for="item in matchedOrder.details"
                :key="item.id"
                class="flex items-center justify-between text-xs p-2 bg-white rounded-lg border border-stone-200"
              >
                <div class="flex items-center gap-2">
                  <Package class="w-3.5 h-3.5 text-stone-400" />
                  <span class="font-medium text-stone-800">{{ item.nama_produk }} ({{ item.kuantitas }}x)</span>
                </div>
                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-stone-100 text-stone-600">
                  {{ item.status_operasional }}
                </span>
              </div>
            </div>
          </div>

          <!-- Action Button for Check-In -->
          <div class="pt-2">
            <Button
              v-if="matchedOrder.status === 'paid'"
              :disabled="isCheckingIn"
              class="w-full h-12 rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white font-bold text-sm shadow-md"
              @click="executeCheckIn"
            >
              <Loader2 v-if="isCheckingIn" class="w-5 h-5 mr-2 animate-spin" />
              <CheckCircle2 v-else class="w-5 h-5 mr-2" />
              Konfirmasi Check-In Rombongan Sekarang
            </Button>

            <div
              v-else-if="matchedOrder.status === 'on_going'"
              class="p-3 bg-emerald-100/60 text-emerald-800 border border-emerald-300 rounded-xl text-center text-xs font-bold"
            >
              Rombongan ini sudah melakukan check-in dan sedang aktif mendaki.
            </div>

            <div
              v-else
              class="p-3 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl text-center text-xs font-bold"
            >
              Status pesanan: {{ matchedOrder.status }}. Check-in hanya dapat dilakukan untuk pesanan yang telah lunas.
            </div>
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
