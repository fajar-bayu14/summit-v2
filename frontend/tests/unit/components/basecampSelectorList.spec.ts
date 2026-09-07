import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BasecampSelectorList from '@/components/pendaki/BasecampSelectorList.vue'
import type { JalurDetail, BasecampMitraSummary } from '@/types/pendakiMountain'

describe('BasecampSelectorList Component (Task 2.4)', () => {
  const sampleBasecamp: BasecampMitraSummary = {
    id: 101,
    mitra_id: 2,
    jalur_id: 10,
    nama_basecamp: 'Basecamp Merbabu Selo Baru',
    jam_operasional: '24 Jam',
    mitra: {
      id: 2,
      nama_mitra: 'Koperasi Paguyuban Selo',
      is_verified: true,
    },
  }

  const sampleTrail: JalurDetail = {
    id: 10,
    gunung_id: 1,
    nama_jalur: 'Jalur Selo',
    status: 'open',
    basecamps: [sampleBasecamp],
  }

  it('should render list of basecamps with partner details and operational hours', () => {
    const wrapper = mount(BasecampSelectorList, {
      props: {
        trail: sampleTrail,
        selectedBasecampId: null,
      },
    })

    expect(wrapper.text()).toContain('Basecamp Merbabu Selo Baru')
    expect(wrapper.text()).toContain('Koperasi Paguyuban Selo')
    expect(wrapper.text()).toContain('24 Jam')
    expect(wrapper.text()).toContain('Pilih Basecamp & Belanja')
  })

  it('should emit select-basecamp event when action button is clicked', async () => {
    const wrapper = mount(BasecampSelectorList, {
      props: {
        trail: sampleTrail,
        selectedBasecampId: null,
      },
    })

    const button = wrapper.find('button')
    await button.trigger('click')

    expect(wrapper.emitted('select-basecamp')).toBeTruthy()
    expect(wrapper.emitted('select-basecamp')?.[0]).toEqual([sampleBasecamp])
  })

  it('should render empty state if no basecamps are registered for the trail', () => {
    const emptyTrail: JalurDetail = {
      id: 20,
      gunung_id: 1,
      nama_jalur: 'Jalur Wekas',
      status: 'open',
      basecamps: [],
    }

    const wrapper = mount(BasecampSelectorList, {
      props: {
        trail: emptyTrail,
        selectedBasecampId: null,
      },
    })

    expect(wrapper.text()).toContain('Belum Ada Mitra Basecamp Aktif')
  })
})
