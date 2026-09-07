import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ProductCategoryTabs from '@/components/mitra/products/ProductCategoryTabs.vue'
import ProductStockModal from '@/components/mitra/products/ProductStockModal.vue'
import type { Produk } from '@/types/product'

describe('Mitra Product Components (Task 2.2)', () => {
  describe('ProductCategoryTabs', () => {
    it('should render all category tabs and highlight the active one', () => {
      const counts = { all: 15, ticket: 2, rental: 8, opentrip: 3, guide: 1, kuliner: 1 }
      const wrapper = mount(ProductCategoryTabs, {
        props: {
          modelValue: 'rental',
          counts,
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBe(6)

      const rentalButton = buttons.find((btn) => btn.text().includes('Sewa Alat'))
      expect(rentalButton).toBeDefined()
      expect(rentalButton?.classes()).toContain('bg-[#1E3A2B]')

      // Count badge
      expect(wrapper.text()).toContain('8')
    })

    it('should emit update:modelValue and change event when a tab is clicked', async () => {
      const wrapper = mount(ProductCategoryTabs, {
        props: {
          modelValue: 'all',
        },
      })

      const opentripButton = wrapper.findAll('button').find((btn) => btn.text().includes('Open Trip'))
      await opentripButton?.trigger('click')

      expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['opentrip'])
      expect(wrapper.emitted('change')?.[0]).toEqual(['opentrip'])
    })
  })

  describe('ProductStockModal', () => {
    const mockProduct: Produk = {
      id: 1,
      basecamp_id: 1,
      nama_produk: 'Tenda Dome 4P',
      kategori: 'rental',
      harga: 50000,
      stok: 10,
      satuan: 'unit',
      is_active: true,
    }

    it('should initialize with product stock and allow adjustStock', async () => {
      const wrapper = mount(ProductStockModal, {
        props: {
          isOpen: true,
          product: mockProduct,
          loading: false,
        },
      })

      const vm = wrapper.vm as any
      expect(vm.stockValue).toBe(10)

      // Increment by 5
      vm.adjustStock(5)
      expect(vm.stockValue).toBe(15)

      // Submit
      vm.handleSubmit()
      expect(wrapper.emitted('submit')?.[0]).toEqual([15])
    })

    it('should not allow stock to go below 0', async () => {
      const zeroProduct: Produk = {
        ...mockProduct,
        stok: 0,
      }

      const wrapper = mount(ProductStockModal, {
        props: {
          isOpen: true,
          product: zeroProduct,
          loading: false,
        },
      })

      const vm = wrapper.vm as any
      expect(vm.stockValue).toBe(0)

      // Decrement when 0
      vm.adjustStock(-5)
      expect(vm.stockValue).toBe(0)

      // Submit
      vm.handleSubmit()
      expect(wrapper.emitted('submit')?.[0]).toEqual([0])
    })

    it('should handle close event properly', async () => {
      const wrapper = mount(ProductStockModal, {
        props: {
          isOpen: true,
          product: mockProduct,
        },
      })

      const vm = wrapper.vm as any
      vm.handleClose()

      expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
      expect(wrapper.emitted('close')).toBeTruthy()
    })
  })
})
