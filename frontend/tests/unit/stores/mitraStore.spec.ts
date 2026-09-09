import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMitraStore } from '@/stores/mitra'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/lib/axios'
import type { Basecamp } from '@/types/basecamp'

describe('Pinia Mitra & Basecamp Context Store (Task 0.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.restoreAllMocks()
  })

  const mockBasecamps: Basecamp[] = [
    {
      id: 1,
      mitra_id: 10,
      jalur_id: 100,
      nama_basecamp: 'Basecamp Bambangan Slamet',
      latitude: '-7.241',
      longitude: '109.214',
      jam_operasional: '24 Jam',
      jalur: {
        id: 100,
        gunung_id: 50,
        nama_jalur: 'Jalur Bambangan',
        gunung: {
          id: 50,
          nama_gunung: 'Gunung Slamet',
          tinggi_mdpl: 3428,
        },
      },
    },
    {
      id: 2,
      mitra_id: 10,
      jalur_id: 101,
      nama_basecamp: 'Basecamp Dipajaya Slamet',
      latitude: '-7.250',
      longitude: '109.220',
      jam_operasional: '06:00 - 20:00',
      jalur: {
        id: 101,
        gunung_id: 50,
        nama_jalur: 'Jalur Dipajaya',
        gunung: {
          id: 50,
          nama_gunung: 'Gunung Slamet',
          tinggi_mdpl: 3428,
        },
      },
    },
  ]

  it('should initialize with empty basecamps and null activeBasecamp', () => {
    const mitraStore = useMitraStore()

    expect(mitraStore.basecamps).toEqual([])
    expect(mitraStore.activeBasecamp).toBeNull()
    expect(mitraStore.hasMultipleBasecamps).toBe(false)
  })

  it('should set basecamps and automatically select the first basecamp as active', () => {
    const mitraStore = useMitraStore()

    mitraStore.setBasecamps(mockBasecamps)

    expect(mitraStore.basecamps.length).toBe(2)
    expect(mitraStore.activeBasecampId).toBe(1)
    expect(mitraStore.activeBasecamp?.nama_basecamp).toBe('Basecamp Bambangan Slamet')
    expect(mitraStore.activeGunung?.nama_gunung).toBe('Gunung Slamet')
    expect(mitraStore.activeJalur?.nama_jalur).toBe('Jalur Bambangan')
    expect(mitraStore.hasMultipleBasecamps).toBe(true)
    expect(localStorage.getItem('active_basecamp_id')).toBe('1')
  })

  it('should allow switching the active basecamp and persist to localStorage', () => {
    const mitraStore = useMitraStore()
    mitraStore.setBasecamps(mockBasecamps)

    mitraStore.setActiveBasecamp(2)

    expect(mitraStore.activeBasecampId).toBe(2)
    expect(mitraStore.activeBasecamp?.nama_basecamp).toBe('Basecamp Dipajaya Slamet')
    expect(localStorage.getItem('active_basecamp_id')).toBe('2')
  })

  it('should restore previous active_basecamp_id from localStorage if valid', () => {
    localStorage.setItem('active_basecamp_id', '2')

    const mitraStore = useMitraStore()
    mitraStore.setBasecamps(mockBasecamps)

    expect(mitraStore.activeBasecampId).toBe(2)
    expect(mitraStore.activeBasecamp?.id).toBe(2)
  })

  it('should fetch basecamps from API and populate store', async () => {
    vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Loaded',
        data: mockBasecamps,
      },
    } as any)

    const mitraStore = useMitraStore()
    const result = await mitraStore.fetchBasecamps()

    expect(result.length).toBe(2)
    expect(mitraStore.basecamps.length).toBe(2)
    expect(mitraStore.activeBasecampId).toBe(1)
  })

  it('should reset store and clean storage on reset() or logout', () => {
    const mitraStore = useMitraStore()
    mitraStore.setBasecamps(mockBasecamps)
    mitraStore.setActiveBasecamp(2)

    mitraStore.reset()

    expect(mitraStore.basecamps).toEqual([])
    expect(mitraStore.activeBasecampId).toBeNull()
    expect(mitraStore.activeBasecamp).toBeNull()
    expect(localStorage.getItem('active_basecamp_id')).toBeNull()
  })

  it('should hydrate mitra basecamps automatically on auth profile sync', () => {
    const authStore = useAuthStore()
    const mitraStore = useMitraStore()

    const mockMitraUser: any = {
      id: 1,
      name: 'Mitra Slamet',
      email: 'mitra@slamet.id',
      role: 'mitra',
      mitra: {
        id: 10,
        nama_pemilik: 'Pak Slamet',
        telepon: '081234567890',
        basecamps: mockBasecamps,
      },
    }

    authStore.user = mockMitraUser
    // Invoke sync
    mitraStore.setBasecamps(mockMitraUser.mitra.basecamps)

    expect(mitraStore.basecamps.length).toBe(2)
    expect(mitraStore.activeBasecamp?.nama_basecamp).toBe('Basecamp Bambangan Slamet')
  })
})
