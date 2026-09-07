import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PorterGuideServiceCards from '@/components/pendaki/PorterGuideServiceCards.vue'
import { formatRupiah } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

describe('PorterGuideServiceCards Component (Task 3.4)', () => {
  const sampleServices: ProdukCatalogItem[] = [
    {
      id: 201,
      basecamp_id: 10,
      nama_produk: 'Jasa Porter Drop & Camp Harian',
      kategori: 'jasa',
      deskripsi: 'Porter angkut beban hingga 20 kg dan standby di area camp.',
      harga: 250000,
      satuan: 'hari',
      is_active: true,
    },
    {
      id: 202,
      basecamp_id: 10,
      nama_produk: 'Pemandu Gunung Guide APGI Berlisensi',
      kategori: 'jasa',
      deskripsi: 'Pemandu resmi bersertifikat APGI berpengalaman pertolongan pertama.',
      harga: 400000,
      satuan: 'trip',
      is_active: true,
    },
  ]

  it('should render service list with tariff, license badge, and max load notes', () => {
    const wrapper = mount(PorterGuideServiceCards, {
      props: {
        services: sampleServices,
      },
    })

    expect(wrapper.text()).toContain('Jasa Porter Drop & Camp Harian')
    expect(wrapper.text()).toContain(formatRupiah(250000))
    expect(wrapper.text()).toContain('Pemandu Gunung Guide APGI Berlisensi')
    expect(wrapper.text()).toContain(formatRupiah(400000))
    expect(wrapper.text()).toContain('Berlisensi')
    expect(wrapper.text()).toContain('Max Beban: 20 Kg')
  })

  it('should emit add-service when "Pesan Jasa Ini" is clicked', async () => {
    const wrapper = mount(PorterGuideServiceCards, {
      props: {
        services: sampleServices,
      },
    })

    const button = wrapper.findAll('button').filter(b => b.text().includes('Pesan Jasa Ini'))[0]
    await button.trigger('click')

    expect(wrapper.emitted('add-service')).toBeTruthy()
    expect(wrapper.emitted('add-service')?.[0]).toEqual([
      {
        product: sampleServices[0],
        quantity: 1,
        specialNotes: '',
      },
    ])
  })
})
