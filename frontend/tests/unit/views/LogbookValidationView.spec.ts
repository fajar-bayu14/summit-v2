import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import LogbookValidationView from '@/views/mitra/LogbookValidationView.vue'
import mitraLogbooksApi from '@/api/mitraLogbooks'
import type { LogbookEntry } from '@/types/logbook'

describe('LogbookValidationView View Component (Task 5.3)', () => {
  const mockLogbooks: LogbookEntry[] = [
    {
      id: 1,
      pesanan_id: 10,
      invoice: 'INV-PRAU-001',
      user_id: 5,
      nama_pendaki: 'Bayu Pendaki',
      gunung_nama: 'Gunung Prau',
      jalur_nama: 'Patakbanteng',
      tinggi_mdpl: 2590,
      foto_summit: 'http://localhost:8000/storage/summit/proof1.jpg',
      waktu_summit: '2026-09-06T05:45:00Z',
      catatan_pendaki: 'Sunrise cerah',
      status_validasi: 'pending',
    },
    {
      id: 2,
      pesanan_id: 11,
      invoice: 'INV-PRAU-002',
      user_id: 6,
      nama_pendaki: 'Rian Pratama',
      gunung_nama: 'Gunung Prau',
      jalur_nama: 'Patakbanteng',
      tinggi_mdpl: 2590,
      foto_summit: 'http://localhost:8000/storage/summit/proof2.jpg',
      waktu_summit: '2026-09-06T06:00:00Z',
      catatan_pendaki: 'Berhasil summit bersama rombongan',
      status_validasi: 'approved',
      certificate_url: 'http://localhost:8000/api/v1/orders/INV-PRAU-002/certificate',
    },
    {
      id: 3,
      pesanan_id: 12,
      invoice: 'INV-PRAU-003',
      user_id: 7,
      nama_pendaki: 'Dedi Kusnadi',
      gunung_nama: 'Gunung Prau',
      jalur_nama: 'Patakbanteng',
      tinggi_mdpl: 2590,
      foto_summit: null,
      waktu_summit: '2026-09-06T06:30:00Z',
      status_validasi: 'rejected',
      catatan_petugas: 'Tidak ada foto bukti',
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load logbooks and compute KPI metrics on mount', async () => {
    const getLogbooksSpy = vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockLogbooks,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    expect(getLogbooksSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.logbooks.length).toBe(3)
    expect(vm.metrics.pending).toBe(1)
    expect(vm.metrics.approved).toBe(1)
    expect(vm.metrics.rejected).toBe(1)

    expect(wrapper.text()).toContain('Bayu Pendaki')
    expect(wrapper.text()).toContain('Rian Pratama')
    expect(wrapper.text()).toContain('Dedi Kusnadi')
  })

  it('should filter logbooks by validation status tab', async () => {
    const getLogbooksSpy = vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [mockLogbooks[0]],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleStatusFilter('pending')
    await flushPromises()

    expect(getLogbooksSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        status_validasi: 'pending',
        page: 1,
      })
    )
  })

  it('should toggle between grid and table view modes', async () => {
    vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockLogbooks,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    const vm = wrapper.vm as any
    expect(vm.viewMode).toBe('grid')

    // Switch to table
    vm.viewMode = 'table'
    await flushPromises()
    expect(wrapper.find('table').exists()).toBe(true)

    // Switch back to grid
    vm.viewMode = 'grid'
    await flushPromises()
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('should open modal when openModal is called', async () => {
    vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockLogbooks,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openModal(mockLogbooks[0])

    expect(vm.selectedLogbook).toEqual(mockLogbooks[0])
    expect(vm.isModalOpen).toBe(true)
  })

  it('should update logbook record when verified event is handled', async () => {
    const getLogbooksSpy = vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockLogbooks],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    const vm = wrapper.vm as any
    const updatedRecord: LogbookEntry = {
      ...mockLogbooks[0],
      status_validasi: 'approved',
      catatan_petugas: 'Sah di puncak',
    }

    vm.handleLogbookVerified(updatedRecord)
    await flushPromises()

    expect(vm.logbooks[0].status_validasi).toBe('approved')
    expect(getLogbooksSpy).toHaveBeenCalledTimes(2)
  })

  it('should reset filters properly', async () => {
    const getLogbooksSpy = vi.spyOn(mitraLogbooksApi, 'getLogbooks').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockLogbooks,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(LogbookValidationView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.search = 'Prau'
    vm.filters.status_validasi = 'approved'

    vm.resetFilters()
    await flushPromises()

    expect(vm.filters.search).toBe('')
    expect(vm.filters.status_validasi).toBe('')
    expect(getLogbooksSpy).toHaveBeenLastCalledWith({
      page: 1,
      per_page: 15,
    })
  })
})
