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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  CalendarRange,
  Check,
  AlertTriangle,
  Info,
} from 'lucide-vue-next'
import type { BatchQuotaPayload } from '@/types/quota'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    productId: number | null
    productName?: string
    loading?: boolean
  }>(),
  {
    isOpen: false,
    productId: null,
    productName: 'Tiket Pendakian',
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submit', payload: BatchQuotaPayload): void
  (e: 'close'): void
}>()

const todayStr = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

const startDate = ref<string>('')
const endDate = ref<string>('')
const kuotaTotal = ref<number>(100)
const errorMessage = ref<string | null>(null)

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errorMessage.value = null
      startDate.value = todayStr.value
      // Default end of month
      const now = new Date()
      const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
      endDate.value = `${lastDay.getFullYear()}-${String(lastDay.getMonth() + 1).padStart(2, '0')}-${String(lastDay.getDate()).padStart(2, '0')}`
      kuotaTotal.value = 100
    }
  },
  { immediate: true }
)

const dayCount = computed(() => {
  if (!startDate.value || !endDate.value) return 0
  const start = new Date(startDate.value)
  const end = new Date(endDate.value)
  const diffTime = end.getTime() - start.getTime()
  if (diffTime < 0) return 0
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
})

const totalAccumulatedQuota = computed(() => {
  return dayCount.value * kuotaTotal.value
})

function handleSubmit() {
  errorMessage.value = null

  if (!startDate.value) {
    errorMessage.value = 'Tanggal mulai wajib diisi.'
    return
  }
  if (!endDate.value) {
    errorMessage.value = 'Tanggal selesai wajib diisi.'
    return
  }
  if (endDate.value < startDate.value) {
    errorMessage.value = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'
    return
  }
  if (kuotaTotal.value < 1) {
    errorMessage.value = 'Jumlah kuota per hari minimal 1.'
    return
  }

  emit('submit', {
    start_date: startDate.value,
    end_date: endDate.value,
    kuota_total: Number(kuotaTotal.value),
  })
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-lg bg-white rounded-2xl p-6">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#1E3A2B]/10 text-[#1E3A2B] flex items-center justify-center">
            <CalendarRange class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              Set Kuota Massal (Batch)
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500">
              Terapkan alokasi kuota harian untuk rentang tanggal tertentu pada {{ props.productName }}.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="py-3 space-y-4">
        <!-- Date Range Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="start-date" class="text-xs font-semibold text-stone-700">
              Tanggal Mulai <span class="text-red-500">*</span>
            </Label>
            <Input
              id="start-date"
              type="date"
              :min="todayStr"
              v-model="startDate"
              class="h-11 rounded-xl border-stone-200"
              :disabled="props.loading"
            />
          </div>

          <div class="space-y-1.5">
            <Label for="end-date" class="text-xs font-semibold text-stone-700">
              Tanggal Selesai <span class="text-red-500">*</span>
            </Label>
            <Input
              id="end-date"
              type="date"
              :min="startDate || todayStr"
              v-model="endDate"
              class="h-11 rounded-xl border-stone-200"
              :disabled="props.loading"
            />
          </div>
        </div>

        <!-- Quota Per Day Input -->
        <div class="space-y-1.5">
          <Label for="batch-quota" class="text-xs font-semibold text-stone-700">
            Kuota Maksimal Pendaki per Hari <span class="text-red-500">*</span>
          </Label>
          <Input
            id="batch-quota"
            type="number"
            min="1"
            v-model.number="kuotaTotal"
            placeholder="100"
            class="h-11 rounded-xl border-stone-200"
            :disabled="props.loading"
          />
        </div>

        <!-- Preview Calculation Card -->
        <div class="p-3.5 bg-stone-50 rounded-xl border border-stone-200 space-y-2">
          <div class="flex items-center gap-2 text-xs font-semibold text-stone-700">
            <Info class="w-4 h-4 text-[#1E3A2B]" />
            <span>Ringkasan Pengaturan Kuota:</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-stone-200/60">
            <div>
              <span class="text-stone-500">Jumlah Hari:</span>
              <span class="ml-1.5 font-bold text-stone-800">{{ dayCount }} Hari</span>
            </div>
            <div>
              <span class="text-stone-500">Total Alokasi:</span>
              <span class="ml-1.5 font-bold text-emerald-800">{{ totalAccumulatedQuota.toLocaleString('id-ID') }} Kuota</span>
            </div>
          </div>
        </div>

        <p v-if="errorMessage" class="flex items-center gap-1.5 text-xs text-red-600 font-medium bg-red-50 p-2.5 rounded-lg border border-red-200">
          <AlertTriangle class="w-4 h-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </p>

        <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
          <Button
            type="button"
            variant="outline"
            class="rounded-xl border-stone-200 text-stone-700 min-h-[44px]"
            :disabled="props.loading"
            @click="handleClose"
          >
            Batal
          </Button>
          <Button
            type="submit"
            class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px]"
            :disabled="props.loading || dayCount === 0"
          >
            <Check class="w-4 h-4 mr-2" />
            {{ props.loading ? 'Menerapkan Kuota...' : 'Terapkan Kuota Massal' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
