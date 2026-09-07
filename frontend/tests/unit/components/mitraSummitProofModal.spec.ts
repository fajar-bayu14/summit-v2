import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SummitProofModal from '@/components/mitra/logbook/SummitProofModal.vue'
import mitraLogbooksApi from '@/api/mitraLogbooks'
import type { LogbookEntry } from '@/types/logbook'

vi.mock('@/api/mitraLogbooks', () => ({
  default: {
    verifyLogbook: vi.fn(),
  },
}))

describe('Mitra Summit Proof Modal Component (Task 5.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockPendingLogbook: LogbookEntry = {
    id: 10,
    pesanan_id: 100,
    invoice: 'INV-PRAU-100',
    user_id: 5,
    nama_pendaki: 'Siti Rahma',
    gunung_nama: 'Gunung Prau',
    jalur_nama: 'Patakbanteng',
    tinggi_mdpl: 2590,
    foto_summit: 'http://localhost:8000/storage/summit/proof.jpg',
    latitude: '-7.1872',
    longitude: '109.9238',
    waktu_summit: '2026-09-06T05:45:00Z',
    catatan_pendaki: 'Puncak sangat indah!',
    status_validasi: 'pending',
    catatan_petugas: null,
    validated_at: null,
    validated_by: null,
    certificate_url: null,
  }

  it('should manipulate photo viewer zoom and rotation controls', () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.zoomLevel).toBe(1)
    expect(vm.rotationAngle).toBe(0)

    // Zoom in
    vm.handleZoomIn()
    expect(vm.zoomLevel).toBe(1.25)

    // Zoom out
    vm.handleZoomOut()
    expect(vm.zoomLevel).toBe(1)

    // Rotate
    vm.handleRotate()
    expect(vm.rotationAngle).toBe(90)

    // Reset
    vm.resetViewer()
    expect(vm.zoomLevel).toBe(1)
    expect(vm.rotationAngle).toBe(0)
  })

  it('should format date and time in indonesian locale', () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.formatDateTimeIndo('2026-09-06T05:45:00Z')).toContain('2026')
    expect(vm.formatDateTimeIndo(null)).toBe('-')
  })

  it('should approve logbook and emit verified event', async () => {
    const approvedLogbook: LogbookEntry = {
      ...mockPendingLogbook,
      status_validasi: 'approved',
      catatan_petugas: 'Sah di puncak',
      validated_at: '2026-09-06T06:00:00Z',
    }

    vi.mocked(mitraLogbooksApi.verifyLogbook).mockResolvedValueOnce({
      success: true,
      message: 'Bukti summit disetujui',
      data: approvedLogbook,
    })

    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    vm.decision = 'approved'
    vm.officerNotes = 'Sah di puncak'

    await vm.handleVerificationSubmit()

    expect(mitraLogbooksApi.verifyLogbook).toHaveBeenCalledWith(10, {
      status_validasi: 'approved',
      catatan_petugas: 'Sah di puncak',
    })
    expect(wrapper.emitted('verified')?.[0]).toEqual([approvedLogbook])
    expect(vm.serverMessage).toContain('berhasil disetujui')
  })

  it('should require officer notes when rejecting logbook', async () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    vm.decision = 'rejected'
    vm.officerNotes = '   '

    await vm.handleVerificationSubmit()

    expect(mitraLogbooksApi.verifyLogbook).not.toHaveBeenCalled()
    expect(vm.formError).toContain('Wajib memberikan catatan')
  })

  it('should reject logbook when notes are provided', async () => {
    const rejectedLogbook: LogbookEntry = {
      ...mockPendingLogbook,
      status_validasi: 'rejected',
      catatan_petugas: 'Foto tidak jelas di plang puncak',
    }

    vi.mocked(mitraLogbooksApi.verifyLogbook).mockResolvedValueOnce({
      success: true,
      message: 'Bukti summit ditolak',
      data: rejectedLogbook,
    })

    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    vm.decision = 'rejected'
    vm.officerNotes = 'Foto tidak jelas di plang puncak'

    await vm.handleVerificationSubmit()

    expect(mitraLogbooksApi.verifyLogbook).toHaveBeenCalledWith(10, {
      status_validasi: 'rejected',
      catatan_petugas: 'Foto tidak jelas di plang puncak',
    })
    expect(wrapper.emitted('verified')?.[0]).toEqual([rejectedLogbook])
    expect(vm.serverMessage).toContain('telah ditolak')
  })

  it('should emit close and update:isOpen on handleClose', () => {
    const wrapper = mount(SummitProofModal, {
      props: {
        isOpen: true,
        logbook: mockPendingLogbook,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
