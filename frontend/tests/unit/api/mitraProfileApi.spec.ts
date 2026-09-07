import { describe, it, expect, vi, beforeEach } from 'vitest'
import mitraProfileApi from '@/api/mitraProfile'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => ({
  default: {
    get: vi.fn(),
    put: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('Mitra Profile API Service (Task 10.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call GET /profile to fetch profile info', async () => {
    const mockUserResponse = {
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: {
        id: 1,
        name: 'Budi Santoso',
        email: 'budi@summit.id',
        role: 'mitra',
        mitra: {
          id: 10,
          nama_pemilik: 'Budi Santoso',
          telepon: '081234567890',
          alamat: 'Jl. Gunung Slamet No. 12',
          bank: 'Bank BCA',
          rekening_bank: '1234567890',
          nama_rekening: 'Budi Santoso',
        },
      },
    }

    vi.mocked(apiClient.get).mockResolvedValueOnce({ data: mockUserResponse })

    const res = await mitraProfileApi.getProfile()

    expect(apiClient.get).toHaveBeenCalledWith('/profile')
    expect(res.data.name).toBe('Budi Santoso')
    expect(res.data.mitra?.bank).toBe('Bank BCA')
  })

  it('should call PUT /mitra/profile with correct payload', async () => {
    const payload = {
      nama_pemilik: 'Budi Santoso Baru',
      telepon: '081298765432',
      alamat: 'Dusun Bambu No. 5',
      deskripsi: 'Basecamp resmi jalur Bambangan',
      nik: '3301234567890001',
      npwp: '12.345.678.9-012.000',
      bank: 'Bank Mandiri',
      rekening_bank: '1400012345678',
      nama_rekening: 'Budi Santoso',
      ewallet: '081298765432',
    }

    const mockResponse = {
      status: 'success',
      message: 'Profil mitra berhasil diperbarui.',
      data: {
        id: 10,
        user_id: 1,
        ...payload,
        status: 'aktif',
      },
    }

    vi.mocked(apiClient.put).mockResolvedValueOnce({ data: mockResponse })

    const res = await mitraProfileApi.updateProfile(payload)

    expect(apiClient.put).toHaveBeenCalledWith('/mitra/profile', payload)
    expect(res.status).toBe('success')
    expect(res.data.nama_pemilik).toBe('Budi Santoso Baru')
  })

  it('should call PUT /profile/password with correct change password payload', async () => {
    const payload = {
      current_password: 'oldPassword123',
      new_password: 'NewSecurePassword123!',
      new_password_confirmation: 'NewSecurePassword123!',
    }

    const mockResponse = {
      status: 'success',
      message: 'Password updated successfully.',
    }

    vi.mocked(apiClient.put).mockResolvedValueOnce({ data: mockResponse })

    const res = await mitraProfileApi.updatePassword(payload)

    expect(apiClient.put).toHaveBeenCalledWith('/profile/password', payload)
    expect(res.status).toBe('success')
  })
})
