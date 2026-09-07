import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DashboardMetricGrid from '@/components/mitra/dashboard/DashboardMetricGrid.vue'
import ActionQueuesBar from '@/components/mitra/dashboard/ActionQueuesBar.vue'
import type { MitraAnalyticsSummary } from '@/types/dashboard'

describe('Dashboard Metrics & Action Queues (Task 1.2)', () => {
  const mockMetrics: MitraAnalyticsSummary = {
    financial: {
      saldo_available: 750000,
      saldo_pending: 250000,
      total_penarikan: 500000,
      total_pendapatan_bersih: 94500,
      total_transaksi_paid: 2,
    },
    operations_today: {
      pendaki_berangkat_hari_ini: 3,
      pendaki_sedang_mendaki: 1,
      total_kuota_hari_ini: 150,
      kuota_terpakai_hari_ini: 20,
      sisa_kuota_hari_ini: 130,
      status_jalur: 'open',
    },
    action_queues: {
      pesanan_paid_count: 5,
      logbook_pending_count: 2,
      refund_pending_count: 1,
    },
    resources: {
      total_basecamp: 1,
      total_produk_aktif: 2,
      total_staf_tersedia: 4,
      total_staf_bertugas: 2,
      produk_stok_menipis_count: 3,
    },
  }

  describe('DashboardMetricGrid', () => {
    it('should render financial metrics formatted as IDR currency', () => {
      const wrapper = mount(DashboardMetricGrid, {
        props: {
          metrics: mockMetrics,
          loading: false,
        },
      })

      expect(wrapper.text()).toContain('750.000')
      expect(wrapper.text()).toContain('94.500')
    })

    it('should render today arrivals and quota availability', () => {
      const wrapper = mount(DashboardMetricGrid, {
        props: {
          metrics: mockMetrics,
          loading: false,
        },
      })

      expect(wrapper.text()).toContain('3 Orang')
      expect(wrapper.text()).toContain('130')
      expect(wrapper.text()).toContain('150')
      expect(wrapper.text()).toContain('Jalur Buka')
    })

    it('should render closed status when status_jalur is close', () => {
      const closedMetrics: MitraAnalyticsSummary = {
        ...mockMetrics,
        operations_today: {
          ...mockMetrics.operations_today,
          status_jalur: 'close',
        },
      }

      const wrapper = mount(DashboardMetricGrid, {
        props: {
          metrics: closedMetrics,
          loading: false,
        },
      })

      expect(wrapper.text()).toContain('Jalur Tutup')
    })
  })

  describe('ActionQueuesBar', () => {
    it('should render pending queues with counts', () => {
      const wrapper = mount(ActionQueuesBar, {
        props: {
          actionQueues: mockMetrics.action_queues,
          resources: mockMetrics.resources,
        },
      })

      expect(wrapper.text()).toContain('5 pesanan lunas')
      expect(wrapper.text()).toContain('2 pending validasi')
      expect(wrapper.text()).toContain('1 permohonan')
      expect(wrapper.text()).toContain('3 alat')
    })
  })
})
