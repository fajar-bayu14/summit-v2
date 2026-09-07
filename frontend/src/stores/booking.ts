import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type {
  GunungItem,
  JalurDetail,
  BasecampMitraSummary,
} from '@/types/pendakiMountain'

export const useBookingStore = defineStore('booking', () => {
  const selectedMountain = ref<GunungItem | null>(null)
  const selectedTrail = ref<JalurDetail | null>(null)
  const selectedBasecamp = ref<BasecampMitraSummary | null>(null)
  const bookingStartDate = ref<string | null>(null)
  const bookingEndDate = ref<string | null>(null)
  const climberCount = ref<number>(1)

  // Getters
  const isSelectionComplete = computed(() => {
    return (
      selectedMountain.value !== null &&
      selectedTrail.value !== null &&
      selectedBasecamp.value !== null
    )
  })

  const mountainName = computed(() => selectedMountain.value?.nama_gunung || '')
  const trailName = computed(() => selectedTrail.value?.nama_jalur || '')
  const basecampName = computed(() => selectedBasecamp.value?.nama_basecamp || '')

  // Actions
  function selectMountain(mountain: GunungItem | null) {
    selectedMountain.value = mountain
    if (selectedTrail.value && selectedTrail.value.gunung_id !== mountain?.id) {
      selectedTrail.value = null
      selectedBasecamp.value = null
    }
  }

  function selectTrail(trail: JalurDetail | null) {
    selectedTrail.value = trail
    if (selectedBasecamp.value && selectedBasecamp.value.jalur_id !== trail?.id) {
      selectedBasecamp.value = null
    }
  }

  function selectBasecamp(basecamp: BasecampMitraSummary | null) {
    selectedBasecamp.value = basecamp
  }

  function setBookingDates(startDate: string | null, endDate?: string | null) {
    bookingStartDate.value = startDate
    bookingEndDate.value = endDate || startDate
  }

  function setClimberCount(count: number) {
    climberCount.value = Math.max(1, count)
  }

  function resetBookingFlow() {
    selectedMountain.value = null
    selectedTrail.value = null
    selectedBasecamp.value = null
    bookingStartDate.value = null
    bookingEndDate.value = null
    climberCount.value = 1
  }

  return {
    selectedMountain,
    selectedTrail,
    selectedBasecamp,
    bookingStartDate,
    bookingEndDate,
    climberCount,
    isSelectionComplete,
    mountainName,
    trailName,
    basecampName,
    selectMountain,
    selectTrail,
    selectBasecamp,
    setBookingDates,
    setClimberCount,
    resetBookingFlow,
  }
})
