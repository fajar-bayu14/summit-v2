import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCartStore } from '@/stores/cart'
import { pendakiCartApi } from '@/api/pendakiCart'

vi.mock('@/api/pendakiCart', () => ({
  pendakiCartApi: {
    getCart: vi.fn(),
    addItem: vi.fn(),
    updateItem: vi.fn(),
    removeItem: vi.fn(),
    clearCart: vi.fn(),
  },
}))

describe('useCartStore (Task 4.1)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should calculate getters totalItems, subtotal, ticketItem, and rentalItems correctly', () => {
    const store = useCartStore()

    store.cart = {
      id: 1,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 3,
      subtotal: 100000,
      items: [
        {
          id: 101,
          cart_id: 1,
          produk_id: 1,
          qty: 2,
          produk: {
            id: 1,
            basecamp_id: 5,
            nama_produk: 'Tiket SIMAKSI',
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
            nama_produk: 'Sewa Tenda Dome',
            kategori: 'rental',
            harga: 50000,
            is_active: true,
          },
        },
      ],
    }

    expect(store.totalItems).toBe(3)
    expect(store.subtotal).toBe(100000)
    expect(store.ticketItem?.produk.nama_produk).toBe('Tiket SIMAKSI')
    expect(store.rentalItems.length).toBe(1)
    expect(store.rentalItems[0].produk.nama_produk).toBe('Sewa Tenda Dome')
    expect(store.hasItems).toBe(true)
    expect(store.currentBasecampId).toBe(5)
  })

  it('should categorize English enum categories (ticket, guide, porter, merchandise) properly', () => {
    const store = useCartStore()

    store.cart = {
      id: 2,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 4,
      subtotal: 350000,
      items: [
        {
          id: 201,
          cart_id: 2,
          produk_id: 10,
          qty: 1,
          produk: {
            id: 10,
            basecamp_id: 5,
            nama_produk: 'Tiket Simaksi Cibodas',
            kategori: 'ticket',
            harga: 20000,
            is_active: true,
          },
        },
        {
          id: 202,
          cart_id: 2,
          produk_id: 11,
          qty: 1,
          produk: {
            id: 11,
            basecamp_id: 5,
            nama_produk: 'Pemandu / Guide APGI',
            kategori: 'guide',
            harga: 250000,
            is_active: true,
          },
        },
        {
          id: 203,
          cart_id: 2,
          produk_id: 12,
          qty: 2,
          produk: {
            id: 12,
            basecamp_id: 5,
            nama_produk: 'Gas Portabel',
            kategori: 'merchandise',
            harga: 40000,
            is_active: true,
          },
        },
      ],
    }

    expect(store.ticketItem?.produk.nama_produk).toBe('Tiket Simaksi Cibodas')
    expect(store.serviceItems.length).toBe(1)
    expect(store.serviceItems[0].produk.nama_produk).toBe('Pemandu / Guide APGI')
    expect(store.rentalItems.length).toBe(1)
    expect(store.rentalItems[0].produk.nama_produk).toBe('Gas Portabel')
  })

  it('should fetch cart from API and update state', async () => {
    const store = useCartStore()
    const mockCart = {
      id: 1,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 1,
      subtotal: 25000,
      items: [],
    }

    vi.mocked(pendakiCartApi.getCart).mockResolvedValueOnce({
      status: 'success',
      data: mockCart,
    })

    await store.fetchCart()

    expect(store.cart).toEqual(mockCart)
    expect(store.isLoading).toBe(false)
  })

  it('should handle removeItem and remove from state', async () => {
    const store = useCartStore()
    store.cart = {
      id: 1,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 1,
      subtotal: 25000,
      items: [
        {
          id: 101,
          cart_id: 1,
          produk_id: 1,
          qty: 1,
          produk: { id: 1, basecamp_id: 5, nama_produk: 'Tiket', kategori: 'tiket', harga: 25000, is_active: true },
        },
      ],
    }

    vi.mocked(pendakiCartApi.removeItem).mockResolvedValueOnce({ status: 'success', data: null })

    await store.removeItem(101)

    expect(store.cart).toBeNull()
  })
})
