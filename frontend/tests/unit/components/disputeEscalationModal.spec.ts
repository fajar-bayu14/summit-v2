import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import DisputeEscalationModal from '@/components/pendaki/DisputeEscalationModal.vue'
import { pendakiRefundApi } from '@/api/pendakiRefund'

vi.mock('@/api/pendakiRefund', () => ({
  pendakiRefundApi: {
    disputeRefund: vi.fn()
  }
}))

describe('DisputeEscalationModal Component (Task 8.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render modal with mitra rejection reason', () => {
    const wrapper = mount(DisputeEscalationModal, {
      props: {
        isOpen: true,
        refundId: 44,
        invoice: 'INV-2026-DISPUTE-01',
        mitraAlasanPenolakan: 'Pengajuan lewat dari batas waktu pembatalan'
      }
    })

    expect(wrapper.text()).toContain('Eskalasi Pusat Sengketa (Dispute)')
    expect(wrapper.text()).toContain('Pengajuan lewat dari batas waktu pembatalan')
  })

  it('should submit dispute reason when form is valid', async () => {
    const mockDisputedResult = {
      id: 44,
      pesanan_id: 10,
      status: 'disputed' as const,
      nominal: 100000,
      alasan: 'Refund',
      is_disputed: true,
      dispute_reason: 'Saya sudah konfirmasi pembatalan sejak 4 hari sebelum hari H'
    }

    vi.mocked(pendakiRefundApi.disputeRefund).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockDisputedResult
    })

    const wrapper = mount(DisputeEscalationModal, {
      props: {
        isOpen: true,
        refundId: 44,
        invoice: 'INV-2026-DISPUTE-01'
      }
    })

    const textarea = wrapper.find('textarea')
    await textarea.setValue('Saya sudah konfirmasi pembatalan sejak 4 hari sebelum hari H')

    await wrapper.find('form').trigger('submit.prevent')

    expect(pendakiRefundApi.disputeRefund).toHaveBeenCalledWith(44, {
      alasan_dispute: 'Saya sudah konfirmasi pembatalan sejak 4 hari sebelum hari H'
    })

    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('success')?.[0]).toEqual([mockDisputedResult])
  })
})
