<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    title: string
    value: string | number
    icon?: any
    change?: string
    trend?: 'up' | 'down' | 'neutral'
    description?: string
    iconClass?: string
  }>(),
  {
    icon: undefined,
    change: '',
    trend: 'neutral',
    description: '',
    iconClass: 'bg-primary/10 text-primary',
  }
)

const trendColorClass = computed(() => {
  if (props.trend === 'up') return 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10'
  if (props.trend === 'down') return 'text-red-600 dark:text-red-400 bg-red-500/10'
  return 'text-slate-600 dark:text-slate-400 bg-slate-500/10'
})
</script>

<template>
  <Card class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs hover:shadow-md transition-all duration-200">
    <CardContent class="p-5">
      <div class="flex items-center justify-between gap-2 mb-3">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ title }}</span>
        <div v-if="icon" class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" :class="iconClass">
          <component :is="icon" class="h-5 w-5 stroke-[2]" />
        </div>
      </div>

      <div class="flex items-baseline justify-between gap-2">
        <h3 class="text-2xl font-bold tracking-tight text-foreground font-mono">{{ value }}</h3>

        <div
          v-if="change"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold select-none"
          :class="trendColorClass"
        >
          <TrendingUp v-if="trend === 'up'" class="h-3 w-3" />
          <TrendingDown v-else-if="trend === 'down'" class="h-3 w-3" />
          <Minus v-else class="h-3 w-3" />
          <span>{{ change }}</span>
        </div>
      </div>

      <p v-if="description" class="text-xs text-muted-foreground mt-2 leading-relaxed">
        {{ description }}
      </p>
    </CardContent>
  </Card>
</template>
