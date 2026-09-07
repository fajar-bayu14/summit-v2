import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import WithdrawalRequestModal from '@/components/mitra/wallet/WithdrawalRequestModal.vue'
import mitraWalletApi from '@/api/mitraWallet'
import type { MitraWallet, WithdrawalRequest } from '@/types/wallet'

vi.mock('@/api/mitraWallet', () => ({
  default: {
    requestWithdrawal: vi.fn(),
  },
}))

describe('Mitra Withdrawal Request Modal Component (Task 6.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockWallet: MitraWallet = {
    id: 1,
    mitra_id: 10,
    saldo_pending: 250000,
    saldo_available: 1200000,
    total_withdrawn: 4500000,
    total_terhitung: 1450000,
    rekening_tujuan: {
      bank: 'BCA',
      rekening_bank: '5210987654',
      nama_rekening: 'Bayu Mitra Basecamp',
    },
  }

  it('should initialize and allow quick preset selection', async () => {
    const wrapper = mount(WithdrawalRequestModal, {
      props: {
        isOpen: true,
        wallet: mockWallet,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.amount).toBe('')

    // Click 500rb preset
    vm.setQuickAmount(500000)
    expect(vm.amount).toBe(500000)

    // Set Max
    vm.setMaxAmount()
    expect(vm.amount).toBe(1200000)
  })

  it('should reject submission if amount is less than 50.000', async () => {
    const wrapper = mount(WithdrawalRequestModal, {
      props: {
        isOpen: true,
        wallet: mockWallet,
      },
    })

    const vm = wrapper.vm as any
    vm.amount = 30000
    await vm.handleSubmit()

    expect(mitraWalletApi.requestWithdrawal).not.toHaveBeenCalled()
    expect(vm.formError).toContain('minimal adalah Rp 50.000')
  })

  it('should reject submission if amount exceeds saldo_available', async () => {
    const wrapper = mount(WithdrawalRequestModal, {
      props: {
        isOpen: true,
        wallet: mockWallet,
      },
    })

    const vm = wrapper.vm as any
    vm.amount = 2000000
    await vm.handleSubmit()

    expect(mitraWalletApi.requestWithdrawal).not.toHaveBeenCalled()
    expect(vm.formError).toContain('melebihi saldo tersedia')
  })

  it('should submit valid withdrawal request and emit submitted event', async () => {
    const mockWithdrawalResult: WithdrawalRequest = {
      id: 55,
      mitra_id: 10,
      nominal: 500000,
      biaya_admin: 0,
      bank: 'BCA',
      rekening_bank: '5210987654',
      nama_rekening: 'Bayu Mitra Basecamp',
      status: 'pending',
      catatan: 'Penarikan mingguan',
      created_at: '2026-09-07T10:00:00Z',
    }

    vi.mocked(mitraWalletApi.requestWithdrawal).mockResolvedValueOnce({
      success: true,
      message: 'Pengajuan penarikan berhasil dibuat',
      data: mockWithdrawalResult,
    })

    const wrapper = mount(WithdrawalRequestModal, {
      props: {
        isOpen: true,
        wallet: mockWallet,
      },
    })

    const vm = wrapper.vm as any
    vm.amount = 500000
    vm.notes = 'Penarikan mingguan'

    await vm.handleSubmit()

    expect(mitraWalletApi.requestWithdrawal).toHaveBeenCalledWith({
      nominal: 500000,
      catatan: 'Penarikan mingguan',
    })
    expect(wrapper.emitted('submitted')?.[0]).toEqual([mockWithdrawalResult])
    expect(vm.serverSuccess).toContain('berhasil dibuat')
  })

  it('should emit close and update:isOpen on handleClose', () => {
    const wrapper = mount(WithdrawalRequestModal, {
      props: {
        isOpen: true,
        wallet: mockWallet,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
