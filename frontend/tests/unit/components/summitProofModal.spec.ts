import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SummitProofModal from '@/components/pendaki/SummitProofModal.vue'
import { pendakiLogbookApi } from '@/api/pendakiLogbook'

vi.mock('@/api/pendakiLogbook', () => ({
  pendakiLogbookApi: {
    submitLogbook: vi.fn(),
  },
  submitLogbook: vi.fn(),
}))

describe('SummitProofModal Component (Task 7.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should render form when isOpen is true', () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        invoice: 'INV/20260710/ABC12',
        mountainName: 'Gunung Slamet',
        trailName: 'Jalur Bambangan',
      },
    })

    expect(wrapper.text()).toContain('Unggah Bukti Puncak (Summit Proof)')
    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('Jalur Bambangan')
    expect(wrapper.text()).toContain('Waktu Tiba di Puncak')
  })

  it('should not render modal when isOpen is false', () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: false,
        invoice: 'INV/20260710/ABC12',
      },
    })

    expect(wrapper.find('.fixed').exists()).toBe(false)
  })

  it('should submit logbook and emit submitted event', async () => {
    const mockLogbook = {
      id: 1,
      invoice: 'INV/20260710/ABC12',
      gunung_nama: 'Gunung Slamet',
      status_validasi: 'pending' as const,
    }

    vi.mocked(pendakiLogbookApi.submitLogbook).mockResolvedValueOnce({
      status: 'success',
      data: mockLogbook as any,
    })

    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        invoice: 'INV/20260710/ABC12',
      },
    })

    const vm = wrapper.vm as any
    // Simulate selected file
    vm.selectedFile = new File(['dummy'], 'photo.jpg', { type: 'image/jpeg' })
    vm.catatan = 'Puncak cerah berkabut tipis'
    await wrapper.vm.$nextTick()

    await vm.handleSubmit()

    expect(pendakiLogbookApi.submitLogbook).toHaveBeenCalled()
    expect(wrapper.emitted('submitted')).toBeTruthy()
    expect(wrapper.emitted('submitted')![0][0]).toEqual(mockLogbook)
  })
})
