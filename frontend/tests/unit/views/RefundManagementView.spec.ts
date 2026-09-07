import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import RefundManagementView from '@/views/mitra/RefundManagementView.vue'
import mitraRefundsApi from '@/api/mitraRefunds'
import type { RefundItem } from '@/types/refund'

describe('RefundManagementView View Component (Task 7.3)', () => {
  const mockRefunds: RefundItem[] = [
    {
      id: 1,
      pesanan_id: 101,
      pesanan_invoice: 'INV-PRAU-001',
      mitra_id: 1,
      nominal: 150000,
      nominal_disetujui: null,
      status: 'pending',
      alasan: 'Sakit mendadak sebelum berangkat',
      bank_tujuan: 'BCA',
      rekening_tujuan: '1122334455',
      nama_tujuan: 'Bayu Pendaki',
      pesanan: {
        id: 101,
        invoice: 'INV-PRAU-001',
        total_bayar: 150000,
        tanggal_booking: '2026-09-08',
        tanggal_pendakian: '2026-09-10',
        status: 'paid',
        user: {
          id: 5,
          name: 'Bayu Pendaki',
          email: 'bayu@example.com',
          telepon: '0812345678',
        },
      },
    },
    {
      id: 2,
      pesanan_id: 102,
      pesanan_invoice: 'INV-PRAU-002',
      mitra_id: 1,
      nominal: 300000,
      nominal_disetujui: 300000,
      status: 'approved_by_mitra',
      alasan: 'Jalur ditutup cuaca ekstrem',
      bank_tujuan: 'Mandiri',
      rekening_tujuan: '9988776655',
      nama_tujuan: 'Rian Pratama',
      pesanan: {
        id: 102,
        invoice: 'INV-PRAU-002',
        total_bayar: 300000,
        tanggal_booking: '2026-09-08',
        tanggal_pendakian: '2026-09-11',
        status: 'cancelled',
        user: {
          id: 6,
          name: 'Rian Pratama',
          email: 'rian@example.com',
          telepon: '0812987654',
        },
      },
    },
    {
      id: 3,
      pesanan_id: 103,
      pesanan_invoice: 'INV-PRAU-003',
      mitra_id: 1,
      nominal: 200000,
      nominal_disetujui: null,
      status: 'rejected_by_mitra',
      mitra_alasan_penolakan: 'Sudah lewat H-1',
      alasan: 'Berubah rencana pribadi',
      bank_tujuan: 'BRI',
      rekening_tujuan: '5544332211',
      nama_tujuan: 'Dedi Kusnadi',
      pesanan: {
        id: 103,
        invoice: 'INV-PRAU-003',
        total_bayar: 200000,
        tanggal_booking: '2026-09-08',
        tanggal_pendakian: '2026-09-09',
        status: 'paid',
        user: {
          id: 7,
          name: 'Dedi Kusnadi',
          email: 'dedi@example.com',
          telepon: '0812112233',
        },
      },
    },
    {
      id: 4,
      pesanan_id: 104,
      pesanan_invoice: 'INV-PRAU-004',
      mitra_id: 1,
      nominal: 250000,
      nominal_disetujui: null,
      status: 'disputed',
      alasan: 'Banding ke admin atas penolakan',
      bank_tujuan: 'BNI',
      rekening_tujuan: '6677889900',
      nama_tujuan: 'Siti Rahma',
      pesanan: {
        id: 104,
        invoice: 'INV-PRAU-004',
        total_bayar: 250000,
        tanggal_booking: '2026-09-08',
        tanggal_pendakian: '2026-09-09',
        status: 'paid',
        user: {
          id: 8,
          name: 'Siti Rahma',
          email: 'siti@example.com',
          telepon: '0812445566',
        },
      },
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load refund requests and compute KPI metrics on mount', async () => {
    const getRefundsSpy = vi.spyOn(mitraRefundsApi, 'getRefunds').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockRefunds,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 4,
      },
    } as any)

    const wrapper = mount(RefundManagementView)
    await flushPromises()

    expect(getRefundsSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.refunds.length).toBe(4)
    expect(vm.metrics.pending).toBe(1)
    expect(vm.metrics.approved).toBe(1)
    expect(vm.metrics.rejected).toBe(1)
    expect(vm.metrics.disputed).toBe(1)

    expect(wrapper.text()).toContain('INV-PRAU-001')
    expect(wrapper.text()).toContain('Bayu Pendaki')
    expect(wrapper.text()).toContain('Rian Pratama')
    expect(wrapper.text()).toContain('Dedi Kusnadi')
    expect(wrapper.text()).toContain('Siti Rahma')
  })

  it('should filter refunds by status tab', async () => {
    const getRefundsSpy = vi.spyOn(mitraRefundsApi, 'getRefunds').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [mockRefunds[0]],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    } as any)

    const wrapper = mount(RefundManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleStatusFilter('pending')
    await flushPromises()

    expect(getRefundsSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        status: 'pending',
        page: 1,
      })
    )
  })

  it('should open review modal when openReviewModal is called', async () => {
    vi.spyOn(mitraRefundsApi, 'getRefunds').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockRefunds,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 4,
      },
    } as any)

    const wrapper = mount(RefundManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openReviewModal(mockRefunds[0])

    expect(vm.selectedRefund).toEqual(mockRefunds[0])
    expect(vm.isModalOpen).toBe(true)
  })

  it('should update refund record and re-fetch when handleRefundReviewed is called', async () => {
    const getRefundsSpy = vi.spyOn(mitraRefundsApi, 'getRefunds').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockRefunds],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 4,
      },
    } as any)

    const wrapper = mount(RefundManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    const updatedRecord: RefundItem = {
      ...mockRefunds[0],
      status: 'approved_by_mitra',
      nominal_disetujui: 150000,
    }

    vm.handleRefundReviewed(updatedRecord)
    await flushPromises()

    expect(vm.refunds[0].status).toBe('approved_by_mitra')
    expect(getRefundsSpy).toHaveBeenCalledTimes(2)
  })

  it('should reset filters properly', async () => {
    const getRefundsSpy = vi.spyOn(mitraRefundsApi, 'getRefunds').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockRefunds,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 4,
      },
    } as any)

    const wrapper = mount(RefundManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.search = 'Bayu'
    vm.filters.status = 'approved_by_mitra'

    vm.resetFilters()
    await flushPromises()

    expect(vm.filters.search).toBe('')
    expect(vm.filters.status).toBe('')
    expect(getRefundsSpy).toHaveBeenLastCalledWith({
      page: 1,
      per_page: 15,
    })
  })

  it('should format rupiah and date indo properly', async () => {
    const wrapper = mount(RefundManagementView)
    const vm = wrapper.vm as any

    expect(vm.formatRupiah(150000)).toContain('150.000')
    expect(vm.formatRupiah(null)).toBe('Rp 0')
    expect(vm.formatDateIndo('2026-09-10')).toContain('2026')
    expect(vm.formatDateIndo(null)).toBe('-')
  })
})
