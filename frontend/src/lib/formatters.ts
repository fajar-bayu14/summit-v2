/**
 * Formatter utility helpers for Summit v2 Frontend (E-Commerce & Climber Portal)
 */

/**
 * Format a number or numeric string to Indonesian Rupiah currency format (e.g. Rp 150.000)
 */
export function formatRupiah(amount: number | string | null | undefined): string {
  if (amount === null || amount === undefined || amount === '') return 'Rp 0'
  let numeric: number
  if (typeof amount === 'string') {
    // If string has dots as thousand separators (e.g. "100.000"), remove them
    const cleanStr = amount.replace(/[^0-9,-]+/g, '').replace(',', '.')
    numeric = parseFloat(cleanStr)
  } else {
    numeric = amount
  }
  if (isNaN(numeric)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(numeric)
}

/**
 * Format altitude/elevation number to standard MDPL notation (e.g. 3.142 MDPL)
 */
export function formatMDPL(elevation: number | string | null | undefined): string {
  if (elevation === null || elevation === undefined || elevation === '') return '0 MDPL'
  const numeric = typeof elevation === 'string' ? parseInt(elevation.replace(/[^0-9]/g, ''), 10) : elevation
  if (isNaN(numeric)) return '0 MDPL'
  return `${new Intl.NumberFormat('id-ID').format(numeric)} MDPL`
}

export const formatMdpl = formatMDPL

/**
 * Format ISO date string or Date object to Indonesian local date string (e.g. 15 Agustus 2026)
 */
export function formatDateIndonesia(
  date: string | Date | null | undefined,
  options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }
): string {
  if (!date) return '-'
  try {
    const d = typeof date === 'string' ? new Date(date) : date
    if (isNaN(d.getTime())) return '-'
    return new Intl.DateTimeFormat('id-ID', options).format(d)
  } catch {
    return '-'
  }
}

export const formatDate = formatDateIndonesia

/**
 * Format climbing duration / time estimate (e.g. "7 Jam" or "7.5 Jam")
 */
export function formatDuration(hours: number | string | null | undefined): string {
  if (hours === null || hours === undefined || hours === '') return '-'
  if (typeof hours === 'string' && hours.toLowerCase().includes('jam')) {
    return hours
  }
  const numeric = typeof hours === 'string' ? parseFloat(hours) : hours
  if (isNaN(numeric)) return String(hours)
  return `${numeric} Jam`
}

/**
 * Calculate total days of trip between start and end date (minimum 1 day)
 */
export function calculateTripDuration(
  startDate: string | Date | null | undefined,
  endDate: string | Date | null | undefined
): number {
  if (!startDate) return 1
  try {
    const start = typeof startDate === 'string' ? new Date(startDate) : startDate
    const end = endDate ? (typeof endDate === 'string' ? new Date(endDate) : endDate) : start
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 1

    const diffTime = end.getTime() - start.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
    return Math.max(1, diffDays)
  } catch {
    return 1
  }
}

/**
 * Format order operational and payment status with label and styling classes
 */
export function formatOrderStatus(status: string | null | undefined): {
  label: string
  variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning' | 'info'
  bgClass: string
  textClass: string
} {
  const normalized = (status || '').toLowerCase()
  switch (normalized) {
    case 'pending':
      return {
        label: 'Menunggu Pembayaran',
        variant: 'warning',
        bgClass: 'bg-amber-100 dark:bg-amber-950/50',
        textClass: 'text-amber-800 dark:text-amber-300',
      }
    case 'paid':
      return {
        label: 'Siap Check-In',
        variant: 'info',
        bgClass: 'bg-blue-100 dark:bg-blue-950/50',
        textClass: 'text-blue-800 dark:text-blue-300',
      }
    case 'on_going':
      return {
        label: 'Sedang Berjalan',
        variant: 'default',
        bgClass: 'bg-emerald-100 dark:bg-emerald-950/50',
        textClass: 'text-emerald-800 dark:text-emerald-300',
      }
    case 'completed':
      return {
        label: 'Selesai Mendaki',
        variant: 'success',
        bgClass: 'bg-teal-100 dark:bg-teal-950/50',
        textClass: 'text-teal-800 dark:text-teal-300',
      }
    case 'cancelled':
      return {
        label: 'Dibatalkan',
        variant: 'destructive',
        bgClass: 'bg-rose-100 dark:bg-rose-950/50',
        textClass: 'text-rose-800 dark:text-rose-300',
      }
    case 'expired':
      return {
        label: 'Kadaluarsa',
        variant: 'destructive',
        bgClass: 'bg-slate-100 dark:bg-slate-800',
        textClass: 'text-slate-600 dark:text-slate-400',
      }
    case 'refunded':
      return {
        label: 'Dana Dikembalikan',
        variant: 'secondary',
        bgClass: 'bg-purple-100 dark:bg-purple-950/50',
        textClass: 'text-purple-800 dark:text-purple-300',
      }
    default:
      return {
        label: status || 'Unknown',
        variant: 'outline',
        bgClass: 'bg-slate-100 dark:bg-slate-800',
        textClass: 'text-slate-700 dark:text-slate-300',
      }
  }
}

/**
 * Format Climber KYC Verification status
 */
export function formatKycStatus(status: string | null | undefined): {
  label: string
  badgeVariant: 'warning' | 'info' | 'success' | 'destructive'
  badgeClass: string
  description: string
} {
  const raw = (status || 'unverified').toLowerCase()
  const normalized = raw === 'disetujui' ? 'verified' : raw === 'ditolak' ? 'rejected' : raw
  switch (normalized) {
    case 'verified':
      return {
        label: 'Terverifikasi',
        badgeVariant: 'success',
        badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
        description: 'Identitas Anda telah diverifikasi resmi oleh Admin.',
      }
    case 'pending':
      return {
        label: 'Menunggu Verifikasi',
        badgeVariant: 'info',
        badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        description: 'Dokumen identitas Anda sedang dalam proses peninjauan Admin (< 24 jam).',
      }
    case 'rejected':
      return {
        label: 'Verifikasi Ditolak',
        badgeVariant: 'destructive',
        badgeClass: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300',
        description: 'Dokumen ditolak. Silakan periksa alasan penolakan dan ajukan ulang.',
      }
    case 'unverified':
    default:
      return {
        label: 'Belum Verifikasi',
        badgeVariant: 'warning',
        badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
        description: 'Lengkapi identitas resmi KTP/Paspor untuk kemudahan booking tiket SIMAKSI.',
      }
  }
}
