<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

withDefaults(
  defineProps<{
    title?: string
    description?: string
    actionLabel?: string
    icon?: any
  }>(),
  {
    title: 'Data Tidak Ditemukan',
    description: 'Belum ada data atau entri yang cocok dengan kriteria pencarian Anda.',
    actionLabel: '',
  }
)

const emit = defineEmits<{
  (e: 'action'): void
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center p-8 text-center max-w-sm mx-auto">
    <div class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800/80 flex items-center justify-center mb-4 text-muted-foreground shadow-xs">
      <component :is="icon || Inbox" class="h-7 w-7 stroke-[1.5]" />
    </div>
    <h4 class="text-base font-semibold text-foreground mb-1">{{ title }}</h4>
    <p class="text-sm text-muted-foreground leading-relaxed mb-4">{{ description }}</p>
    <Button
      v-if="actionLabel"
      variant="outline"
      size="sm"
      class="rounded-lg shadow-xs"
      @click="emit('action')"
    >
      {{ actionLabel }}
    </Button>
  </div>
</template>
