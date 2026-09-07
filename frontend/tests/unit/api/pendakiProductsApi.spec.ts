import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiProductsApi } from '@/api/pendakiProducts'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
  },
}))

describe('pendakiProductsApi (Task 3.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call getProducts with query parameters including basecamp_id and kategori', async () => {
    const mockData = {
      status: 'success',
      message: 'Active products fetched successfully.',
      data: [
        {
          id: 1,
          basecamp_id: 10,
          nama_produk: 'Sewa Tenda Dome 4P',
          kategori: 'rental',
          harga: 45000,
          stok: 12,
          is_active: true,
        },
      ],
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockData })

    const result = await pendakiProductsApi.getProducts({ basecamp_id: 10, kategori: 'rental' })

    expect(apiClient.get).toHaveBeenCalledWith('/products', {
      params: { basecamp_id: 10, kategori: 'rental' },
    })
    expect(result).toEqual(mockData)
  })

  it('should call getProductById with product ID', async () => {
    const mockDetail = {
      status: 'success',
      message: 'Product details fetched successfully.',
      data: {
        id: 1,
        basecamp_id: 10,
        nama_produk: 'Tiket SIMAKSI Merbabu Selo',
        kategori: 'tiket',
        harga: 25000,
        is_active: true,
        tiket: {
          id: 1,
          produk_id: 1,
          jalur_id: 5,
          kuotas: [
            { id: 1, produk_tiket_id: 1, tanggal: '2026-07-20', kuota_total: 100, kuota_tersisa: 65 },
          ],
        },
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockDetail })

    const result = await pendakiProductsApi.getProductById(1)

    expect(apiClient.get).toHaveBeenCalledWith('/products/1')
    expect(result).toEqual(mockDetail)
  })
})
