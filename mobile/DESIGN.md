---
name: SUMMIT Mobile Design System
description:
  Modern Utility & Outdoor Adventure Design System for SUMMIT Mobile App
  (Quasar + Capacitor)
colors:
  primary: '#1E3A2B'
  secondary: '#E65100'
  background: '#F8F9FA'
  surface: '#FFFFFF'
  text-primary: '#111827'
  text-secondary: '#6B7280'
  border: '#E5E7EB'
  success: '#10B981'
  warning: '#F59E0B'
  error: '#EF4444'
  surface-dim: '#dadad7'
  surface-bright: '#faf9f6'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f4f1'
  surface-container: '#eeeeeb'
  surface-container-high: '#e9e8e5'
  surface-container-highest: '#e3e2e0'
  on-surface: '#1a1c1a'
  on-surface-variant: '#424843'
  inverse-surface: '#2f312f'
  inverse-on-surface: '#f1f1ee'
  outline: '#727973'
  outline-variant: '#c2c8c2'
  surface-tint: '#486554'
  on-primary: '#ffffff'
  primary-container: '#1e3a2b'
  on-primary-container: '#85a490'
  inverse-primary: '#aeceb9'
  on-secondary: '#ffffff'
  secondary-container: '#fc6018'
  on-secondary-container: '#531800'
  tertiary: '#341618'
  on-tertiary: '#ffffff'
  tertiary-container: '#4d2b2c'
  on-tertiary-container: '#c19192'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#caead4'
  primary-fixed-dim: '#aeceb9'
  on-primary-fixed: '#042013'
  on-primary-fixed-variant: '#304d3d'
  secondary-fixed: '#ffdbcf'
  secondary-fixed-dim: '#ffb59a'
  on-secondary-fixed: '#380d00'
  on-secondary-fixed-variant: '#802a00'
  tertiary-fixed: '#ffdad9'
  tertiary-fixed-dim: '#eebaba'
  on-tertiary-fixed: '#301314'
  on-tertiary-fixed-variant: '#623d3e'
  on-background: '#1a1c1a'
  surface-variant: '#e3e2e0'
typography:
  h1:
    fontFamily: Inter, sans-serif
    fontSize: 1.375rem
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: -0.01em
  h2:
    fontFamily: Inter, sans-serif
    fontSize: 1.125rem
    fontWeight: 600
    lineHeight: 1.3
  body-md:
    fontFamily: Inter, sans-serif
    fontSize: 0.875rem
    fontWeight: 400
    lineHeight: 1.5
  label-sm:
    fontFamily: Inter, sans-serif
    fontSize: 0.75rem
    fontWeight: 500
    lineHeight: '1.2'
  headline-lg:
    fontFamily: Inter
    fontSize: 22px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: '1.3'
rounded:
  sm: 4px
  md: 8px
  lg: 12px
  xl: 16px
  full: 9999px
  DEFAULT: 0.5rem
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  margin-mobile: 16px
  touch-target-min: 44px
components:
  button-primary:
    backgroundColor: '{colors.primary}'
    textColor: '{colors.surface}'
    rounded: '{rounded.md}'
    padding: 12px 20px
    minHeight: 44px
  card:
    backgroundColor: '{colors.surface}'
    rounded: '{rounded.lg}'
    padding: '{spacing.md}'
    border: 1px solid {colors.border}
  status-pill:
    rounded: '{rounded.full}'
    padding: 4px 10px
    fontSize: '{typography.label-sm.fontSize}'
---

## Overview

SUMMIT Design System menggabungkan fungsionalitas utility tinggi dengan kebersihan tata letak editorial modern. Dirancang khusus untuk pengalaman mobile pendaki gunung, sistem ini menekankan pada kejelasan informasi (kuota, status jalur, dan SOP), keterbacaan tinggi di luar ruangan, serta alur transaksi instan tanpa hambatan.

## Colors

- **Primary (`#1E3A2B`):** Deep Forest Green mewakili identitas utama brand SUMMIT. Digunakan secara eksklusif untuk Top Bar, Primary Action (CTA), dan indikator navigasi aktif.
- **Secondary (`#E65100`):** Trailblaze Orange digunakan untuk komponen perhatian tinggi seperti rating, indikator sisa kuota kritis (<10 slot), dan elemen aktif terpilih.
- **Background (`#F8F9FA`):** Muted Off-White untuk mengurangi _glare_ atau pantulan cahaya matahari saat aplikasi digunakan di luar ruangan.
- **Surface (`#FFFFFF`):** Pure White pada Card & Sheet untuk memisahkan hierarki informasi secara tegas.

## Typography

- **Font Family:** Inter / Standard Geometric Sans.
- **Numbers & Prices:** Selalu menggunakan angka dengan properti `font-variant-numeric: tabular-nums` agar lebar karakter angka konsisten, mempermudah kalkulasi harga dan kuota harian.

## Spacing & Layout

- Berbasis pada **8px Grid System**.
- Margin kiri dan kanan layar mobile terkunci pada **16px** (`sm` token).
- Area sentuh minimal (_Touch Target_) untuk seluruh komponen interaktif adalah **44x44px**.

## Shapes & Radius

- **Cards & Modals:** `12px` (`rounded.lg`) memberikan kesan modern namun tidak terlalu bulat.
- **Buttons & Inputs:** `8px` (`rounded.md`) memberikan kesan kokoh dan fungsional.
- **Badges & Statuses:** `9999px` (`rounded.full`) untuk visibilitas status cepat.

## Elevation & Depth

- Mengutamakan _Flat with Hairline Borders_ (`1px solid #E5E7EB`).
- **Floating Action Bar (Bottom):** Menggunakan top shadow `0 -4px 12px rgba(0,0,0,0.08)` untuk memastikan tombol _Checkout_ selalu berada di atas layer konten yang di-scroll.

## Rules to Never Break

- Jangan pernah menggunakan teks _Lorem Ipsum_; selalu gunakan nama gunung, jalur, atau data logistik riil.
- Tombol utama checkout/pemesanan wajib bersifat _sticky_ di bagian bawah layar mobile.
- Semua dokumen dan indikator status wajib mematuhi standar kontras minimal WCAG AA (rasio 4.5:1 untuk teks biasa, 3:1 untuk teks besar).
