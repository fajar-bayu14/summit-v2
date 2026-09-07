import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mitraStaffApi } from '@/api/mitraStaff'
import { apiClient } from '@/lib/axios'
import type { MitraStaff, StoreStaffPayload, UpdateStaffPayload } from '@/types/staff'

vi.mock('@/lib/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('Mitra Staff API Service (Task 8.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockStaff: MitraStaff = {
    id: 1,
    mitra_id: 10,
    basecamp_id: 2,
    nama: 'Ahmad Pemandu',
    role: 'guide',
    telepon: '081234567890',
    is_available: true,
    jadwal_tugas: 'Senin - Jumat',
    created_at: '2026-09-01T08:00:00Z',
    updated_at: '2026-09-01T08:00:00Z',
  }

  it('should fetch staff list with query params', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Daftar staf berhasil dimuat.',
        data: [mockStaff],
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce(mockResponse)

    const params = { role: 'guide' as const, search: 'Ahmad' }
    const result = await mitraStaffApi.getStaffList(params)

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/staff', { params })
    expect(result).toEqual(mockResponse.data)
  })

  it('should fetch staff detail by ID', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        data: mockStaff,
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce(mockResponse)

    const result = await mitraStaffApi.getStaffById(1)

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/staff/1')
    expect(result).toEqual(mockResponse.data)
  })

  it('should create new staff member', async () => {
    const payload: StoreStaffPayload = {
      nama: 'Budi Porter',
      role: 'porter',
      telepon: '085712345678',
      is_available: true,
      jadwal_tugas: 'Weekend Only',
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Staf baru berhasil ditambahkan.',
        data: { id: 2, mitra_id: 10, ...payload },
      },
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce(mockResponse)

    const result = await mitraStaffApi.createStaff(payload)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/staff', payload)
    expect(result).toEqual(mockResponse.data)
  })

  it('should update staff member details', async () => {
    const payload: UpdateStaffPayload = {
      nama: 'Ahmad Pemandu Senior',
      jadwal_tugas: 'Setiap Hari',
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Data staf berhasil diperbarui.',
        data: { ...mockStaff, ...payload },
      },
    }

    vi.mocked(apiClient.put).mockResolvedValueOnce(mockResponse)

    const result = await mitraStaffApi.updateStaff(1, payload)

    expect(apiClient.put).toHaveBeenCalledWith('/mitra/staff/1', payload)
    expect(result).toEqual(mockResponse.data)
  })

  it('should toggle staff availability status', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        data: { ...mockStaff, is_available: false },
      },
    }

    vi.mocked(apiClient.put).mockResolvedValueOnce(mockResponse)

    const result = await mitraStaffApi.toggleStaffAvailability(1, false)

    expect(apiClient.put).toHaveBeenCalledWith('/mitra/staff/1', { is_available: false })
    expect(result).toEqual(mockResponse.data)
  })

  it('should delete staff member', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Staf berhasil dihapus.',
        data: null,
      },
    }

    vi.mocked(apiClient.delete).mockResolvedValueOnce(mockResponse)

    const result = await mitraStaffApi.deleteStaff(1)

    expect(apiClient.delete).toHaveBeenCalledWith('/mitra/staff/1')
    expect(result).toEqual(mockResponse.data)
  })
})
