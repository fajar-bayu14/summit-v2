import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ProductFormModal from '@/components/mitra/products/ProductFormModal.vue'
import type { Produk } from '@/types/product'

describe('Mitra Product Form Modal (Task 2.3)', () => {
  const basecamps = [
    { id: 1, nama: 'Basecamp Selo', nama_gunung: 'Gunung Merbabu' },
    { id: 2, nama: 'Basecamp Suwanting', nama_gunung: 'Gunung Merbabu' },
  ]

  it('should initialize with default values in create mode', () => {
    const wrapper = mount(ProductFormModal, {
      props: {
        isOpen: true,
        mode: 'create',
        basecamps,
        defaultBasecampId: 1,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.form.basecamp_id).toBe(1)
    expect(vm.form.kategori).toBe('rental')
    expect(vm.form.is_active).toBe(true)
    expect(vm.isStockCategory).toBe(true)
    expect(vm.isTicketCategory).toBe(false)
  })

  it('should populate form fields in edit mode', () => {
    const existingProduct: Produk = {
      id: 5,
      basecamp_id: 2,
      nama_produk: 'Tenda Dome 4P Kapasitas Besar',
      kategori: 'rental',
      deskripsi: 'Tahan badai dan hujan deras',
      harga: 45000,
      stok: 12,
      satuan: 'unit/hari',
      is_active: true,
    }

    const wrapper = mount(ProductFormModal, {
      props: {
        isOpen: true,
        mode: 'edit',
        product: existingProduct,
        basecamps,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.form.nama_produk).toBe('Tenda Dome 4P Kapasitas Besar')
    expect(vm.form.harga).toBe(45000)
    expect(vm.form.stok).toBe(12)
    expect(vm.form.basecamp_id).toBe(2)
  })

  it('should switch conditional category fields dynamically', async () => {
    const wrapper = mount(ProductFormModal, {
      props: {
        isOpen: true,
        mode: 'create',
        basecamps,
      },
    })

    const vm = wrapper.vm as any

    // Switch to ticket
    vm.form.kategori = 'ticket'
    expect(vm.isTicketCategory).toBe(true)
    expect(vm.isStockCategory).toBe(false)
    expect(vm.isOpenTripCategory).toBe(false)

    // Switch to opentrip
    vm.form.kategori = 'opentrip'
    expect(vm.isOpenTripCategory).toBe(true)
    expect(vm.isTicketCategory).toBe(false)
    expect(vm.isStockCategory).toBe(false)
  })

  it('should validate required fields on submit and reject invalid forms', () => {
    const wrapper = mount(ProductFormModal, {
      props: {
        isOpen: true,
        mode: 'create',
        basecamps,
      },
    })

    const vm = wrapper.vm as any
    vm.form.nama_produk = ''
    vm.form.harga = -100

    vm.handleSubmit()

    expect(vm.clientErrors.nama_produk).toBeDefined()
    expect(vm.clientErrors.harga).toBeDefined()
    expect(wrapper.emitted('submit')).toBeFalsy()
  })

  it('should emit submit event with properly formatted payload when valid', () => {
    const wrapper = mount(ProductFormModal, {
      props: {
        isOpen: true,
        mode: 'create',
        basecamps,
      },
    })

    const vm = wrapper.vm as any
    vm.form.basecamp_id = 1
    vm.form.nama_produk = 'Matras Foam Double Layer'
    vm.form.kategori = 'rental'
    vm.form.harga = 10000
    vm.form.stok = 20
    vm.form.satuan = 'lembar'
    vm.form.is_active = true

    vm.handleSubmit()

    expect(wrapper.emitted('submit')).toBeTruthy()
    const emittedPayload = wrapper.emitted('submit')?.[0]?.[0] as any
    expect(emittedPayload.nama_produk).toBe('Matras Foam Double Layer')
    expect(emittedPayload.harga).toBe(10000)
    expect(emittedPayload.stok).toBe(20)
    expect(emittedPayload.satuan).toBe('lembar')
  })
})
