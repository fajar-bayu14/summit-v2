import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import BasecampStorefrontView from '@/views/pendaki/BasecampStorefrontView.vue'
import { pendakiProductsApi } from '@/api/pendakiProducts'

const mockBack = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    back: mockBack,
  }),
  useRoute: () => ({
    params: { id: '10' },
  }),
}))

vi.mock('@/api/pendakiProducts', () => ({
  pendakiProductsApi: {
    getProducts: vi.fn(),
  },
}))

describe('BasecampStorefrontView View Component (Task 3.5)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should fetch storefront products and render header tabs on mount', async () => {
    const mockProducts = [
      {
        id: 1,
        basecamp_id: 10,
        nama_produk: 'Tiket Masuk SIMAKSI Merbabu Selo',
        kategori: 'tiket',
        harga: 25000,
        is_active: true,
        basecamp: {
          id: 10,
          nama_basecamp: 'Basecamp Merbabu Selo',
          jam_operasional: '24 Jam',
          mitra: { id: 2, nama_mitra: 'Koperasi Paguyuban Selo' },
        },
        tiket: {
          id: 1,
          produk_id: 1,
          jalur_id: 5,
          kuotas: [
            { id: 101, produk_tiket_id: 1, tanggal: '2026-07-20', kuota_total: 100, kuota_tersisa: 75 },
          ],
        },
      },
      {
        id: 2,
        basecamp_id: 10,
        nama_produk: 'Sewa Tenda Dome 4P',
        kategori: 'rental',
        harga: 45000,
        stok: 10,
        satuan: 'hari',
        is_active: true,
      },
      {
        id: 3,
        basecamp_id: 10,
        nama_produk: 'Jasa Porter Camp',
        kategori: 'jasa',
        harga: 250000,
        is_active: true,
      },
    ]

    vi.mocked(pendakiProductsApi.getProducts).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockProducts,
    })

    const wrapper = mount(BasecampStorefrontView, {
      global: {
        stubs: {
          'router-link': {
            template: '<a><slot /></a>',
          },
        },
      },
    })
    await flushPromises()

    expect(pendakiProductsApi.getProducts).toHaveBeenCalledWith({ basecamp_id: 10 })
    expect(wrapper.text()).toContain('Basecamp Merbabu Selo')
    expect(wrapper.text()).toContain('Tiket SIMAKSI & Kuota')
    expect(wrapper.text()).toContain('Sewa Alat Outdoor (1)')
    expect(wrapper.text()).toContain('Porter & Guide (1)')
  })
})
