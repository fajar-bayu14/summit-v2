<script setup lang="ts">
import { ref, watch } from 'vue'
import { trailApi } from '@/api/trail'
import type { JalurPendakian } from '@/types/mountain'
import type { ForceMajeurePayload } from '@/types/finance'
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
import { Label } from '@/components/ui/label'
import { AlertTriangle, Loader2, ShieldAlert } from 'lucide-vue-next'

const props = defineProps<{
  open: boolean
  isLoading: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [payload: ForceMajeurePayload]
}>()

const trails = ref<JalurPendakian[]>([])
const isLoadingTrails = ref(false)

const selectedJalurId = ref<number | ''>('')
const startDate = ref('')
const endDate = ref('')
const reason = ref('')
const errorMsg = ref('')

async function loadTrails() {
  isLoadingTrails.value = true
  try {
    const res = await trailApi.getTrails({ per_page: 100 })
    trails.value = res.items || []
  } catch {
    // silent fallback
  } finally {
    isLoadingTrails.value = false
  }
}

watch(
  () => props.open,
  newVal => {
    if (newVal) {
      selectedJalurId.value = ''
      startDate.value = new Date().toISOString().split('T')[0]
      endDate.value = ''
      reason.value = ''
      errorMsg.value = ''
      loadTrails()
    }
  }
)

function handleSubmit() {
  errorMsg.value = ''

  if (!selectedJalurId.value) {
    errorMsg.value = 'Silakan pilih jalur pendakian yang ditutup.'
    return
  }

  if (!reason.value.trim() || reason.value.trim().length < 5) {
    errorMsg.value = 'Alasan penutupan jalur resmi wajib diisi minimal 5 karakter.'
    return
  }

  const payload: ForceMajeurePayload = {
    jalur_id: Number(selectedJalurId.value),
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    alasan: reason.value.trim(),
  }

  emit('confirm', payload)
}
</script>

<template>
  <Dialog :open="open" @update:open="val => emit('update:open', val)">
    <DialogContent class="sm:max-w-md rounded-2xl">
      <DialogHeader>
        <div class="h-12 w-12 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center mb-2">
          <ShieldAlert class="h-6 w-6" />
        </div>
        <DialogTitle class="text-lg font-bold">Otomasi Force Majeure / Penutupan Jalur</DialogTitle>
        <DialogDescription class="text-xs">
          Batalkan dan lakukan pengembalian dana penuh (100% Refund) secara massal kepada seluruh tiket pendaki yang terdampak cuaca ekstrem/bencana.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-3.5 py-2 text-xs">
        <!-- Trail Selector -->
        <div class="space-y-1.5">
          <Label for="jalurSelect" class="text-xs font-semibold">
            Pilih Jalur Pendakian Yang Ditutup <span class="text-destructive">*</span>
          </Label>
          <select
            id="jalurSelect"
            v-model="selectedJalurId"
            class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-xs shadow-xs focus:outline-none focus:ring-1 focus:ring-ring"
            :disabled="isLoading || isLoadingTrails"
          >
            <option value="" disabled>-- Pilih Destinasi Jalur --</option>
            <option v-for="t in trails" :key="t.id" :value="t.id">
              {{ t.gunung?.nama_gunung || 'Gunung' }} - {{ t.nama_jalur }} (Status: {{ t.status }})
            </option>
          </select>
        </div>

        <!-- Date Range Impacted -->
        <div class="grid grid-cols-2 gap-2">
          <div class="space-y-1">
            <Label for="startDate" class="text-xs font-semibold">Dari Tanggal Booking</Label>
            <Input
              id="startDate"
              v-model="startDate"
              type="date"
              class="h-9 text-xs"
              :disabled="isLoading"
            />
          </div>
          <div class="space-y-1">
            <Label for="endDate" class="text-xs font-semibold">Sampai Tanggal (Opsional)</Label>
            <Input
              id="endDate"
              v-model="endDate"
              type="date"
              class="h-9 text-xs"
              :disabled="isLoading"
            />
          </div>
        </div>

        <!-- Reason -->
        <div class="space-y-1.5">
          <Label for="disasterReason" class="text-xs font-semibold">
            Alasan Resmi Penutupan Balai Taman Nasional <span class="text-destructive">*</span>
          </Label>
          <textarea
            id="disasterReason"
            v-model="reason"
            rows="3"
            placeholder="Contoh: Surat Edaran BTNGC No. 12/2026: Jalur ditutup darurat akibat badai angin dan potensi longsor pos 2..."
            class="flex min-h-[70px] w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-xs focus:outline-none focus:ring-1 focus:ring-ring resize-none"
            :disabled="isLoading"
          />
        </div>

        <div class="p-3 rounded-xl bg-rose-500/10 text-rose-800 dark:text-rose-300 border border-rose-500/20 text-[11px] flex items-start gap-2">
          <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
          <span>
            <strong>Perhatian:</strong> Tindakan ini akan langsung mengubah status seluruh pesanan aktif pada jalur dan tanggal tersebut menjadi <strong>refunded</strong> dan memotong holding escrow mitra terkait.
          </span>
        </div>

        <p v-if="errorMsg" class="text-xs text-destructive font-medium">{{ errorMsg }}</p>

        <DialogFooter class="gap-2 sm:gap-0 pt-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="isLoading"
            @click="emit('update:open', false)"
          >
            Batal
          </Button>
          <Button
            type="submit"
            variant="destructive"
            size="sm"
            class="gap-1.5"
            :disabled="isLoading"
          >
            <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
            <span>Eksekusi Refund Massal</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
