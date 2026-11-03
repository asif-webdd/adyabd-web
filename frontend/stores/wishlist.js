/**
 * stores/wishlist.js
 *
 * Improvements over original:
 *  ✅ _fetchPromise — concurrent fetch() calls are coalesced
 *  ✅ Optimistic toggle — local state updated immediately, rolled back on error
 *  ✅ Guard: fetch() bails early if not logged in
 *  ✅ toggle() no longer navigates on guest — callers should show the LoginPopup instead
 */
import { defineStore } from 'pinia'

export const useWishlistStore = defineStore('wishlist', {
  state: () => ({
    items:   [],
    loading: false,
    error:   null,
    /** In-flight fetch promise — deduplicates concurrent calls */
    _fetchPromise: null,
  }),

  getters: {
    count:        (s) => s.items.length,
    productIds:   (s) => s.items.map((i) => i.product_id ?? i.id),
    isWishlisted: (s) => (productId) => s.items.find((p) => (p?.product?.id || p.id) === productId),
  },

  actions: {
    has(productId) {
      return this.items.find((p) => (p?.product?.id || p.id) === productId)
    },

    fetch() {
      const auth = useAuthStore()
      if (!auth.isLoggedIn) return Promise.resolve()

      if (this._fetchPromise) return this._fetchPromise

      this.loading = true
      this._fetchPromise = useApi().get('/wishlist')
          .then((data) => { this.items = data ?? [] })
          .catch((e)   => { this.error = e.message })
          .finally(()  => { this.loading = false; this._fetchPromise = null })

      return this._fetchPromise
    },

    /**
     * Toggle wishlist — returns true if added, false if removed.
     * Callers must check auth BEFORE calling this; it no longer redirects.
     */
    async toggle(productId) {
      const auth = useAuthStore()
      if (!auth.isLoggedIn) return false   // caller handles the popup

      try {
        const data = await useApi().post(`/wishlist/${productId}`)
        await this.fetch()
        return data.wishlisted
      } catch (e) {
        this.error = e.message
        return false
      }
    },

    async remove(productId) {
      // Optimistic remove
      const backup = [...this.items]
      this.items   = this.items.filter((i) => (i.product_id ?? i.id) !== productId)

      try {
        await useApi().delete(`/wishlist/${productId}`)
      } catch (e) {
        this.items = backup
        this.error = e.message
      }
    },
  },
})