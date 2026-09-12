import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import PendakiHeader from '@/components/pendaki/PendakiHeader.vue'
import PendakiMobileNav from '@/components/pendaki/PendakiMobileNav.vue'
import PendakiLayout from '@/layouts/PendakiLayout.vue'
import { useAuthStore } from '@/stores/auth'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
  useRoute: () => ({
    path: '/pendaki',
    query: {},
  }),
}))

describe('Pendaki Navigation & Layout Shell Components (Task 0.3)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('PendakiHeader Component', () => {
    it('should render brand logo, navigation links, and guest action buttons when unauthenticated', () => {
      const authStore = useAuthStore()
      authStore.token = null
      authStore.user = null

      const wrapper = mount(PendakiHeader, {
        props: {
          cartItemCount: 2,
        },
        global: {
          stubs: {
            'router-link': {
              template: '<a><slot /></a>',
            },
            'router-view': true,
          },
        },
      })

      expect(wrapper.text()).toContain('SUMMIT')
      expect(wrapper.text()).toContain('MARKETPLACE')
      expect(wrapper.text()).toContain('Jelajah Gunung')
      expect(wrapper.text()).toContain('Masuk')
      expect(wrapper.text()).toContain('Daftar')
      expect(wrapper.text()).toContain('2') // Cart count
    })

    it('should emit search and navigate when search form is submitted', async () => {
      const wrapper = mount(PendakiHeader, {
        global: {
          stubs: {
            'router-link': {
              template: '<a><slot /></a>',
            },
            'router-view': true,
          },
        },
      })

      const searchInput = wrapper.find('input')
      await searchInput.setValue('Merbabu')
      await wrapper.find('form').trigger('submit.prevent')

      expect(wrapper.emitted('search')).toBeTruthy()
      expect(wrapper.emitted('search')?.[0]?.[0]).toBe('Merbabu')
      expect(mockPush).toHaveBeenCalledWith({ path: '/pendaki', query: { q: 'Merbabu' } })
    })

    it('should show user avatar and kyc status when authenticated', () => {
      const authStore = useAuthStore()
      authStore.token = 'test-token'
      authStore.user = {
        id: 1,
        name: 'Fajar Pendaki',
        email: 'fajar@example.com',
        role: 'pendaki',
        pendaki: {
          status_verifikasi: 'verified',
        },
      }

      const wrapper = mount(PendakiHeader, {
        global: {
          stubs: {
            'router-link': {
              template: '<a><slot /></a>',
            },
            'router-view': true,
          },
        },
      })

      expect(wrapper.text()).toContain('Fajar Pendaki')
      expect(wrapper.text()).not.toContain('Masuk')
    })

    it('should link cart icon to /pendaki/cart and pesanan to /pendaki/orders in user dropdown', async () => {
      const authStore = useAuthStore()
      authStore.token = 'test-token'
      authStore.user = {
        id: 1,
        name: 'Fajar Pendaki',
        email: 'fajar@example.com',
        role: 'pendaki',
      }

      const wrapper = mount(PendakiHeader, {
        props: { cartItemCount: 1 },
        global: {
          stubs: {
            'router-link': {
              props: ['to'],
              template: '<a :href="to"><slot /></a>',
            },
          },
        },
      })

      const cartLink = wrapper.find('a[href="/pendaki/cart"]')
      expect(cartLink.exists()).toBe(true)

      // Open dropdown
      await wrapper.find('button.cursor-pointer').trigger('click')
      const ordersLink = wrapper.find('a[href="/pendaki/orders"]')
      expect(ordersLink.exists()).toBe(true)
    })
  })

  describe('PendakiMobileNav Component', () => {
    it('should render 5 quick navigation buttons with correct routes for Keranjang and Pesanan', () => {
      const wrapper = mount(PendakiMobileNav, {
        props: {
          cartItemCount: 3,
        },
        global: {
          stubs: {
            'router-link': {
              props: ['to'],
              template: '<a :href="to"><slot /></a>',
            },
            'router-view': true,
          },
        },
      })

      expect(wrapper.text()).toContain('Beranda')
      expect(wrapper.text()).toContain('Jelajah')
      expect(wrapper.text()).toContain('Keranjang')
      expect(wrapper.text()).toContain('Pesanan')
      expect(wrapper.text()).toContain('Akun')
      expect(wrapper.text()).toContain('3') // Cart badge

      expect(wrapper.find('a[href="/pendaki/cart"]').exists()).toBe(true)
      expect(wrapper.find('a[href="/pendaki/orders"]').exists()).toBe(true)
    })
  })

  describe('PendakiLayout Component', () => {
    it('should render header, main container, mobile nav, and footer', () => {
      const wrapper = mount(PendakiLayout, {
        global: {
          stubs: {
            'router-link': {
              template: '<a><slot /></a>',
            },
            'router-view': true,
          },
        },
      })

      expect(wrapper.findComponent(PendakiHeader).exists()).toBe(true)
      expect(wrapper.findComponent(PendakiMobileNav).exists()).toBe(true)
      expect(wrapper.text()).toContain('SUMMIT MARKETPLACE')
      expect(wrapper.text()).toContain('Hak Cipta Dilindungi')
    })
  })
})
