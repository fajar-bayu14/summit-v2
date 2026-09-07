import { describe, it, expect, beforeEach, vi } from 'vitest'
import mitraQuotasApi from '@/api/mitraQuotas'
import { apiClient } from '@/lib/axios'
import type { BatchQuotaPayload, UpdateSingleQuotaPayload } from '@/types/quota'

describe('Mitra Quotas API Service (Task 3.1)', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('should call getProductQuotas with productId and date params', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Daftar kuota harian berhasil dimuat.',
        data: [
          {
            id: 1,
            produk_tiket_id: 10,
            tanggal: '2026-09-10',
            kuota_total: 100,
            kuota_tersisa: 80,
          },
          {
            id: 2,
            produk_tiket_id: 10,
            tanggal: '2026-09-11',
            kuota_total: 100,
            kuota_tersisa: 50,
          },
        ],
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const params = { start_date: '2026-09-01', end_date: '2026-09-30' }
    const result = await mitraQuotasApi.getProductQuotas(10, params)

    expect(getSpy).toHaveBeenCalledWith('/mitra/products/10/quotas', { params })
    expect(result.status).toBe('success')
    expect(result.data?.length).toBe(2)
    expect(result.data?.[0].kuota_total).toBe(100)
  })

  it('should call batchSetQuotas with productId and payload', async () => {
    const payload: BatchQuotaPayload = {
      start_date: '2026-10-01',
      end_date: '2026-10-31',
      kuota_total: 150,
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Kuota harian berhasil diset untuk rentang tanggal tersebut.',
        data: null,
      },
    }

    const postSpy = vi.spyOn(apiClient, 'post').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraQuotasApi.batchSetQuotas(10, payload)

    expect(postSpy).toHaveBeenCalledWith('/mitra/products/10/quotas/batch', payload)
    expect(result.status).toBe('success')
  })

  it('should call updateSingleQuota with quotaId and payload', async () => {
    const payload: UpdateSingleQuotaPayload = {
      kuota_total: 120,
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Kuota harian berhasil diperbarui.',
        data: {
          id: 5,
          produk_tiket_id: 10,
          tanggal: '2026-09-15',
          kuota_total: 120,
          kuota_tersisa: 100,
        },
      },
    }

    const putSpy = vi.spyOn(apiClient, 'put').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraQuotasApi.updateSingleQuota(5, payload)

    expect(putSpy).toHaveBeenCalledWith('/mitra/quotas/5', payload)
    expect(result.data?.kuota_total).toBe(120)
  })
})
