import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ClimberPassportView from '@/views/pendaki/ClimberPassportView.vue'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiLogbook', () => ({
  pendakiLogbookApi: {
    getBadges: vi.fn(),
  },
  getBadges: vi.fn(),
}))

describe('ClimberPassportView Component (Task 7.4)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should fetch badges on mount and render stats and 7 summits', async () => {
    const mockBadges = [
      {
        id: 1,
        badge_title: 'Penakluk Gunung Slamet',
        gunung_nama: 'Gunung Slamet',
        tinggi_mdpl: 3428,
        lokasi: 'Purbalingga, Jawa Tengah',
        tanggal_summit: '2026-07-15T06:00:00Z',
        certificate_url: 'http://localhost:8000/api/v1/orders/INV-123/certificate',
      },
    ]

    vi.mocked(pendakiLogbookApi.getBadges).mockResolvedValueOnce({
      status: 'success',
      data: mockBadges,
    })

    const wrapper = mount(ClimberPassportView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
        },
      },
    })
    await flushPromises()

    expect(pendakiLogbookApi.getBadges).toHaveBeenCalled()
    expect(wrapper.text()).toContain('Paspor Pendakian Gunung Indonesia')
    expect(wrapper.text()).toContain('Penakluk Gunung Slamet')
    expect(wrapper.text()).toContain('3.428 MDPL')
    expect(wrapper.text()).toContain('The 7 Summits of Indonesia Challenge')
  })
})
