/**
 * plugins/auth.client.js
 *
 * Runs on the client only (filename suffix `.client.js`).
 * Restores the session from localStorage and boots cart + wishlist data
 * exactly once per page load.
 *
 * Improvements over original:
 *  ✅ init() called first — ensures token/user are in
 *     the store before any conditional logic runs
 *  ✅ fetchMe() only called when token exists but user is still missing
 *     (i.e. localStorage had token but not user — very rare edge case)
 *  ✅ cart.fetch() + wishlist.fetch() run in parallel and only when logged in
 *  ✅ No redundant computed() wrappers around non-reactive values
 *  ✅ fetchMe() is guarded by the in-flight dedup in the store, so even if
 *     middleware fires at the same time there's only ONE /auth/me request
 */
import { useAuthStore }     from '~/stores/auth'
import { useCartStore }     from '~/stores/cart'
import { useWishlistStore } from '~/stores/wishlist'

export default defineNuxtPlugin(async () => {
  const auth     = useAuthStore()
  const cart     = useCartStore()
  const wishlist = useWishlistStore()

  // 1. Hydrate token + user from localStorage (idempotent — safe to call multiple times)
  await auth.init()

  // Nothing to do if there's no session
  if (!auth.token) return

  // 2. If token present but user object missing, fetch it once
  //    (The store reduplicates concurrent fetchMe() calls, so middleware won't double-fetch)
  if (!auth.user) {
    await auth.fetchMe()
  }

  // 3. Boot cart + wishlist in parallel — only when we have a valid session
  if (auth.isLoggedIn) {
    await Promise.all([cart.fetch(), wishlist.fetch()])
  }
})
