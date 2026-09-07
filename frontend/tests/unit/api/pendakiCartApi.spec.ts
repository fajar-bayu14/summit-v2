import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiCartApi } from '@/api/pendakiCart'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('pendakiCartApi (Task 4.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call getCart', async () => {
    const mockCart = {
      status: 'success',
      data: {
        id: 1,
        user_id: 10,
        basecamp_id: 2,
        jalur_id: 3,
        total_item: 2,
        subtotal: 50000,
        items: [],
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockCart })

    const result = await pendakiCartApi.getCart()

    expect(apiClient.get).toHaveBeenCalledWith('/cart')
    expect(result).toEqual(mockCart)
  })

  it('should call addItem with product and booking details', async () => {
    const payload = {
      produk_id: 5,
      qty: 2,
      jalur_id: 1,
      tanggal_booking: '2026-07-20',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: { status: 'success' } })

    await pendakiCartApi.addItem(payload)

    expect(apiClient.post).toHaveBeenCalledWith('/cart/items', payload)
  })

  it('should call updateItem with item ID and quantity', async () => {
    vi.mocked(apiClient.patch).mockResolvedValueOnce({ data: { status: 'success' } })

    await pendakiCartApi.updateItem(10, { qty: 3 })

    expect(apiClient.patch).toHaveBeenCalledWith('/cart/items/10', { qty: 3 })
  })

  it('should call removeItem with item ID', async () => {
    vi.mocked(apiClient.delete).mockResolvedValueOnce({ data: { status: 'success' } })

    await pendakiCartApi.removeItem(10)

    expect(apiClient.delete).toHaveBeenCalledWith('/cart/items/10')
  })

  it('should call clearCart', async () => {
    vi.mocked(apiClient.delete).mockResolvedValueOnce({ data: { status: 'success' } })

    await pendakiCartApi.clearCart()

    expect(apiClient.delete).toHaveBeenCalledWith('/cart')
  })
})
