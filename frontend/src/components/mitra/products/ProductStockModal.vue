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
import { Package, AlertTriangle, Plus, Minus, Check } from 'lucide-vue-next'
import type { Produk } from '@/types/product'

const props = withDefaults(
  defineProps<{
    isOpen: boolean
    product: Produk | null
    loading?: boolean
  }>(),
  {
    isOpen: false,
    product: null,
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void
  (e: 'submit', stock: number): void
  (e: 'close'): void
}>()

const stockValue = ref<number>(0)
const errorMessage = ref<string | null>(null)

watch(
  () => props.product,
  (newProduct) => {
    if (newProduct) {
      stockValue.value = newProduct.stok ?? 0
      errorMessage.value = null
    }
  },
  { immediate: true }
)

function adjustStock(delta: number) {
  const nextValue = stockValue.value + delta
  stockValue.value = Math.max(0, nextValue)
}

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  const parsed = parseInt(target.value, 10)
  if (isNaN(parsed) || parsed < 0) {
    stockValue.value = 0
  } else {
    stockValue.value = parsed
  }
}

function handleSubmit() {
  if (stockValue.value < 0) {
    errorMessage.value = 'Jumlah stok tidak boleh kurang dari 0.'
    return
  }
  emit('submit', stockValue.value)
}

function handleClose() {
  emit('update:isOpen', false)
  emit('close')
}

const isLowStock = computed(() => stockValue.value > 0 && stockValue.value <= 5)
const isOutOfStock = computed(() => stockValue.value === 0)
</script>

<template>
  <Dialog :open="props.isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-md bg-white rounded-2xl p-6">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
            <Package class="w-5 h-5" />
          </div>
          <div>
            <DialogTitle class="text-lg font-bold text-stone-900">
              Update Stok Fisik
            </DialogTitle>
            <DialogDescription class="text-sm text-stone-500">
              {{ props.product?.nama_produk || 'Produk Rental' }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <div class="py-4 space-y-4">
        <!-- Stock Status Banner -->
        <div
          v-if="isOutOfStock"
          class="flex items-center gap-2 p-3 bg-red-50 text-red-700 rounded-xl text-xs font-medium border border-red-200"
        >
          <AlertTriangle class="w-4 h-4 shrink-0" />
          <span>Perhatian: Stok produk habis (0). Produk tidak akan bisa dipesan pendaki.</span>
        </div>
        <div
          v-else-if="isLowStock"
          class="flex items-center gap-2 p-3 bg-amber-50 text-amber-700 rounded-xl text-xs font-medium border border-amber-200"
        >
          <AlertTriangle class="w-4 h-4 shrink-0" />
          <span>Peringatan: Stok menipis (Tersisa {{ stockValue }} unit).</span>
        </div>

        <!-- Stock Input Controls -->
        <div class="space-y-2">
          <Label for="stock-input" class="text-sm font-medium text-stone-700">
            Jumlah Unit Tersedia ({{ props.product?.satuan || 'unit' }})
          </Label>
          <div class="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="icon"
              class="h-11 w-11 shrink-0 rounded-xl border-stone-200 hover:bg-stone-100"
              :disabled="stockValue <= 0 || props.loading"
              @click="adjustStock(-1)"
            >
              <Minus class="w-4 h-4" />
            </Button>
            <Input
              id="stock-input"
              type="number"
              min="0"
              :value="stockValue"
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
              @click="adjustStock(1)"
            >
              <Plus class="w-4 h-4" />
            </Button>
          </div>
        </div>

        <!-- Quick Delta Buttons -->
        <div class="flex items-center justify-between gap-1.5 pt-1">
          <span class="text-xs text-stone-500 font-medium">Tambah Cepat:</span>
          <div class="flex items-center gap-1.5">
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustStock(5)"
            >
              +5
            </Button>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustStock(10)"
            >
              +10
            </Button>
            <Button
              type="button"
              variant="secondary"
              size="sm"
              class="h-8 px-2.5 text-xs rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-medium"
              :disabled="props.loading"
              @click="adjustStock(25)"
            >
              +25
            </Button>
          </div>
        </div>

        <p v-if="errorMessage" class="text-xs text-red-600 font-medium">
          {{ errorMessage }}
        </p>
      </div>

      <DialogFooter class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
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
          {{ props.loading ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
