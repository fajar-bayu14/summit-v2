import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('pendakiOrdersApi (Task 6.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call getMyOrders with status and page parameters', async () => {
    const mockList = {
      status: 'success',
      data: [
        {
          id: 1,
          invoice: 'INV/20260706/ABC12',
          status: 'paid',
          total_bayar: 150000,
        },
      ],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockList })

    const result = await pendakiOrdersApi.getMyOrders({ status: 'paid', page: 1 })

    expect(apiClient.get).toHaveBeenCalledWith('/orders', {
      params: { status: 'paid', page: 1 },
    })
    expect(result).toEqual(mockList)
  })

  it('should call getOrderByInvoice with encoded invoice', async () => {
    const invoice = 'INV/20260706/ABC12'
    const mockDetail = {
      status: 'success',
      data: {
        id: 1,
        invoice,
        status: 'paid',
        total_bayar: 150000,
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockDetail })

    const result = await pendakiOrdersApi.getOrderByInvoice(invoice)

    expect(apiClient.get).toHaveBeenCalledWith(`/orders/${encodeURIComponent(invoice)}`)
    expect(result).toEqual(mockDetail)
  })

  it('should call cancelOrder with invoice parameter', async () => {
    const invoice = 'INV/20260706/ABC12'
    const mockCancel = {
      status: 'success',
      message: 'Pesanan berhasil dibatalkan.',
      data: {
        id: 1,
        invoice,
        status: 'cancelled',
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: mockCancel })

    const result = await pendakiOrdersApi.cancelOrder(invoice)

    expect(apiClient.post).toHaveBeenCalledWith(`/orders/${encodeURIComponent(invoice)}/cancel`)
    expect(result).toEqual(mockCancel)
  })
})
