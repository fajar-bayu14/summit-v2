import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import CheckoutView from '@/views/pendaki/CheckoutView.vue'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { pendakiCheckoutApi } from '@/api/pendakiCheckout'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiCheckout', () => ({
  pendakiCheckoutApi: {
    checkout: vi.fn(),
    getOrderDetail: vi.fn(),
    cancelOrder: vi.fn(),
    listOrders: vi.fn(),
  },
  getOrderDetail: vi.fn(),
  cancelOrder: vi.fn(),
  checkoutOrder: vi.fn(),
  listOrders: vi.fn(),
}))

describe('CheckoutView Component (Task 5.4)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should render empty cart guard if cart is empty', async () => {
    const cartStore = useCartStore()
    cartStore.cart = null
    vi.spyOn(cartStore, 'fetchCart').mockImplementation(async () => {})

    const wrapper = mount(CheckoutView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          KycStatusBanner: true,
          ClimberManifestForm: true,
          SafetySopChecklist: true,
          PaymentModal: true,
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Keranjang Masih Kosong')
  })

  it('should render manifest, sop checklist, and order summary when cart has items', async () => {
    const cartStore = useCartStore()
    const authStore = useAuthStore()

    authStore.user = {
      id: 1,
      name: 'Fajar Bayu',
      email: 'fajar@example.com',
      telepon: '081234567890',
      role: 'pendaki',
    } as any

    authStore.updatePendakiProfile({
      id: 1,
      nik: '3301234567890001',
      status_verifikasi: 'verified',
      telepon_darurat: '081298765432',
      hubungan_darurat: 'Orang Tua',
    })

    cartStore.cart = {
      id: 1,
      user_id: 1,
      basecamp_id: 2,
      jalur_id: 3,
      total_item: 2,
      subtotal: 100000,
      tanggal_booking: '2026-07-15',
      basecamp: {
        id: 2,
        nama_basecamp: 'Basecamp Bambangan',
      } as any,
      jalur: {
        id: 3,
        nama_jalur: 'Jalur Bambangan',
      } as any,
      items: [
        {
          id: 10,
          cart_id: 1,
          produk_id: 1,
          qty: 2,
          tipe_produk: 'tiket',
          harga_saat_ini: 50000,
          subtotal: 100000,
          produk: {
            id: 1,
            nama_produk: 'Tiket SIMAKSI Gn. Slamet',
            tipe: 'tiket',
            harga: 50000,
            stok: 100,
            status: 'aktif',
          } as any,
        },
      ],
    } as any

    vi.spyOn(cartStore, 'fetchCart').mockImplementation(async () => {})

    const wrapper = mount(CheckoutView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          KycStatusBanner: true,
          ClimberManifestForm: {
            template: '<div class="climber-manifest-mock">Manifest Form</div>',
          },
          SafetySopChecklist: {
            template: '<div class="safety-sop-mock">SOP Checklist</div>',
          },
          PaymentModal: {
            template: '<div class="payment-modal-mock">Payment Modal</div>',
          },
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Manifes Rombongan & Checkout')
    expect(wrapper.text()).toContain('Basecamp Bambangan')
    expect(wrapper.text()).toContain('Jalur Bambangan')
    expect(wrapper.text()).toContain('SIMAKSI (2 Org)')
    expect(wrapper.text()).toContain('Rincian Pembayaran')
  })

  it('should call checkout API when checkout button is clicked with valid form', async () => {
    const cartStore = useCartStore()
    const authStore = useAuthStore()

    authStore.user = {
      id: 1,
      name: 'Fajar Bayu',
      email: 'fajar@example.com',
      telepon: '081234567890',
      role: 'pendaki',
    } as any

    cartStore.cart = {
      id: 1,
      user_id: 1,
      basecamp_id: 2,
      jalur_id: 3,
      total_item: 1,
      subtotal: 50000,
      tanggal_booking: '2026-07-15',
      basecamp: { id: 2, nama_basecamp: 'Basecamp Bambangan' } as any,
      jalur: { id: 3, nama_jalur: 'Jalur Bambangan' } as any,
      items: [
        {
          id: 10,
          cart_id: 1,
          produk_id: 1,
          qty: 1,
          tipe_produk: 'tiket',
          harga_saat_ini: 50000,
          subtotal: 50000,
          produk: { id: 1, nama_produk: 'Tiket SIMAKSI', harga: 50000 } as any,
        },
      ],
    } as any

    const mockResponse = {
      status: 'success',
      message: 'Order created',
      checkout_url: 'https://checkout.xendit.co/v2/invoice/abc123',
      data: {
        id: 100,
        invoice: 'INV/20260715/001',
        total_bayar: 52500,
        status: 'pending' as const,
        pembayaran: {
          id: 10,
          pesanan_id: 100,
          checkout_url: 'https://checkout.xendit.co/v2/invoice/abc123',
          amount: 52500,
          status: 'pending' as const,
        },
      } as any,
    }

    vi.mocked(pendakiCheckoutApi.checkout).mockResolvedValueOnce(mockResponse)
    vi.spyOn(cartStore, 'fetchCart').mockImplementation(async () => {})

    const wrapper = mount(CheckoutView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
          KycStatusBanner: true,
          ClimberManifestForm: {
            template: '<div class="climber-manifest-mock">Manifest Form</div>',
          },
          SafetySopChecklist: {
            template: '<div class="safety-sop-mock">SOP Checklist</div>',
          },
          PaymentModal: {
            template: '<div class="payment-modal-mock">Payment Modal</div>',
          },
        },
      },
    })
    await flushPromises()

    // Set internal state to ready
    const vm = wrapper.vm as any
    vm.manifestMembers = [
      {
        nama_anggota: 'Fajar Bayu',
        nik_identitas: '3301234567890001',
        telepon: '081234567890',
        telepon_darurat: '081298765432',
        hubungan_darurat: 'Orang Tua',
      },
    ]
    vm.isManifestValid = true
    vm.sopAgreed = true
    await wrapper.vm.$nextTick()

    await vm.handleCheckout()

    expect(pendakiCheckoutApi.checkout).toHaveBeenCalledWith({
      anggotas: vm.manifestMembers,
    })
    expect(vm.createdOrder).toEqual(mockResponse.data)
    expect(vm.isPaymentModalOpen).toBe(true)
  })

  it('should only render validation hints box when there are invalid fields, and never show empty Perhatian box', async () => {
    const cartStore = useCartStore()
    const authStore = useAuthStore()

    authStore.user = {
      id: 1,
      name: 'Fajar Bayu',
      role: 'pendaki',
    } as any

    cartStore.cart = {
      id: 1,
      total_item: 1,
      subtotal: 50000,
      items: [{ id: 1, qty: 1, tipe_produk: 'tiket', subtotal: 50000 }],
    } as any

    const wrapper = mount(CheckoutView, {
      global: {
        stubs: {
          'router-link': true,
          KycStatusBanner: true,
          ClimberManifestForm: true,
          SafetySopChecklist: true,
          PaymentModal: true,
        },
      },
    })
    await flushPromises()

    const vm = wrapper.vm as any

    // Initially invalid: manifest is invalid and sop is not agreed
    expect(wrapper.text()).toContain('Perhatian:')
    expect(wrapper.text()).toContain('Lengkapi nama & 16-digit NIK semua anggota manifes.')
    expect(wrapper.text()).toContain('Centang kotak persetujuan SOP Pendakian.')

    // When manifest becomes valid and SOP agreed
    vm.isManifestValid = true
    vm.sopAgreed = true
    await wrapper.vm.$nextTick()

    // The Perhatian box must NOT be rendered at all (no empty box)
    expect(wrapper.text()).not.toContain('Perhatian:')
  })
})
