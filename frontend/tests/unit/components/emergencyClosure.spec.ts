import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import EmergencyTrailBanner from '@/components/mitra/dashboard/EmergencyTrailBanner.vue'
import EmergencyClosureModal from '@/components/mitra/dashboard/EmergencyClosureModal.vue'
import mitraDashboardApi from '@/api/mitraDashboard'

describe('Emergency Trail Closure Controls (Task 1.3)', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  describe('EmergencyTrailBanner', () => {
    it('should render alert banner with reason when status is close', () => {
      const wrapper = mount(EmergencyTrailBanner, {
        props: {
          status: 'close',
          trailName: 'Jalur Bambangan',
          mountainName: 'Gn. Slamet',
          reason: 'Cuaca badai petir di pos 4',
        },
      })

      expect(wrapper.text()).toContain('Jalur Ini Sedang Ditutup Sementara')
      expect(wrapper.text()).toContain('Cuaca badai petir di pos 4')
      expect(wrapper.text()).toContain('Buka Kembali Jalur')
    })

    it('should emit openModal when button is clicked', async () => {
      const wrapper = mount(EmergencyTrailBanner, {
        props: {
          status: 'open',
          trailName: 'Jalur Bambangan',
          mountainName: 'Gn. Slamet',
        },
      })

      const button = wrapper.find('button')
      await button.trigger('click')

      expect(wrapper.emitted('openModal')).toBeTruthy()
    })
  })

  describe('EmergencyClosureModal', () => {
    it('should call emergencyCloseTrail API on submit and emit success', async () => {
      const apiSpy = vi.spyOn(mitraDashboardApi, 'emergencyCloseTrail').mockResolvedValueOnce({
        status: 'success',
        message: 'Jalur ditutup',
        data: {} as any,
      })

      const wrapper = mount(EmergencyClosureModal, {
        props: {
          open: true,
          currentStatus: 'open',
          trailId: 10,
          trailName: 'Jalur Bambangan',
        },
      })

      // Directly invoke component submit or test props
      const vm = wrapper.vm as any
      vm.reason = 'Badai angin kencang di pos 3'
      vm.targetStatus = 'close'

      await vm.handleSubmit()

      expect(apiSpy).toHaveBeenCalledWith(10, {
        status: 'close',
        alasan_penutupan: 'Badai angin kencang di pos 3',
      })
      expect(wrapper.emitted('success')).toBeTruthy()
      expect(wrapper.emitted('update:open')).toEqual([[false]])
    })

    it('should show validation error if reason is too short when closing', async () => {
      const wrapper = mount(EmergencyClosureModal, {
        props: {
          open: true,
          currentStatus: 'open',
          trailId: 10,
          trailName: 'Jalur Bambangan',
        },
      })

      const vm = wrapper.vm as any
      vm.reason = 'abc'
      vm.targetStatus = 'close'

      await vm.handleSubmit()

      expect(vm.errorMessage).toContain('minimal 5 karakter')
    })
  })
})
