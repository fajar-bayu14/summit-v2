import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CheckInScannerModal from '@/components/mitra/orders/CheckInScannerModal.vue'
import mitraOrdersApi from '@/api/mitraOrders'
import type { MitraPesanan } from '@/types/order'

vi.mock('@/api/mitraOrders', () => ({
  default: {
    getOrders: vi.fn(),
    getOrderById: vi.fn(),
    checkInOrder: vi.fn(),
  },
}))

describe('Mitra Check-In Scanner Component (Task 4.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockPaidOrder: MitraPesanan = {
    id: 201,
    invoice: 'INV-20260906-001',
    user_id: 10,
    user: {
      id: 10,
      name: 'Rian Firdaus',
      email: 'rian@example.com',
      telepon: '081299887766',
    },
    gunung_id: 1,
    jalur_id: 1,
    basecamp_id: 1,
    tanggal_booking: '2026-09-10',
    tanggal_pendakian: '2026-09-10',
    status: 'paid',
    subtotal: 100000,
    total_bayar: 105000,
    anggotas: [
      {
        id: 1,
        pesanan_id: 201,
        nama_anggota: 'Dewi Lestari',
        identitas_tipe: 'ktp',
        identitas_nomor: '3301000011110002',
      },
    ],
    details: [
      {
        id: 50,
        pesanan_id: 201,
        produk_id: 1,
        nama_produk: 'Tiket Masuk',
        harga_satuan: 50000,
        kuantitas: 2,
        subtotal: 100000,
        status_operasional: 'ready',
      },
    ],
  }

  it('should require non-empty search input before searching', async () => {
    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.scanInput = ''
    await vm.handleSearch()

    expect(vm.errorMessage).toContain('Silakan masukkan nomor invoice')
    expect(mitraOrdersApi.getOrders).not.toHaveBeenCalled()
  })

  it('should search order by invoice and display matched order', async () => {
    vi.mocked(mitraOrdersApi.getOrders).mockResolvedValueOnce({
      success: true,
      data: {
        data: [mockPaidOrder],
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 1,
      },
    } as any)

    vi.mocked(mitraOrdersApi.getOrderById).mockResolvedValueOnce({
      success: true,
      data: mockPaidOrder,
    })

    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.scanInput = 'INV-20260906-001'
    await vm.handleSearch()

    expect(mitraOrdersApi.getOrders).toHaveBeenCalledWith({
      search: 'INV-20260906-001',
      per_page: 5,
    })
    expect(mitraOrdersApi.getOrderById).toHaveBeenCalledWith(201)
    expect(vm.matchedOrder).toEqual(mockPaidOrder)
    expect(vm.errorMessage).toBeNull()
  })

  it('should display error message when no order matches search', async () => {
    vi.mocked(mitraOrdersApi.getOrders).mockResolvedValueOnce({
      success: true,
      data: {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 0,
      },
    } as any)

    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.scanInput = 'INV-NOT-FOUND'
    await vm.handleSearch()

    expect(vm.errorMessage).toContain('tidak ditemukan')
    expect(vm.matchedOrder).toBeNull()
  })

  it('should execute check-in for paid order and emit checkedIn', async () => {
    const updatedOrder: MitraPesanan = {
      ...mockPaidOrder,
      status: 'on_going',
    }

    vi.mocked(mitraOrdersApi.checkInOrder).mockResolvedValueOnce({
      success: true,
      message: 'Check-in berhasil',
      data: updatedOrder,
    })

    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.matchedOrder = { ...mockPaidOrder }

    await vm.executeCheckIn()

    expect(mitraOrdersApi.checkInOrder).toHaveBeenCalledWith(201)
    expect(vm.matchedOrder.status).toBe('on_going')
    expect(vm.successMessage).toContain('Check-In Berhasil')
    expect(wrapper.emitted('checkedIn')?.[0]).toEqual([updatedOrder])
  })

  it('should prevent check-in if order is already on_going or not paid', async () => {
    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.matchedOrder = { ...mockPaidOrder, status: 'on_going' }

    await vm.executeCheckIn()
    expect(mitraOrdersApi.checkInOrder).not.toHaveBeenCalled()
    expect(vm.errorMessage).toContain('sudah melakukan check-in')

    vm.matchedOrder = { ...mockPaidOrder, status: 'pending' }
    await vm.executeCheckIn()
    expect(mitraOrdersApi.checkInOrder).not.toHaveBeenCalled()
    expect(vm.errorMessage).toContain('Hanya pesanan Lunas')
  })

  it('should auto check-in when autoCheckIn flag is enabled and order is paid', async () => {
    vi.mocked(mitraOrdersApi.getOrders).mockResolvedValueOnce({
      success: true,
      data: {
        data: [mockPaidOrder],
        current_page: 1,
        last_page: 1,
        per_page: 5,
        total: 1,
      },
    } as any)

    vi.mocked(mitraOrdersApi.getOrderById).mockResolvedValueOnce({
      success: true,
      data: mockPaidOrder,
    })

    const updatedOrder: MitraPesanan = {
      ...mockPaidOrder,
      status: 'on_going',
    }

    vi.mocked(mitraOrdersApi.checkInOrder).mockResolvedValueOnce({
      success: true,
      message: 'Check-in auto berhasil',
      data: updatedOrder,
    })

    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.autoCheckIn = true
    vm.scanInput = 'INV-20260906-001'

    await vm.handleSearch()

    expect(mitraOrdersApi.checkInOrder).toHaveBeenCalledWith(201)
    expect(wrapper.emitted('checkedIn')?.[0]).toEqual([updatedOrder])
  })

  it('should emit close and update:isOpen on close', () => {
    const wrapper = mount(CheckInScannerModal, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
