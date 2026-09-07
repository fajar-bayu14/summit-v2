import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import CartView from '@/views/pendaki/CartView.vue'
import { useCartStore } from '@/stores/cart'
import { formatRupiah } from '@/lib/formatters'

const mockPush = vi.fn()
const mockBack = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
    back: mockBack,
  }),
}))

describe('CartView View Component (Task 4.3)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should render empty state when cart has no items', async () => {
    const cartStore = useCartStore()
    vi.spyOn(cartStore, 'fetchCart').mockImplementation(async () => {
      cartStore.cart = null
    })

    const wrapper = mount(CartView, {
      global: {
        stubs: {
          'router-link': {
            template: '<a><slot /></a>',
          },
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Keranjang Belanja Kosong')
    expect(wrapper.text()).toContain('Eksplorasi Gunung Sekarang')
  })

  it('should render grouped cart items, ticket, rental, services, and summary', async () => {
    const cartStore = useCartStore()
    cartStore.cart = {
      id: 1,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 4,
      subtotal: 350000,
      basecamp: {
        id: 5,
        nama_basecamp: 'Basecamp Merbabu Selo',
      },
      jalur: {
        id: 2,
        nama_jalur: 'Jalur Selo',
      },
      items: [
        {
          id: 101,
          cart_id: 1,
          produk_id: 1,
          qty: 2,
          produk: {
            id: 1,
            basecamp_id: 5,
            nama_produk: 'Tiket SIMAKSI Merbabu',
            kategori: 'tiket',
            harga: 25000,
            is_active: true,
          },
        },
        {
          id: 102,
          cart_id: 1,
          produk_id: 2,
          qty: 1,
          produk: {
            id: 2,
            basecamp_id: 5,
            nama_produk: 'Sewa Tenda Dome 4P',
            kategori: 'rental',
            harga: 50000,
            is_active: true,
          },
        },
        {
          id: 103,
          cart_id: 1,
          produk_id: 3,
          qty: 1,
          produk: {
            id: 3,
            basecamp_id: 5,
            nama_produk: 'Jasa Porter Camp',
            kategori: 'jasa',
            harga: 250000,
            is_active: true,
          },
        },
      ],
    }

    vi.spyOn(cartStore, 'fetchCart').mockImplementation(async () => {})

    const wrapper = mount(CartView, {
      global: {
        stubs: {
          'router-link': {
            template: '<a><slot /></a>',
          },
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Basecamp Merbabu Selo')
    expect(wrapper.text()).toContain('Tiket SIMAKSI Resmi')
    expect(wrapper.text()).toContain('Sewa Perlengkapan Outdoor (1)')
    expect(wrapper.text()).toContain('Jasa Porter & Guide APGI (1)')
    expect(wrapper.text()).toContain('Ringkasan Belanja')
    expect(wrapper.text()).toContain(formatRupiah(350000))
  })
})
