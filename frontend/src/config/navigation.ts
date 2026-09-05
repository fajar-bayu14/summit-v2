import { markRaw } from 'vue'
import type { NavGroup } from '@/types/navigation'
import {
  LayoutDashboard,
  Mountain,
  Compass,
  CalendarDays,
  ShoppingBag,
  Users,
  ShieldCheck,
  CreditCard,
  Building2,
  RefreshCw,
  Wallet,
  Megaphone,
  FileSpreadsheet,
  Settings,
} from 'lucide-vue-next'

export const navigationConfig: NavGroup[] = [
  {
    heading: 'Menu Utama',
    items: [
      {
        title: 'Dashboard Admin',
        to: '/admin',
        icon: markRaw(LayoutDashboard),
        roles: ['admin'],
      },
      {
        title: 'Dashboard Mitra',
        to: '/mitra',
        icon: markRaw(LayoutDashboard),
        roles: ['mitra'],
      },
    ],
  },
  {
    heading: 'Verifikasi & Regulasi',
    roles: ['admin'],
    items: [
      {
        title: 'Verifikasi KYC Pendaki',
        to: '/admin/verifications',
        icon: markRaw(ShieldCheck),
        roles: ['admin'],
      },
    ],
  },
  {
    heading: 'Master Destinasi & Kemitraan',
    roles: ['admin'],
    items: [
      {
        title: 'Master Gunung',
        to: '/admin/mountains',
        icon: markRaw(Mountain),
        roles: ['admin'],
      },
      {
        title: 'Jalur Pendakian',
        to: '/admin/trails',
        icon: markRaw(Compass),
        roles: ['admin'],
      },
      {
        title: 'Mitra Basecamp',
        to: '/admin/partners',
        icon: markRaw(Users),
        roles: ['admin'],
      },
      {
        title: 'Pemetaan Basecamp',
        to: '/admin/basecamps',
        icon: markRaw(Building2),
        roles: ['admin'],
      },
      {
        title: 'Monitoring Produk',
        to: '/admin/products',
        icon: markRaw(ShoppingBag),
        roles: ['admin'],
      },
    ],
  },
  {
    heading: 'Operasional Finansial & Iklan',
    roles: ['admin'],
    items: [
      {
        title: 'Penarikan Dana Mitra',
        to: '/admin/withdrawals',
        icon: markRaw(CreditCard),
        roles: ['admin'],
      },
      {
        title: 'Klaim Refund & Sengketa',
        to: '/admin/refunds',
        icon: markRaw(RefreshCw),
        roles: ['admin'],
      },
      {
        title: 'Ledger Escrow',
        to: '/admin/escrow',
        icon: markRaw(Wallet),
        roles: ['admin'],
      },
      {
        title: 'Banner Iklan Promosi',
        to: '/admin/ads',
        icon: markRaw(Megaphone),
        roles: ['admin'],
      },
    ],
  },
  {
    heading: 'Operasional Basecamp',
    roles: ['mitra'],
    items: [
      {
        title: 'Data Booking Tiket',
        to: '/mitra/orders',
        icon: markRaw(CalendarDays),
        roles: ['mitra'],
      },
      {
        title: 'Kelola Kuota Jalur',
        to: '/mitra/quotas',
        icon: markRaw(Compass),
        roles: ['mitra'],
      },
      {
        title: 'Rental & Peralatan',
        to: '/mitra/products',
        icon: markRaw(ShoppingBag),
        roles: ['mitra'],
      },
      {
        title: 'Validasi Logbook Summit',
        to: '/mitra/logbooks',
        icon: markRaw(ShieldCheck),
        roles: ['mitra'],
      },
      {
        title: 'Dompet & Penarikan Dana',
        to: '/mitra/wallet',
        icon: markRaw(Wallet),
        roles: ['mitra'],
      },
    ],
  },
  {
    heading: 'Laporan & Pengaturan',
    items: [
      {
        title: 'Laporan Rekap',
        to: '/admin/reports',
        icon: markRaw(FileSpreadsheet),
        roles: ['admin', 'mitra'],
      },
      {
        title: 'Pengaturan Akun',
        to: '/settings',
        icon: markRaw(Settings),
        roles: ['admin', 'mitra'],
      },
    ],
  },
]
