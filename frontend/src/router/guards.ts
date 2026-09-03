import type { Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { UserRole } from '@/types/auth'

export function getDashboardRouteByRole(role: UserRole | null | undefined): string {
  switch (role) {
    case 'admin':
      return '/admin'
    case 'mitra':
      return '/mitra'
    case 'pendaki':
      return '/pendaki'
    default:
      return '/'
  }
}

export function setupNavigationGuards(router: Router) {
  router.beforeEach(async (to) => {
    // Set document title
    if (to.meta.title) {
      document.title = String(to.meta.title)
    }

    const authStore = useAuthStore()

    // If authenticated, optionally hydrate profile if needed
    if (authStore.token && !authStore.user) {
      await authStore.fetchProfile()
    }

    const isAuthenticated = authStore.isAuthenticated
    const userRole = authStore.userRole

    // If route is guest-only (like /login) and user is already authenticated
    if (to.meta.guestOnly && isAuthenticated) {
      return getDashboardRouteByRole(userRole)
    }

    // If route requires authentication and user is not logged in
    if (to.meta.requiresAuth && !isAuthenticated) {
      return {
        path: '/login',
        query: { redirect: to.fullPath },
      }
    }

    // Role-based authorization check
    if (to.meta.roles && Array.isArray(to.meta.roles)) {
      const allowedRoles = to.meta.roles as string[]
      if (!userRole || !allowedRoles.includes(userRole)) {
        return getDashboardRouteByRole(userRole)
      }
    }

    return true
  })
}
