import { describe, it, expect, beforeEach, vi } from 'vitest'
import mitraDashboardApi from '@/api/mitraDashboard'
import apiClient from '@/lib/axios'

describe('Mitra Dashboard API Service (Task 1.1)', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('should call getSummary without basecamp_id when omitted', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Summary loaded',
        data: {
          financial: {
            saldo_available: 750000,
            saldo_pending: 250000,
            total_penarikan: 500000,
            total_pendapatan_bersih: 94500,
            total_transaksi_paid: 2,
          },
          operations_today: {
            pendaki_berangkat_hari_ini: 3,
            pendaki_sedang_mendaki: 1,
            total_kuota_hari_ini: 150,
            kuota_terpakai_hari_ini: 20,
            sisa_kuota_hari_ini: 130,
            status_jalur: 'open',
          },
          action_queues: {
            pesanan_paid_count: 1,
            logbook_pending_count: 1,
            refund_pending_count: 1,
          },
          resources: {
            total_basecamp: 1,
            total_produk_aktif: 2,
            total_staf_tersedia: 1,
            total_staf_bertugas: 1,
            produk_stok_menipis_count: 1,
          },
        },
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraDashboardApi.getSummary()

    expect(getSpy).toHaveBeenCalledWith('/mitra/analytics/summary', { params: {} })
    expect(result.data?.financial.saldo_available).toBe(750000)
    expect(result.data?.operations_today.status_jalur).toBe('open')
  })

  it('should call getSummary with basecamp_id when provided', async () => {
    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: {
        status: 'success',
        data: {},
      },
    } as any)

    await mitraDashboardApi.getSummary(5)

    expect(getSpy).toHaveBeenCalledWith('/mitra/analytics/summary', {
      params: { basecamp_id: 5 },
    })
  })

  it('should call emergencyCloseTrail with trailId and payload', async () => {
    const mockPayload = {
      status: 'close' as const,
      alasan_penutupan: 'Badai angin kencang di pos 3',
    }

    const postSpy = vi.spyOn(apiClient, 'post').mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Jalur berhasil ditutup darurat.',
        data: { id: 10, status: 'close' },
      },
    } as any)

    const result = await mitraDashboardApi.emergencyCloseTrail(10, mockPayload)

    expect(postSpy).toHaveBeenCalledWith('/mitra/trails/10/emergency-close', mockPayload)
    expect(result.status).toBe('success')
  })

  it('should call getTodayOrders with basecamp_id parameter', async () => {
    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce({
      data: {
        status: 'success',
        data: [],
      },
    } as any)

    await mitraDashboardApi.getTodayOrders(3)

    expect(getSpy).toHaveBeenCalledWith('/mitra/orders', {
      params: { per_page: 10, basecamp_id: 3 },
    })
  })
})
