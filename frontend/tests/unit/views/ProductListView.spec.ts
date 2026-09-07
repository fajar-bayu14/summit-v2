import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ProductListView from '@/views/mitra/ProductListView.vue'
import mitraProductsApi from '@/api/mitraProducts'
import type { Produk } from '@/types/product'

describe('ProductListView Component (Task 2.4)', () => {
  const mockProducts: Produk[] = [
    {
      id: 1,
      basecamp_id: 1,
      nama_produk: 'Tenda Dome 4P',
      kategori: 'rental',
      deskripsi: 'Tenda tahan hujan kapasitas 4 orang',
      harga: 50000,
      stok: 10,
      satuan: 'unit/hari',
      is_active: true,
      basecamp: { id: 1, nama: 'Basecamp Selo', nama_gunung: 'Merbabu' },
    },
    {
      id: 2,
      basecamp_id: 1,
      nama_produk: 'Tiket Pendakian Jalur Selo',
      kategori: 'ticket',
      deskripsi: 'Tiket registrasi simaksi resmi',
      harga: 25000,
      is_active: true,
      basecamp: { id: 1, nama: 'Basecamp Selo', nama_gunung: 'Merbabu' },
      tiket: {
        id: 1,
        produk_id: 2,
        jalur_id: 1,
        jam_buka: '06:00',
        jam_tutup: '17:00',
      },
    },
    {
      id: 3,
      basecamp_id: 1,
      nama_produk: 'Paket Open Trip Sunrise Merbabu 2D1N',
      kategori: 'opentrip',
      deskripsi: 'All inclusive trip guide & tenda',
      harga: 350000,
      is_active: false,
      basecamp: { id: 1, nama: 'Basecamp Selo', nama_gunung: 'Merbabu' },
      opentrip: {
        id: 1,
        produk_id: 3,
        tanggal_berangkat: '2026-10-10',
        tanggal_pulang: '2026-10-11',
        meeting_point: 'Basecamp Selo',
        minimal_peserta: 5,
        maksimal_peserta: 15,
        sisa_kursi: 12,
      },
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load and render products on mount', async () => {
    const getSpy = vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: mockProducts as any,
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    expect(getSpy).toHaveBeenCalled()
    const vm = wrapper.vm as any
    expect(vm.products.length).toBe(3)
    expect(wrapper.text()).toContain('Tenda Dome 4P')
    expect(wrapper.text()).toContain('Tiket Pendakian Jalur Selo')
    expect(wrapper.text()).toContain('Paket Open Trip Sunrise Merbabu')
  })

  it('should filter products when category tab changes', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: mockProducts as any,
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.activeCategory = 'rental'

    expect(vm.filteredProducts.length).toBe(1)
    expect(vm.filteredProducts[0].nama_produk).toBe('Tenda Dome 4P')

    vm.activeCategory = 'ticket'
    expect(vm.filteredProducts.length).toBe(1)
    expect(vm.filteredProducts[0].nama_produk).toBe('Tiket Pendakian Jalur Selo')
  })

  it('should filter products by search query', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: mockProducts as any,
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.searchQuery = 'sunrise'

    expect(vm.filteredProducts.length).toBe(1)
    expect(vm.filteredProducts[0].nama_produk).toContain('Sunrise')
  })

  it('should toggle product active status and call toggleProductStatus API', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: mockProducts as any,
    })

    const patchSpy = vi.spyOn(mitraProductsApi, 'toggleProductStatus').mockResolvedValueOnce({
      status: 'success',
      message: 'Status produk berhasil diperbarui.',
      data: { ...mockProducts[0], is_active: false },
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleToggleStatus(mockProducts[0])

    expect(patchSpy).toHaveBeenCalledWith(1, false)
    expect(mockProducts[0].is_active).toBe(false)
  })

  it('should handle stock update submission', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: mockProducts as any,
    })

    const stockSpy = vi.spyOn(mitraProductsApi, 'updateProductStock').mockResolvedValueOnce({
      status: 'success',
      message: 'Stok diperbarui',
      data: { ...mockProducts[0], stok: 18 },
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.stockTargetProduct = mockProducts[0]
    await vm.handleStockSubmit(18)

    expect(stockSpy).toHaveBeenCalledWith(1, 18)
    expect(vm.products.find((p: any) => p.id === 1)?.stok).toBe(18)
  })

  it('should delete product when confirmed', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'Products loaded',
      data: [...mockProducts] as any,
    })

    const deleteSpy = vi.spyOn(mitraProductsApi, 'deleteProduct').mockResolvedValueOnce({
      status: 'success',
      message: 'Deleted',
      data: null,
    })

    const wrapper = mount(ProductListView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.productToDelete = mockProducts[0]
    await vm.confirmDeleteProduct()

    expect(deleteSpy).toHaveBeenCalledWith(1)
    expect(vm.products.some((p: any) => p.id === 1)).toBe(false)
  })
})
