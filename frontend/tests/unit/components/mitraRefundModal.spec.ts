import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RefundReviewModal from '@/components/mitra/refunds/RefundReviewModal.vue'
import mitraRefundsApi from '@/api/mitraRefunds'
import type { RefundItem } from '@/types/refund'

vi.mock('@/api/mitraRefunds', () => ({
  default: {
    approveRefund: vi.fn(),
    rejectRefund: vi.fn(),
  },
}))

describe('Mitra Refund Review Modal Component (Task 7.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockRefund: RefundItem = {
    id: 10,
    pesanan_id: 100,
    pesanan_invoice: 'INV-PRAU-100',
    mitra_id: 1,
    nominal: 200000,
    nominal_disetujui: null,
    status: 'pending',
    alasan: 'Jalur mendadak badai dan tidak bisa berangkat',
    bank_tujuan: 'BCA',
    rekening_tujuan: '1234567890',
    nama_tujuan: 'Siti Rahma',
    pesanan: {
      id: 100,
      invoice: 'INV-PRAU-100',
      total_bayar: 200000,
      tanggal_booking: '2026-09-08',
      tanggal_pendakian: '2026-09-10',
      status: 'paid',
      user: {
        id: 5,
        name: 'Siti Rahma',
        email: 'siti@example.com',
        telepon: '08123456789',
      },
    },
  }

  it('should render refund request details and format helpers properly', () => {
    const wrapper = mount(RefundReviewModal, {
      props: {
        isOpen: true,
        refund: mockRefund,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.formatRupiah(200000)).toContain('200.000')
    expect(vm.formatRupiah(null)).toBe('Rp 0')
    expect(vm.formatDateIndo('2026-09-08')).toContain('2026')
    expect(vm.formatDateIndo(null)).toBe('-')
    expect(vm.props.refund.pesanan_invoice).toBe('INV-PRAU-100')
    expect(vm.decision).toBe('approved')
  })

  it('should approve refund and emit reviewed event', async () => {
    const approvedRefund: RefundItem = {
      ...mockRefund,
      status: 'approved_by_mitra',
      nominal_disetujui: 200000,
    }

    vi.mocked(mitraRefundsApi.approveRefund).mockResolvedValueOnce({
      success: true,
      message: 'Refund disetujui',
      data: approvedRefund,
    })

    const wrapper = mount(RefundReviewModal, {
      props: {
        isOpen: true,
        refund: mockRefund,
      },
    })

    const vm = wrapper.vm as any
    await vm.handleApprove()

    expect(mitraRefundsApi.approveRefund).toHaveBeenCalledWith(10)
    expect(wrapper.emitted('reviewed')?.[0]).toEqual([approvedRefund])
    expect(vm.serverMessage).toContain('berhasil disetujui')
  })

  it('should require rejection reason when rejecting', async () => {
    const wrapper = mount(RefundReviewModal, {
      props: {
        isOpen: true,
        refund: mockRefund,
      },
    })

    const vm = wrapper.vm as any
    vm.decision = 'rejected'
    vm.rejectionReason = ''

    await vm.handleReject()

    expect(mitraRefundsApi.rejectRefund).not.toHaveBeenCalled()
    expect(vm.formError).toContain('Wajib mencantumkan alasan')
  })

  it('should reject refund when reason is provided and emit reviewed event', async () => {
    const rejectedRefund: RefundItem = {
      ...mockRefund,
      status: 'rejected_by_mitra',
      mitra_alasan_penolakan: 'Logistik dan tiket sudah siap',
    }

    vi.mocked(mitraRefundsApi.rejectRefund).mockResolvedValueOnce({
      success: true,
      message: 'Refund ditolak',
      data: rejectedRefund,
    })

    const wrapper = mount(RefundReviewModal, {
      props: {
        isOpen: true,
        refund: mockRefund,
      },
    })

    const vm = wrapper.vm as any
    vm.decision = 'rejected'
    vm.rejectionReason = 'Logistik dan tiket sudah siap'

    await vm.handleReject()

    expect(mitraRefundsApi.rejectRefund).toHaveBeenCalledWith(10, {
      alasan_penolakan: 'Logistik dan tiket sudah siap',
    })
    expect(wrapper.emitted('reviewed')?.[0]).toEqual([rejectedRefund])
    expect(vm.serverMessage).toContain('telah ditolak')
  })

  it('should emit close and update:isOpen on handleClose', () => {
    const wrapper = mount(RefundReviewModal, {
      props: {
        isOpen: true,
        refund: mockRefund,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
