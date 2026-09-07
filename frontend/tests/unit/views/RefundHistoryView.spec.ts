import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RefundHistoryView from '@/views/pendaki/RefundHistoryView.vue'
import { pendakiOrdersApi } from '@/api/pendakiOrders'

vi.mock('@/api/pendakiOrders', () => ({
  pendakiOrdersApi: {
    getMyOrders: vi.fn()
  }
}))

describe('RefundHistoryView Component (Task 8.4)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render header, guarantees, and load refund orders', async () => {
    const mockOrders = [
      {
        id: 1,
        invoice: 'INV-2026-REF-01',
        total_bayar: 200000,
        status: 'cancelled',
        tanggal_booking: '2026-09-15',
        created_at: '2026-09-01',
        refund: {
          id: 101,
          pesanan_id: 1,
          nominal: 200000,
          status: 'pending',
          alasan: 'Jalur ditutup cuaca buruk',
          bank_tujuan: 'BCA',
          rekening_tujuan: '1234567890',
          nama_tujuan: 'Fajar Bayu'
        }
      }
    ]

    vi.mocked(pendakiOrdersApi.getMyOrders).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        items: mockOrders as any,
        meta: { current_page: 1, total: 1, last_page: 1, per_page: 10 }
      }
    })

    const wrapper = mount(RefundHistoryView, {
      global: {
        stubs: ['router-link']
      }
    })

    expect(wrapper.text()).toContain('Pusat Bantuan & Pengajuan Refund')
    expect(wrapper.text()).toContain('Garansi 100% (H-3)')

    await vi.dynamicImportSettled()

    expect(wrapper.text()).toContain('INV-2026-REF-01')
    expect(wrapper.text()).toContain('200.000')
    expect(wrapper.text()).toContain('Menunggu Review Mitra')
  })
})
