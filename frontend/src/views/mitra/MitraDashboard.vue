<script setup lang="ts">
import {
  Compass,
  ShoppingBag,
  Users,
  CheckCircle2,
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

const metricStats = [
  {
    title: 'Sisa Kuota Hari Ini',
    value: '65 / 150',
    change: '43% terisi',
    icon: Compass,
    description: 'Jalur Sembalun - Pos 1',
  },
  {
    title: 'Pendaki Terjadwal Hari Ini',
    value: '85 Orang',
    change: '18 rombongan',
    icon: Users,
    description: 'Estimasi check-in 08:00 - 15:00',
  },
  {
    title: 'Rental Gear Disewa',
    value: '34 Unit',
    change: 'Tenda & Matras',
    icon: ShoppingBag,
    description: '8 unit siap diambil hari ini',
  },
  {
    title: 'Check-in Selesai',
    value: '52 Orang',
    change: '61% dari total',
    icon: CheckCircle2,
    description: 'Sudah briefing keselamatan',
  },
]

const recentBookings = [
  {
    id: 'BKG-MT-901',
    leader: 'Hendra Wijaya',
    phone: '081234567890',
    track: 'Jalur Sembalun',
    checkinDate: 'Hari ini, 09:30',
    members: 4,
    rental: 'Tenda Dome (1x)',
    status: 'READY_CHECKIN',
  },
  {
    id: 'BKG-MT-902',
    leader: 'Rina Kusuma',
    phone: '085712345678',
    track: 'Jalur Sembalun',
    checkinDate: 'Hari ini, 10:15',
    members: 2,
    rental: 'None',
    status: 'CHECKED_IN',
  },
  {
    id: 'BKG-MT-903',
    leader: 'Agus Pratama',
    phone: '087812345678',
    track: 'Jalur Sembalun',
    checkinDate: 'Besok, 07:00',
    members: 6,
    rental: 'Sleeping Bag (4x)',
    status: 'PAID',
  },
]
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Dashboard Mitra Basecamp</h1>
        <p class="text-sm text-muted-foreground mt-0.5">
          Kelola kuota jalur harian, validasi tiket QR pendaki, dan inventaris peralatan rental.
        </p>
      </div>
      <div class="flex items-center gap-2.5">
        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-9">
          <Calendar class="h-3.5 w-3.5" /> Hari Ini (03 Sep 2026)
        </Button>
        <Button size="sm" class="gap-1.5 text-xs h-9 bg-emerald-700 hover:bg-emerald-800 text-white">
          <Compass class="h-3.5 w-3.5" /> Scan QR Tiket
        </Button>
      </div>
    </div>

    <!-- Section 1: Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card v-for="(stat, idx) in metricStats" :key="idx" class="border shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
          <CardTitle class="text-xs font-medium text-muted-foreground">{{ stat.title }}</CardTitle>
          <div class="h-8 w-8 rounded-md bg-muted/60 flex items-center justify-center text-muted-foreground">
            <component :is="stat.icon" class="h-4 w-4" />
          </div>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-extrabold tracking-tight text-foreground">{{ stat.value }}</div>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-1">{{ stat.change }}</p>
          <p class="text-[11px] text-muted-foreground mt-0.5">{{ stat.description }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Section 2: Bookings -->
    <Card class="border shadow-xs">
      <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 gap-3">
        <div>
          <CardTitle class="text-base font-semibold">Antrean Check-in Pendaki</CardTitle>
          <CardDescription class="text-xs">Daftar rombongan pendaki yang dijadwalkan masuk pos hari ini</CardDescription>
        </div>
        <Button variant="outline" size="sm" class="text-xs h-8 gap-1.5">
          <Filter class="h-3.5 w-3.5" /> Filter Jalur
        </Button>
      </CardHeader>
      <CardContent class="p-0">
        <div class="overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="text-xs font-semibold">Kode Reservasi</TableHead>
                <TableHead class="text-xs font-semibold">Ketua Rombongan</TableHead>
                <TableHead class="text-xs font-semibold">Jadwal Tiba</TableHead>
                <TableHead class="text-xs font-semibold">Jumlah Peserta</TableHead>
                <TableHead class="text-xs font-semibold">Item Rental</TableHead>
                <TableHead class="text-xs font-semibold">Status</TableHead>
                <TableHead class="text-xs font-semibold text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in recentBookings" :key="item.id" class="hover:bg-muted/30 transition">
                <TableCell class="font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                  {{ item.id }}
                </TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span class="font-medium text-xs text-foreground">{{ item.leader }}</span>
                    <span class="text-[11px] text-muted-foreground">{{ item.phone }}</span>
                  </div>
                </TableCell>
                <TableCell class="text-xs font-medium">{{ item.checkinDate }}</TableCell>
                <TableCell class="text-xs font-medium">{{ item.members }} Orang</TableCell>
                <TableCell class="text-xs text-muted-foreground">{{ item.rental }}</TableCell>
                <TableCell>
                  <Badge
                    variant="outline"
                    class="text-[10px] font-semibold"
                    :class="[
                      item.status === 'CHECKED_IN' ? 'bg-blue-500/10 text-blue-600 border-blue-500/20' :
                      item.status === 'READY_CHECKIN' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' :
                      'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                    ]"
                  >
                    {{ item.status === 'CHECKED_IN' ? 'Sudah Naik' : item.status === 'READY_CHECKIN' ? 'Siap Check-in' : 'Lunas' }}
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
                      <DropdownMenuItem>Proses Check-in</DropdownMenuItem>
                      <DropdownMenuItem>Serah Terima Rental</DropdownMenuItem>
                      <DropdownMenuItem>Lihat Detail Anggota</DropdownMenuItem>
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
