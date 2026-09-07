import { describe, it, expect } from 'vitest'
import {
  formatRupiah,
  formatMDPL,
  formatDateIndonesia,
  formatDuration,
  calculateTripDuration,
  formatOrderStatus,
  formatKycStatus,
} from '@/lib/formatters'

describe('Formatters Utility Helpers (Task 0.1)', () => {
  describe('formatRupiah', () => {
    it('should format numbers correctly to IDR', () => {
      expect(formatRupiah(150000)).toMatch(/Rp\s?150\.000/)
      expect(formatRupiah(0)).toMatch(/Rp\s?0/)
      expect(formatRupiah(2550000.5)).toMatch(/Rp\s?2\.550\.001/)
    })

    it('should handle numeric string inputs', () => {
      expect(formatRupiah('50000')).toMatch(/Rp\s?50\.000/)
      expect(formatRupiah('Rp 100.000')).toMatch(/Rp\s?100\.000/)
    })

    it('should handle null, undefined, or invalid values', () => {
      expect(formatRupiah(null)).toBe('Rp 0')
      expect(formatRupiah(undefined)).toBe('Rp 0')
      expect(formatRupiah('')).toBe('Rp 0')
      expect(formatRupiah('invalid')).toBe('Rp 0')
    })
  })

  describe('formatMDPL', () => {
    it('should format numbers to MDPL notation', () => {
      expect(formatMDPL(3142)).toBe('3.142 MDPL')
      expect(formatMDPL(2565)).toBe('2.565 MDPL')
      expect(formatMDPL(0)).toBe('0 MDPL')
    })

    it('should handle string inputs and fallback safely', () => {
      expect(formatMDPL('3726')).toBe('3.726 MDPL')
      expect(formatMDPL('3805 MDPL')).toBe('3.805 MDPL')
      expect(formatMDPL(null)).toBe('0 MDPL')
    })
  })

  describe('formatDateIndonesia', () => {
    it('should format valid date strings to Indonesian format', () => {
      const result = formatDateIndonesia('2026-08-15')
      expect(result).toContain('15')
      expect(result).toContain('Agustus')
      expect(result).toContain('2026')
    })

    it('should handle Date instances', () => {
      const date = new Date(2026, 6, 10) // 10 Juli 2026
      const result = formatDateIndonesia(date)
      expect(result).toContain('10')
      expect(result).toContain('Juli')
      expect(result).toContain('2026')
    })

    it('should return fallback for invalid or null dates', () => {
      expect(formatDateIndonesia(null)).toBe('-')
      expect(formatDateIndonesia(undefined)).toBe('-')
      expect(formatDateIndonesia('invalid-date')).toBe('-')
    })
  })

  describe('formatDuration', () => {
    it('should format duration in hours', () => {
      expect(formatDuration(7)).toBe('7 Jam')
      expect(formatDuration('8.5')).toBe('8.5 Jam')
      expect(formatDuration('6 Jam')).toBe('6 Jam')
    })

    it('should handle empty or null values', () => {
      expect(formatDuration(null)).toBe('-')
      expect(formatDuration('')).toBe('-')
    })
  })

  describe('calculateTripDuration', () => {
    it('should calculate days difference correctly including start day', () => {
      expect(calculateTripDuration('2026-08-15', '2026-08-17')).toBe(3)
      expect(calculateTripDuration('2026-08-15', '2026-08-15')).toBe(1)
    })

    it('should default to minimum 1 day for same date or missing end date', () => {
      expect(calculateTripDuration('2026-08-15', null)).toBe(1)
      expect(calculateTripDuration(null, null)).toBe(1)
    })
  })

  describe('formatOrderStatus', () => {
    it('should return appropriate labels and variants for order statuses', () => {
      expect(formatOrderStatus('pending').label).toBe('Menunggu Pembayaran')
      expect(formatOrderStatus('pending').variant).toBe('warning')

      expect(formatOrderStatus('paid').label).toBe('Siap Check-In')
      expect(formatOrderStatus('on_going').label).toBe('Sedang Berjalan')
      expect(formatOrderStatus('completed').label).toBe('Selesai Mendaki')
      expect(formatOrderStatus('cancelled').label).toBe('Dibatalkan')
      expect(formatOrderStatus('refunded').label).toBe('Dana Dikembalikan')
    })
  })

  describe('formatKycStatus', () => {
    it('should return correct badge labels and descriptions for KYC statuses', () => {
      expect(formatKycStatus('verified').label).toBe('Terverifikasi')
      expect(formatKycStatus('pending').label).toBe('Menunggu Verifikasi')
      expect(formatKycStatus('rejected').label).toBe('Verifikasi Ditolak')
      expect(formatKycStatus('unverified').label).toBe('Belum Verifikasi')
      expect(formatKycStatus(null).label).toBe('Belum Verifikasi')
    })
  })
})
