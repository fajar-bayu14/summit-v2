import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import WalletManagementView from '@/views/mitra/WalletManagementView.vue'
import mitraWalletApi from '@/api/mitraWallet'
import type { MitraWallet, WalletTransaction, WithdrawalRequest } from '@/types/wallet'

describe('WalletManagementView View Component (Task 6.4)', () => {
  const mockWallet: MitraWallet = {
    id: 1,
    mitra_id: 10,
    saldo_pending: 300000,
    saldo_available: 1500000,
    total_withdrawn: 3000000,
    total_terhitung: 1800000,
    rekening_tujuan: {
      bank: 'BCA',
      rekening_bank: '1234567890',
      nama_rekening: 'Bayu Mitra Basecamp',
    },
  }

  const mockTransactions: WalletTransaction[] = [
    {
      id: 1,
      wallet_id: 1,
      pesanan_id: 10,
      pesanan_invoice: 'INV-PRAU-001',
      type: 'inflow_holding',
      nominal: 150000,
      saldo_pending_after: 150000,
      saldo_available_after: 0,
      catatan: 'Dana pesanan masuk holding',
      created_at: '2026-09-06T10:00:00Z',
    },
    {
      id: 2,
      wallet_id: 1,
      pesanan_id: 10,
      pesanan_invoice: 'INV-PRAU-001',
      type: 'release_to_available',
      nominal: 142500,
      saldo_pending_after: 0,
      saldo_available_after: 142500,
      catatan: 'Dana rilis ke saldo tersedia',
      created_at: '2026-09-06T12:00:00Z',
    },
  ]

  const mockWithdrawals: WithdrawalRequest[] = [
    {
      id: 50,
      mitra_id: 10,
      nominal: 500000,
      biaya_admin: 0,
      bank: 'BCA',
      rekening_bank: '1234567890',
      nama_rekening: 'Bayu Mitra Basecamp',
      status: 'completed',
      catatan: 'Tarik dana mingguan',
      created_at: '2026-09-05T08:00:00Z',
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load wallet summary, ledger, and withdrawals on mount', async () => {
    const walletSpy = vi.spyOn(mitraWalletApi, 'getWalletSummary').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockWallet,
    })

    const ledgerSpy = vi.spyOn(mitraWalletApi, 'getLedgerTransactions').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockTransactions,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
    } as any)

    const withdrawalsSpy = vi.spyOn(mitraWalletApi, 'getWithdrawals').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockWithdrawals,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    } as any)

    const wrapper = mount(WalletManagementView)
    await flushPromises()

    expect(walletSpy).toHaveBeenCalled()
    expect(ledgerSpy).toHaveBeenCalled()
    expect(withdrawalsSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.wallet?.saldo_available).toBe(1500000)
    expect(vm.transactions.length).toBe(2)
    expect(vm.withdrawals.length).toBe(1)

    expect(wrapper.text()).toContain('INV-PRAU-001')
    expect(wrapper.text()).toContain('Dana rilis ke saldo tersedia')
  })

  it('should switch between ledger tab and withdrawals tab', async () => {
    vi.spyOn(mitraWalletApi, 'getWalletSummary').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockWallet,
    })
    vi.spyOn(mitraWalletApi, 'getLedgerTransactions').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockTransactions, current_page: 1, last_page: 1, per_page: 15, total: 2 } as any,
    })
    vi.spyOn(mitraWalletApi, 'getWithdrawals').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockWithdrawals, current_page: 1, last_page: 1, per_page: 15, total: 1 } as any,
    })

    const wrapper = mount(WalletManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.activeTab).toBe('ledger')

    // Switch to withdrawals
    vm.activeTab = 'withdrawals'
    await flushPromises()

    expect(wrapper.text()).toContain('Tarik dana mingguan')
    expect(wrapper.text()).toContain('500.000')
  })

  it('should filter ledger transactions by type', async () => {
    vi.spyOn(mitraWalletApi, 'getWalletSummary').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockWallet,
    })
    const ledgerSpy = vi.spyOn(mitraWalletApi, 'getLedgerTransactions').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: [mockTransactions[1]], current_page: 1, last_page: 1, per_page: 15, total: 1 } as any,
    })
    vi.spyOn(mitraWalletApi, 'getWithdrawals').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockWithdrawals, current_page: 1, last_page: 1, per_page: 15, total: 1 } as any,
    })

    const wrapper = mount(WalletManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleLedgerFilter('release_to_available')
    await flushPromises()

    expect(ledgerSpy).toHaveBeenLastCalledWith({
      type: 'release_to_available',
      page: 1,
      per_page: 15,
    })
  })

  it('should filter withdrawals by status', async () => {
    vi.spyOn(mitraWalletApi, 'getWalletSummary').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockWallet,
    })
    vi.spyOn(mitraWalletApi, 'getLedgerTransactions').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockTransactions, current_page: 1, last_page: 1, per_page: 15, total: 2 } as any,
    })
    const withdrawalsSpy = vi.spyOn(mitraWalletApi, 'getWithdrawals').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockWithdrawals, current_page: 1, last_page: 1, per_page: 15, total: 1 } as any,
    })

    const wrapper = mount(WalletManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleWithdrawalFilter('completed')
    await flushPromises()

    expect(withdrawalsSpy).toHaveBeenLastCalledWith({
      status: 'completed',
      page: 1,
      per_page: 15,
    })
  })

  it('should open withdrawal modal and refresh all data after submission', async () => {
    const walletSpy = vi.spyOn(mitraWalletApi, 'getWalletSummary').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockWallet,
    })
    vi.spyOn(mitraWalletApi, 'getLedgerTransactions').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockTransactions, current_page: 1, last_page: 1, per_page: 15, total: 2 } as any,
    })
    vi.spyOn(mitraWalletApi, 'getWithdrawals').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: { data: mockWithdrawals, current_page: 1, last_page: 1, per_page: 15, total: 1 } as any,
    })

    const wrapper = mount(WalletManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openWithdrawalModal()
    expect(vm.isWithdrawalModalOpen).toBe(true)

    vm.handleWithdrawalSubmitted(mockWithdrawals[0])
    expect(walletSpy).toHaveBeenCalledTimes(2)
  })
})
