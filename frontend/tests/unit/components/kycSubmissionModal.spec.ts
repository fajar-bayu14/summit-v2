import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import KycSubmissionModal from '@/components/pendaki/KycSubmissionModal.vue'
import { pendakiKycApi } from '@/api/pendakiKyc'

vi.mock('@/api/pendakiKyc', () => ({
  pendakiKycApi: {
    submitKyc: vi.fn(),
  },
}))

describe('KycSubmissionModal Component (Task 1.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    global.URL.createObjectURL = vi.fn(() => 'blob:mock-preview-url')
    global.URL.revokeObjectURL = vi.fn()
  })

  it('should populate initial values when opened with initialData', () => {
    const wrapper = mount(KycSubmissionModal, {
      props: {
        isOpen: true,
        initialData: {
          nama_lengkap: 'Budi Pendaki',
          jenis_identitas: 'ktp',
          nomor_identitas: '3301123456780002',
          tanggal_lahir: '1998-05-20',
          jenis_kelamin: 'l',
          alamat: 'Jl. Merbabu No. 10',
          telepon: '081234567890',
          nama_kontak_darurat: 'Siti Rahma',
          telepon_darurat: '081298765432',
          hubungan_darurat: 'Orang Tua',
        },
      },
    })

    const vm = wrapper.vm as any
    expect(vm.form.nama_lengkap).toBe('Budi Pendaki')
    expect(vm.form.nomor_identitas).toBe('3301123456780002')
    expect(vm.form.jenis_identitas).toBe('ktp')
    expect(vm.form.tanggal_lahir).toBe('1998-05-20')
    expect(vm.form.telepon).toBe('081234567890')
  })

  it('should validate required fields and 16 digit NIK for KTP', async () => {
    const wrapper = mount(KycSubmissionModal, {
      props: {
        isOpen: true,
        initialData: null,
      },
    })

    const vm = wrapper.vm as any
    vm.form.nama_lengkap = ''
    vm.form.nomor_identitas = '12345' // invalid KTP length

    await vm.handleSubmit()

    expect(pendakiKycApi.submitKyc).not.toHaveBeenCalled()
    expect(vm.fieldErrors.nama_lengkap).toContain('Nama lengkap wajib diisi')
    expect(vm.fieldErrors.nomor_identitas).toContain('16 digit angka')
  })

  it('should submit form when valid and emit submitted event', async () => {
    const mockSubmittedResponse = {
      id: 1,
      user_id: 1,
      nama_lengkap: 'Budi Pendaki',
      nomor_identitas: '3301123456780002',
      jenis_identitas: 'ktp' as const,
      status_verifikasi: 'pending' as const,
      tanggal_lahir: '1998-05-20',
      jenis_kelamin: 'l' as const,
      alamat: 'Jl. Merbabu No. 10',
      telepon: '081234567890',
      nama_kontak_darurat: 'Siti Rahma',
      telepon_darurat: '081298765432',
      hubungan_darurat: 'Orang Tua',
    }

    vi.mocked(pendakiKycApi.submitKyc).mockResolvedValueOnce({
      status: 'success',
      data: mockSubmittedResponse,
      message: 'KYC identity submitted successfully.',
    })

    const wrapper = mount(KycSubmissionModal, {
      props: {
        isOpen: true,
        initialData: {
          nama_lengkap: 'Budi Pendaki',
          jenis_identitas: 'ktp',
          nomor_identitas: '3301123456780002',
          tanggal_lahir: '1998-05-20',
          jenis_kelamin: 'l',
          alamat: 'Jl. Merbabu No. 10',
          telepon: '081234567890',
          nama_kontak_darurat: 'Siti Rahma',
          telepon_darurat: '081298765432',
          hubungan_darurat: 'Orang Tua',
          foto_identitas: 'existing-photo.jpg',
        },
      },
    })

    const vm = wrapper.vm as any
    await vm.handleSubmit()

    expect(pendakiKycApi.submitKyc).toHaveBeenCalled()
    expect(wrapper.emitted('submitted')).toBeTruthy()
    expect(wrapper.emitted('submitted')?.[0]?.[0]).toEqual(mockSubmittedResponse)
  })
})
