/**
 * composables/useAuth.js
 *
 * Thin reactive wrapper around useAuthStore().
 *
 * Improvements over original:
 *  ✅ Returns computed refs (NOT unwrapped .value) so templates stay reactive.
 *    The original called .value immediately, breaking reactivity — components
 *    would never see token/user updates after the initial render.
 */
import { useAuthStore }     from '~/stores/auth'
import { useCartStore }     from '~/stores/cart'
import { useWishlistStore } from '~/stores/wishlist'

export const useAuth = () => {
  const authStore     = useAuthStore()
  const cartStore     = useCartStore()
  const wishlistStore = useWishlistStore()

  return {
    // ── Reactive state (keep as computed refs — do NOT call .value here) ──
    user:          computed(() => authStore.user),
    accessToken:   computed(() => authStore.token),
    isLoggedIn:    computed(() => authStore.isLoggedIn),
    loading:       computed(() => authStore.loading),
    error:         computed(() => authStore.error),
    cartItems:     computed(() => cartStore.items),
    wishListItems: computed(() => wishlistStore.items),

    // ── Actions ───────────────────────────────────────────────────────────
    login:         (credentials) => authStore.login(credentials.phone, credentials.password),
    register:      (data)        => authStore.register(data),
    logout:        ()            => authStore.logout(),
    updateProfile: (updates)     => authStore.updateProfile(updates),
    fetchMe:       ()            => authStore.fetchMe(),
    sendLoginOtp:  (phone)       => authStore.sendLoginOtp(phone),
    loginWithOtp:  (phone, otp)  => authStore.loginWithOtp(phone, otp),
    socialLogin:   (p, t)        => authStore.socialLogin(p, t),
  }
}

export default useAuth
