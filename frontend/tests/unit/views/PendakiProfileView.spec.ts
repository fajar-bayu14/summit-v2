import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PendakiProfileView from '@/views/pendaki/PendakiProfileView.vue'
import { useAuthStore } from '@/stores/auth'
import { pendakiKycApi } from '@/api/pendakiKyc'

vi.mock('@/api/pendakiKyc', () => ({
  pendakiKycApi: {
    getKycStatus: vi.fn(),
    updatePassword: vi.fn(),
  },
}))

describe('PendakiProfileView View Component (Task 1.4)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should render user profile header and KYC status', async () => {
    const authStore = useAuthStore()
    authStore.token = 'mock-token'
    authStore.user = {
      id: 1,
      name: 'Rian Pratama',
      email: 'rian@example.com',
      role: 'pendaki',
      pendaki: {
        id: 10,
        user_id: 1,
        nama_lengkap: 'Rian Pratama',
        jenis_identitas: 'ktp',
        nomor_identitas: '3201123456780009',
        tanggal_lahir: '1995-03-12',
        jenis_kelamin: 'l',
        alamat: 'Jl. Slamet No. 8',
        telepon: '081234567890',
        nama_kontak_darurat: 'Ibu Rian',
        telepon_darurat: '081298765432',
        hubungan_darurat: 'Orang Tua',
        status_verifikasi: 'verified',
      },
    }

    vi.mocked(pendakiKycApi.getKycStatus).mockResolvedValueOnce({
      status: 'success',
      data: authStore.user.pendaki,
      message: 'KYC status retrieved successfully.',
    })

    const wrapper = mount(PendakiProfileView, {
      global: {
        stubs: {
          KycSubmissionModal: true,
        },
      },
    })

    expect(wrapper.text()).toContain('Rian Pratama')
    expect(wrapper.text()).toContain('rian@example.com')
    expect(wrapper.text()).toContain('Terverifikasi')
  })

  it('should switch tabs between biodata, emergency, and security', async () => {
    const authStore = useAuthStore()
    authStore.token = 'mock-token'
    authStore.user = {
      id: 1,
      name: 'Rian Pratama',
      email: 'rian@example.com',
      role: 'pendaki',
      pendaki: {
        id: 10,
        user_id: 1,
        nama_lengkap: 'Rian Pratama',
        jenis_identitas: 'ktp',
        nomor_identitas: '3201123456780009',
        tanggal_lahir: '1995-03-12',
        jenis_kelamin: 'l',
        alamat: 'Jl. Slamet No. 8',
        telepon: '081234567890',
        nama_kontak_darurat: 'Ibu Rian',
        telepon_darurat: '081298765432',
        hubungan_darurat: 'Orang Tua',
        status_verifikasi: 'verified',
      },
    }

    vi.mocked(pendakiKycApi.getKycStatus).mockResolvedValueOnce({
      status: 'success',
      data: authStore.user.pendaki,
      message: 'KYC status retrieved successfully.',
    })

    const wrapper = mount(PendakiProfileView, {
      global: {
        stubs: {
          KycSubmissionModal: true,
        },
      },
    })

    // Default tab is biodata
    expect(wrapper.text()).toContain('Informasi Identitas Resmi')

    // Switch to emergency contact tab
    const emergencyTabBtn = wrapper.findAll('button').find((b) => b.text().includes('Kontak Darurat'))
    expect(emergencyTabBtn).toBeDefined()
    await emergencyTabBtn!.trigger('click')
    expect(wrapper.text()).toContain('Kontak Darurat (Emergency Contact)')
    expect(wrapper.text()).toContain('Ibu Rian')
    expect(wrapper.text()).toContain('081298765432')

    // Switch to security tab
    const securityTabBtn = wrapper.findAll('button').find((b) => b.text().includes('Keamanan Akun'))
    expect(securityTabBtn).toBeDefined()
    await securityTabBtn!.trigger('click')
    expect(wrapper.text()).toContain('Ganti Kata Sandi')
  })

  it('should validate and submit password change', async () => {
    const authStore = useAuthStore()
    authStore.token = 'mock-token'
    authStore.user = {
      id: 1,
      name: 'Rian Pratama',
      email: 'rian@example.com',
      role: 'pendaki',
      pendaki: null,
    }

    vi.mocked(pendakiKycApi.getKycStatus).mockResolvedValueOnce({
      status: 'success',
      data: null,
      message: 'No KYC yet.',
    })

    vi.mocked(pendakiKycApi.updatePassword).mockResolvedValueOnce({
      status: 'success',
      data: null,
      message: 'Kata sandi berhasil diperbarui.',
    })

    const wrapper = mount(PendakiProfileView, {
      global: {
        stubs: {
          KycSubmissionModal: true,
        },
      },
    })

    const vm = wrapper.vm as any
    vm.activeTab = 'security'
    await wrapper.vm.$nextTick()

    vm.passwordForm.current_password = 'oldpassword123'
    vm.passwordForm.new_password = 'newpassword123'
    vm.passwordForm.new_password_confirmation = 'newpassword123'

    await vm.handlePasswordSubmit()

    expect(pendakiKycApi.updatePassword).toHaveBeenCalledWith({
      current_password: 'oldpassword123',
      new_password: 'newpassword123',
      new_password_confirmation: 'newpassword123',
    })
    expect(vm.passwordSuccess).toBe('Kata sandi berhasil diperbarui.')
  })
})
