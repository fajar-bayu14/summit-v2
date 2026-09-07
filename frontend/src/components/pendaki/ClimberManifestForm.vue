<script setup lang="ts">
import { computed, watch } from 'vue'
import {
  Users,
  User,
  ShieldCheck,
  Plus,
  Trash2,
  Copy,
  Phone,
  AlertCircle,
  IdCard,
  HeartHandshake,
} from 'lucide-vue-next'
import type { PesananAnggotaPayload } from '@/types/pendakiCheckout'

interface UserKycProfile {
  name?: string
  nik?: string
  telepon?: string | null
  telepon_darurat?: string | null
  hubungan_darurat?: string | null
}

const props = withDefaults(
  defineProps<{
    modelValue: PesananAnggotaPayload[]
    ticketQty?: number
    userProfile?: UserKycProfile | null
    disabled?: boolean
  }>(),
  {
    ticketQty: 1,
    userProfile: null,
    disabled: false,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: PesananAnggotaPayload[]): void
  (e: 'validity-change', isValid: boolean): void
}>()

// Initialize members if empty or sync with ticketQty
function initMembers() {
  const current = [...props.modelValue]
  const targetCount = Math.max(1, props.ticketQty)

  if (current.length === 0) {
    // Member 1 is Ketua
    current.push({
      nama_anggota: props.userProfile?.name || '',
      nik_identitas: props.userProfile?.nik || '',
      telepon: props.userProfile?.telepon || '',
      telepon_darurat: props.userProfile?.telepon_darurat || '',
      hubungan_darurat: props.userProfile?.hubungan_darurat || '',
    })
  }

  // Ensure count matches targetCount if ticketQty provided
  while (current.length < targetCount) {
    current.push({
      nama_anggota: '',
      nik_identitas: '',
      telepon: '',
      telepon_darurat: current[0]?.telepon_darurat || '',
      hubungan_darurat: current[0]?.hubungan_darurat || '',
    })
  }

  if (current.length !== props.modelValue.length) {
    emit('update:modelValue', current)
  }
}

watch(
  () => [props.ticketQty, props.userProfile],
  () => {
    initMembers()
  },
  { immediate: true }
)

function updateMember(index: number, field: keyof PesananAnggotaPayload, value: string) {
  const updated = props.modelValue.map((m, i) => (i === index ? { ...m, [field]: value } : m))
  emit('update:modelValue', updated)
}

function addMember() {
  const ketua = props.modelValue[0]
  const updated = [
    ...props.modelValue,
    {
      nama_anggota: '',
      nik_identitas: '',
      telepon: '',
      telepon_darurat: ketua?.telepon_darurat || '',
      hubungan_darurat: ketua?.hubungan_darurat || '',
    },
  ]
  emit('update:modelValue', updated)
}

function removeMember(index: number) {
  if (index === 0 || props.modelValue.length <= 1) return
  const updated = props.modelValue.filter((_, i) => i !== index)
  emit('update:modelValue', updated)
}

function copyEmergencyContactFromLeader(targetIndex: number) {
  const leader = props.modelValue[0]
  if (!leader) return

  const updated = props.modelValue.map((m, i) =>
    i === targetIndex
      ? {
          ...m,
          telepon_darurat: leader.telepon_darurat || '',
          hubungan_darurat: leader.hubungan_darurat || '',
        }
      : m
  )
  emit('update:modelValue', updated)
}

function copyEmergencyContactToAll() {
  const leader = props.modelValue[0]
  if (!leader) return

  const updated = props.modelValue.map((m, i) =>
    i === 0
      ? m
      : {
          ...m,
          telepon_darurat: leader.telepon_darurat || '',
          hubungan_darurat: leader.hubungan_darurat || '',
        }
  )
  emit('update:modelValue', updated)
}

// Validation logic
const validationErrors = computed(() => {
  const errors: Record<number, { nama?: string; nik?: string }> = {}

  props.modelValue.forEach((m, idx) => {
    const err: { nama?: string; nik?: string } = {}
    if (!m.nama_anggota.trim()) {
      err.nama = 'Nama lengkap wajib diisi sesuai KTP'
    }
    if (!m.nik_identitas.trim()) {
      err.nik = 'NIK identitas wajib diisi'
    } else if (!/^\d{16}$/.test(m.nik_identitas.trim())) {
      err.nik = 'NIK harus 16 digit angka'
    }

    if (Object.keys(err).length > 0) {
      errors[idx] = err
    }
  })

  return errors
})

