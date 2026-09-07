import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import SettingsView from '@/views/mitra/SettingsView.vue'
import mitraProfileApi from '@/api/mitraProfile'

vi.mock('@/api/mitraProfile', () => ({
  default: {
    getProfile: vi.fn(),
    updateProfile: vi.fn(),
    updatePassword: vi.fn(),
  },
}))

const mockProfileUser = {
  id: 1,
  name: 'Budi Santoso',
  email: 'budi@summit.id',
  role: 'mitra',
  mitra: {
    id: 10,
    user_id: 1,
    nama_pemilik: 'Budi Santoso',
    telepon: '081234567890',
    alamat: 'Jl. Raya Basecamp Bambangan No. 12',
    deskripsi: 'Pengelola resmi basecamp Bambangan Gunung Slamet',
    status: 'aktif',
    nik: '3301234567890001',
    npwp: '12.345.678.9-012.000',
    bank: 'Bank BCA',
    rekening_bank: '1234567890',
    nama_rekening: 'Budi Santoso',
    ewallet: '081234567890',
    basecamps: [
      {
        id: 1,
        nama_basecamp: 'Basecamp Bambangan',
        status: 'open',
        jam_operasional: '24 Jam',
        jalur: {
          id: 101,
          nama_jalur: 'Bambangan',
          kuota_harian: 300,
          gunung: {
            nama_gunung: 'Slamet',
          },
        },
      },
    ],
  },
}

describe('SettingsView View Component (Task 10.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should load profile information on mount and populate form fields', async () => {
    vi.mocked(mitraProfileApi.getProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: mockProfileUser as any,
    })

    const wrapper = mount(SettingsView)
    await flushPromises()

    expect(mitraProfileApi.getProfile).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Pengaturan Akun & Profil Mitra')
    expect(wrapper.text()).toContain('AKTIF')

    const namaInput = wrapper.find('#nama_pemilik')
    expect((namaInput.element as HTMLInputElement).value).toBe('Budi Santoso')

    const nikInput = wrapper.find('#nik')
    expect((nikInput.element as HTMLInputElement).value).toBe('3301234567890001')
  })

  it('should switch between tabs properly', async () => {
    vi.mocked(mitraProfileApi.getProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: mockProfileUser as any,
    })

    const wrapper = mount(SettingsView)
    await flushPromises()

    const tabButtons = wrapper.findAll('button[type="button"]')
    const bankTabBtn = tabButtons.find(b => b.text().includes('Rekening Pencairan'))
    expect(bankTabBtn).toBeDefined()

    await bankTabBtn?.trigger('click')
    expect(wrapper.text()).toContain('Rekening Bank Tujuan Pencairan')
    expect(wrapper.text()).toContain('Proteksi Rekening & Keamanan Saldo Escrow')

    const securityTabBtn = tabButtons.find(b => b.text().includes('Keamanan Sandi'))
    await securityTabBtn?.trigger('click')
    expect(wrapper.text()).toContain('Ganti Kata Sandi Akun')

    const basecampsTabBtn = tabButtons.find(b => b.text().includes('Basecamp Terdaftar'))
    await basecampsTabBtn?.trigger('click')
    expect(wrapper.text()).toContain('Daftar Basecamp Operasional')
    expect(wrapper.text()).toContain('Basecamp Bambangan')
    expect(wrapper.text()).toContain('Gunung Slamet • Jalur Bambangan')
  })

  it('should handle profile save submission', async () => {
    vi.mocked(mitraProfileApi.getProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: mockProfileUser as any,
    })

    vi.mocked(mitraProfileApi.updateProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profil mitra berhasil diperbarui.',
      data: {
        ...mockProfileUser.mitra,
        nama_pemilik: 'Budi Santoso Edited',
      } as any,
    })

    const wrapper = mount(SettingsView)
    await flushPromises()

    const namaInput = wrapper.find('#nama_pemilik')
    await namaInput.setValue('Budi Santoso Edited')

    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    await flushPromises()

    expect(mitraProfileApi.updateProfile).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Profil mitra berhasil diperbarui.')
  })

  it('should handle bank details save submission', async () => {
    vi.mocked(mitraProfileApi.getProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: mockProfileUser as any,
    })

    vi.mocked(mitraProfileApi.updateProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profil mitra berhasil diperbarui.',
      data: mockProfileUser.mitra as any,
    })

    const wrapper = mount(SettingsView)
    await flushPromises()

    // Switch to Bank Tab
    const tabButtons = wrapper.findAll('button[type="button"]')
    const bankTabBtn = tabButtons.find(b => b.text().includes('Rekening Pencairan'))
    await bankTabBtn?.trigger('click')

    const bankForm = wrapper.find('form')
    await bankForm.trigger('submit.prevent')
    await flushPromises()

    expect(mitraProfileApi.updateProfile).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Informasi rekening bank berhasil diperbarui.')
  })

  it('should validate and handle password change submission', async () => {
    vi.mocked(mitraProfileApi.getProfile).mockResolvedValueOnce({
      status: 'success',
      message: 'Profile retrieved successfully.',
      data: mockProfileUser as any,
    })

    vi.mocked(mitraProfileApi.updatePassword).mockResolvedValueOnce({
      status: 'success',
      message: 'Kata sandi akun berhasil diubah.',
      data: null as any,
    })

    const wrapper = mount(SettingsView)
    await flushPromises()

    // Switch to Security Tab
    const tabButtons = wrapper.findAll('button[type="button"]')
    const securityTabBtn = tabButtons.find(b => b.text().includes('Keamanan Sandi'))
    await securityTabBtn?.trigger('click')

    const currentPwdInput = wrapper.find('#current_password')
    const newPwdInput = wrapper.find('#new_password')
    const confirmPwdInput = wrapper.find('#new_password_confirmation')

    // Test mismatched passwords
    await currentPwdInput.setValue('oldPassword123')
    await newPwdInput.setValue('NewSecurePassword123!')
    await confirmPwdInput.setValue('DifferentPassword!')

    const securityForm = wrapper.find('form')
    await securityForm.trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.text()).toContain('Konfirmasi kata sandi baru tidak cocok.')
    expect(mitraProfileApi.updatePassword).not.toHaveBeenCalled()

    // Test matching password
    await confirmPwdInput.setValue('NewSecurePassword123!')
    await securityForm.trigger('submit.prevent')
    await flushPromises()

    expect(mitraProfileApi.updatePassword).toHaveBeenCalledWith({
      current_password: 'oldPassword123',
      new_password: 'NewSecurePassword123!',
      new_password_confirmation: 'NewSecurePassword123!',
    })
    expect(wrapper.text()).toContain('Kata sandi akun berhasil diubah.')
  })
})
