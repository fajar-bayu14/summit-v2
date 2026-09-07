import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import StaffFormModal from '@/components/mitra/staff/StaffFormModal.vue'
import mitraStaffApi from '@/api/mitraStaff'
import type { MitraStaff } from '@/types/staff'

vi.mock('@/api/mitraStaff', () => ({
  default: {
    createStaff: vi.fn(),
    updateStaff: vi.fn(),
  },
}))

describe('Mitra Staff Form Modal Component (Task 8.2)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockStaff: MitraStaff = {
    id: 10,
    mitra_id: 1,
    basecamp_id: 2,
    nama: 'Ahmad Pemandu',
    role: 'guide',
    telepon: '081234567890',
    is_available: true,
    jadwal_tugas: 'Senin - Jumat',
  }

  it('should initialize with default values in create mode', () => {
    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: null,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.isEdit).toBe(false)
    expect(vm.form.nama).toBe('')
    expect(vm.form.role).toBe('guide')
    expect(vm.form.telepon).toBe('')
    expect(vm.form.is_available).toBe(true)
  })

  it('should populate form in edit mode with staff data', () => {
    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: mockStaff,
      },
    })

    const vm = wrapper.vm as any
    expect(vm.isEdit).toBe(true)
    expect(vm.form.nama).toBe('Ahmad Pemandu')
    expect(vm.form.role).toBe('guide')
    expect(vm.form.telepon).toBe('081234567890')
    expect(vm.form.jadwal_tugas).toBe('Senin - Jumat')
    expect(vm.form.is_available).toBe(true)
  })

  it('should validate required fields', async () => {
    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: null,
      },
    })

    const vm = wrapper.vm as any
    vm.form.nama = ''
    vm.form.telepon = ''

    await vm.handleSubmit()

    expect(mitraStaffApi.createStaff).not.toHaveBeenCalled()
    expect(vm.errors.nama).toContain('Nama lengkap staf wajib diisi')
    expect(vm.errors.telepon).toContain('Nomor telepon / WhatsApp wajib diisi')
  })

  it('should call createStaff API when submitting new staff', async () => {
    const createdResult: MitraStaff = {
      id: 20,
      mitra_id: 1,
      nama: 'Budi Porter',
      role: 'porter',
      telepon: '085712345678',
      is_available: true,
      jadwal_tugas: 'Weekend Only',
    }

    vi.mocked(mitraStaffApi.createStaff).mockResolvedValueOnce({
      success: true,
      message: 'Staf baru berhasil didaftarkan.',
      data: createdResult,
    })

    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: null,
      },
    })

    const vm = wrapper.vm as any
    vm.form.nama = 'Budi Porter'
    vm.form.role = 'porter'
    vm.form.telepon = '085712345678'
    vm.form.jadwal_tugas = 'Weekend Only'
    vm.form.is_available = true

    await vm.handleSubmit()

    expect(mitraStaffApi.createStaff).toHaveBeenCalledWith({
      nama: 'Budi Porter',
      role: 'porter',
      telepon: '085712345678',
      jadwal_tugas: 'Weekend Only',
      is_available: true,
    })
    expect(wrapper.emitted('saved')?.[0]).toEqual([createdResult])
    expect(vm.serverSuccess).toContain('berhasil didaftarkan')
  })

  it('should call updateStaff API when submitting updated staff', async () => {
    const updatedResult: MitraStaff = {
      ...mockStaff,
      nama: 'Ahmad Pemandu Senior',
    }

    vi.mocked(mitraStaffApi.updateStaff).mockResolvedValueOnce({
      success: true,
      message: 'Data staf berhasil diperbarui.',
      data: updatedResult,
    })

    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: mockStaff,
      },
    })

    const vm = wrapper.vm as any
    vm.form.nama = 'Ahmad Pemandu Senior'

    await vm.handleSubmit()

    expect(mitraStaffApi.updateStaff).toHaveBeenCalledWith(10, {
      nama: 'Ahmad Pemandu Senior',
      role: 'guide',
      telepon: '081234567890',
      jadwal_tugas: 'Senin - Jumat',
      is_available: true,
    })
    expect(wrapper.emitted('saved')?.[0]).toEqual([updatedResult])
    expect(vm.serverSuccess).toContain('berhasil diperbarui')
  })

  it('should emit close and update:isOpen on handleClose', () => {
    const wrapper = mount(StaffFormModal, {
      props: {
        isOpen: true,
        staff: mockStaff,
      },
    })

    const vm = wrapper.vm as any
    vm.handleClose()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
