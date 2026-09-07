import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ETicketView from '@/views/pendaki/ETicketView.vue'
import { pendakiOrdersApi } from '@/api/pendakiOrders'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { invoice: 'INV/20260710/ABC12' },
  }),
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiOrders', () => ({
  pendakiOrdersApi: {
    getOrderByInvoice: vi.fn(),
  },
  getOrderByInvoice: vi.fn(),
}))

describe('ETicketView Component (Task 6.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should fetch order by invoice and render digital pass information', async () => {
    const mockOrder = {
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
        jam_operasional: '06:00 - 18:00 WIB',
        mitra: { id: 1, telepon: '08123456789' },
      } as any,
      jalur: {
        id: 3,
        nama_jalur: 'Jalur Bambangan Gn. Slamet',
      } as any,
      anggotas: [
        {
          id: 1,
          pesanan_id: 1,
          nama_anggota: 'Fajar Bayu',
          nik_identitas: '3301234567890001',
          telepon: '081234567890',
          telepon_darurat: '081298765432',
          hubungan_darurat: 'Orang Tua',
        },
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
      pembayaran: {
        id: 1,
        pesanan_id: 1,
        metode: 'qris',
        provider: 'QRIS BCA',
        reference_id: 'ref-1',
        checkout_url: null,
        amount: 102500,
        paid_amount: 102500,
        status: 'paid' as const,
        biaya_gateway: 0,
        paid_at: '2026-07-10T10:05:00Z',
        expired_at: null,
      },
    }

    vi.mocked(pendakiOrdersApi.getOrderByInvoice).mockResolvedValueOnce({
      status: 'success',
      data: mockOrder,
    })

    const wrapper = mount(ETicketView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
        },
      },
    })
    await flushPromises()

    expect(pendakiOrdersApi.getOrderByInvoice).toHaveBeenCalledWith('INV/20260710/ABC12')
    expect(wrapper.text()).toContain('SUMMIT DIGITAL PASS')
    expect(wrapper.text()).toContain('Jalur Bambangan Gn. Slamet')
    expect(wrapper.text()).toContain('Basecamp Bambangan')
    expect(wrapper.text()).toContain('Fajar Bayu')
    expect(wrapper.text()).toContain('3301234567890001')
    expect(wrapper.find('img').attributes('src')).toContain('api.qrserver.com')
  })
})
