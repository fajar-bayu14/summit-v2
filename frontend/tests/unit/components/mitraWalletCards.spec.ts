import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import WalletBalanceCards from '@/components/mitra/wallet/WalletBalanceCards.vue'
import type { MitraWallet } from '@/types/wallet'

describe('Mitra Wallet Balance Cards Component (Task 6.2)', () => {
  const mockWallet: MitraWallet = {
    id: 1,
    mitra_id: 10,
    saldo_pending: 250000,
    saldo_available: 1200000,
    total_withdrawn: 4500000,
    total_terhitung: 1450000,
    rekening_tujuan: {
      bank: 'Bank Central Asia (BCA)',
      rekening_bank: '5210987654',
      nama_rekening: 'Bayu Basecamp Prau',
    },
  }

  it('should render all balance amounts and destination bank account', () => {
    const wrapper = mount(WalletBalanceCards, {
      props: {
        wallet: mockWallet,
        loading: false,
      },
    })

    expect(wrapper.text()).toContain('1.200.000') // Saldo available
    expect(wrapper.text()).toContain('250.000') // Saldo pending
    expect(wrapper.text()).toContain('4.500.000') // Total withdrawn
    expect(wrapper.text()).toContain('Bank Central Asia (BCA)')
    expect(wrapper.text()).toContain('5210987654')
    expect(wrapper.text()).toContain('Bayu Basecamp Prau')
  })

  it('should emit requestWithdrawal when Tarik Dana button is clicked', async () => {
    const wrapper = mount(WalletBalanceCards, {
      props: {
        wallet: mockWallet,
        loading: false,
      },
    })

    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    await button.trigger('click')

    expect(wrapper.emitted('requestWithdrawal')).toBeTruthy()
  })

  it('should disable Tarik Dana button when saldo_available is less than Rp 50.000', () => {
    const lowBalanceWallet: MitraWallet = {
      ...mockWallet,
      saldo_available: 30000,
    }

    const wrapper = mount(WalletBalanceCards, {
      props: {
        wallet: lowBalanceWallet,
        loading: false,
      },
    })

    const button = wrapper.find('button')
    expect(button.attributes('disabled')).toBeDefined()
  })
})
