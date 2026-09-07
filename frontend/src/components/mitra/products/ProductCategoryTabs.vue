<script setup lang="ts">
import {
  Layers,
  Ticket,
  Tent,
  Compass,
  Users,
  Utensils,
} from 'lucide-vue-next'

export interface CategoryTab {
  key: string
  label: string
  icon: any
}

const props = withDefaults(
  defineProps<{
    modelValue: string
    counts?: Record<string, number>
  }>(),
  {
    modelValue: 'all',
    counts: () => ({}),
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
}>()

const categories: CategoryTab[] = [
  { key: 'all', label: 'Semua', icon: Layers },
  { key: 'ticket', label: 'Tiket', icon: Ticket },
  { key: 'rental', label: 'Sewa Alat', icon: Tent },
  { key: 'opentrip', label: 'Open Trip', icon: Compass },
  { key: 'guide', label: 'Guide & Porter', icon: Users },
  { key: 'kuliner', label: 'Logistik & Kuliner', icon: Utensils },
]

function selectCategory(key: string) {
  emit('update:modelValue', key)
  emit('change', key)
}
</script>

<template>
  <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
    <button
      v-for="cat in categories"
      :key="cat.key"
      type="button"
      :class="[
        'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 whitespace-nowrap min-h-[44px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1E3A2B]/20',
        props.modelValue === cat.key
          ? 'bg-[#1E3A2B] text-white shadow-sm shadow-[#1E3A2B]/20'
          : 'bg-white border border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-stone-900',
      ]"
      @click="selectCategory(cat.key)"
    >
      <component :is="cat.icon" class="w-4 h-4" />
      <span>{{ cat.label }}</span>
      <span
        v-if="props.counts[cat.key] !== undefined"
        :class="[
          'px-2 py-0.5 rounded-full text-xs font-semibold',
          props.modelValue === cat.key
            ? 'bg-white/20 text-white'
            : 'bg-stone-100 text-stone-600',
        ]"
      >
        {{ props.counts[cat.key] }}
      </span>
    </button>
  </div>
</template>
