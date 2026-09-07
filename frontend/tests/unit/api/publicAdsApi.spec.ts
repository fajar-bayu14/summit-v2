import { describe, it, expect, vi, beforeEach } from 'vitest'
import { publicAdsApi } from '@/api/publicAds'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn()
  }
}))

describe('Public Ads API Client (Task 10.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should fetch active banners with position filter', async () => {
    const mockBanners = [
      {
        id: 1,
        judul: 'Promo Hari Kemerdekaan Diskon SIMAKSI 20%',
        posisi: 'home_top' as const,
        gambar_url: 'https://example.com/banner.jpg',
        is_active: true
      }
    ]

    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { status: 'success', data: mockBanners }
    })

    const res = await publicAdsApi.getBanners('home_top')

    expect(apiClient.get).toHaveBeenCalledWith('/ads/banners', { params: { posisi: 'home_top' } })
    expect(res.data).toHaveLength(1)
    expect(res.data[0].judul).toContain('Diskon SIMAKSI')
  })

  it('should record banner ad click', async () => {
    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { status: 'success', message: 'Klik banner berhasil dicatat.' }
    })

    const res = await publicAdsApi.recordClick(1)

    expect(apiClient.post).toHaveBeenCalledWith('/ads/banners/1/click')
    expect(res.status).toBe('success')
  })
})
