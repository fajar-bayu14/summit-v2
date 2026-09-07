import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiCheckoutApi } from '@/api/pendakiCheckout'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('pendakiCheckoutApi (Task 5.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call checkout with manifest payload', async () => {
    const payload = {
      anggotas: [
        {
          nama_anggota: 'Fajar Bayu',
          nik_identitas: '3301234567890001',
          telepon: '081234567890',
          telepon_darurat: '081298765432',
          hubungan_darurat: 'Orang Tua',
        },
      ],
    }

    const mockResponse = {
      status: 'success',
      message: 'Order created, silakan lanjutkan pembayaran.',
      checkout_url: 'https://checkout.xendit.co/v2/invoice/abc12345',
      data: {
        id: 1,
        invoice: 'INV/20260706/ABC12',
        total_bayar: 150000,
        status: 'pending',
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({ data: mockResponse })

    const result = await pendakiCheckoutApi.checkout(payload)

    expect(apiClient.post).toHaveBeenCalledWith('/orders/checkout', payload)
    expect(result).toEqual(mockResponse)
  })

  it('should call getOrderDetail with encoded invoice', async () => {
    const invoice = 'INV/20260706/ABC12'
    const mockDetail = {
      status: 'success',
      data: {
        id: 1,
        invoice,
        status: 'pending',
        total_bayar: 150000,
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockDetail })

    const result = await pendakiCheckoutApi.getOrderDetail(invoice)

    expect(apiClient.get).toHaveBeenCalledWith(`/orders/${encodeURIComponent(invoice)}`)
    expect(result).toEqual(mockDetail)
  })

  it('should call cancelOrder with invoice', async () => {
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

    const result = await pendakiCheckoutApi.cancelOrder(invoice)

    expect(apiClient.post).toHaveBeenCalledWith(`/orders/${encodeURIComponent(invoice)}/cancel`)
    expect(result).toEqual(mockCancel)
  })

  it('should call listOrders with query params', async () => {
    const mockList = {
      status: 'success',
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockList })

    const result = await pendakiCheckoutApi.listOrders({ status: 'pending', page: 1 })

    expect(apiClient.get).toHaveBeenCalledWith('/orders', {
      params: { status: 'pending', page: 1 },
    })
    expect(result).toEqual(mockList)
  })
})
