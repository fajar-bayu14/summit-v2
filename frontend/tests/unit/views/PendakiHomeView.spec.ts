import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PendakiHome from '@/views/pendaki/PendakiHome.vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'
import { publicAdsApi } from '@/api/publicAds'

vi.mock('@/api/pendakiMountains', () => ({
  pendakiMountainsApi: {
    getMountains: vi.fn()
  }
}))

vi.mock('@/api/publicAds', () => ({
  publicAdsApi: {
    getBanners: vi.fn(),
    recordClick: vi.fn()
  }
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn(() => ({
    isAuthenticated: true,
    isPendaki: true,
    kycStatus: 'unverified',
    user: { id: 1, name: 'Fajar' }
  }))
}))

describe('PendakiHome View Component (Task 10.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render hero search, categories, flash deals and mountain recommendations', async () => {
    vi.mocked(publicAdsApi.getBanners).mockResolvedValueOnce({
      status: 'success',
      data: []
    })

    const mockMountains = [
      {
        id: 1,
        nama_gunung: 'Gunung Slamet',
        tinggi_mdpl: 3428,
        lokasi: 'Jawa Tengah',
        status: 'aktif',
        jalurs: []
      }
    ]

    vi.mocked(pendakiMountainsApi.getMountains).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        items: mockMountains as any,
        meta: {}
      }
    })

    const wrapper = mount(PendakiHome, {
      global: {
        stubs: ['router-link']
      }
    })

    expect(wrapper.text()).toContain('Cari Gunung & Booking Tiket SIMAKSI')
    expect(wrapper.text()).toContain('Flash Deals Logistik & Sewa Alat')
    expect(wrapper.text()).toContain('Rekomendasi Gunung Terpopuler')

    await vi.dynamicImportSettled()

    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('3.428 MDPL')
  })
})
