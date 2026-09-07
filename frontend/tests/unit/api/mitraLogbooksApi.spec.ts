import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiClient } from '@/lib/axios'
import mitraLogbooksApi from '@/api/mitraLogbooks'
import type { LogbookEntry, VerifyLogbookPayload } from '@/types/logbook'

vi.mock('@/lib/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('Mitra Logbooks API Service (Task 5.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockLogbook: LogbookEntry = {
    id: 1,
    pesanan_id: 10,
    invoice: 'INV-PRAU-001',
    user_id: 5,
    nama_pendaki: 'Bayu Pendaki',
    gunung_nama: 'Gunung Prau',
    jalur_nama: 'Patakbanteng',
    tinggi_mdpl: 2590,
    foto_summit: 'http://localhost:8000/storage/summit/proof.jpg',
    latitude: '-7.1872',
    longitude: '109.9238',
    waktu_summit: '2026-09-06T05:45:00Z',
    catatan_pendaki: 'Sunrise sangat cerah di puncak 2590 mdpl!',
    status_validasi: 'pending',
    catatan_petugas: null,
    validated_at: null,
    validated_by: null,
    certificate_url: null,
    created_at: '2026-09-06T06:00:00Z',
  }

  it('should fetch logbooks list with filter params', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Daftar permohonan validasi logbook berhasil dimuat.',
        data: {
          data: [mockLogbook],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      },
    })

    const res = await mitraLogbooksApi.getLogbooks({
      status_validasi: 'pending',
      page: 1,
    })

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/logbooks', {
      params: { status_validasi: 'pending', page: 1 },
    })
    expect(res.status).toBe('success')
    expect((res.data as any).data[0].invoice).toBe('INV-PRAU-001')
  })

  it('should verify logbook (approve) successfully', async () => {
    const payload: VerifyLogbookPayload = {
      status_validasi: 'approved',
      catatan_petugas: 'Foto puncak valid dan sesuai patok triangulasi',
    }

    const approvedLogbook: LogbookEntry = {
      ...mockLogbook,
      status_validasi: 'approved',
      catatan_petugas: payload.catatan_petugas,
      validated_at: '2026-09-06T07:00:00Z',
      validated_by: 'Petugas Basecamp',
      certificate_url: 'http://localhost:8000/api/v1/orders/INV-PRAU-001/certificate',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Bukti summit berhasil disetujui',
        data: approvedLogbook,
      },
    })

    const res = await mitraLogbooksApi.verifyLogbook(1, payload)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/logbooks/1/verify', payload)
    expect(res.data.status_validasi).toBe('approved')
    expect(res.data.certificate_url).toBeTruthy()
  })

  it('should verify logbook (reject) with officer notes', async () => {
    const payload: VerifyLogbookPayload = {
      status_validasi: 'rejected',
      catatan_petugas: 'Foto bukan di area tugu puncak',
    }

    const rejectedLogbook: LogbookEntry = {
      ...mockLogbook,
      status_validasi: 'rejected',
      catatan_petugas: payload.catatan_petugas,
      validated_at: '2026-09-06T07:00:00Z',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Bukti summit ditolak oleh petugas.',
        data: rejectedLogbook,
      },
    })

    const res = await mitraLogbooksApi.verifyLogbook(1, payload)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/logbooks/1/verify', payload)
    expect(res.data.status_validasi).toBe('rejected')
    expect(res.data.catatan_petugas).toBe('Foto bukan di area tugu puncak')
  })
})
