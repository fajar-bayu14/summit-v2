import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import StaffManagementView from '@/views/mitra/StaffManagementView.vue'
import mitraStaffApi from '@/api/mitraStaff'
import type { MitraStaff } from '@/types/staff'

describe('StaffManagementView View Component (Task 8.3)', () => {
  const mockStaffList: MitraStaff[] = [
    {
      id: 1,
      mitra_id: 10,
      basecamp_id: 2,
      nama: 'Ahmad Pemandu',
      role: 'guide',
      telepon: '081234567890',
      is_available: true,
      jadwal_tugas: 'Senin - Jumat',
    },
    {
      id: 2,
      mitra_id: 10,
      basecamp_id: 2,
      nama: 'Budi Porter',
      role: 'porter',
      telepon: '085712345678',
      is_available: true,
      jadwal_tugas: 'Weekend Only',
    },
    {
      id: 3,
      mitra_id: 10,
      basecamp_id: 2,
      nama: 'Citra Petugas',
      role: 'petugas',
      telepon: '089912345678',
      is_available: false,
      jadwal_tugas: 'Shift Pagi',
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load staff list and compute KPI metrics on mount', async () => {
    const getStaffSpy = vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        data: mockStaffList,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    expect(getStaffSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.staffList.length).toBe(3)
    expect(vm.metrics.total).toBe(3)
    expect(vm.metrics.guidesAvailable).toBe(1)
    expect(vm.metrics.portersAvailable).toBe(1)
    expect(vm.metrics.onDutyCount).toBe(1)

    expect(wrapper.text()).toContain('Ahmad Pemandu')
    expect(wrapper.text()).toContain('Budi Porter')
    expect(wrapper.text()).toContain('Citra Petugas')
  })

  it('should filter staff by role tabs', async () => {
    const getStaffSpy = vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [mockStaffList[0]],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.handleRoleFilter('guide')
    await flushPromises()

    expect(getStaffSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        role: 'guide',
        page: 1,
      })
    )
  })

  it('should toggle staff availability status', async () => {
    vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockStaffList],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const toggleSpy = vi.spyOn(mitraStaffApi, 'toggleStaffAvailability').mockResolvedValueOnce({
      status: 'success',
      data: { ...mockStaffList[0], is_available: false },
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleToggleAvailability(vm.staffList[0])

    expect(toggleSpy).toHaveBeenCalledWith(1, false)
    expect(vm.staffList[0].is_available).toBe(false)
  })

  it('should open create modal and handle staff saved event', async () => {
    const getStaffSpy = vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockStaffList],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openCreateModal()

    expect(vm.selectedStaff).toBeNull()
    expect(vm.isFormModalOpen).toBe(true)

    const newStaff: MitraStaff = {
      id: 4,
      mitra_id: 10,
      nama: 'Dani Guide Baru',
      role: 'guide',
      telepon: '081299998888',
      is_available: true,
    }

    vm.handleStaffSaved(newStaff)
    await flushPromises()

    expect(getStaffSpy).toHaveBeenCalledTimes(2)
  })

  it('should handle delete staff confirmation flow', async () => {
    vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: [...mockStaffList],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const deleteSpy = vi.spyOn(mitraStaffApi, 'deleteStaff').mockResolvedValueOnce({
      status: 'success',
      message: 'Staf berhasil dihapus.',
      data: null,
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.openDeleteModal(mockStaffList[0])

    expect(vm.staffToDelete).toEqual(mockStaffList[0])
    expect(vm.isDeleteModalOpen).toBe(true)

    await vm.confirmDelete()

    expect(deleteSpy).toHaveBeenCalledWith(1)
    expect(vm.staffList.length).toBe(2)
    expect(vm.successFeedback).toContain('berhasil dihapus')
  })

  it('should format WhatsApp URL and role labels properly', async () => {
    const wrapper = mount(StaffManagementView)
    const vm = wrapper.vm as any

    expect(vm.formatWhatsAppUrl('081234567890')).toBe('https://wa.me/6281234567890')
    expect(vm.formatWhatsAppUrl('6281234567890')).toBe('https://wa.me/6281234567890')
    expect(vm.getRoleLabel('guide')).toBe('Pemandu (Guide)')
    expect(vm.getRoleLabel('porter')).toBe('Porter Logistik')
    expect(vm.getRoleLabel('petugas')).toBe('Petugas Basecamp')
  })

  it('should reset filters properly', async () => {
    const getStaffSpy = vi.spyOn(mitraStaffApi, 'getStaffList').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: {
        data: mockStaffList,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 3,
      },
    } as any)

    const wrapper = mount(StaffManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    vm.filters.search = 'Ahmad'
    vm.filters.role = 'guide'

    vm.resetFilters()
    await flushPromises()

    expect(vm.filters.search).toBe('')
    expect(vm.filters.role).toBe('')
    expect(getStaffSpy).toHaveBeenLastCalledWith({
      page: 1,
      per_page: 15,
    })
  })
})
