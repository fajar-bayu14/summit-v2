import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiRefundApi } from '@/api/pendakiRefund'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn()
  }
}))

describe('Pendaki Refund API Client (Task 8.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should post refund request with valid payload and invoice', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Pengajuan refund berhasil dikirim',
        data: {
          id: 1,
          pesanan_id: 10,
          nominal: 150000,
          status: 'pending',
          alasan: 'Jalur ditutup karena cuaca buruk',
          bank_tujuan: 'BCA',
          rekening_tujuan: '1234567890',
          nama_tujuan: 'Fajar Bayu'
        }
      }
    }
    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const payload = {
      alasan: 'Jalur ditutup karena cuaca buruk',
      bank_tujuan: 'BCA',
      rekening_tujuan: '1234567890',
      nama_tujuan: 'Fajar Bayu',
      nominal: 150000,
      refund_category: 'force_majeure' as const
    }

    const res = await pendakiRefundApi.requestRefund('INV-2026-001', payload)

    expect(apiClient.post).toHaveBeenCalledWith('/orders/INV-2026-001/refund-request', payload)
    expect(res.data.status).toBe('pending')
    expect(res.data.nominal).toBe(150000)
  })

  it('should post dispute refund escalation with reason', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Banding sengketa berhasil diajukan',
        data: {
          id: 1,
          is_disputed: true,
          status: 'disputed',
          dispute_reason: 'Mitra menolak padahal ada badai di pos 2'
        }
      }
    }
    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const payload = {
      alasan_dispute: 'Mitra menolak padahal ada badai di pos 2'
    }

    const res = await pendakiRefundApi.disputeRefund(1, payload)

    expect(apiClient.post).toHaveBeenCalledWith('/refunds/1/dispute', payload)
    expect(res.data.status).toBe('disputed')
    expect(res.data.is_disputed).toBe(true)
  })
})
