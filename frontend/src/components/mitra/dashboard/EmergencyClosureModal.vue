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
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import mitraDashboardApi from '@/api/mitraDashboard'
import { getApiErrorMessage } from '@/lib/axios'
import {
  AlertTriangle,
  ShieldCheck,
  Power,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    open: boolean
    currentStatus?: string
    trailId?: number | null
    trailName?: string
  }>(),
  {
    currentStatus: 'open',
    trailId: null,
    trailName: 'Jalur Pendakian',
  }
)

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'success', newStatus: 'open' | 'close'): void
}>()

const targetStatus = ref<'open' | 'close'>('close')
const reason = ref('')
const isLoading = ref(false)
const errorMessage = ref<string | null>(null)

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      targetStatus.value = props.currentStatus === 'open' ? 'close' : 'open'
      reason.value = ''
      errorMessage.value = null
    }
  }
)

const isClosing = computed(() => targetStatus.value === 'close')

async function handleSubmit() {
  if (!props.trailId) {
    errorMessage.value = 'Jalur tidak ditemukan atau belum terhubung dengan basecamp ini.'
    return
  }

  if (isClosing.value && reason.value.trim().length < 5) {
    errorMessage.value = 'Alasan penutupan darurat wajib diisi minimal 5 karakter.'
    return
  }

  isLoading.value = true
  errorMessage.value = null

  try {
    const payload = {
      status: targetStatus.value,
      alasan_penutupan: isClosing.value ? reason.value.trim() : 'Jalur dibuka kembali untuk operasional normal.',
    }

    await mitraDashboardApi.emergencyCloseTrail(props.trailId, payload)
    
    emit('success', targetStatus.value)
    emit('update:open', false)
  } catch (err) {
    errorMessage.value = getApiErrorMessage(err, 'Gagal memperbarui status jalur darurat.')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md rounded-2xl p-6">
      <DialogHeader class="flex flex-col items-center text-center gap-2">
        <div
          class="h-12 w-12 rounded-full flex items-center justify-center mb-1"
          :class="isClosing ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400'"
        >
          <Power v-if="isClosing" class="h-6 w-6 stroke-[2]" />
          <ShieldCheck v-else class="h-6 w-6 stroke-[2]" />
        </div>

        <DialogTitle class="text-lg font-bold text-foreground">
          {{ isClosing ? 'Tutup Jalur Darurat' : 'Buka Kembali Jalur' }}
        </DialogTitle>
        <DialogDescription class="text-xs text-muted-foreground leading-relaxed">
          {{ isClosing 
            ? `Tindakan ini akan menghentikan proses check-in pada ${trailName} dan menampilkan pengumuman darurat.`
            : `Jalur ${trailName} akan dibuka kembali dan siap menerima kedatangan pendaki.`
          }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-2">
        <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
          <AlertTriangle class="h-4 w-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <div v-if="isClosing" class="space-y-1.5">
          <Label for="alasan" class="text-xs font-semibold text-foreground">
            Alasan Penutupan Darurat <span class="text-rose-500">*</span>
          </Label>
          <Input
            id="alasan"
            v-model="reason"
            placeholder="Misal: Badai angin kencang, pohon tumbang di Pos 3..."
            class="text-xs h-9 rounded-xl"
            :disabled="isLoading"
            required
          />
          <p class="text-[11px] text-muted-foreground">
            Alasan ini akan tampil pada aplikasi pendaki dan dashboard operasional.
          </p>
        </div>

        <div v-else class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-800 dark:text-emerald-300">
          Pastikan kondisi cuaca dan jalur di lapangan telah aman sebelum membuka kembali pos pendakian.
        </div>

        <DialogFooter class="flex sm:justify-center gap-2 pt-2 bg-transparent border-t-0 p-0">
          <Button
            type="button"
            variant="outline"
            class="flex-1 rounded-xl text-xs h-9"
            :disabled="isLoading"
            @click="emit('update:open', false)"
          >
            Batal
          </Button>
          <Button
            type="submit"
            class="flex-1 rounded-xl text-xs h-9 font-semibold"
            :variant="isClosing ? 'destructive' : 'default'"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="inline-block animate-spin mr-1.5">⏳</span>
            {{ isClosing ? 'Konfirmasi Tutup' : 'Konfirmasi Buka' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
