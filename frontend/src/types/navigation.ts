import type { Component } from 'vue'
import type { UserRole } from '@/types/auth'

export interface NavItem {
  title: string
  to: string | { name: string; params?: Record<string, any> }
  icon?: Component
  roles?: UserRole[] // Jika undefined, menu dapat diakses oleh semua role terotentikasi
  badge?: string | number
  badgeVariant?: 'default' | 'secondary' | 'outline' | 'destructive'
  external?: boolean
  children?: NavItem[]
}

export interface NavGroup {
  heading?: string
  roles?: UserRole[]
  items: NavItem[]
}
