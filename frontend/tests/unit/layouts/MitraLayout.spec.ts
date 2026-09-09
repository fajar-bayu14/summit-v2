import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { navigationConfig } from '@/config/navigation'
import BasecampSwitcher from '@/components/mitra/BasecampSwitcher.vue'
import { useMitraStore } from '@/stores/mitra'
import type { Basecamp } from '@/types/basecamp'

describe('Mitra Navigation & Basecamp Switcher (Task 0.3)', () => {
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
      nama_basecamp: 'Basecamp Bambangan Utama',
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
        },
      },
    },
    {
      id: 2,
      mitra_id: 10,
      jalur_id: 101,
      nama_basecamp: 'Basecamp Dipajaya',
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
        },
      },
    },
  ]

  describe('navigationConfig for Mitra role', () => {
    it('should include all required 10 modules for Mitra role', () => {
      const currentRole = 'mitra'
      const mitraGroups = navigationConfig
        .filter(group => !group.roles || group.roles.includes(currentRole))
        .map(group => ({
          ...group,
          items: group.items.filter(item => !item.roles || item.roles.includes(currentRole)),
        }))
        .filter(group => group.items.length > 0)

      const allMitraPaths = mitraGroups.flatMap(g => g.items.map(i => i.to))

      expect(allMitraPaths).toContain('/mitra')
      expect(allMitraPaths).toContain('/mitra/orders')
      expect(allMitraPaths).toContain('/mitra/quotas')
      expect(allMitraPaths).toContain('/mitra/products')
      expect(allMitraPaths).toContain('/mitra/logbooks')
      expect(allMitraPaths).toContain('/mitra/wallet')
      expect(allMitraPaths).toContain('/mitra/refunds')
      expect(allMitraPaths).toContain('/mitra/staff')
      expect(allMitraPaths).toContain('/mitra/chat')
      expect(allMitraPaths).toContain('/mitra/settings')
    })
  })

  describe('BasecampSwitcher Component', () => {
    it('should render single basecamp static pill when only 1 basecamp exists', () => {
      const mitraStore = useMitraStore()
      mitraStore.setBasecamps([mockBasecamps[0]])

      const wrapper = mount(BasecampSwitcher)

      expect(wrapper.text()).toContain('Basecamp Bambangan Utama')
      expect(wrapper.text()).toContain('Gn. Slamet')
    })

    it('should render fallback when no basecamp is loaded', () => {
      const mitraStore = useMitraStore()
      mitraStore.setBasecamps([])

      const wrapper = mount(BasecampSwitcher)

      expect(wrapper.text()).toContain('Semua Basecamp')
    })

    it('should display active basecamp in dropdown trigger when multiple basecamps exist', () => {
      const mitraStore = useMitraStore()
      mitraStore.setBasecamps(mockBasecamps)

      const wrapper = mount(BasecampSwitcher)

      expect(wrapper.text()).toContain('Basecamp Bambangan Utama')
      expect(wrapper.find('button').exists()).toBe(true)
    })
  })
})
