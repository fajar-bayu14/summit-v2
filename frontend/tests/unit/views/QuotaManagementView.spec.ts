import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import QuotaManagementView from '@/views/mitra/QuotaManagementView.vue'
import mitraProductsApi from '@/api/mitraProducts'
import mitraQuotasApi from '@/api/mitraQuotas'
import type { Produk } from '@/types/product'
import type { KuotaHarian } from '@/types/quota'

describe('QuotaManagementView View Component (Task 3.3)', () => {
  const mockTicketProducts: Produk[] = [
    {
      id: 10,
      basecamp_id: 1,
      nama_produk: 'Tiket Pendakian Jalur Selo',
      kategori: 'ticket',
      harga: 25000,
      is_active: true,
      basecamp: { id: 1, nama_basecamp: 'Basecamp Selo', nama_gunung: 'Merbabu' },
    },
  ]

  const now = new Date()
  const todayDateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`

  const mockQuotas: KuotaHarian[] = [
    {
      id: 1,
      produk_tiket_id: 10,
      tanggal: todayDateStr,
      kuota_total: 100,
      kuota_tersisa: 75, // 25 booked
    },
    {
      id: 2,
      produk_tiket_id: 10,
      tanggal: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-28`,
      kuota_total: 100,
      kuota_tersisa: 50, // 50 booked
    },
  ]

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('should load ticket products and monthly quotas on mount', async () => {
    const productsSpy = vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockTicketProducts as any,
    })

    const quotasSpy = vi.spyOn(mitraQuotasApi, 'getProductQuotas').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockQuotas,
    })

    const wrapper = mount(QuotaManagementView)
    await flushPromises()

    expect(productsSpy).toHaveBeenCalled()
    expect(quotasSpy).toHaveBeenCalled()

    const vm = wrapper.vm as any
    expect(vm.ticketProducts.length).toBe(1)
    expect(vm.selectedProductId).toBe(10)
    expect(vm.quotas.length).toBe(2)

    // Check KPI calculations
    expect(vm.todayQuota?.kuota_tersisa).toBe(75)
    expect(vm.totalMonthBooked).toBe(75) // 25 + 50
    expect(vm.totalMonthCapacity).toBe(200) // 100 + 100
    expect(vm.monthOccupancyRate).toBe(38) // 75/200 = 37.5 -> 38%

    expect(wrapper.text()).toContain('Tiket Pendakian Jalur Selo')
    expect(wrapper.text()).toContain('Terdaftar Bulan Ini')
  })

  it('should navigate months and reload quotas', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockTicketProducts as any,
    })

    const quotasSpy = vi.spyOn(mitraQuotasApi, 'getProductQuotas').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockQuotas,
    })

    const wrapper = mount(QuotaManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    const initialMonth = vm.currentMonth

    // Go next month
    vm.handleNextMonth()
    await flushPromises()

    if (initialMonth === 12) {
      expect(vm.currentMonth).toBe(1)
    } else {
      expect(vm.currentMonth).toBe(initialMonth + 1)
    }

    expect(quotasSpy).toHaveBeenCalledTimes(2)
  })

  it('should handle single quota submission via modal', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockTicketProducts as any,
    })

    vi.spyOn(mitraQuotasApi, 'getProductQuotas').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockQuotas,
    })

    const updateSingleSpy = vi.spyOn(mitraQuotasApi, 'updateSingleQuota').mockResolvedValueOnce({
      status: 'success',
      message: 'Updated',
      data: { ...mockQuotas[0], kuota_total: 150 },
    })

    const wrapper = mount(QuotaManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    await vm.handleSingleQuotaSubmit({
      quotaId: 1,
      date: todayDateStr,
      kuota_total: 150,
    })

    expect(updateSingleSpy).toHaveBeenCalledWith(1, { kuota_total: 150 })
    expect(vm.isSingleModalOpen).toBe(false)
  })

  it('should handle batch quota submission via modal', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: mockTicketProducts as any,
    })

    vi.spyOn(mitraQuotasApi, 'getProductQuotas').mockResolvedValue({
      status: 'success',
      message: 'OK',
      data: mockQuotas,
    })

    const batchSpy = vi.spyOn(mitraQuotasApi, 'batchSetQuotas').mockResolvedValueOnce({
      status: 'success',
      message: 'Batch set successfully',
      data: null,
    })

    const wrapper = mount(QuotaManagementView)
    await flushPromises()

    const vm = wrapper.vm as any
    const payload = {
      start_date: '2026-10-01',
      end_date: '2026-10-15',
      kuota_total: 120,
    }

    await vm.handleBatchQuotaSubmit(payload)

    expect(batchSpy).toHaveBeenCalledWith(10, payload)
    expect(vm.isBatchModalOpen).toBe(false)
  })

  it('should display empty state when no ticket product is found', async () => {
    vi.spyOn(mitraProductsApi, 'getProducts').mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: [],
    })

    const wrapper = mount(QuotaManagementView)
    await flushPromises()

    expect(wrapper.text()).toContain('Belum Ada Tiket Pendakian')
  })
})
