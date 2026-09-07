<script setup lang="ts">
import { ref, watch, computed } from 'vue'
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
  CalendarDays,
  Users,
  Check,
  AlertTriangle,
  Minus,
  Plus,
} from 'lucide-vue-next'
import type { KuotaHarian } from '@/types/quota'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    date: string
    quota?: KuotaHarian | null
    productName?: string
    loading?: boolean
  }>(),
  {
    isOpen: false,
    date: '',
    quota: null,
    productName: 'Tiket Pendakian',
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submit', payload: { quotaId?: number; date: string; kuota_total: number }): void
  (e: 'close'): void
}>()

const totalQuotaInput = ref<number>(100)
const errorMessage = ref<string | null>(null)

const bookedCount = computed(() => {
  if (!props.quota) return 0
  return Math.max(0, props.quota.kuota_total - props.quota.kuota_tersisa)
})

watch(
  () => [props.isOpen, props.quota, props.date],
  () => {
    errorMessage.value = null
    if (props.quota) {
      totalQuotaInput.value = props.quota.kuota_total
    } else {
      totalQuotaInput.value = 100
    }
  },
  { immediate: true }
)

function adjustTotal(delta: number) {
  const minAllowed = Math.max(1, bookedCount.value)
  const nextVal = totalQuotaInput.value + delta
  totalQuotaInput.value = Math.max(minAllowed, nextVal)
}

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  const parsed = parseInt(target.value, 10)
  if (isNaN(parsed) || parsed < 0) {
    totalQuotaInput.value = 0
  } else {
    totalQuotaInput.value = parsed
  }
}

function handleSubmit() {
  if (totalQuotaInput.value < bookedCount.value) {
    errorMessage.value = `Total kuota tidak boleh lebih kecil dari tiket yang sudah terpesan (${bookedCount.value} tiket).`
    return
  }

  if (totalQuotaInput.value < 1) {
    errorMessage.value = 'Total kuota minimal 1 pendaki.'
    return
  }

  emit('submit', {
    quotaId: props.quota?.id,
    date: props.date,
    kuota_total: totalQuotaInput.value,
  })
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}

function formatDateIndo(dateStr: string): string {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(d)
  } catch {
    return dateStr
  }
}
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-md bg-white rounded-2xl p-6">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
            <CalendarDays class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              Atur Kuota Pendakian
            </DialogTitle>
            <DialogDescription class="text-xs text-stone-500">
              {{ formatDateIndo(props.date) }} • {{ props.productName }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <div class="py-3 space-y-4">
        <!-- Current Booking Breakdown -->
        <div class="grid grid-cols-2 gap-3 p-3 bg-stone-50 rounded-xl border border-stone-200">
          <div class="space-y-0.5">
            <span class="text-xs text-stone-500 font-medium">Tiket Terpesan</span>
            <div class="flex items-center gap-1.5 text-stone-900 font-bold text-base">
              <Users class="w-4 h-4 text-stone-500" />
              <span>{{ bookedCount }} Orang</span>
            </div>
          </div>
          <div class="space-y-0.5">
            <span class="text-xs text-stone-500 font-medium">Sisa Kuota Saat Ini</span>
            <div class="text-emerald-700 font-bold text-base">
              {{ props.quota?.kuota_tersisa ?? totalQuotaInput }} Kuota
            </div>
          </div>
        </div>

        <!-- Input Total Quota -->
        <div class="space-y-2">
          <Label for="total-quota-input" class="text-xs font-semibold text-stone-700">
            Total Kuota Harian (Maksimal Pendaki)
          </Label>
          <div class="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="icon"
              class="h-11 w-11 shrink-0 rounded-xl border-stone-200 hover:bg-stone-100"
              :disabled="totalQuotaInput <= Math.max(1, bookedCount) || props.loading"
              @click="adjustTotal(-5)"
            >
              <Minus class="w-4 h-4" />
            </Button>
            <Input
              id="total-quota-input"
              type="number"
              min="1"
              :value="totalQuotaInput"
              class="h-11 text-center font-bold text-lg rounded-xl border-stone-200"
              :disabled="props.loading"
              @input="handleInput"
            />
            <Button
              type="button"
              variant="outline"
              size="icon"
              class="h-11 w-11 shrink-0 rounded-xl border-stone-200 hover:bg-stone-100"
              :disabled="props.loading"
              @click="adjustTotal(5)"
            >
              <Plus class="w-4 h-4" />
            </Button>
          </div>
        </div>

        <!-- Quick Increments -->
        <div class="flex items-center justify-between gap-1 pt-1">
          <span class="text-xs text-stone-500 font-medium">Tambah Cepat:</span>
          <div class="flex items-center gap-1.5">
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustTotal(10)"
            >
              +10
            </Button>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustTotal(25)"
            >
              +25
            </Button>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustTotal(50)"
            >
              +50
            </Button>
          </div>
        </div>

        <p v-if="errorMessage" class="flex items-center gap-1.5 text-xs text-red-600 font-medium bg-red-50 p-2.5 rounded-lg border border-red-200">
          <AlertTriangle class="w-4 h-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </p>
      </div>

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
          type="button"
          class="rounded-xl bg-[#1E3A2B] hover:bg-[#1E3A2B]/90 text-white min-h-[44px]"
          :disabled="props.loading"
          @click="handleSubmit"
        >
          <Check class="w-4 h-4 mr-2" />
          {{ props.loading ? 'Menyimpan...' : 'Simpan Kuota' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
