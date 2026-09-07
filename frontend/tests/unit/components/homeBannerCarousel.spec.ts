import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import HomeBannerCarousel from '@/components/pendaki/HomeBannerCarousel.vue'
import { publicAdsApi } from '@/api/publicAds'

vi.mock('@/api/publicAds', () => ({
  publicAdsApi: {
    getBanners: vi.fn(),
    recordClick: vi.fn()
  }
}))

describe('HomeBannerCarousel Component (Task 10.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render carousel with loaded promotional banners', async () => {
    const mockBanners = [
      {
        id: 1,
        judul: 'Promo Gebyar Kemerdekaan Summit 2026',
        gambar_url: 'https://example.com/banner1.jpg',
        target_url: 'https://example.com/promo',
        posisi: 'home_top' as const,
        tipe: 'promo' as const,
        tanggal_mulai: '2026-08-01',
        tanggal_selesai: '2026-08-31',
        is_active: true
      }
    ]

    vi.mocked(publicAdsApi.getBanners).mockResolvedValueOnce({
      status: 'success',
      data: mockBanners
    })

    const wrapper = mount(HomeBannerCarousel)

    expect(publicAdsApi.getBanners).toHaveBeenCalledWith('home_top')

    await vi.dynamicImportSettled()

    expect(wrapper.text()).toContain('Promo Gebyar Kemerdekaan Summit 2026')
    expect(wrapper.text()).toContain('Promo Spesial')
  })
})
