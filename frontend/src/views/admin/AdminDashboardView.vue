<script setup lang="ts">
import { ref } from 'vue'
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  Users,
  Mountain,
  Compass,
  Download,
  Calendar,
  Filter,
  MoreVertical,
} from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// Filter periode waktu chart
const selectedPeriod = ref<'7d' | '30d' | '90d'>('30d')

// Ringkasan 4 Kartu Metrik (Analytics Section Cards)
const metricStats = [
  {
    title: 'Total Pendapatan Tiket',
    value: 'Rp 148.250.000',
    change: '+18.2% vs bln lalu',
    trend: 'up',
    icon: DollarSign,
    description: 'Dari 1.240 transaksi sukses',
  },
  {
    title: 'Pendaki Aktif (Di Gunung)',
    value: '842 Jiwa',
    change: '+5.4% kapasitas aman',
    trend: 'up',
    icon: Users,
    description: 'Tersebar di 8 gunung aktif',
  },
  {
    title: 'Total Booking Tiket',
    value: '2.845 Tiket',
    change: '+12.1% dari target',
    trend: 'up',
    icon: Compass,
    description: 'Bulan berjalan September 2026',
  },
  {
    title: 'Jalur Basecamp Aktif',
    value: '24 Jalur',
    change: '2 Jalur Ditutup Cuaca',
    trend: 'neutral',
    icon: Mountain,
    description: 'Total 26 titik pos perizinan',
  },
]

// Mock data transaksi tiket terkini (Recent Orders Data Table)
const recentTransactions = [
  {
    id: 'SMT-2026-0901',
    climber: 'Budi Santoso',
    email: 'budi.s@gmail.com',
    mountain: 'Gunung Rinjani',
    track: 'Jalur Sembalun',
    date: '04 Sep 2026',
    members: 4,
    total: 'Rp 1.400.000',
    status: 'PAID',
  },
  {
    id: 'SMT-2026-0902',
    climber: 'Siti Rahmawati',
    email: 'siti.rahma@yahoo.com',
    mountain: 'Gunung Semeru',
    track: 'Jalur Ranu Pani',
    date: '06 Sep 2026',
    members: 2,
    total: 'Rp 520.000',
    status: 'CHECKED_IN',
  },
  {
    id: 'SMT-2026-0903',
    climber: 'Dimas Pratama',
    email: 'dimas.p@outlook.com',
    mountain: 'Gunung Prau',
    track: 'Jalur Dieng',
    date: '05 Sep 2026',
    members: 5,
    total: 'Rp 650.000',
    status: 'PAID',
  },
  {
    id: 'SMT-2026-0904',
    climber: 'Andi Setiawan',
    email: 'andis@gmail.com',
    mountain: 'Gunung Gede',
    track: 'Jalur Putri',
    date: '03 Sep 2026',
    members: 3,
    total: 'Rp 450.000',
    status: 'PENDING',
  },
  {
    id: 'SMT-2026-0905',
    climber: 'Ayu Lestari',
    email: 'ayu.l@gmail.com',
    mountain: 'Gunung Merbabu',
    track: 'Jalur Selo',
    date: '02 Sep 2026',
    members: 6,
    total: 'Rp 900.000',
    status: 'CANCELLED',
  },
]

