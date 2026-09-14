import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { routerKey } from 'vue-router'
import NotificationDropdown from '@/components/common/NotificationDropdown.vue'
import { useNotificationCenter } from '@/composables/useNotificationCenter'
import { useAuthStore } from '@/stores/auth'
import adminAnalyticsApi from '@/api/adminAnalytics'
import mitraDashboardApi from '@/api/mitraDashboard'

vi.mock('@/api/adminAnalytics', () => ({
  default: {
    getSummary: vi.fn(),
  },
}))

vi.mock('@/api/mitraDashboard', () => ({
  default: {
    getSummary: vi.fn(),
  },
}))

describe('NotificationDropdown & useNotificationCenter', () => {
  const mockRouter = {
    push: vi.fn(),
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    const { clear } = useNotificationCenter()
    clear()
  })

  it('renders trigger button without badge when totalCount is 0', () => {
    const wrapper = mount(NotificationDropdown, {
      global: {
        provide: {
          [routerKey as symbol]: mockRouter,
        },
        stubs: {
          DropdownMenu: { template: '<div><slot /></div>' },
          DropdownMenuTrigger: { template: '<div><slot /></div>' },
          DropdownMenuContent: { template: '<div><slot /></div>' },
          DropdownMenuItem: { template: '<div><slot /></div>' },
          DropdownMenuSeparator: { template: '<hr />' },
        },
      },
    })

    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.attributes('title')).toBe('Pusat Notifikasi')
    // No red badge rendered
    const badge = wrapper.find('[role="status"]')
    expect(badge.exists()).toBe(false)
  })

  it('fetches and populates notifications for Admin role', async () => {
    const authStore = useAuthStore()
    authStore.user = {
      id: 1,
      name: 'Admin Summit',
      email: 'admin@summit.id',
      role: 'admin',
    } as any
    authStore.token = 'fake-token'

    vi.mocked(adminAnalyticsApi.getSummary).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        action_queues: {
          kyc_pending_count: 5,
          withdrawal_pending_count: 2,
          refund_pending_count: 1,
        },
        total_pending: 8,
        overview: {
          total_gunung: 10,
          total_mitra: 4,
        },
      },
    } as any)

    const { fetchNotifications, notifications, totalCount } = useNotificationCenter()
    await fetchNotifications()

    expect(adminAnalyticsApi.getSummary).toHaveBeenCalledTimes(1)
    expect(totalCount.value).toBe(8)
    expect(notifications.value).toHaveLength(3)

    const kycItem = notifications.value.find(n => n.id === 'admin-kyc')
    expect(kycItem).toBeDefined()
    expect(kycItem?.count).toBe(5)
    expect(kycItem?.link).toBe('/admin/verifications')

    const withdrawalItem = notifications.value.find(n => n.id === 'admin-withdrawals')
    expect(withdrawalItem).toBeDefined()
    expect(withdrawalItem?.count).toBe(2)
    expect(withdrawalItem?.link).toBe('/admin/withdrawals')

    const refundItem = notifications.value.find(n => n.id === 'admin-refunds')
    expect(refundItem).toBeDefined()
    expect(refundItem?.count).toBe(1)
    expect(refundItem?.link).toBe('/admin/refunds')
  })

  it('fetches and populates notifications for Mitra role', async () => {
    const authStore = useAuthStore()
    authStore.user = {
      id: 2,
      name: 'Mitra Merbabu',
      email: 'mitra@merbabu.id',
      role: 'mitra',
    } as any
    authStore.token = 'fake-mitra-token'

    vi.mocked(mitraDashboardApi.getSummary).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        financial: {} as any,
        operations_today: {} as any,
        action_queues: {
          pesanan_paid_count: 4,
          logbook_pending_count: 3,
          refund_pending_count: 1,
        },
        resources: {
          total_basecamp: 1,
          total_produk_aktif: 5,
          total_staf_tersedia: 2,
          total_staf_bertugas: 1,
          produk_stok_menipis_count: 2,
        },
      },
    } as any)

    const { fetchNotifications, notifications, totalCount } = useNotificationCenter()
    await fetchNotifications()

    expect(mitraDashboardApi.getSummary).toHaveBeenCalledTimes(1)
    expect(totalCount.value).toBe(10) // 4 + 3 + 1 + 2
    expect(notifications.value).toHaveLength(4)

    const ordersItem = notifications.value.find(n => n.id === 'mitra-orders')
    expect(ordersItem?.count).toBe(4)
    expect(ordersItem?.link).toBe('/mitra/orders')

    const logbooksItem = notifications.value.find(n => n.id === 'mitra-logbooks')
    expect(logbooksItem?.count).toBe(3)
    expect(logbooksItem?.link).toBe('/mitra/logbooks')

    const refundsItem = notifications.value.find(n => n.id === 'mitra-refunds')
    expect(refundsItem?.count).toBe(1)
    expect(refundsItem?.link).toBe('/mitra/refunds')

    const stockItem = notifications.value.find(n => n.id === 'mitra-low-stock')
    expect(stockItem?.count).toBe(2)
    expect(stockItem?.link).toBe('/mitra/products')
  })

  it('displays the counter badge when totalCount > 0 in NotificationDropdown', async () => {
    const authStore = useAuthStore()
    authStore.user = {
      id: 1,
      name: 'Admin Summit',
      email: 'admin@summit.id',
      role: 'admin',
    } as any
    authStore.token = 'fake-token'

    vi.mocked(adminAnalyticsApi.getSummary).mockResolvedValueOnce({
      status: 'success',
      message: 'OK',
      data: {
        action_queues: {
          kyc_pending_count: 3,
          withdrawal_pending_count: 0,
          refund_pending_count: 0,
        },
        total_pending: 3,
        overview: { total_gunung: 1, total_mitra: 1 },
      },
    } as any)

    const wrapper = mount(NotificationDropdown, {
      global: {
        provide: {
          [routerKey as symbol]: mockRouter,
        },
        stubs: {
          DropdownMenu: { template: '<div><slot /></div>' },
          DropdownMenuTrigger: { template: '<div><slot /></div>' },
          DropdownMenuContent: { template: '<div><slot /></div>' },
          DropdownMenuItem: { template: '<div><slot /></div>' },
          DropdownMenuSeparator: { template: '<hr />' },
        },
      },
    })

    // Wait for onMounted fetchNotifications to resolve
    await vi.waitFor(() => {
      expect(wrapper.find('[role="status"]').exists()).toBe(true)
    })

    const badge = wrapper.find('[role="status"]')
    expect(badge.text()).toBe('3')
    expect(badge.classes()).toContain('bg-rose-600')
  })
})
