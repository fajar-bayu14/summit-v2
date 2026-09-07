import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import OrderManagementView from '@/views/mitra/OrderManagementView.vue'
import mitraOrdersApi from '@/api/mitraOrders'
import type { MitraPesanan } from '@/types/order'

describe('OrderManagementView View Component (Task 4.4)', () => {
  const mockOrders: MitraPesanan[] = [
    {
      id: 1,
      invoice: 'INV-20260906-001',
      user_id: 10,
      user: {
        id: 10,
        name: 'Ahmad Fauzi',
        email: 'ahmad@example.com',
        telepon: '081234567890',
      },
      basecamp_id: 1,
      tanggal_booking: '2026-09-10',
      tanggal_pendakian: '2026-09-10',
      status: 'paid',
      subtotal: 150000,
      total_bayar: 155000,
      pendapatan_mitra: 142500,
      anggotas: [
        {
          id: 101,
          pesanan_id: 1,
          nama_anggota: 'Siti Rahma',
          identitas_tipe: 'ktp',
          identitas_nomor: '3201123456780001',
        },
      ],
      details: [
        {
          id: 201,
          pesanan_id: 1,
          produk_id: 1,
          nama_produk: 'Tiket Masuk',
          harga_satuan: 50000,
          kuantitas: 2,
          subtotal: 100000,
          status_operasional: 'ready',
        },
      ],
    },
    {
      id: 2,
      invoice: 'INV-20260906-002',
      user_id: 11,
      user: {
        id: 11,
        name: 'Bambang Sudirman',
        email: 'bambang@example.com',
        telepon: '081298765432',
      },
      basecamp_id: 1,
      tanggal_booking: '2026-09-11',
      tanggal_pendakian: '2026-09-11',
      status: 'on_going',
      subtotal: 200000,
      total_bayar: 200000,
      pendapatan_mitra: 190000,
      anggotas: [],
      details: [],
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load orders and compute summary metrics on mount', async () => {
    const getOrdersSpy = vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockOrders,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
      meta: {
        total_pendapatan_bersih: 332500,
        total_transaksi_paid: 2,
      },
    } as any)

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    expect(getOrdersSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.orders.length).toBe(2)
    expect(vm.metrics.totalOrders).toBe(2)
    expect(vm.metrics.totalNetIncome).toBe(332500)

    expect(wrapper.text()).toContain('INV-20260906-001')
    expect(wrapper.text()).toContain('Ahmad Fauzi')
    expect(wrapper.text()).toContain('Bambang Sudirman')
  })

  it('should filter orders when status tab is selected', async () => {
    const getOrdersSpy = vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [mockOrders[0]],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    } as any)

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleStatusFilter('paid')
    await flushPromises()

    expect(getOrdersSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        status: 'paid',
        page: 1,
      })
    )
  })

  it('should handle date filter and reset filters', async () => {
    const getOrdersSpy = vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockOrders,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
    } as any)

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.tanggal_booking = '2026-09-10'
    vm.handleDateFilter()
    await flushPromises()

    expect(getOrdersSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        tanggal_booking: '2026-09-10',
      })
    )

    // Reset filters
    vm.resetFilters()
    await flushPromises()

    expect(vm.filters.search).toBe('')
    expect(vm.filters.status).toBe('')
    expect(vm.filters.tanggal_booking).toBe('')
  })

  it('should open detail drawer when detail button is clicked', async () => {
    vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockOrders,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
    } as any)

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openDetail(mockOrders[0])

    expect(vm.selectedOrder).toEqual(mockOrders[0])
    expect(vm.isDetailDrawerOpen).toBe(true)
  })

  it('should handle check-in order and update status locally', async () => {
    vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockOrders],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
    } as any)

    const checkInSpy = vi.spyOn(mitraOrdersApi, 'checkInOrder').mockResolvedValueOnce({
      status: 'success',
      message: 'Check-in berhasil',
      data: {
        ...mockOrders[0],
        status: 'on_going',
      },
    })

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleCheckInOrder(mockOrders[0])
    await flushPromises()

    expect(checkInSpy).toHaveBeenCalledWith(1)
  })

  it('should open scanner modal and handle checked-in event', async () => {
    const getOrdersSpy = vi.spyOn(mitraOrdersApi, 'getOrders').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockOrders,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
      },
    } as any)

    const wrapper = mount(OrderManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openScanner()
    expect(vm.isScannerModalOpen).toBe(true)

    vm.handleCheckedInFromScanner(mockOrders[0])
    expect(getOrdersSpy).toHaveBeenCalledTimes(2)
  })
})
