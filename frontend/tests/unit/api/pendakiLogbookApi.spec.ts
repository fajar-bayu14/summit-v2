import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('pendakiLogbookApi (Task 7.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call submitLogbook with multipart formData', async () => {
    const invoice = 'INV/20260710/ABC12'
    const formData = new FormData()
    formData.append('catatan_pendaki', 'Puncak cerah')

    const mockResponse = {
      status: 'success',
      data: {
        id: 1,
        invoice,
        status_validasi: 'pending',
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: mockResponse })

    const result = await pendakiLogbookApi.submitLogbook(invoice, formData)

    expect(apiClient.post).toHaveBeenCalledWith(
      `/orders/${encodeURIComponent(invoice)}/logbook`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )
    expect(result).toEqual(mockResponse)
  })

  it('should call getLogbook with encoded invoice', async () => {
    const invoice = 'INV/20260710/ABC12'
    const mockResponse = {
      status: 'success',
      data: {
        id: 1,
        invoice,
        gunung_nama: 'Gunung Slamet',
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockResponse })

    const result = await pendakiLogbookApi.getLogbook(invoice)

    expect(apiClient.get).toHaveBeenCalledWith(`/orders/${encodeURIComponent(invoice)}/logbook`)
    expect(result).toEqual(mockResponse)
  })

  it('should call getBadges', async () => {
    const mockBadges = {
      status: 'success',
      data: [
        {
          id: 1,
          badge_title: 'Penakluk Gunung Slamet',
          gunung_nama: 'Gunung Slamet',
          tinggi_mdpl: 3428,
        },
      ],
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockBadges })

    const result = await pendakiLogbookApi.getBadges()

    expect(apiClient.get).toHaveBeenCalledWith('/pendaki/badges')
    expect(result).toEqual(mockBadges)
  })
})
