import { ref, computed, type Component } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMitraStore } from '@/stores/mitra'
import adminAnalyticsApi from '@/api/adminAnalytics'
import mitraDashboardApi from '@/api/mitraDashboard'
import {
  ShieldAlert,
  CreditCard,
  RotateCcw,
  ShoppingBag,
  ClipboardList,
  AlertTriangle,
} from 'lucide-vue-next'

export interface NotificationItem {
  id: string
  title: string
  description: string
  link: string
  count: number
  type: 'critical' | 'warning' | 'info'
  icon: Component
  iconColor: string
  iconBg: string
  badgeVariant?: 'default' | 'destructive' | 'secondary' | 'outline'
}

const notifications = ref<NotificationItem[]>([])
const isLoading = ref<boolean>(false)
const lastFetchedAt = ref<Date | null>(null)

export function useNotificationCenter() {
  const authStore = useAuthStore()
  const mitraStore = useMitraStore()

  const totalCount = computed<number>(() => {
    return notifications.value.reduce((acc, item) => acc + item.count, 0)
  })

  async function fetchNotifications(): Promise<NotificationItem[]> {
    if (!authStore.isAuthenticated) {
      notifications.value = []
      return []
    }

    isLoading.value = true
    const items: NotificationItem[] = []

    try {
      if (authStore.isAdmin) {
        const response = await adminAnalyticsApi.getSummary()
        const queues = response.data?.action_queues

        if (queues) {
          if (queues.kyc_pending_count > 0) {
            items.push({
              id: 'admin-kyc',
              title: 'Verifikasi KYC Pengguna',
              description: `${queues.kyc_pending_count} permohonan verifikasi identitas pendaki menunggu persetujuan`,
              link: '/admin/verifications',
              count: queues.kyc_pending_count,
              type: 'warning',
              icon: ShieldAlert,
              iconColor: 'text-amber-600 dark:text-amber-400',
              iconBg: 'bg-amber-100/70 dark:bg-amber-950/40',
            })
          }

          if (queues.withdrawal_pending_count > 0) {
            items.push({
              id: 'admin-withdrawals',
              title: 'Penarikan Dana Mitra',
              description: `${queues.withdrawal_pending_count} pengajuan pencairan saldo escrow mitra siap diproses`,
              link: '/admin/withdrawals',
              count: queues.withdrawal_pending_count,
              type: 'critical',
              icon: CreditCard,
              iconColor: 'text-rose-600 dark:text-rose-400',
              iconBg: 'bg-rose-100/70 dark:bg-rose-950/40',
            })
          }

          if (queues.refund_pending_count > 0) {
            items.push({
              id: 'admin-refunds',
              title: 'Pengajuan Refund Tiket',
              description: `${queues.refund_pending_count} sengketa refund atau pembatalan membutuhkan review admin`,
              link: '/admin/refunds',
              count: queues.refund_pending_count,
              type: 'warning',
              icon: RotateCcw,
              iconColor: 'text-blue-600 dark:text-blue-400',
              iconBg: 'bg-blue-100/70 dark:bg-blue-950/40',
            })
          }
        }
      } else if (authStore.isMitra) {
        const response = await mitraDashboardApi.getSummary(mitraStore.activeBasecampId)
        const queues = response.data?.action_queues
        const resources = response.data?.resources

        if (queues) {
          // Keep mitra store in sync
          mitraStore.incomingOrdersCount = queues.pesanan_paid_count ?? 0

          if (queues.pesanan_paid_count > 0) {
            items.push({
              id: 'mitra-orders',
              title: 'Pesanan Masuk Perlu Diproses',
              description: `${queues.pesanan_paid_count} pesanan baru telah dibayar dan perlu diverifikasi`,
              link: '/mitra/orders',
              count: queues.pesanan_paid_count,
              type: 'critical',
              icon: ShoppingBag,
              iconColor: 'text-emerald-600 dark:text-emerald-400',
              iconBg: 'bg-emerald-100/70 dark:bg-emerald-950/40',
            })
          }

          if (queues.logbook_pending_count > 0) {
            items.push({
              id: 'mitra-logbooks',
              title: 'Logbook Pendaki Menunggu Validasi',
              description: `${queues.logbook_pending_count} pendaki telah melapor dan menunggu konfirmasi check-in/out`,
              link: '/mitra/logbooks',
              count: queues.logbook_pending_count,
              type: 'warning',
              icon: ClipboardList,
              iconColor: 'text-blue-600 dark:text-blue-400',
              iconBg: 'bg-blue-100/70 dark:bg-blue-950/40',
            })
          }

          if (queues.refund_pending_count > 0) {
            items.push({
              id: 'mitra-refunds',
              title: 'Permintaan Refund / Pembatalan',
              description: `${queues.refund_pending_count} pengajuan refund pendaki memerlukan persetujuan mitra`,
              link: '/mitra/refunds',
              count: queues.refund_pending_count,
              type: 'critical',
              icon: RotateCcw,
              iconColor: 'text-rose-600 dark:text-rose-400',
              iconBg: 'bg-rose-100/70 dark:bg-rose-950/40',
            })
          }
        }

        if (resources && resources.produk_stok_menipis_count > 0) {
          items.push({
            id: 'mitra-low-stock',
            title: 'Peringatan Stok Alat Menipis',
            description: `${resources.produk_stok_menipis_count} perlengkapan sewa memiliki stok di bawah batas minimum`,
            link: '/mitra/products',
            count: resources.produk_stok_menipis_count,
            type: 'warning',
            icon: AlertTriangle,
            iconColor: 'text-amber-600 dark:text-amber-400',
            iconBg: 'bg-amber-100/70 dark:bg-amber-950/40',
          })
        }
      }

      notifications.value = items
      lastFetchedAt.value = new Date()
      return items
    } catch {
      // Retain existing state or clear on auth error
      return notifications.value
    } finally {
      isLoading.value = false
    }
  }

  function clear(): void {
    notifications.value = []
    lastFetchedAt.value = null
  }

  return {
    notifications,
    totalCount,
    isLoading,
    lastFetchedAt,
    fetchNotifications,
    clear,
  }
}
