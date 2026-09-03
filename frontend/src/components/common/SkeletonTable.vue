<script setup lang="ts">
import { TableCell, TableRow } from '@/components/ui/table'

withDefaults(
  defineProps<{
    columns?: number
    rows?: number
  }>(),
  {
    columns: 5,
    rows: 5,
  }
)
</script>

<template>
  <TableRow v-for="r in rows" :key="r" class="animate-pulse">
    <TableCell v-for="c in columns" :key="c" class="py-4">
      <div
        class="h-4 bg-slate-200 dark:bg-slate-800 rounded-md"
        :style="{ width: `${Math.floor(Math.random() * (90 - 45 + 1)) + 45}%` }"
      ></div>
    </TableCell>
  </TableRow>
</template>