function getStatusBadge(status: string) {
  switch (status) {
    case 'PAID':
      return { label: 'Lunas', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' }
    case 'CHECKED_IN':
      return { label: 'Di Gunung', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' }
    case 'PENDING':
      return { label: 'Menunggu', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' }
    case 'CANCELLED':
      return { label: 'Batal', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' }
    default:
      return { label: status, class: 'bg-muted text-muted-foreground' }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Dashboard Administrator</h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Ikhtisar performa kuota pendakian, reservasi tiket, dan aktivitas basecamp mitra se-Indonesia.
        </p>
      </div>
      <div class="flex items-center gap-2.5">
        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-9">
          <Calendar class="h-3.5 w-3.5" /> 01 Sep - 30 Sep 2026
        </Button>
        <Button size="sm" class="gap-1.5 text-xs h-9 bg-emerald-700 hover:bg-emerald-800 text-white">
          <Download class="h-3.5 w-3.5" /> Ekspor Laporan
        </Button>
      </div>
    </div>

    <!-- Section 1: Metrics Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card v-for="(stat, idx) in metricStats" :key="idx" class="relative overflow-hidden border shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
          <CardTitle class="text-xs font-medium text-muted-foreground">{{ stat.title }}</CardTitle>
          <div class="h-8 w-8 rounded-md bg-muted/60 flex items-center justify-center text-muted-foreground">
            <component :is="stat.icon" class="h-4 w-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-extrabold tracking-tight text-foreground">{{ stat.value }}</div>
          <div class="flex items-center gap-1.5 text-xs mt-1.5 font-medium">
            <span
              :class="[
                stat.trend === 'up' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
              ]"
              class="flex items-center gap-0.5"
            >
              <TrendingUp v-if="stat.trend === 'up'" class="h-3 w-3" />
              <TrendingDown v-else class="h-3 w-3" />
              {{ stat.change }}
            </span>
          </div>
          <p class="text-[11px] text-muted-foreground mt-1">{{ stat.description }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Section 2: Interactive Analytics Chart Card -->
    <Card class="border shadow-xs">
      <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 gap-3 border-b">
        <div>
          <CardTitle class="text-base font-semibold">Tren Volume Booking & Pendaki</CardTitle>
          <CardDescription class="text-xs">Statistik pergerakan tiket booking vs pendaki riil di jalur pendakian</CardDescription>
        </div>
        <div class="flex items-center gap-1.5 bg-muted/50 p-1 rounded-lg self-start sm:self-auto">
          <Button
            size="sm"
            variant="ghost"
            class="h-7 text-xs px-3 rounded-md"
            :class="selectedPeriod === '7d' ? 'bg-background shadow-xs font-semibold' : 'text-muted-foreground'"
            @click="selectedPeriod = '7d'"
          >
            7 Hari
          </Button>
          <Button
            size="sm"
            variant="ghost"
            class="h-7 text-xs px-3 rounded-md"
            :class="selectedPeriod === '30d' ? 'bg-background shadow-xs font-semibold' : 'text-muted-foreground'"
            @click="selectedPeriod = '30d'"
          >
            30 Hari
          </Button>
          <Button
            size="sm"
            variant="ghost"
            class="h-7 text-xs px-3 rounded-md"
            :class="selectedPeriod === '90d' ? 'bg-background shadow-xs font-semibold' : 'text-muted-foreground'"
            @click="selectedPeriod = '90d'"
          >
            3 Bulan
          </Button>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <!-- Visualization bars with gradient & hover indicators -->
        <div class="h-64 w-full flex flex-col justify-end">
          <div class="flex-1 flex items-end gap-3 sm:gap-6 pt-4 pb-2 px-2 border-b border-dashed">
            <div v-for="i in 12" :key="i" class="flex-1 flex flex-col items-center gap-2 group cursor-pointer">
              <div class="w-full flex items-end justify-center gap-1 h-44">
                <div
                  class="w-full max-w-[18px] bg-emerald-600/80 hover:bg-emerald-600 rounded-t transition-all group-hover:scale-y-105"
                  :style="{ height: `${(i * 7 + (i % 3) * 15) % 85 + 15}%` }"
                  :title="`Booking Tgl ${i * 2}: ${(i * 12 + 20)} tiket`"
                />
                <div
                  class="w-full max-w-[18px] bg-amber-500/70 hover:bg-amber-500 rounded-t transition-all group-hover:scale-y-105"
                  :style="{ height: `${(i * 5 + (i % 4) * 18) % 70 + 10}%` }"
                  :title="`Check-in Tgl ${i * 2}: ${(i * 8 + 15)} orang`"
                />
              </div>
              <span class="text-[10px] text-muted-foreground font-medium">Tgl {{ i * 2 }}</span>
            </div>
          </div>
          <!-- Legend indicators -->
          <div class="flex items-center justify-center gap-6 mt-4 text-xs text-muted-foreground font-medium">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-sm bg-emerald-600 inline-block" />
              <span>Tiket Terbayar (Booking Online)</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-sm bg-amber-500 inline-block" />
              <span>Pendaki Check-in (Di Jalur)</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Section 3: Recent Transactions Data Table -->
    <Card class="border shadow-xs">
      <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 gap-3">
        <div>
          <CardTitle class="text-base font-semibold">Transaksi Reservasi Terbaru</CardTitle>
          <CardDescription class="text-xs">Daftar booking tiket masuk pos pendakian terkini</CardDescription>
        </div>
        <Button variant="outline" size="sm" class="text-xs h-8 gap-1.5">
          <Filter class="h-3.5 w-3.5" /> Filter Status
        </Button>
      </CardHeader>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="text-xs font-semibold">Kode Booking</TableHead>
                <TableHead class="text-xs font-semibold">Nama Pendaki</TableHead>
                <TableHead class="text-xs font-semibold">Destinasi Jalur</TableHead>
                <TableHead class="text-xs font-semibold">Jadwal Naik</TableHead>
                <TableHead class="text-xs font-semibold">Peserta</TableHead>
                <TableHead class="text-xs font-semibold">Total Tagihan</TableHead>
                <TableHead class="text-xs font-semibold">Status</TableHead>
                <TableHead class="text-xs font-semibold text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in recentTransactions" :key="item.id" class="hover:bg-muted/30 transition">
                <TableCell class="font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                  {{ item.id }}
                </TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span class="font-medium text-xs text-foreground">{{ item.climber }}</span>
                    <span class="text-[11px] text-muted-foreground">{{ item.email }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span class="font-medium text-xs">{{ item.mountain }}</span>
                    <span class="text-[11px] text-muted-foreground">{{ item.track }}</span>
                  </div>
                </TableCell>
                <TableCell class="text-xs text-muted-foreground">{{ item.date }}</TableCell>
                <TableCell class="text-xs font-medium">{{ item.members }} Orang</TableCell>
                <TableCell class="text-xs font-bold text-foreground">{{ item.total }}</TableCell>
                <TableCell>
                  <Badge variant="outline" :class="getStatusBadge(item.status).class" class="text-[10px] font-semibold">
                    {{ getStatusBadge(item.status).label }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="icon" class="h-7 w-7">
                        <MoreVertical class="h-3.5 w-3.5" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem>Lihat Detail Reservasi</DropdownMenuItem>
                      <DropdownMenuItem>Cetak E-Tiket</DropdownMenuItem>
                      <DropdownMenuItem class="text-destructive">Batalkan Booking</DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
