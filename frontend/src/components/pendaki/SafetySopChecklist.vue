<script setup lang="ts">
import { ref } from 'vue'
import {
  ShieldAlert,
  Trash2,
  Backpack,
  Compass,
  FileCheck2,
  ChevronDown,
  CheckCircle2,
  AlertTriangle,
} from 'lucide-vue-next'

withDefaults(
  defineProps<{
    modelValue: boolean
    mountainName?: string
    trailName?: string
    disabled?: boolean
  }>(),
  {
    mountainName: 'Gunung',
    trailName: 'Jalur Resmi',
    disabled: false,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const expandedItems = ref<Record<number, boolean>>({
  0: true,
  1: false,
  2: false,
  3: false,
})

function toggleAccordion(index: number) {
  expandedItems.value[index] = !expandedItems.value[index]
}

const sopRules = [
  {
    id: 1,
    icon: Trash2,
    title: 'Zero Waste Policy (Bawa Turun Sampah)',
    summary: 'Dilarang meninggalkan sampah plastik, botol, atau sisa logistik di kawasan konservasi.',
    details:
      'Semua sampah non-organik wajib dibawa turun kembali ke Basecamp untuk diperiksa dan dicocokkan dengan logbook logistik awal pendakian saat proses check-out.',
  },
  {
    id: 2,
    icon: Backpack,
    title: 'Standar Perlengkapan Minimum',
    summary: 'Wajib membawa tenda, sleeping bag, matras, headlamp, dan pakaian hangat.',
    details:
      'Setiap tim wajib membawa perlengkapan bivak/tenda yang layak, pakaian tahan angin/hujan, P3K standar gunung, serta cadangan logistik dan air minimal 2x24 jam.',
  },
  {
    id: 3,
    icon: Compass,
    title: 'Kepatuhan Jalur & Jam Operasional',
    summary: 'Hanya mendaki melalui jalur resmi dan mematuhi batas waktu check-in basecamp.',
    details:
      'Dilarang keras memotong jalur atau membuka rute liar yang membahayakan ekosistem dan keselamatan tim. Wajib mematuhi instruksi buka-tutup jalur dari petugas basecamp.',
  },
  {
    id: 4,
    icon: FileCheck2,
    title: 'Keabsahan Manifes & Asuransi SAR',
    summary: 'Data NIK manifes mengikat untuk aktivasi polis asuransi dan izin SIMAKSI resmi.',
    details:
      'Ketua rombongan menjamin seluruh data identitas anggota adalah benar dan siap bertanggung jawab penuh atas keselamatan seluruh anggota tim selama berada di kawasan pendakian.',
  },
]
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
    <!-- Header -->
    <div class="p-5 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-b border-gray-100 flex items-start gap-3.5">
      <div class="p-2.5 rounded-xl bg-amber-500 text-white shrink-0 shadow-xs">
        <ShieldAlert class="w-5 h-5" />
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-gray-900 text-base">Standar Operasional Prosedur (SOP) Pendakian</h3>
          <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900">
            Wajib
          </span>
        </div>
        <p class="text-xs text-gray-600 mt-0.5">
          Ketentuan keselamatan pendakian {{ mountainName }} melalui {{ trailName }}.
        </p>
      </div>
    </div>

    <!-- SOP Items List Accordion -->
    <div class="p-5 space-y-3">
      <div
        v-for="(rule, idx) in sopRules"
        :key="rule.id"
        class="border border-gray-100 rounded-xl overflow-hidden transition-colors"
        :class="expandedItems[idx] ? 'bg-gray-50/70 border-gray-200' : 'bg-white hover:bg-gray-50/50'"
      >
        <button
          type="button"
          class="w-full px-4 py-3 flex items-center justify-between gap-3 text-left transition-colors"
          @click="toggleAccordion(idx)"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-forest-50 text-forest-700 shrink-0">
              <component :is="rule.icon" class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-bold text-xs sm:text-sm text-gray-900">
                {{ rule.id }}. {{ rule.title }}
              </h4>
              <p class="text-xs text-gray-500 line-clamp-1">
                {{ rule.summary }}
              </p>
            </div>
          </div>
          <ChevronDown
            class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200"
            :class="{ 'rotate-180': expandedItems[idx] }"
          />
        </button>

        <div v-if="expandedItems[idx]" class="px-4 pb-3.5 pt-1 text-xs text-gray-600 border-t border-gray-100/60 leading-relaxed">
          {{ rule.details }}
        </div>
      </div>

      <!-- Agreement Checkbox -->
      <div
        class="mt-5 p-4 rounded-xl border transition-all duration-200"
        :class="
          modelValue
            ? 'bg-forest-50/60 border-forest-200 ring-1 ring-forest-200'
            : 'bg-amber-50/40 border-amber-200'
        "
      >
        <label class="flex items-start gap-3 cursor-pointer select-none">
          <input
            type="checkbox"
            :checked="modelValue"
            :disabled="disabled"
            class="mt-1 h-4 w-4 rounded border-gray-300 text-forest-600 focus:ring-forest-500 cursor-pointer disabled:opacity-50"
            @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
          />
          <div class="text-xs">
            <span class="font-bold text-gray-900 block mb-0.5">
              Pernyataan Persetujuan SOP & Tanggung Jawab
            </span>
            <p class="text-gray-600 leading-relaxed">
              Saya selaku ketua rombongan menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan keselamatan, kebijakan Zero Waste, dan data manifes di atas untuk pendakian {{ mountainName }}.
            </p>
          </div>
        </label>
      </div>

      <!-- Warning if unchecked -->
      <div v-if="!modelValue" class="flex items-center gap-1.5 text-xs text-amber-700 px-1 pt-1">
        <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
        <span>Anda wajib menyetujui SOP sebelum dapat memproses pembayaran pesanan.</span>
      </div>
      <div v-else class="flex items-center gap-1.5 text-xs text-forest-700 px-1 pt-1">
        <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
        <span>Persetujuan SOP terverifikasi. Siap melanjutkan transaksi.</span>
      </div>
    </div>
  </div>
</template>
