import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiMountainsApi } from '@/api/pendakiMountains'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
  },
}))

describe('pendakiMountainsApi (Task 2.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call getMountains with query parameters', async () => {
    const mockData = {
      status: 'success',
      message: 'Mountains fetched successfully.',
      data: [
        {
          id: 1,
          nama_gunung: 'Gunung Merbabu',
          tinggi_mdpl: 3145,
          lokasi: 'Boyolali, Jawa Tengah',
          status: 'aktif',
        },
      ],
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockData })

    const result = await pendakiMountainsApi.getMountains({ search: 'Merbabu', page: 1 })

    expect(apiClient.get).toHaveBeenCalledWith('/mountains', {
      params: { search: 'Merbabu', page: 1 },
    })
    expect(result).toEqual(mockData)
  })

  it('should call getMountainById with mountain ID', async () => {
    const mockDetail = {
      status: 'success',
      message: 'Mountain details fetched successfully.',
      data: {
        id: 1,
        nama_gunung: 'Gunung Merbabu',
        tinggi_mdpl: 3145,
        lokasi: 'Boyolali, Jawa Tengah',
        status: 'aktif',
        jalurs: [
          {
            id: 1,
            gunung_id: 1,
            nama_jalur: 'Jalur Selo',
            status: 'open',
          },
        ],
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockDetail })

    const result = await pendakiMountainsApi.getMountainById(1)

    expect(apiClient.get).toHaveBeenCalledWith('/mountains/1')
    expect(result).toEqual(mockDetail)
  })
})
