<script setup lang="ts">
import { computed } from 'vue'
import { ShieldCheck, ShieldAlert, Shield, Clock } from 'lucide-vue-next'
import { formatKycStatus } from '@/lib/formatters'
import type { KycStatus } from '@/types/pendakiKyc'

const props = withDefaults(
  defineProps<{
    status?: KycStatus | string | null
    showText?: boolean
    compact?: boolean
  }>(),
  {
    status: 'unverified',
    showText: true,
    compact: false,
  }
)

const kycInfo = computed(() => formatKycStatus(props.status))

const iconComponent = computed(() => {
  const norm = (props.status || '').toLowerCase()
  if (norm === 'verified' || norm === 'disetujui') return ShieldCheck
  if (norm === 'pending') return Clock
  if (norm === 'rejected' || norm === 'ditolak') return ShieldAlert
  return Shield
})
</script>

<template>
  <div
    class="inline-flex items-center gap-1.5 rounded-full font-medium transition-colors"
    :class="[
      kycInfo.badgeClass,
      compact ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs sm:text-sm',
    ]"
    :title="kycInfo.description"
  >
    <component :is="iconComponent" :class="compact ? 'w-3.5 h-3.5' : 'w-4 h-4'" />
    <span v-if="showText" class="font-semibold">{{ kycInfo.label }}</span>
  </div>
</template>
