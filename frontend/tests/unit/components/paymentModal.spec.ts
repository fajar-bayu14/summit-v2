import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PaymentModal from '@/components/pendaki/PaymentModal.vue'
import * as checkoutApi from '@/api/pendakiCheckout'
import type { Pesanan } from '@/types/pendakiCheckout'

describe('PaymentModal Component (Task 5.4)', () => {
  const mockOrder: Pesanan = {
    id: 1,
    invoice: 'INV/20260706/ABC12',
    user_id: 10,
    basecamp_id: 2,
    jalur_id: 3,
    status: 'pending',
    subtotal: 100000,
    tanggal_booking: '2026-07-10',
    diskon: 0,
    biaya_layanan_user: 2500,
    komisi_admin: 10000,
    pendapatan_mitra: 90000,
    total_bayar: 102500,
    pembayaran: {
      id: 1,
      pesanan_id: 1,
      metode: 'qris',
      provider: 'Xendit',
      reference_id: 'xendit-ref-1',
      checkout_url: 'https://checkout.xendit.co/v2/invoice/abc123',
      amount: 102500,
      paid_amount: null,
      status: 'pending',
      biaya_gateway: 0,
      paid_at: null,
      expired_at: new Date(Date.now() + 1800 * 1000).toISOString(),
    },
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render invoice and formatted total amount when open', () => {
    const wrapper = mount(PaymentModal, {
      props: {
        isOpen: true,
        order: mockOrder,
        checkoutUrl: mockOrder.pembayaran?.checkout_url,
      },
    })

    expect(wrapper.text()).toContain('INV/20260706/ABC12')
    expect(wrapper.text()).toContain('Pembayaran Xendit Gateway')
    expect(wrapper.text()).toContain('Bayar via Xendit')
  })

  it('should not render content when isOpen is false', () => {
    const wrapper = mount(PaymentModal, {
      props: {
        isOpen: false,
        order: mockOrder,
      },
    })

    expect(wrapper.find('.fixed').exists()).toBe(false)
  })

  it('should check status and emit payment-success when order becomes paid', async () => {
    const paidOrder = {
      ...mockOrder,
      status: 'paid' as const,
      pembayaran: {
        ...mockOrder.pembayaran!,
        status: 'paid' as const,
      },
    }

    vi.spyOn(checkoutApi, 'getOrderDetail').mockResolvedValueOnce({
      status: 'success',
      data: paidOrder,
    })

    const wrapper = mount(PaymentModal, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const checkStatusBtn = wrapper.findAll('button').find(b => b.text().includes('Saya Sudah Membayar'))
    expect(checkStatusBtn).toBeDefined()
    await checkStatusBtn!.trigger('click')

    expect(checkoutApi.getOrderDetail).toHaveBeenCalledWith('INV/20260706/ABC12')
    expect(wrapper.emitted('payment-success')).toBeTruthy()
  })
})
