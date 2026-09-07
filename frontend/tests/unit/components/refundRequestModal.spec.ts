import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RefundRequestModal from '@/components/pendaki/RefundRequestModal.vue'
import { pendakiRefundApi } from '@/api/pendakiRefund'

vi.mock('@/api/pendakiRefund', () => ({
  pendakiRefundApi: {
    requestRefund: vi.fn()
  }
}))

describe('RefundRequestModal Component (Task 8.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render modal with invoice, total payment, and SOP calculations', () => {
    // 5 days ahead => H-3 or more => 100% full refund
    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 5)
    const tanggalBooking = futureDate.toISOString().split('T')[0]

    const wrapper = mount(RefundRequestModal, {
      props: {
        isOpen: true,
        invoice: 'INV-2026-0099',
        totalBayar: 200000,
        tanggalBooking
      }
    })

    expect(wrapper.text()).toContain('INV-2026-0099')
    expect(wrapper.text()).toContain('Pengajuan Refund & Pembatalan')
    expect(wrapper.text()).toContain('100% Pengembalian Penuh')
    expect(wrapper.text()).toContain('200.000')
  })

  it('should validate form and submit refund request', async () => {
    const mockRefundResult = {
      id: 12,
      pesanan_id: 99,
      status: 'pending' as const,
      nominal: 200000,
      alasan: 'Alasan mendesak tidak bisa berangkat'
    }
    vi.mocked(pendakiRefundApi.requestRefund).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockRefundResult
    })

    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 5)
    const tanggalBooking = futureDate.toISOString().split('T')[0]

    const wrapper = mount(RefundRequestModal, {
      props: {
        isOpen: true,
        invoice: 'INV-2026-0099',
        totalBayar: 200000,
        tanggalBooking
      }
    })

    // Fill in inputs
    const inputs = wrapper.findAll('input[type="text"]')
    await inputs[0].setValue('1234567890') // rekening
    await inputs[1].setValue('Budi Santoso') // nama

    const textarea = wrapper.find('textarea')
    await textarea.setValue('Alasan mendesak tidak bisa berangkat')

    await wrapper.find('form').trigger('submit.prevent')

    expect(pendakiRefundApi.requestRefund).toHaveBeenCalledWith(
      'INV-2026-0099',
      expect.objectContaining({
        alasan: 'Alasan mendesak tidak bisa berangkat',
        bank_tujuan: 'BCA',
        rekening_tujuan: '1234567890',
        nama_tujuan: 'Budi Santoso',
        nominal: 200000,
        refund_category: 'pre_trip'
      })
    )

    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('success')?.[0]).toEqual([mockRefundResult])
  })
})
