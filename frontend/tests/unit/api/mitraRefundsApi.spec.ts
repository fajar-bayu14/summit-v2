import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiClient } from '@/lib/axios'
import mitraRefundsApi from '@/api/mitraRefunds'
import type { RefundItem, MitraRejectRefundPayload } from '@/types/refund'

vi.mock('@/lib/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('Mitra Refunds API Service (Task 7.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockRefund: RefundItem = {
    id: 1,
    pesanan_id: 10,
    pesanan_invoice: 'INV-PRAU-001',
    mitra_id: 1,
    nominal: 150000,
    nominal_disetujui: null,
    status: 'pending',
    alasan: 'Sakit mendadak sebelum pendakian',
    bank_tujuan: 'BCA',
    rekening_tujuan: '1234567890',
    nama_tujuan: 'Bayu Pendaki',
    created_at: '2026-09-06T10:00:00Z',
  }

  it('should fetch refunds list with filter params', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Daftar pengajuan refund mitra berhasil dimuat.',
        data: {
          data: [mockRefund],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      },
    })

    const res = await mitraRefundsApi.getRefunds({ status: 'pending', page: 1 })

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/refunds', {
      params: { status: 'pending', page: 1 },
    })
    expect(res.status).toBe('success')
    expect((res.data as any).data[0].pesanan_invoice).toBe('INV-PRAU-001')
  })

  it('should fetch refund details by ID', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Detail refund berhasil dimuat.',
        data: mockRefund,
      },
    })

    const res = await mitraRefundsApi.getRefundById(1)

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/refunds/1')
    expect(res.status).toBe('success')
    expect(res.data.id).toBe(1)
  })

  it('should approve refund request', async () => {
    const approvedRefund: RefundItem = {
      ...mockRefund,
      status: 'approved_by_mitra',
      nominal_disetujui: 150000,
      refunded_at: '2026-09-07T10:00:00Z',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Pengajuan refund berhasil disetujui',
        data: approvedRefund,
      },
    })

    const res = await mitraRefundsApi.approveRefund(1)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/refunds/1/approve')
    expect(res.status).toBe('success')
    expect(res.data.status).toBe('approved_by_mitra')
  })

  it('should reject refund request with reason', async () => {
    const payload: MitraRejectRefundPayload = {
      alasan_penolakan: 'Pembatalan melewati batas H-1 SOP basecamp',
    }

    const rejectedRefund: RefundItem = {
      ...mockRefund,
      status: 'rejected_by_mitra',
      mitra_alasan_penolakan: payload.alasan_penolakan,
      mitra_reviewed_at: '2026-09-07T10:00:00Z',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Pengajuan refund telah ditolak oleh Mitra.',
        data: rejectedRefund,
      },
    })

    const res = await mitraRefundsApi.rejectRefund(1, payload)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/refunds/1/reject', payload)
    expect(res.status).toBe('success')
    expect(res.data.status).toBe('rejected_by_mitra')
    expect(res.data.mitra_alasan_penolakan).toBe(payload.alasan_penolakan)
  })
})
