import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TrailCard from '@/components/pendaki/TrailCard.vue'
import type { JalurDetail } from '@/types/pendakiMountain'

describe('TrailCard Component (Task 2.3)', () => {
  const sampleTrail: JalurDetail = {
    id: 10,
    gunung_id: 1,
    nama_jalur: 'Jalur Selo Boyolali',
    deskripsi: 'Jalur paling populer dengan sabana luas.',
    titik_awal_mdpl: '1.600 MDPL',
    titik_akhir_mdpl: '3.145 MDPL',
    panjang_jalur: '6.5 Km',
    waktu_tempuh: '5 - 6 Jam',
    tingkat_kesulitan: 'sedang',
    status: 'open',
    basecamps: [
      {
        id: 101,
        mitra_id: 2,
        jalur_id: 10,
        nama_basecamp: 'Basecamp Merbabu Selo Baru',
      },
    ],
  }

  it('should render trail name, elevation stats, distance, and difficulty badge', () => {
    const wrapper = mount(TrailCard, {
      props: {
        trail: sampleTrail,
        isSelected: false,
      },
    })

    expect(wrapper.text()).toContain('Jalur Selo Boyolali')
    expect(wrapper.text()).toContain('1.600 MDPL')
    expect(wrapper.text()).toContain('6.5 Km')
    expect(wrapper.text()).toContain('5 - 6 Jam')
    expect(wrapper.text()).toContain('Tingkat: Sedang')
    expect(wrapper.text()).toContain('Buka')
    expect(wrapper.text()).toContain('Pilih Jalur Ini')
  })

  it('should emit select-trail when button is clicked', async () => {
    const wrapper = mount(TrailCard, {
      props: {
        trail: sampleTrail,
        isSelected: false,
      },
    })

    const button = wrapper.find('button')
    await button.trigger('click')

    expect(wrapper.emitted('select-trail')).toBeTruthy()
    expect(wrapper.emitted('select-trail')?.[0]).toEqual([sampleTrail])
  })

  it('should show "Jalur Terpilih" state when isSelected is true', () => {
    const wrapper = mount(TrailCard, {
      props: {
        trail: sampleTrail,
        isSelected: true,
      },
    })

    expect(wrapper.text()).toContain('Jalur Terpilih')
  })
})
