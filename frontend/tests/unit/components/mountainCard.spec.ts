import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MountainCard from '@/components/pendaki/MountainCard.vue'
import type { GunungItem } from '@/types/pendakiMountain'

describe('MountainCard Component (Task 2.2)', () => {
  const sampleMountain: GunungItem = {
    id: 1,
    nama_gunung: 'Gunung Merbabu',
    deskripsi: 'Gunung dengan panorama sabana indah di Jawa Tengah.',
    tinggi_mdpl: 3145,
    lokasi: 'Boyolali, Jawa Tengah',
    foto: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b',
    status: 'aktif',
    jalurs: [
      { id: 101, gunung_id: 1, nama_jalur: 'Jalur Selo', status: 'open' },
      { id: 102, gunung_id: 1, nama_jalur: 'Jalur Suwanting', status: 'open' },
      { id: 103, gunung_id: 1, nama_jalur: 'Jalur Wekas', status: 'close' },
    ],
  }

  it('should render mountain details, elevation, and active trails count', () => {
    const wrapper = mount(MountainCard, {
      props: {
        mountain: sampleMountain,
      },
    })

    expect(wrapper.text()).toContain('Gunung Merbabu')
    expect(wrapper.text()).toContain('3.145 MDPL')
    expect(wrapper.text()).toContain('Boyolali, Jawa Tengah')
    expect(wrapper.text()).toContain('2') // 2 open trails out of 3
    expect(wrapper.text()).toContain('Jalur Buka')
  })

  it('should render closed status when mountain is not active', () => {
    const closedMountain: GunungItem = {
      ...sampleMountain,
      status: 'tutup_sementara',
    }
    const wrapper = mount(MountainCard, {
      props: {
        mountain: closedMountain,
      },
    })

    expect(wrapper.text()).toContain('Tutup Sementara')
  })

  it('should emit select event when "Pilih Jalur" button is clicked', async () => {
    const wrapper = mount(MountainCard, {
      props: {
        mountain: sampleMountain,
      },
    })

    const selectButton = wrapper.find('button')
    await selectButton.trigger('click')

    expect(wrapper.emitted('select')).toBeTruthy()
    expect(wrapper.emitted('select')?.[0]).toEqual([sampleMountain])
  })
})
