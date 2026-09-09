import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import TodayArrivalTable from '@/components/mitra/dashboard/TodayArrivalTable.vue'
import MitraDashboard from '@/views/mitra/MitraDashboard.vue'
import mitraDashboardApi from '@/api/mitraDashboard'
import { useMitraStore } from '@/stores/mitra'

describe('Today Arrival Table & Mitra Dashboard View (Task 1.4)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.restoreAllMocks()
  })

  const mockOrders = [
    {
      id: 1,
      invoice: 'INV-SLAMET-001',
      tanggal_booking: '2026-09-06',
      total_bayar: 150000,
      subtotal: 150000,
      status: 'paid',
      user: {
        id: 10,
        name: 'Fajar Bayu',
        telepon: '081234567890',
      },
      anggotas: [
        { id: 101, nama_anggota: 'Fajar' },
        { id: 102, nama_anggota: 'Bayu' },
      ],
    },
    {
      id: 2,
      invoice: 'INV-SLAMET-002',
      tanggal_booking: '2026-09-06',
      total_bayar: 75000,
      subtotal: 75000,
      status: 'on_going',
      user: {
        id: 11,
        name: 'Rian Pratama',
        telepon: '085712345678',
      },
      anggotas: [{ id: 103, nama_anggota: 'Rian' }],
    },
  ]

  describe('TodayArrivalTable Component', () => {
    it('should render invoice, leader name, members, and formatted amount', () => {
      const wrapper = mount(TodayArrivalTable, {
        props: {
          orders: mockOrders,
          loading: false,
        },
      })

      expect(wrapper.text()).toContain('INV-SLAMET-001')
      expect(wrapper.text()).toContain('Fajar Bayu')
      expect(wrapper.text()).toContain('2 Orang')
      expect(wrapper.text()).toContain('150.000')
      expect(wrapper.text()).toContain('Sudah Bayar')
    })

    it('should filter orders based on search query', async () => {
      const wrapper = mount(TodayArrivalTable, {
        props: {
          orders: mockOrders,
          loading: false,
        },
      })

      const searchInput = wrapper.find('input[type="search"]')
      await searchInput.setValue('Rian')

      expect(wrapper.text()).toContain('Rian Pratama')
      expect(wrapper.text()).not.toContain('Fajar Bayu')
    })

    it('should emit processCheckIn when check-in button is clicked on paid order', async () => {
      const wrapper = mount(TodayArrivalTable, {
        props: {
          orders: mockOrders,
          loading: false,
        },
      })

      const checkInBtn = wrapper.findAll('button').find(b => b.text().includes('Check-In'))
      expect(checkInBtn).toBeDefined()
      await checkInBtn?.trigger('click')

      expect(wrapper.emitted('processCheckIn')).toBeTruthy()
      expect(wrapper.emitted('processCheckIn')?.[0]).toEqual([mockOrders[0]])
    })
  })

  describe('MitraDashboard View Component', () => {
    it('should load summary and today orders on mount', async () => {
      const summarySpy = vi.spyOn(mitraDashboardApi, 'getSummary').mockResolvedValueOnce({
        status: 'success',
        data: {
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
            pesanan_paid_count: 1,
            logbook_pending_count: 1,
            refund_pending_count: 1,
          },
          resources: {
            total_basecamp: 1,
            total_produk_aktif: 2,
            total_staf_tersedia: 1,
            total_staf_bertugas: 1,
            produk_stok_menipis_count: 1,
          },
        },
      } as any)

      const ordersSpy = vi.spyOn(mitraDashboardApi, 'getTodayOrders').mockResolvedValueOnce({
        status: 'success',
        data: mockOrders,
      } as any)

      const wrapper = mount(MitraDashboard)

      await new Promise(r => setTimeout(r, 50))

      expect(summarySpy).toHaveBeenCalled()
      expect(ordersSpy).toHaveBeenCalled()
      expect(wrapper.text()).toContain('Pusat Operasional Basecamp')
    })
  })
})
