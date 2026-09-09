<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { StatusBadge, EmptyState } from '@/components/common'
import {
  Search,
  Users,
  QrCode,
  ChevronRight,
  ExternalLink,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    orders?: any[]
    loading?: boolean
  }>(),
  {
    orders: () => [],
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'viewDetail', order: any): void
  (e: 'processCheckIn', order: any): void
  (e: 'refresh'): void
}>()

const router = useRouter()
const searchQuery = ref('')

function formatRupiah(value: number | undefined | null): string {
  if (value === undefined || value === null) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

const filteredOrders = computed(() => {
  if (!props.orders) return []
  if (!searchQuery.value.trim()) return props.orders

  const query = searchQuery.value.toLowerCase().trim()
  return props.orders.filter((order) => {
    const invoice = (order.invoice || '').toLowerCase()
    const name = (order.user?.name || order.nama_pemesan || '').toLowerCase()
    const phone = (order.user?.telepon || order.user?.email || '').toLowerCase()
    return invoice.includes(query) || name.includes(query) || phone.includes(query)
  })
})
</script>

<template>
  <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-card shadow-xs overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Antrean Check-In Pendaki Hari Ini</span>
        </h3>
        <p class="text-xs text-muted-foreground mt-0.5">
          Daftar rombongan pendaki yang terjadwal masuk dan memulai pendakian di basecamp ini
        </p>
      </div>

      <div class="flex items-center gap-2">
        <div class="relative w-full sm:w-64">
          <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="search"
            placeholder="Cari invoice / nama..."
            class="pl-8 h-8 text-xs rounded-xl bg-muted/30"
          />
        </div>

        <Button
          size="sm"
          variant="outline"
          class="h-8 px-3 text-xs gap-1.5 rounded-xl shrink-0"
          @click="router.push('/mitra/orders')"
        >
          <span>Lihat Semua</span>
          <ChevronRight class="h-3.5 w-3.5" />
        </Button>
      </div>
    </div>

    <!-- Table content -->
    <div class="overflow-x-auto">
      <Table>
        <TableHeader class="bg-muted/30">
          <TableRow>
            <TableHead class="text-xs font-semibold">No. Invoice</TableHead>
            <TableHead class="text-xs font-semibold">Ketua Rombongan</TableHead>
            <TableHead class="text-xs font-semibold">Jumlah Anggota</TableHead>
            <TableHead class="text-xs font-semibold">Tanggal Booking</TableHead>
            <TableHead class="text-xs font-semibold">Total Tagihan</TableHead>
            <TableHead class="text-xs font-semibold">Status</TableHead>
            <TableHead class="text-xs font-semibold text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>

        <!-- Loading Skeleton -->
        <TableBody v-if="loading">
          <TableRow v-for="i in 4" :key="i">
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-24"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-32"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-16"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-20"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-20"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-16"></div></TableCell>
            <TableCell><div class="h-4 bg-muted animate-pulse rounded w-16 ml-auto"></div></TableCell>
          </TableRow>
        </TableBody>

        <!-- Data Rows -->
        <TableBody v-else-if="filteredOrders.length > 0">
          <TableRow
            v-for="order in filteredOrders"
            :key="order.id"
            class="hover:bg-muted/30 transition-colors"
          >
            <TableCell class="font-mono text-xs font-bold text-emerald-700 dark:text-emerald-400">
              {{ order.invoice }}
            </TableCell>

            <TableCell>
              <div class="flex flex-col">
                <span class="font-semibold text-xs text-foreground">
                  {{ order.user?.name || order.nama_pemesan || 'Pendaki' }}
                </span>
                <span class="text-[11px] text-muted-foreground">
                  {{ order.user?.telepon || order.user?.email || '-' }}
                </span>
              </div>
            </TableCell>

            <TableCell class="text-xs">
              <div class="inline-flex items-center gap-1 font-medium text-foreground">
                <Users class="h-3.5 w-3.5 text-muted-foreground" />
                <span>{{ order.anggotas?.length || order.jumlah_anggota || 1 }} Orang</span>
              </div>
            </TableCell>

            <TableCell class="text-xs text-muted-foreground font-medium">
              {{ order.tanggal_booking || '-' }}
            </TableCell>

            <TableCell class="text-xs font-bold text-foreground font-mono">
              {{ formatRupiah(order.total_bayar || order.subtotal) }}
            </TableCell>

            <TableCell>
              <StatusBadge :status="order.status" />
            </TableCell>

            <TableCell class="text-right">
              <div class="inline-flex items-center gap-1.5 justify-end">
                <Button
                  v-if="order.status === 'paid'"
                  size="sm"
                  class="h-7 px-2.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white gap-1"
                  @click="emit('processCheckIn', order)"
                >
                  <QrCode class="h-3 w-3" />
                  <span>Check-In</span>
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  class="h-7 px-2.5 rounded-lg text-xs font-medium gap-1 text-muted-foreground hover:text-foreground"
                  @click="emit('viewDetail', order)"
                >
                  <ExternalLink class="h-3 w-3" />
                  <span>Detail</span>
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>

        <!-- Empty State -->
        <TableBody v-else>
          <TableRow>
            <TableCell colspan="7" class="py-12">
              <EmptyState
                title="Tidak Ada Antrean Check-In"
                description="Belum ada rombongan pendaki yang dijadwalkan masuk pos hari ini."
              />
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
