/**
 * middleware/auth.js
 *
 * Protects routes that require a logged-in user.
 * Usage: definePageMeta({ middleware: 'auth' })
 *
 */
import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()

  await auth.init()

  if (auth.isLoggedIn) {
    return
  }

  if (to.path.startsWith('/auth/')) {
    return
  }

  return navigateTo(`/auth/login?redirect=${encodeURIComponent(to.fullPath)}`)
})