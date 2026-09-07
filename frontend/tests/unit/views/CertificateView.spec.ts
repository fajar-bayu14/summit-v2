import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import CertificateView from '@/views/pendaki/CertificateView.vue'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'

const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { invoice: 'INV/20260710/ABC12' },
  }),
  useRouter: () => ({
    push: mockPush,
  }),
}))

vi.mock('@/api/pendakiLogbook', () => ({
  pendakiLogbookApi: {
    getLogbook: vi.fn(),
  },
  getLogbook: vi.fn(),
}))

describe('CertificateView Component (Task 7.3)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should fetch logbook and render official certificate details', async () => {
    const mockLogbook = {
      id: 1,
      pesanan_id: 10,
      invoice: 'INV/20260710/ABC12',
      user_id: 1,
      nama_pendaki: 'Fajar Bayu',
      gunung_nama: 'Gunung Slamet',
      jalur_nama: 'Jalur Bambangan',
      tinggi_mdpl: 3428,
      foto_summit: 'http://localhost:8000/storage/summit.jpg',
      latitude: '-7.24',
      longitude: '109.21',
      waktu_summit: '2026-07-15T06:00:00Z',
      catatan_pendaki: 'Puncak cerah',
      status_validasi: 'approved' as const,
      catatan_petugas: 'Valid',
      validated_at: '2026-07-15T12:00:00Z',
      validated_by: 'Pak Slamet (Petugas)',
      certificate_url: 'http://localhost:8000/api/v1/orders/INV/20260710/ABC12/certificate',
      created_at: '2026-07-15T07:00:00Z',
    }

    vi.mocked(pendakiLogbookApi.getLogbook).mockResolvedValueOnce({
      status: 'success',
      data: mockLogbook,
    })

    const wrapper = mount(CertificateView, {
      global: {
        stubs: {
          'router-link': { template: '<a><slot /></a>' },
        },
      },
    })
    await flushPromises()

    expect(pendakiLogbookApi.getLogbook).toHaveBeenCalledWith('INV/20260710/ABC12')
    expect(wrapper.text()).toContain('Sertifikat Penakluk Puncak')
    expect(wrapper.text()).toContain('Fajar Bayu')
    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('3.428 MDPL')
    expect(wrapper.text()).toContain('Pak Slamet (Petugas)')
  })
})
