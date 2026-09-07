<script setup lang="ts">
import { computed } from 'vue'
import {
  CreditCard,
  QrCode,
  Mountain,
  CheckCircle2,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    status: string
    checkInTime?: string | null
    checkOutTime?: string | null
    mountainName?: string
    trailName?: string
  }>(),
  {
    status: 'paid',
    checkInTime: null,
    checkOutTime: null,
    mountainName: 'Gunung',
    trailName: 'Jalur Resmi',
  }
)

const currentStepIndex = computed(() => {
  const s = props.status.toLowerCase()
  if (s === 'pending') return 0
  if (s === 'paid' || s === 'confirmed') return 1
  if (s === 'on_going' || s === 'active') return 2
  if (s === 'completed') return 3
  return 0
})

const steps = [
  {
    id: 1,
    title: 'Booking & Pembayaran',
    desc: 'SIMAKSI terbit',
    icon: CreditCard,
  },
  {
    id: 2,
    title: 'Check-In Basecamp',
    desc: 'Scan QR & cek barang',
    icon: QrCode,
  },
  {
    id: 3,
    title: 'Di Jalur Pendakian',
    desc: 'Summit & eksplorasi',
    icon: Mountain,
  },
  {
    id: 4,
    title: 'Check-Out & Logbook',
    desc: 'Setor sampah & sertifikat',
    icon: CheckCircle2,
  },
]
</script>

<template>
  <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 p-6 shadow-xs space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
            Pelacakan Status Pendakian (Live Tracker)
          </h3>
          <span
            v-if="status === 'on_going'"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 animate-pulse"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
            Sedang Mendaki
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          {{ mountainName }} • {{ trailName }}
        </p>
      </div>

      <div class="text-xs text-slate-500 sm:text-right">
        <span v-if="checkInTime" class="block font-medium">
          Check-in: {{ checkInTime }}
        </span>
        <span v-if="checkOutTime" class="block font-medium text-emerald-600">
          Check-out: {{ checkOutTime }}
        </span>
      </div>
    </div>

    <!-- Stepper Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 relative">
      <div
        v-for="(step, idx) in steps"
        :key="step.id"
        class="relative flex flex-col items-center text-center space-y-2"
      >
        <!-- Connector line for desktop -->
        <div
          v-if="idx < steps.length - 1"
          class="hidden md:block absolute top-5 left-1/2 w-full h-0.5 transition-colors"
          :class="idx < currentStepIndex ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'"
        />

        <!-- Step Icon Circle -->
        <div
          class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-bold relative z-10 transition-all duration-200 shadow-xs"
          :class="[
            idx < currentStepIndex
              ? 'bg-emerald-600 text-white shadow-emerald-200 dark:shadow-none'
              : idx === currentStepIndex
                ? 'bg-emerald-700 text-white ring-4 ring-emerald-100 dark:ring-emerald-950'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-400',
          ]"
        >
          <component :is="step.icon" class="w-4 h-4" />
        </div>

        <!-- Step Labels -->
        <div class="space-y-0.5">
          <h4
            class="text-xs font-bold"
            :class="idx <= currentStepIndex ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400'"
          >
            {{ step.title }}
          </h4>
          <p class="text-[11px] text-slate-500 leading-tight">
            {{ step.desc }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
