import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { MetricCard, StatusBadge, ConfirmModal, EmptyState } from '@/components/common'

describe('Reusable Common UI Components & Brand Tokens (Task 0.4)', () => {
  describe('MetricCard Component', () => {
    it('should render title, value, and description correctly', () => {
      const wrapper = mount(MetricCard, {
        props: {
          title: 'Total Pendapatan',
          value: 'Rp 15.000.000',
          description: 'Pendapatan bersih siap ditarik',
        },
      })

      expect(wrapper.text()).toContain('Total Pendapatan')
      expect(wrapper.text()).toContain('Rp 15.000.000')
      expect(wrapper.text()).toContain('Pendapatan bersih siap ditarik')
    })

    it('should render trend badge with up styling when trend is up', () => {
      const wrapper = mount(MetricCard, {
        props: {
          title: 'Pendaki Hari Ini',
          value: 45,
          change: '+12%',
          trend: 'up',
        },
      })

      expect(wrapper.text()).toContain('+12%')
      expect(wrapper.find('.text-emerald-600').exists()).toBe(true)
    })
  })

  describe('StatusBadge Component', () => {
    it('should map "paid" status to Indonesian text and emerald styles', () => {
      const wrapper = mount(StatusBadge, {
        props: {
          status: 'paid',
        },
      })

      expect(wrapper.text()).toContain('Sudah Bayar')
      expect(wrapper.classes()).toContain('text-emerald-700')
    })

    it('should map "open" status to Jalur Buka', () => {
      const wrapper = mount(StatusBadge, {
        props: {
          status: 'open',
        },
      })

      expect(wrapper.text()).toContain('Jalur Buka')
    })

    it('should map "close" status to Jalur Ditutup and rose styles', () => {
      const wrapper = mount(StatusBadge, {
        props: {
          status: 'close',
        },
      })

      expect(wrapper.text()).toContain('Jalur Ditutup')
      expect(wrapper.classes()).toContain('text-rose-700')
    })

    it('should render fallback label for unknown status', () => {
      const wrapper = mount(StatusBadge, {
        props: {
          status: 'custom_status',
        },
      })

      expect(wrapper.text()).toContain('custom_status')
    })
  })

  describe('EmptyState Component', () => {
    it('should render title and description', () => {
      const wrapper = mount(EmptyState, {
        props: {
          title: 'Belum Ada Pesanan',
          description: 'Belum ada pesanan booking yang masuk ke basecamp ini.',
        },
      })

      expect(wrapper.text()).toContain('Belum Ada Pesanan')
      expect(wrapper.text()).toContain('Belum ada pesanan booking')
    })
  })
})
