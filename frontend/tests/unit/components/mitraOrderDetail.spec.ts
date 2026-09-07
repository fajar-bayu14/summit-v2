import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import OrderDetailDrawer from '@/components/mitra/orders/OrderDetailDrawer.vue'
import mitraOrdersApi from '@/api/mitraOrders'
import type { MitraPesanan } from '@/types/order'

vi.mock('@/api/mitraOrders', () => ({
  default: {
    updateItemStatus: vi.fn(),
  },
}))

describe('Mitra Order Detail Component (Task 4.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockOrder: MitraPesanan = {
    id: 101,
    invoice: 'INV-20260906-001',
    user_id: 5,
    user: {
      id: 5,
      name: 'Bayu Pendaki',
      email: 'bayu@example.com',
      telepon: '08123456789',
    },
    gunung_id: 1,
    jalur_id: 2,
    basecamp_id: 1,
    tanggal_booking: '2026-09-10',
    tanggal_pendakian: '2026-09-10',
    tanggal_turun: '2026-09-12',
    status: 'paid',
    subtotal: 350000,
    total_bayar: 355000,
    pendapatan_mitra: 332500,
    created_at: '2026-09-06T10:00:00Z',
    anggotas: [
      {
        id: 1,
        pesanan_id: 101,
        nama_anggota: 'Siti Rahma',
        identitas_tipe: 'ktp',
        identitas_nomor: '3201123456780001',
        jenis_kelamin: 'perempuan',
        telepon_darurat: '08129876543',
      },
      {
        id: 2,
        pesanan_id: 101,
        nama_anggota: 'Budi Santoso',
        identitas_tipe: 'ktp',
        identitas_nomor: '3201123456780002',
        jenis_kelamin: 'laki-laki',
        telepon_darurat: '08129876544',
      },
    ],
    details: [
      {
        id: 201,
        pesanan_id: 101,
        produk_id: 10,
        nama_produk: 'Tiket Masuk Gn. Prau (Patakbanteng)',
        harga_satuan: 50000,
        kuantitas: 3,
        subtotal: 150000,
        status_operasional: 'ready',
      },
      {
        id: 202,
        pesanan_id: 101,
        produk_id: 15,
        nama_produk: 'Tenda Dome 4P',
        harga_satuan: 100000,
        kuantitas: 2,
        subtotal: 200000,
        status_operasional: 'pending',
      },
    ],
  }

  it('should initialize and switch between tabs', async () => {
    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.activeTab).toBe('manifest')

    // Switch to items tab
    vm.activeTab = 'items'
    expect(vm.activeTab).toBe('items')

    // Switch to payment tab
    vm.activeTab = 'payment'
    expect(vm.activeTab).toBe('payment')
  })

  it('should format rupiah and indonesian date correctly', () => {
    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.formatRupiah(150000)).toContain('150.000')
    expect(vm.formatDateIndo('2026-09-10')).toContain('2026')
    expect(vm.formatDateIndo(null)).toBe('-')
  })

  it('should emit checkIn event when checkIn is triggered', async () => {
    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    vm.handleTriggerCheckIn()

    expect(wrapper.emitted('checkIn')?.[0]).toEqual([mockOrder])
  })

  it('should update item status through API and emit itemStatusUpdated', async () => {
    vi.mocked(mitraOrdersApi.updateItemStatus).mockResolvedValueOnce({
      success: true,
      message: 'Status item berhasil diperbarui',
      data: {
        id: 202,
        pesanan_id: 101,
        produk_id: 15,
        nama_produk: 'Tenda Dome 4P',
        harga_satuan: 100000,
        kuantitas: 2,
        subtotal: 200000,
        status_operasional: 'active',
      },
    })

    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    const targetItem = mockOrder.details![1]

    await vm.handleUpdateItemStatus(targetItem, 'active')

    expect(mitraOrdersApi.updateItemStatus).toHaveBeenCalledWith(101, 202, 'active')
    expect(targetItem.status_operasional).toBe('active')
    expect(wrapper.emitted('itemStatusUpdated')).toBeTruthy()
    expect(vm.itemActionMessage).toContain('Tenda Dome 4P')
  })

  it('should handle item status update error gracefully', async () => {
    vi.mocked(mitraOrdersApi.updateItemStatus).mockRejectedValueOnce(
      new Error('Network error or forbidden')
    )

    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    const targetItem = mockOrder.details![0]

    await vm.handleUpdateItemStatus(targetItem, 'completed')

    expect(mitraOrdersApi.updateItemStatus).toHaveBeenCalledWith(101, 201, 'completed')
    expect(vm.itemActionError).toBeTruthy()
  })

  it('should emit close and update:isOpen events on close', async () => {
    const wrapper = mount(OrderDetailDrawer, {
      props: {
        isOpen: true,
        order: mockOrder,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
