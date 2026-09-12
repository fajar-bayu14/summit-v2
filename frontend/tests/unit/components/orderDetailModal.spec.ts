import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import OrderDetailModal from '@/components/pendaki/OrderDetailModal.vue'
import { pendakiOrdersApi } from '@/api/pendakiOrders'
import type { PesananDetail } from '@/types/pendakiOrder'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiOrders', () => ({
  pendakiOrdersApi: {
    getOrderByInvoice: vi.fn(),
  },
}))

describe('OrderDetailModal Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockOrderPaid: PesananDetail = {
    id: 1,
    invoice: 'INV/20260710/ABC12',
    user_id: 10,
    basecamp_id: 2,
    jalur_id: 3,
    status: 'paid',
    subtotal: 100000,
    tanggal_booking: '2026-07-15',
    diskon: 0,
    biaya_layanan_user: 2500,
    komisi_admin: 10000,
    pendapatan_mitra: 90000,
    total_bayar: 102500,
    created_at: '2026-07-10T10:00:00Z',
    basecamp: {
      id: 2,
      nama_basecamp: 'Basecamp Selo',
    } as any,
    jalur: {
      id: 3,
      nama_jalur: 'Jalur Selo Gn. Merbabu',
    } as any,
    pembayaran: {
      id: 1,
      pesanan_id: 1,
      metode: 'qris',
      provider: 'QRIS',
      reference_id: 'REF-XENDIT-12345',
      checkout_url: 'https://checkout.xendit.co/12345',
      amount: 102500,
      paid_amount: 102500,
      status: 'paid',
      biaya_gateway: 750,
      paid_at: '2026-07-10T10:05:00Z',
      expired_at: null,
    },
    anggotas: [
      { id: 1, pesanan_id: 1, nama_anggota: 'Fajar', nik_identitas: '3301234567890001', telepon: null, telepon_darurat: null, hubungan_darurat: null },
    ],
    details: [
      {
        id: 1,
        pesanan_id: 1,
        produk_id: 10,
        qty: 1,
        harga: 100000,
        subtotal: 100000,
        status_operasional: 'paid',
        kode_tiket: 'TKT-001',
        produk: { id: 10, basecamp_id: 2, nama_produk: 'Tiket SIMAKSI Merbabu', kategori: 'tiket', harga: 100000, is_active: true },
      },
    ],
  }

  it('should render order details and paid payment status when opened', () => {
    const wrapper = mount(OrderDetailModal, {
      props: {
        isOpen: true,
        order: mockOrderPaid,
      },
    })

    expect(wrapper.text()).toContain('INV/20260710/ABC12')
    expect(wrapper.text()).toContain('Lunas (Berhasil)')
    expect(wrapper.text()).toContain('QRIS')
    expect(wrapper.text()).toContain('REF-XENDIT-12345')
    expect(wrapper.text()).toContain('Basecamp Selo')
    expect(wrapper.text()).toContain('Jalur Selo Gn. Merbabu')
    expect(wrapper.text()).toContain('Tiket SIMAKSI Merbabu')
    expect(wrapper.text()).toContain('Lihat E-Tiket & Barcode')
  })

  it('should render pending status and call getOrderByInvoice when check status is clicked', async () => {
    const mockOrderPending: PesananDetail = {
      ...mockOrderPaid,
      status: 'pending',
      pembayaran: {
        ...mockOrderPaid.pembayaran!,
        status: 'pending',
        paid_at: null,
      },
    }

    vi.mocked(pendakiOrdersApi.getOrderByInvoice).mockResolvedValueOnce({
      status: 'success',
      data: {
        ...mockOrderPending,
        status: 'paid',
        pembayaran: {
          ...mockOrderPending.pembayaran!,
          status: 'paid',
          paid_at: '2026-07-10T10:10:00Z',
        },
      },
    })

    const wrapper = mount(OrderDetailModal, {
      props: {
        isOpen: true,
        order: mockOrderPending,
      },
    })

    expect(wrapper.text()).toContain('Menunggu Pembayaran')
    expect(wrapper.text()).toContain('Bayar Sekarang')

    const checkStatusBtn = wrapper.findAll('button').find(b => b.text().includes('Cek Status Pembayaran'))
    expect(checkStatusBtn).toBeDefined()
    await checkStatusBtn!.trigger('click')
    await flushPromises()

    expect(pendakiOrdersApi.getOrderByInvoice).toHaveBeenCalledWith('INV/20260710/ABC12')
    expect(wrapper.emitted('order-updated')).toBeTruthy()
  })

  it('should navigate to e-ticket when Lihat E-Tiket & Barcode is clicked', async () => {
    const wrapper = mount(OrderDetailModal, {
      props: {
        isOpen: true,
        order: mockOrderPaid,
      },
    })

    const ticketBtn = wrapper.findAll('button').find(b => b.text().includes('Lihat E-Tiket & Barcode'))
    expect(ticketBtn).toBeDefined()
    await ticketBtn!.trigger('click')

    expect(mockPush).toHaveBeenCalledWith('/pendaki/orders/INV%2F20260710%2FABC12/ticket')
  })
})
