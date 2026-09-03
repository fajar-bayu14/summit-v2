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
        icon: LayoutDashboard,
        roles: ['admin'],
      },
      {
        title: 'Dashboard Mitra',
        to: '/mitra',
        icon: LayoutDashboard,
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
        icon: ShieldCheck,
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
        icon: Mountain,
        roles: ['admin'],
      },
      {
        title: 'Jalur Pendakian',
        to: '/admin/trails',
        icon: Compass,
        roles: ['admin'],
      },
      {
        title: 'Mitra Basecamp',
        to: '/admin/partners',
        icon: Users,
        roles: ['admin'],
      },
      {
        title: 'Pemetaan Basecamp',
        to: '/admin/basecamps',
        icon: Building2,
        roles: ['admin'],
      },
      {
        title: 'Monitoring Produk',
        to: '/admin/products',
        icon: ShoppingBag,
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
        icon: CreditCard,
        roles: ['admin'],
      },
      {
        title: 'Klaim Refund',
        to: '/admin/refunds',
        icon: RefreshCw,
        roles: ['admin'],
      },
      {
        title: 'Ledger Escrow',
        to: '/admin/escrow',
        icon: Wallet,
        roles: ['admin'],
      },
      {
        title: 'Banner Iklan Promosi',
        to: '/admin/ads',
        icon: Megaphone,
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
        icon: CalendarDays,
        roles: ['mitra'],
      },
      {
        title: 'Kelola Kuota Jalur',
        to: '/mitra/quotas',
        icon: Compass,
        roles: ['mitra'],
      },
      {
        title: 'Rental & Peralatan',
        to: '/mitra/products',
        icon: ShoppingBag,
        roles: ['mitra'],
      },
      {
        title: 'Validasi Logbook Summit',
        to: '/mitra/logbooks',
        icon: ShieldCheck,
        roles: ['mitra'],
      },
      {
        title: 'Dompet & Penarikan Dana',
        to: '/mitra/wallet',
        icon: Wallet,
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
        icon: FileSpreadsheet,
        roles: ['admin', 'mitra'],
      },
      {
        title: 'Pengaturan Akun',
        to: '/settings',
        icon: Settings,
        roles: ['admin', 'mitra'],
      },
    ],
  },
]

