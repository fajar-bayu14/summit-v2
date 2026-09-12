import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/lib/axios'
import type { Basecamp } from '@/types/basecamp'
import type { ApiResponse } from '@/types/api'
import { getApiErrorMessage } from '@/lib/axios'
import mitraDashboardApi from '@/api/mitraDashboard'

function getInitialActiveBasecampId(): number | null {
  try {
    const raw = localStorage.getItem('active_basecamp_id')
    return raw ? parseInt(raw, 10) : null
  } catch {
    return null
  }
}

export const useMitraStore = defineStore('mitra', () => {
  const basecamps = ref<Basecamp[]>([])
  const activeBasecampId = ref<number | null>(getInitialActiveBasecampId())
  const incomingOrdersCount = ref<number>(0)
  const isLoading = ref<boolean>(false)
  const error = ref<string | null>(null)

  const activeBasecamp = computed<Basecamp | null>(() => {
    if (!basecamps.value.length) return null
    if (activeBasecampId.value !== null) {
      const found = basecamps.value.find(b => b.id === activeBasecampId.value)
      if (found) return found
    }
    return basecamps.value[0] || null
  })

  const hasMultipleBasecamps = computed<boolean>(() => basecamps.value.length > 1)
  const activeJalur = computed(() => activeBasecamp.value?.jalur || null)
  const activeGunung = computed(() => activeBasecamp.value?.jalur?.gunung || null)

  function setActiveBasecamp(id: number | string): void {
    const numericId = typeof id === 'string' ? parseInt(id, 10) : id
    activeBasecampId.value = numericId
    localStorage.setItem('active_basecamp_id', String(numericId))
  }

  function setBasecamps(items: Basecamp[]): void {
    basecamps.value = items || []
    
    if (items && items.length > 0) {
      const storedId = getInitialActiveBasecampId()
      const exists = storedId ? items.some(b => b.id === storedId) : false
      
      if (exists && storedId !== null) {
        activeBasecampId.value = storedId
      } else {
        setActiveBasecamp(items[0].id)
      }
    } else {
      activeBasecampId.value = null
      localStorage.removeItem('active_basecamp_id')
    }
  }

  async function fetchBasecamps(): Promise<Basecamp[]> {
    isLoading.value = true
    error.value = null

    try {
      const response = await apiClient.get<ApiResponse<Basecamp[]>>('/mitra/basecamps')
      const items = response.data?.data || []
      setBasecamps(items)
      return items
    } catch (err) {
      error.value = getApiErrorMessage(err, 'Gagal memuat daftar basecamp mitra.')
      return []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchIncomingOrdersCount(): Promise<number> {
    try {
      const response = await mitraDashboardApi.getSummary(activeBasecampId.value)
      const count = response.data?.action_queues?.pesanan_paid_count ?? 0
      incomingOrdersCount.value = count
      return count
    } catch {
      return 0
    }
  }

  function reset(): void {
    basecamps.value = []
    activeBasecampId.value = null
    incomingOrdersCount.value = 0
    error.value = null
    isLoading.value = false
    localStorage.removeItem('active_basecamp_id')
  }

  return {
    basecamps,
    activeBasecampId,
    incomingOrdersCount,
    isLoading,
    error,
    activeBasecamp,
    hasMultipleBasecamps,
    activeJalur,
    activeGunung,
    setActiveBasecamp,
    setBasecamps,
    fetchBasecamps,
    fetchIncomingOrdersCount,
    reset,
  }
})
