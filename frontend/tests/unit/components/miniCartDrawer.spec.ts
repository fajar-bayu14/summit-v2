import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import MiniCartDrawer from '@/components/pendaki/MiniCartDrawer.vue'
import { useCartStore } from '@/stores/cart'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

describe('MiniCartDrawer Component (Task 4.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should expose cart items list from store', () => {
    const cartStore = useCartStore()
    cartStore.cart = {
      id: 1,
      user_id: 10,
      basecamp_id: 5,
      jalur_id: 2,
      tanggal_booking: '2026-07-20',
      status: 'active',
      total_item: 2,
      subtotal: 50000,
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
      ],
    }

    const wrapper = mount(MiniCartDrawer, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.items.length).toBe(1)
    expect(vm.items[0].produk.nama_produk).toBe('Tiket SIMAKSI Merbabu')
  })

  it('should handle quantity modifications and navigation', () => {
    const cartStore = useCartStore()
    const updateSpy = vi.spyOn(cartStore, 'updateQuantity').mockImplementation(vi.fn())

    const wrapper = mount(MiniCartDrawer, {
      props: {
        isOpen: true,
      },
    })

    const vm = wrapper.vm as any
    vm.handleIncrement(101, 2)
    expect(updateSpy).toHaveBeenCalledWith(101, 3)

    vm.handleGoToCheckout()
    expect(mockPush).toHaveBeenCalledWith('/pendaki/checkout')
  })
})
