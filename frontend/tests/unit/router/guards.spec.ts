import { describe, it, expect, beforeEach, vi } from 'vitest'
import { getDashboardRouteByRole } from '@/router/guards'

describe('Router Navigation Guards (Task 0.2)', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.restoreAllMocks()
  })

  describe('getDashboardRouteByRole', () => {
    it('should return /admin for admin role', () => {
      expect(getDashboardRouteByRole('admin')).toBe('/admin')
    })

    it('should return /mitra for mitra role', () => {
      expect(getDashboardRouteByRole('mitra')).toBe('/mitra')
    })

    it('should return /pendaki for pendaki role', () => {
      expect(getDashboardRouteByRole('pendaki')).toBe('/pendaki')
    })

    it('should return / for null or undefined role', () => {
      expect(getDashboardRouteByRole(null)).toBe('/')
      expect(getDashboardRouteByRole(undefined)).toBe('/')
    })
  })
})
