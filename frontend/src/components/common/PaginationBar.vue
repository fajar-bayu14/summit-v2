<script setup lang="ts">
import { computed } from 'vue'
import type { PaginationMeta } from '@/types/api'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    meta: PaginationMeta
    perPageOptions?: number[]
  }>(),
  {
    perPageOptions: () => [10, 15, 25, 50],
  }
)

const emit = defineEmits<{
  (e: 'pageChange', page: number): void
  (e: 'perPageChange', perPage: number): void
}>()

const currentPage = computed(() => props.meta.current_page || 1)
const lastPage = computed(() => props.meta.last_page || 1)
const total = computed(() => props.meta.total || 0)
const from = computed(() => props.meta.from || (total.value > 0 ? (currentPage.value - 1) * props.meta.per_page + 1 : 0))
const to = computed(() => props.meta.to || Math.min(currentPage.value * props.meta.per_page, total.value))

function goToPage(page: number) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  emit('pageChange', page)
}

function handlePerPageChange(event: Event) {
  const target = event.target as HTMLSelectElement
  const perPage = parseInt(target.value, 10)
  emit('perPageChange', perPage)
}
</script>

<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 py-1 text-xs text-muted-foreground select-none">
    <!-- Records Counter & Per-Page Selector -->
    <div class="flex items-center gap-3">
      <span>
        Menampilkan <strong class="font-medium text-foreground">{{ from }}</strong> –
        <strong class="font-medium text-foreground">{{ to }}</strong> dari
        <strong class="font-medium text-foreground">{{ total }}</strong> data
      </span>

      <div class="flex items-center gap-1.5 ml-2">
        <label for="per-page-select" class="text-xs">Baris:</label>
        <select
          id="per-page-select"
          :value="meta.per_page"
          class="h-7 px-2 bg-card border border-slate-200 dark:border-slate-800 rounded-md text-xs font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
          @change="handlePerPageChange"
        >
          <option v-for="opt in perPageOptions" :key="opt" :value="opt">
            {{ opt }}
          </option>
        </select>
      </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex items-center gap-1">
      <Button
        variant="outline"
        size="icon"
        class="h-8 w-8 rounded-lg"
        :disabled="currentPage <= 1"
        aria-label="Halaman Pertama"
        @click="goToPage(1)"
      >
        <ChevronsLeft class="h-4 w-4" />
      </Button>

      <Button
        variant="outline"
        size="icon"
        class="h-8 w-8 rounded-lg"
        :disabled="currentPage <= 1"
        aria-label="Halaman Sebelumnya"
        @click="goToPage(currentPage - 1)"
      >
        <ChevronLeft class="h-4 w-4" />
      </Button>

      <span class="px-2.5 py-1 font-medium text-foreground">
        {{ currentPage }} / {{ lastPage }}
      </span>

      <Button
        variant="outline"
        size="icon"
        class="h-8 w-8 rounded-lg"
        :disabled="currentPage >= lastPage"
        aria-label="Halaman Selanjutnya"
        @click="goToPage(currentPage + 1)"
      >
        <ChevronRight class="h-4 w-4" />
      </Button>

      <Button
        variant="outline"
        size="icon"
        class="h-8 w-8 rounded-lg"
        :disabled="currentPage >= lastPage"
        aria-label="Halaman Terakhir"
        @click="goToPage(lastPage)"
      >
        <ChevronsRight class="h-4 w-4" />
      </Button>
    </div>
  </div>
</template>
