import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TicketQuotaCalendar from '@/components/pendaki/TicketQuotaCalendar.vue'
import { formatRupiah } from '@/lib/formatters'
import type { ProdukCatalogItem } from '@/types/pendakiProduct'

describe('TicketQuotaCalendar Component (Task 3.2)', () => {
  const sampleTicketProduct: ProdukCatalogItem = {
    id: 1,
    basecamp_id: 10,
    nama_produk: 'Tiket Masuk SIMAKSI Merbabu',
    kategori: 'tiket',
    harga: 25000,
    is_active: true,
    tiket: {
      id: 1,
      produk_id: 1,
      jalur_id: 5,
      jam_buka: '07:00',
      jam_tutup: '17:00',
      kuotas: [
        { id: 101, produk_tiket_id: 1, tanggal: '2026-07-20', kuota_total: 100, kuota_tersisa: 50 },
        { id: 102, produk_tiket_id: 1, tanggal: '2026-07-21', kuota_total: 100, kuota_tersisa: 0 },
      ],
    },
  }

  it('should render product name, price, and daily quota tiles', () => {
    const wrapper = mount(TicketQuotaCalendar, {
      props: {
        ticketProduct: sampleTicketProduct,
      },
    })

    expect(wrapper.text()).toContain('Tiket Masuk SIMAKSI Merbabu')
    expect(wrapper.text()).toContain(formatRupiah(25000))
    expect(wrapper.text()).toContain('50 Kuota')
    expect(wrapper.text()).toContain('Habis')
  })

  it('should emit add-ticket event when date is selected and CTA button is clicked', async () => {
    const wrapper = mount(TicketQuotaCalendar, {
      props: {
        ticketProduct: sampleTicketProduct,
      },
    })

    // Click available date button (first quota tile)
    const availableTile = wrapper.findAll('button').filter(b => b.text().includes('50 Kuota'))[0]
    await availableTile.trigger('click')

    // Find and click "+ Masukkan Tiket" button
    const addButton = wrapper.findAll('button').filter(b => b.text().includes('+ Masukkan Tiket'))[0]
    await addButton.trigger('click')

    expect(wrapper.emitted('add-ticket')).toBeTruthy()
    expect(wrapper.emitted('add-ticket')?.[0]).toEqual([
      {
        product: sampleTicketProduct,
        selectedDate: '2026-07-20',
        climberCount: 1,
        totalAmount: 25000,
      },
    ])
  })
})
