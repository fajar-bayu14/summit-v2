import { describe, it, expect, beforeEach, vi } from 'vitest'
import mitraOrdersApi from '@/api/mitraOrders'
import { apiClient } from '@/lib/axios'

describe('Mitra Orders API Service (Task 4.1)', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('should call getOrders with params and return orders list & revenue meta', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Orders fetched',
        meta: {
          total_pendapatan_bersih: 450000,
          total_transaksi_paid: 3,
        },
        data: {
          data: [
            {
              id: 1,
              invoice: 'INV-001',
              total_bayar: 150000,
              status: 'paid',
            },
          ],
        },
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const params = { status: 'paid', search: 'INV-001', basecamp_id: 2 }
    const result = await mitraOrdersApi.getOrders(params)

    expect(getSpy).toHaveBeenCalledWith('/mitra/orders', { params })
    expect(result.status).toBe('success')
    expect(result.meta?.total_pendapatan_bersih).toBe(450000)
    expect(result.meta?.total_transaksi_paid).toBe(3)
  })

  it('should call getOrderById by ID', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Order details loaded',
        data: {
          id: 15,
          invoice: 'INV-015',
          status: 'paid',
          anggotas: [{ id: 1, nama_anggota: 'Fajar Bayu' }],
          details: [{ id: 10, nama_produk: 'Sewa Tenda', status_operasional: 'pending' }],
        },
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraOrdersApi.getOrderById(15)

    expect(getSpy).toHaveBeenCalledWith('/mitra/orders/15')
    expect(result.data?.id).toBe(15)
    expect(result.data?.anggotas?.length).toBe(1)
  })

  it('should call updateItemStatus with pesananId, itemId, and status', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Item operational status updated successfully.',
      },
    }

    const patchSpy = vi.spyOn(apiClient, 'patch').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraOrdersApi.updateItemStatus(15, 10, 'ready')

    expect(patchSpy).toHaveBeenCalledWith('/mitra/orders/15/items/10', {
      status_operasional: 'ready',
    })
    expect(result.status).toBe('success')
  })

  it('should call checkInOrder with ID', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Check-in berhasil.',
        data: { id: 15, status: 'on_going' },
      },
    }

    const postSpy = vi.spyOn(apiClient, 'post').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraOrdersApi.checkInOrder(15)

    expect(postSpy).toHaveBeenCalledWith('/mitra/orders/15/check-in')
    expect(result.data?.status).toBe('on_going')
  })

  it('should call checkInByInvoice with invoice code', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Check-in berhasil.',
        data: { id: 15, invoice: 'INV-QR-99', status: 'on_going' },
      },
    }

    const postSpy = vi.spyOn(apiClient, 'post').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraOrdersApi.checkInByInvoice('INV-QR-99')

    expect(postSpy).toHaveBeenCalledWith('/mitra/orders/check-in', {
      invoice: 'INV-QR-99',
    })
    expect(result.data?.status).toBe('on_going')
  })
})
