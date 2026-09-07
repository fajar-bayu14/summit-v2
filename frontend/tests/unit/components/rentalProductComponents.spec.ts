import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RentalProductCard from '@/components/pendaki/RentalProductCard.vue'
import RentalProductGrid from '@/components/pendaki/RentalProductGrid.vue'
import { formatRupiah } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

describe('Rental Product Components (Task 3.3)', () => {
  const sampleProduct: ProdukCatalogItem = {
    id: 1,
    basecamp_id: 10,
    nama_produk: 'Sewa Tenda Dome 4P Double Layer',
    kategori: 'rental',
    deskripsi: 'Tenda tahan badai kapasitas 4 orang merk Arei.',
    harga: 50000,
    stok: 8,
    satuan: 'hari',
    is_active: true,
  }

  it('RentalProductCard should render product details, price, and stock', () => {
    const wrapper = mount(RentalProductCard, {
      props: {
        product: sampleProduct,
      },
    })

    expect(wrapper.text()).toContain('Sewa Tenda Dome 4P Double Layer')
    expect(wrapper.text()).toContain(formatRupiah(50000))
    expect(wrapper.text()).toContain('Tersedia 8 unit')
    expect(wrapper.text()).toContain('+ Keranjang')
  })

  it('RentalProductCard should emit add-to-cart when button is clicked', async () => {
    const wrapper = mount(RentalProductCard, {
      props: {
        product: sampleProduct,
      },
    })

    const addButton = wrapper.findAll('button').filter(b => b.text().includes('+ Keranjang'))[0]
    await addButton.trigger('click')

    expect(wrapper.emitted('add-to-cart')).toBeTruthy()
    expect(wrapper.emitted('add-to-cart')?.[0]).toEqual([sampleProduct, 1])
  })

  it('RentalProductGrid should render category tabs and list cards', () => {
    const wrapper = mount(RentalProductGrid, {
      props: {
        products: [sampleProduct],
      },
    })

    expect(wrapper.text()).toContain('Semua Alat')
    expect(wrapper.text()).toContain('Tenda & Shelter')
    expect(wrapper.text()).toContain('Sewa Tenda Dome 4P Double Layer')
  })
})
