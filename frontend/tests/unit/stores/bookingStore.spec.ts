import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBookingStore } from '@/stores/booking'
import type { GunungItem, JalurDetail, BasecampMitraSummary } from '@/types/pendakiMountain'

describe('useBookingStore (Task 2.1)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('should initialize with default empty values', () => {
    const store = useBookingStore()
    expect(store.selectedMountain).toBeNull()
    expect(store.selectedTrail).toBeNull()
    expect(store.selectedBasecamp).toBeNull()
    expect(store.bookingStartDate).toBeNull()
    expect(store.isSelectionComplete).toBe(false)
    expect(store.climberCount).toBe(1)
  })

  it('should handle selecting mountain and resetting subordinate selections if mountain changes', () => {
    const store = useBookingStore()
    const mountain1: GunungItem = {
      id: 1,
      nama_gunung: 'Gunung Merbabu',
      tinggi_mdpl: 3145,
      lokasi: 'Jawa Tengah',
      status: 'aktif',
    }
    const trail1: JalurDetail = {
      id: 10,
      gunung_id: 1,
      nama_jalur: 'Jalur Selo',
      status: 'open',
    }

    store.selectMountain(mountain1)
    store.selectTrail(trail1)
    expect(store.selectedMountain?.nama_gunung).toBe('Gunung Merbabu')
    expect(store.selectedTrail?.nama_jalur).toBe('Jalur Selo')

    const mountain2: GunungItem = {
      id: 2,
      nama_gunung: 'Gunung Rinjani',
      tinggi_mdpl: 3726,
      lokasi: 'Lombok',
      status: 'aktif',
    }

    store.selectMountain(mountain2)
    expect(store.selectedMountain?.nama_gunung).toBe('Gunung Rinjani')
    expect(store.selectedTrail).toBeNull()
  })

  it('should compute isSelectionComplete when mountain, trail, and basecamp are selected', () => {
    const store = useBookingStore()
    const mountain: GunungItem = {
      id: 1,
      nama_gunung: 'Gunung Slamet',
      tinggi_mdpl: 3428,
      lokasi: 'Jawa Tengah',
      status: 'aktif',
    }
    const trail: JalurDetail = {
      id: 5,
      gunung_id: 1,
      nama_jalur: 'Jalur Bambangan',
      status: 'open',
    }
    const basecamp: BasecampMitraSummary = {
      id: 20,
      mitra_id: 2,
      jalur_id: 5,
      nama_basecamp: 'Basecamp Bambangan Purbalingga',
    }

    store.selectMountain(mountain)
    store.selectTrail(trail)
    store.selectBasecamp(basecamp)
    store.setBookingDates('2026-07-20', '2026-07-22')
    store.setClimberCount(4)

    expect(store.isSelectionComplete).toBe(true)
    expect(store.mountainName).toBe('Gunung Slamet')
    expect(store.trailName).toBe('Jalur Bambangan')
    expect(store.basecampName).toBe('Basecamp Bambangan Purbalingga')
    expect(store.bookingStartDate).toBe('2026-07-20')
    expect(store.bookingEndDate).toBe('2026-07-22')
    expect(store.climberCount).toBe(4)

    store.resetBookingFlow()
    expect(store.isSelectionComplete).toBe(false)
    expect(store.selectedMountain).toBeNull()
  })
})
