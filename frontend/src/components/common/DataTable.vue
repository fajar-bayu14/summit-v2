<script setup lang="ts">
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import SkeletonTable from './SkeletonTable.vue'
import EmptyState from './EmptyState.vue'
import PaginationBar from './PaginationBar.vue'
import type { PaginationMeta } from '@/types/api'
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'

export interface ColumnDef<T = any> {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
  width?: string
  cellClass?: string
  headerClass?: string
  formatter?: (row: T) => any
}

const props = withDefaults(
  defineProps<{
    columns: ColumnDef[]
    data: any[]
    loading?: boolean
    emptyTitle?: string
    emptyDescription?: string
    emptyIcon?: any
    emptyActionLabel?: string
    sortBy?: string
    sortOrder?: 'asc' | 'desc'
    pagination?: PaginationMeta | null
    perPageOptions?: number[]
  }>(),
  {
    loading: false,
    emptyTitle: 'Tidak ada data',
    emptyDescription: 'Belum ada data yang tersedia untuk ditampilkan.',
    emptyIcon: undefined,
    emptyActionLabel: '',
    sortBy: '',
    sortOrder: 'asc',
    pagination: null,
    perPageOptions: () => [10, 15, 25, 50],
  }
)

const emit = defineEmits<{
  (e: 'sort', key: string): void
  (e: 'pageChange', page: number): void
  (e: 'perPageChange', perPage: number): void
  (e: 'emptyAction'): void
}>()

function handleSort(col: ColumnDef) {
  if (!col.sortable) return
  emit('sort', col.key)
}

function getAlignmentClass(align?: 'left' | 'center' | 'right') {
  if (align === 'center') return 'text-center'
  if (align === 'right') return 'text-right'
  return 'text-left'
}
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-card overflow-hidden shadow-xs">
      <div class="relative w-full overflow-x-auto">
        <Table>
          <TableHeader class="bg-slate-50/75 dark:bg-slate-900/50">
            <TableRow>
              <TableHead
                v-for="col in columns"
                :key="col.key"
                :class="[
                  getAlignmentClass(col.align),
                  col.sortable ? 'cursor-pointer select-none hover:text-foreground transition-colors' : '',
                  col.headerClass || '',
                ]"
                :style="col.width ? { width: col.width } : undefined"
                @click="handleSort(col)"
              >
                <div class="inline-flex items-center gap-1.5 font-semibold text-xs tracking-wider uppercase text-muted-foreground">
                  <span>{{ col.label }}</span>
                  <template v-if="col.sortable">
                    <ArrowUp v-if="sortBy === col.key && sortOrder === 'asc'" class="h-3.5 w-3.5 text-primary" />
                    <ArrowDown v-else-if="sortBy === col.key && sortOrder === 'desc'" class="h-3.5 w-3.5 text-primary" />
                    <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-30 group-hover:opacity-75" />
                  </template>
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <!-- Loading State -->
            <template v-if="loading">
              <SkeletonTable :columns="columns.length" :rows="5" />
            </template>

            <!-- Empty State -->
            <template v-else-if="!data || data.length === 0">
              <TableRow>
                <TableCell :colspan="columns.length" class="py-12 text-center">
                  <slot name="empty">
                    <EmptyState
                      :title="emptyTitle"
                      :description="emptyDescription"
                      :icon="emptyIcon"
                      :action-label="emptyActionLabel"
                      @action="emit('emptyAction')"
                    />
                  </slot>
                </TableCell>
              </TableRow>
            </template>

            <!-- Data Rows -->
            <template v-else>
              <TableRow
                v-for="(row, rowIndex) in data"
                :key="row.id || rowIndex"
                class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition-colors"
              >
                <TableCell
                  v-for="col in columns"
                  :key="col.key"
                  :class="[getAlignmentClass(col.align), col.cellClass || '']"
                >
                  <!-- Custom Slot for Column Key (Supports both #cell(key) and #cell-key) -->
                  <slot :name="`cell(${col.key})`" :row="row" :item="row" :value="row[col.key]" :index="rowIndex">
                    <slot :name="`cell-${col.key}`" :row="row" :item="row" :value="row[col.key]" :index="rowIndex">
                      <template v-if="col.formatter">
                        {{ col.formatter(row) }}
                      </template>
                      <template v-else>
                        {{ row[col.key] !== undefined && row[col.key] !== null ? row[col.key] : '-' }}
                      </template>
                    </slot>
                  </slot>
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- Pagination Control -->
    <PaginationBar
      v-if="pagination && pagination.total > 0"
      :meta="pagination"
      :per-page-options="perPageOptions"
      @page-change="emit('pageChange', $event)"
      @per-page-change="emit('perPageChange', $event)"
    />
  </div>
</template>
