import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import QuotaCalendarGrid from '@/components/mitra/quota/QuotaCalendarGrid.vue'
import SingleQuotaEditModal from '@/components/mitra/quota/SingleQuotaEditModal.vue'
import type { KuotaHarian } from '@/types/quota'

describe('Mitra Quota Calendar Components (Task 3.2)', () => {
  describe('QuotaCalendarGrid', () => {
    const mockQuotas: KuotaHarian[] = [
      {
        id: 1,
        produk_tiket_id: 10,
        tanggal: '2026-09-10',
        kuota_total: 100,
        kuota_tersisa: 80,
      },
      {
        id: 2,
        produk_tiket_id: 10,
        tanggal: '2026-09-11',
        kuota_total: 100,
        kuota_tersisa: 20,
      },
      {
        id: 3,
        produk_tiket_id: 10,
        tanggal: '2026-09-12',
        kuota_total: 100,
        kuota_tersisa: 0,
      },
    ]

    it('should render month header and calendar day cells', () => {
      const wrapper = mount(QuotaCalendarGrid, {
        props: {
          year: 2026,
          month: 9, // September 2026
          quotas: mockQuotas,
        },
      })

      expect(wrapper.text()).toContain('September 2026')
      expect(wrapper.text()).toContain('Sen')
      expect(wrapper.text()).toContain('Min')

      // Check quota badges
      expect(wrapper.text()).toContain('Sisa 80')
      expect(wrapper.text()).toContain('Sisa 20')
      expect(wrapper.text()).toContain('Penuh (0 sisa)')
    })

    it('should emit prevMonth, nextMonth, and selectDate events', async () => {
      const wrapper = mount(QuotaCalendarGrid, {
        props: {
          year: 2026,
          month: 9,
          quotas: mockQuotas,
        },
      })

      // Emit prev / next / today
      const buttons = wrapper.findAll('button')
      const todayBtn = buttons.find((b) => b.text().includes('Hari Ini'))
      await todayBtn?.trigger('click')
      expect(wrapper.emitted('today')).toBeTruthy()

      // Click on a day cell
      const vm = wrapper.vm as any
      const cell = vm.calendarDays.find((c: any) => c.dateStr === '2026-09-10')
      vm.handleCellClick(cell)

      expect(wrapper.emitted('selectDate')?.[0]?.[0]).toEqual({
        dateStr: '2026-09-10',
        quota: mockQuotas[0],
      })
    })
  })

  describe('SingleQuotaEditModal', () => {
    const existingQuota: KuotaHarian = {
      id: 5,
      produk_tiket_id: 10,
      tanggal: '2026-09-15',
      kuota_total: 100,
      kuota_tersisa: 60, // 40 booked
    }

    it('should calculate booked count and allow quota adjustments', () => {
      const wrapper = mount(SingleQuotaEditModal, {
        props: {
          isOpen: true,
          date: '2026-09-15',
          quota: existingQuota,
          productName: 'Tiket Jalur Selo',
        },
      })

      const vm = wrapper.vm as any
      expect(vm.bookedCount).toBe(40)
      expect(vm.totalQuotaInput).toBe(100)

      // Adjust +25
      vm.adjustTotal(25)
      expect(vm.totalQuotaInput).toBe(125)

      // Submit
      vm.handleSubmit()
      expect(wrapper.emitted('submit')?.[0]?.[0]).toEqual({
        quotaId: 5,
        date: '2026-09-15',
        kuota_total: 125,
      })
    })

    it('should prevent reducing quota below booked count', () => {
      const wrapper = mount(SingleQuotaEditModal, {
        props: {
          isOpen: true,
          date: '2026-09-15',
          quota: existingQuota,
          productName: 'Tiket Jalur Selo',
        },
      })

      const vm = wrapper.vm as any
      expect(vm.bookedCount).toBe(40)

      // Set below booked count
      vm.totalQuotaInput = 30
      vm.handleSubmit()

      expect(vm.errorMessage).toContain('tidak boleh lebih kecil dari tiket yang sudah terpesan (40 tiket)')
      expect(wrapper.emitted('submit')).toBeFalsy()
    })
  })
})
