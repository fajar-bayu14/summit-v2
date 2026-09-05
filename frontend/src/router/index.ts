import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { setupNavigationGuards } from './guards'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { title: 'Summit - Platform Pendakian Gunung Indonesia' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { title: 'Masuk - Summit', guestOnly: true },
  },
  // Pendaki (Web Version)
  {
    path: '/pendaki',
    component: () => import('@/layouts/PendakiLayout.vue'),
    meta: { requiresAuth: true, roles: ['pendaki', 'admin'] },
    children: [
      {
        path: '',
        name: 'pendaki.home',
        component: () => import('@/views/pendaki/PendakiHome.vue'),
        meta: { title: 'Pendaki - Summit' },
      },
    ],
  },
  // Mitra Basecamp Portal (Shared Reusable Layout)
  {
    path: '/mitra',
    component: () => import('@/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true, roles: ['mitra', 'admin'] },
    children: [
      {
        path: '',
        name: 'mitra.dashboard',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Dashboard Mitra - Summit', roles: ['mitra', 'admin'] },
      },
      {
        path: 'orders',
        name: 'mitra.orders',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Data Booking Tiket - Summit', roles: ['mitra', 'admin'] },
      },
      {
        path: 'quotas',
        name: 'mitra.quotas',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Kelola Kuota Jalur - Summit', roles: ['mitra', 'admin'] },
      },
      {
        path: 'products',
        name: 'mitra.products',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Rental & Peralatan - Summit', roles: ['mitra', 'admin'] },
      },
      {
        path: 'logbooks',
        name: 'mitra.logbooks',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Validasi Logbook Summit - Summit', roles: ['mitra', 'admin'] },
      },
      {
        path: 'wallet',
        name: 'mitra.wallet',
        component: () => import('@/views/mitra/MitraDashboard.vue'),
        meta: { title: 'Dompet & Penarikan Dana - Summit', roles: ['mitra', 'admin'] },
      },
    ],
  },
  // Admin Central Portal (Shared Reusable Layout)
  {
    path: '/admin',
    component: () => import('@/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
    children: [
      {
        path: '',
        name: 'admin.dashboard',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
        meta: { title: 'Dashboard Admin - Summit', roles: ['admin'] },
      },
      {
        path: 'verifications',
        name: 'admin.verifications',
        component: () => import('@/views/admin/KycVerificationView.vue'),
        meta: { title: 'Verifikasi KYC Pendaki - Summit', roles: ['admin'] },
      },
      {
        path: 'mountains',
        name: 'admin.mountains',
        component: () => import('@/views/admin/MountainListView.vue'),
        meta: { title: 'Master Gunung - Summit', roles: ['admin'] },
      },
      {
        path: 'trails',
        name: 'admin.trails',
        component: () => import('@/views/admin/TrailListView.vue'),
        meta: { title: 'Jalur Pendakian - Summit', roles: ['admin'] },
      },
      {
        path: 'partners',
        name: 'admin.partners',
        component: () => import('@/views/admin/PartnerListView.vue'),
        meta: { title: 'Mitra Basecamp - Summit', roles: ['admin'] },
      },
      {
        path: 'basecamps',
        name: 'admin.basecamps',
        component: () => import('@/views/admin/BasecampListView.vue'),
        meta: { title: 'Pemetaan Basecamp - Summit', roles: ['admin'] },
      },
      {
        path: 'products',
        name: 'admin.products',
        component: () => import('@/views/admin/ProductListView.vue'),
        meta: { title: 'Monitoring Produk & Kuota - Summit', roles: ['admin'] },
      },
      {
        path: 'withdrawals',
        name: 'admin.withdrawals',
        component: () => import('@/views/admin/finance/WithdrawalListView.vue'),
        meta: { title: 'Penarikan Dana Mitra - Summit', roles: ['admin'] },
      },
      {
        path: 'refunds',
        name: 'admin.refunds',
        component: () => import('@/views/admin/finance/RefundManagementView.vue'),
        meta: { title: 'Klaim Refund & Sengketa - Summit', roles: ['admin'] },
      },
      {
        path: 'escrow',
        name: 'admin.escrow',
        component: () => import('@/views/admin/finance/EscrowLedgerView.vue'),
        meta: { title: 'Ledger Escrow - Summit', roles: ['admin'] },
      },
      {
        path: 'ads',
        name: 'admin.ads',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
        meta: { title: 'Banner Iklan Promosi - Summit', roles: ['admin'] },
      },
      {
        path: 'reports',
        name: 'admin.reports',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
        meta: { title: 'Laporan Rekap - Summit', roles: ['admin', 'mitra'] },
      },
    ],
  },
  // Catch all 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

setupNavigationGuards(router)

export default router
