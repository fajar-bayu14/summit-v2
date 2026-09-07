import { describe, it, expect, vi, beforeEach } from 'vitest'
import { pendakiKycApi } from '@/api/pendakiKyc'
import apiClient from '@/lib/axios'

vi.mock('@/lib/axios', () => {
  return {
    default: {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
    },
  }
})

describe('Pendaki KYC & Profile API Service (Task 1.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should call getProfile and return user profile response', async () => {
    const mockUser = { id: 1, name: 'Ahmad Pendaki', email: 'ahmad@example.com', role: 'pendaki' }
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { status: 'success', data: mockUser },
    })

    const result = await pendakiKycApi.getProfile()
    expect(apiClient.get).toHaveBeenCalledWith('/profile')
    expect(result.data).toEqual(mockUser)
  })

  it('should call getKycStatus and return pendaki kyc profile', async () => {
    const mockKyc = {
      id: 1,
      user_id: 1,
      nama_lengkap: 'Ahmad Pendaki',
      nomor_identitas: '3201123456780001',
      jenis_identitas: 'ktp',
      status_verifikasi: 'verified',
    }
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: { status: 'success', data: mockKyc },
    })

    const result = await pendakiKycApi.getKycStatus()
    expect(apiClient.get).toHaveBeenCalledWith('/kyc/status')
    expect(result.data).toEqual(mockKyc)
  })

  it('should call submitKyc with FormData and multipart header', async () => {
    const mockFormData = new FormData()
    mockFormData.append('nama_lengkap', 'Ahmad Pendaki')
    mockFormData.append('nomor_identitas', '3201123456780001')

    const mockResponse = {
      id: 1,
      nama_lengkap: 'Ahmad Pendaki',
      status_verifikasi: 'pending',
    }
    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: { status: 'success', data: mockResponse },
    })

    const result = await pendakiKycApi.submitKyc(mockFormData)
    expect(apiClient.post).toHaveBeenCalledWith(
      '/kyc/submit',
      mockFormData,
      expect.objectContaining({
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    )
    expect(result.data).toEqual(mockResponse)
  })

  it('should call updatePassword with payload', async () => {
    const payload = {
      current_password: 'oldpassword123',
      new_password: 'newpassword123',
      new_password_confirmation: 'newpassword123',
    }

    vi.mocked(apiClient.put).mockResolvedValueOnce({
      data: { status: 'success', message: 'Password updated successfully.' },
    })

    const result = await pendakiKycApi.updatePassword(payload)
    expect(apiClient.put).toHaveBeenCalledWith('/profile/password', payload)
    expect(result.message).toBe('Password updated successfully.')
  })
})
