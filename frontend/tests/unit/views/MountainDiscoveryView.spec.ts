import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import MountainDiscoveryView from '@/views/pendaki/MountainDiscoveryView.vue'
import { pendakiMountainsApi } from '@/api/pendakiMountains'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
  useRoute: () => ({
    query: {},
  }),
}))

vi.mock('@/api/pendakiMountains', () => ({
  pendakiMountainsApi: {
    getMountains: vi.fn(),
  },
}))

describe('MountainDiscoveryView Component (Task 2.2)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should fetch mountains on mount and render mountain cards', async () => {
    const mockMountains = [
      {
        id: 1,
        nama_gunung: 'Gunung Merbabu',
        tinggi_mdpl: 3145,
        lokasi: 'Boyolali, Jawa Tengah',
        status: 'aktif',
        jalurs: [{ id: 10, gunung_id: 1, nama_jalur: 'Jalur Selo', status: 'open' }],
      },
      {
        id: 2,
        nama_gunung: 'Gunung Rinjani',
        tinggi_mdpl: 3726,
        lokasi: 'Lombok, NTB',
        status: 'aktif',
        jalurs: [{ id: 20, gunung_id: 2, nama_jalur: 'Jalur Sembalun', status: 'open' }],
      },
    ]

    vi.mocked(pendakiMountainsApi.getMountains).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockMountains,
    })

    const wrapper = mount(MountainDiscoveryView)
    await flushPromises()

    expect(pendakiMountainsApi.getMountains).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Gunung Merbabu')
    expect(wrapper.text()).toContain('Gunung Rinjani')
  })

  it('should display empty state when no mountains match', async () => {
    vi.mocked(pendakiMountainsApi.getMountains).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: [],
    })

    const wrapper = mount(MountainDiscoveryView)
    await flushPromises()

    expect(wrapper.text()).toContain('Destinasi Tidak Ditemukan')
  })
})
