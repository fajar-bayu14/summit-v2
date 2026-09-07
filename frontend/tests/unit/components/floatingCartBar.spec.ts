import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import FloatingCartBar from '@/components/pendaki/FloatingCartBar.vue'
import { useCartStore } from '@/stores/cart'
import { formatRupiah } from '@/lib/formatters'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

describe('FloatingCartBar Component (Task 4.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should not render when cart has 0 items', () => {
    const wrapper = mount(FloatingCartBar, {
      props: {
        customItemCount: 0,
        customSubtotal: 0,
      },
    })

    expect(wrapper.html()).toBe('<!--v-if-->')
  })

  it('should render item count and formatted subtotal when cart has items', () => {
    const wrapper = mount(FloatingCartBar, {
      props: {
        customItemCount: 3,
        customSubtotal: 125000,
      },
    })

    expect(wrapper.text()).toContain('3')
    expect(wrapper.text()).toContain(formatRupiah(125000))
    expect(wrapper.text()).toContain('Checkout')
  })

  it('should trigger navigation to checkout on checkout click', async () => {
    const wrapper = mount(FloatingCartBar, {
      props: {
        customItemCount: 2,
        customSubtotal: 50000,
      },
    })

    const checkoutBtn = wrapper.findAll('button').filter(b => b.text().includes('Checkout'))[0]
    await checkoutBtn.trigger('click')

    expect(mockPush).toHaveBeenCalledWith('/pendaki/checkout')
  })
})
