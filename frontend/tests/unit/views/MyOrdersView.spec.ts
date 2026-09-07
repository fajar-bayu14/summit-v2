import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import MyOrdersView from '@/views/pendaki/MyOrdersView.vue'
import { pendakiOrdersApi } from '@/api/pendakiOrders'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiOrders', () => ({
  pendakiOrdersApi: {
    getMyOrders: vi.fn(),
    getOrderByInvoice: vi.fn(),
    cancelOrder: vi.fn(),
  },
  getMyOrders: vi.fn(),
  getOrderByInvoice: vi.fn(),
  cancelOrder: vi.fn(),
}))

describe('MyOrdersView Component (Task 6.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should fetch orders on mount and render empty state if no orders', async () => {
    vi.mocked(pendakiOrdersApi.getMyOrders).mockResolvedValueOnce({
      status: 'success',
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    })

    const wrapper = mount(MyOrdersView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          PaymentModal: true,
        },
      },
    })
    await flushPromises()

    expect(pendakiOrdersApi.getMyOrders).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Belum Ada Pesanan')
  })

  it('should render order cards with invoice, mountain, total, and action buttons', async () => {
    const mockOrders = [
      {
        id: 1,
        invoice: 'INV/20260710/ABC12',
        user_id: 10,
        basecamp_id: 2,
        jalur_id: 3,
        status: 'paid' as const,
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
          nama_basecamp: 'Basecamp Bambangan',
        } as any,
        jalur: {
          id: 3,
          nama_jalur: 'Jalur Bambangan Gn. Slamet',
        } as any,
        anggotas: [
          { id: 1, pesanan_id: 1, nama_anggota: 'Fajar', nik_identitas: '3301234567890001', telepon: null, telepon_darurat: null, hubungan_darurat: null },
        ],
        details: [
          {
            id: 1,
            pesanan_id: 1,
            produk_id: 10,
            qty: 1,
            harga: 50000,
            subtotal: 50000,
            status_operasional: 'paid',
            kode_tiket: 'TKT-001',
            produk: { id: 10, basecamp_id: 2, nama_produk: 'Tiket SIMAKSI', kategori: 'tiket', harga: 50000, is_active: true },
          },
        ],
      },
    ]

    vi.mocked(pendakiOrdersApi.getMyOrders).mockResolvedValueOnce({
      status: 'success',
      data: mockOrders,
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
    })

    const wrapper = mount(MyOrdersView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          PaymentModal: true,
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('INV/20260710/ABC12')
    expect(wrapper.text()).toContain('Basecamp Bambangan')
    expect(wrapper.text()).toContain('Jalur Bambangan Gn. Slamet')
    expect(wrapper.text()).toContain('Siap Check-In')
    expect(wrapper.text()).toContain('Lihat E-Tiket & QR')
  })

  it('should filter orders when status tab is clicked', async () => {
    vi.mocked(pendakiOrdersApi.getMyOrders).mockResolvedValue({
      status: 'success',
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    })

    const wrapper = mount(MyOrdersView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          PaymentModal: true,
        },
      },
    })
    await flushPromises()

    const pendingTab = wrapper.findAll('button').find(b => b.text().includes('Menunggu Bayar'))
    expect(pendingTab).toBeDefined()
    await pendingTab!.trigger('click')
    await flushPromises()

    expect(pendakiOrdersApi.getMyOrders).toHaveBeenCalledWith({
      page: 1,
      status: 'pending',
    })
  })
})