const isValid = computed(() => {
  return (
    props.modelValue.length >= Math.max(1, props.ticketQty) &&
    Object.keys(validationErrors.value).length === 0
  )
})

watch(
  isValid,
  val => {
    emit('validity-change', val)
  },
  { immediate: true }
)
</script>

<template>
  <div class="space-y-6">
    <!-- Header info banner -->
    <div
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-forest-50/70 border border-forest-100"
    >
      <div class="flex items-start gap-3">
        <div class="p-2.5 rounded-lg bg-forest-600 text-white shrink-0 shadow-xs">
          <Users class="w-5 h-5" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-gray-900 text-base">Manifes Rombongan Pendaki</h3>
            <span
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
              :class="
                modelValue.length === ticketQty
                  ? 'bg-forest-100 text-forest-800'
                  : 'bg-amber-100 text-amber-800'
              "
            >
              {{ modelValue.length }} / {{ ticketQty }} Orang
            </span>
          </div>
          <p class="text-xs text-gray-600 mt-0.5">
            Setiap pendaki wajib didaftarkan dengan NIK KTP yang valid untuk asuransi dan izin SIMAKSI.
          </p>
        </div>
      </div>

      <div v-if="modelValue.length > 1" class="shrink-0">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-forest-700 bg-white border border-forest-200 hover:bg-forest-50 transition-colors shadow-xs"
          @click="copyEmergencyContactToAll"
        >
          <Copy class="w-3.5 h-3.5" />
          Salin Kontak Darurat ke Semua
        </button>
      </div>
    </div>

    <!-- Member cards list -->
    <div class="space-y-4">
      <div
        v-for="(member, idx) in modelValue"
        :key="idx"
        class="bg-white rounded-xl border transition-all duration-200 shadow-xs overflow-hidden"
        :class="[
          validationErrors[idx] ? 'border-red-300 ring-1 ring-red-100' : 'border-gray-200 hover:border-gray-300',
        ]"
      >
        <!-- Card Header -->
        <div
          class="flex items-center justify-between px-5 py-3.5 border-b"
          :class="idx === 0 ? 'bg-forest-50/50 border-forest-100' : 'bg-gray-50/70 border-gray-100'"
        >
          <div class="flex items-center gap-2.5">
            <div
              class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs"
              :class="idx === 0 ? 'bg-forest-600 text-white' : 'bg-gray-200 text-gray-700'"
            >
              {{ idx + 1 }}
            </div>
            <div>
              <span class="font-bold text-sm text-gray-900">
                {{ idx === 0 ? 'Ketua Rombongan' : `Anggota #${idx + 1}` }}
              </span>
              <span
                v-if="idx === 0"
                class="ml-2 inline-flex items-center gap-1 text-[11px] font-semibold text-forest-700 bg-forest-100/80 px-2 py-0.5 rounded-full"
              >
                <ShieldCheck class="w-3 h-3" />
                Penanggung Jawab
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="idx > 0"
              type="button"
              class="inline-flex items-center gap-1 px-2.5 py-1 text-xs text-forest-700 hover:text-forest-800 hover:bg-forest-50 rounded-md transition-colors"
              title="Salin kontak darurat dari ketua"
              @click="copyEmergencyContactFromLeader(idx)"
            >
              <Copy class="w-3 h-3" />
              <span class="hidden sm:inline">Salin Kontak Darurat</span>
            </button>
            <button
              v-if="idx > 0 && modelValue.length > ticketQty"
              type="button"
              class="p-1 text-gray-400 hover:text-red-600 rounded-md transition-colors"
              title="Hapus anggota"
              @click="removeMember(idx)"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Card Body / Form Inputs -->
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Nama Lengkap -->
          <div>
            <label :for="`nama-${idx}`" class="block text-xs font-semibold text-gray-700 mb-1">
              Nama Lengkap (sesuai KTP) <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <User class="w-4 h-4" />
              </div>
              <input
                :id="`nama-${idx}`"
                type="text"
                :value="member.nama_anggota"
                placeholder="cth: Fajar Bayu"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border focus:outline-none focus:ring-2 focus:ring-forest-500 transition-colors"
                :class="
                  validationErrors[idx]?.nama
                    ? 'border-red-300 bg-red-50/20 text-red-900 focus:border-red-500 focus:ring-red-200'
                    : 'border-gray-300 focus:border-forest-500'
                "
                :disabled="disabled"
                @input="updateMember(idx, 'nama_anggota', ($event.target as HTMLInputElement).value)"
              />
            </div>
            <p v-if="validationErrors[idx]?.nama" class="text-xs text-red-600 mt-1 flex items-center gap-1">
              <AlertCircle class="w-3.5 h-3.5 shrink-0" />
              {{ validationErrors[idx]?.nama }}
            </p>
          </div>

          <!-- NIK KTP -->
          <div>
            <label :for="`nik-${idx}`" class="block text-xs font-semibold text-gray-700 mb-1">
              Nomor Induk Kependudukan (NIK 16 Digit) <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <IdCard class="w-4 h-4" />
              </div>
              <input
                :id="`nik-${idx}`"
                type="text"
                inputmode="numeric"
                maxlength="16"
                :value="member.nik_identitas"
                placeholder="3301234567890001"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border font-mono tracking-wide focus:outline-none focus:ring-2 focus:ring-forest-500 transition-colors"
                :class="
                  validationErrors[idx]?.nik
                    ? 'border-red-300 bg-red-50/20 text-red-900 focus:border-red-500 focus:ring-red-200'
                    : 'border-gray-300 focus:border-forest-500'
                "
                :disabled="disabled"
                @input="
                  updateMember(
                    idx,
                    'nik_identitas',
                    ($event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 16)
                  )
                "
              />
            </div>
            <p v-if="validationErrors[idx]?.nik" class="text-xs text-red-600 mt-1 flex items-center gap-1">
              <AlertCircle class="w-3.5 h-3.5 shrink-0" />
              {{ validationErrors[idx]?.nik }}
            </p>
          </div>

          <!-- Telepon Pribadi -->
          <div>
            <label :for="`telp-${idx}`" class="block text-xs font-semibold text-gray-700 mb-1">
              Nomor WhatsApp / HP
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <Phone class="w-4 h-4" />
              </div>
              <input
                :id="`telp-${idx}`"
                type="tel"
                :value="member.telepon || ''"
                placeholder="081234567890"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-forest-500 focus:border-forest-500 transition-colors"
                :disabled="disabled"
                @input="updateMember(idx, 'telepon', ($event.target as HTMLInputElement).value)"
              />
            </div>
          </div>

          <!-- Kontak Darurat (Telepon & Hubungan) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div>
              <label :for="`telp-darurat-${idx}`" class="block text-xs font-semibold text-gray-700 mb-1">
                Telepon Darurat
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <Phone class="w-4 h-4" />
                </div>
                <input
                  :id="`telp-darurat-${idx}`"
                  type="tel"
                  :value="member.telepon_darurat || ''"
                  placeholder="081298765432"
                  class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-forest-500 focus:border-forest-500 transition-colors"
                  :disabled="disabled"
                  @input="updateMember(idx, 'telepon_darurat', ($event.target as HTMLInputElement).value)"
                />
              </div>
            </div>

            <div>
              <label :for="`hub-darurat-${idx}`" class="block text-xs font-semibold text-gray-700 mb-1">
                Hubungan Darurat
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <HeartHandshake class="w-4 h-4" />
                </div>
                <input
                  :id="`hub-darurat-${idx}`"
                  type="text"
                  :value="member.hubungan_darurat || ''"
                  placeholder="cth: Orang Tua, Saudara"
                  class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-forest-500 focus:border-forest-500 transition-colors"
                  :disabled="disabled"
                  @input="updateMember(idx, 'hubungan_darurat', ($event.target as HTMLInputElement).value)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add member manual trigger if needed -->
    <div v-if="modelValue.length < ticketQty" class="flex justify-center pt-2">
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm text-forest-700 bg-forest-50 hover:bg-forest-100 border border-forest-200 transition-colors shadow-xs"
        @click="addMember"
      >
        <Plus class="w-4 h-4" />
        Tambah Data Anggota Ke-{{ modelValue.length + 1 }}
      </button>
    </div>
  </div>
</template>
