import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MountainDetailView from '@/views/pendaki/MountainDetailView.vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
  useRoute: () => ({
    params: { id: '1' },
  }),
}))

vi.mock('@/api/pendakiMountains', () => ({
  pendakiMountainsApi: {
    getMountainById: vi.fn(),
  },
}))

describe('MountainDetailView Component (Task 2.3)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should load mountain details, render trails and basecamps on mount', async () => {
    const mockMountain = {
      id: 1,
      nama_gunung: 'Gunung Merbabu',
      tinggi_mdpl: 3145,
      lokasi: 'Boyolali, Jawa Tengah',
      status: 'aktif',
      jalurs: [
        {
          id: 10,
          gunung_id: 1,
          nama_jalur: 'Jalur Selo Boyolali',
          titik_awal_mdpl: '1.600 MDPL',
          titik_akhir_mdpl: '3.145 MDPL',
          status: 'open',
          basecamps: [
            {
              id: 101,
              mitra_id: 2,
              jalur_id: 10,
              nama_basecamp: 'Basecamp Merbabu Selo Baru',
            },
          ],
        },
      ],
    }

    vi.mocked(pendakiMountainsApi.getMountainById).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockMountain,
    })

    const wrapper = mount(MountainDetailView, {
      global: {
        stubs: {
          'router-link': {
            template: '<a><slot /></a>',
          },
        },
      },
    })
    await flushPromises()

    expect(pendakiMountainsApi.getMountainById).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Gunung Merbabu')
    expect(wrapper.text()).toContain('3.145 MDPL')
    expect(wrapper.text()).toContain('Jalur Selo Boyolali')
    expect(wrapper.text()).toContain('Basecamp Merbabu Selo Baru')
  })
})
