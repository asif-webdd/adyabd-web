/**
 * stores/cart.js
 *
 * Improvements over original:
 *  ✅ _fetchPromise — concurrent fetch() calls are coalesced (no double network hit)
 *  ✅ Optimistic UI for remove() — item removed from local state immediately, rolled back on error
 *  ✅ Guard: fetch() bails early if not logged in (no wasted request)
 *  ✅ add() no longer navigates on 401 — callers check auth and show LoginPopup instead
 */
import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
    state: () => ({
        items:    [],
        subtotal: 0,
        count:    0,
        coupon:   null,
        discount: 0,
        loading:  false,
        error:    null,
        /** In-flight fetch promise — deduplicates concurrent calls */
        _fetchPromise: null,
    }),

    getters: {
        total:   (s) => Math.max(0, s.subtotal - s.discount),
        isEmpty: (s) => s.items.length === 0,
    },

    actions: {
        _applyCartData(data) {
            this.items    = data.items    ?? []
            this.subtotal = data.subtotal ?? 0
            this.count    = data.count    ?? 0
        },

        fetch() {
            const auth = useAuthStore()
            if (!auth.isLoggedIn) return Promise.resolve()

            if (this._fetchPromise) return this._fetchPromise

            this.loading = true
            this._fetchPromise = useApi().get('/cart')
                .then((data) => { this._applyCartData(data) })
                .catch((e)   => { this.error = e.message })
                .finally(()  => { this.loading = false; this._fetchPromise = null })

            return this._fetchPromise
        },

        /**
         * Add a product to cart. Caller must check auth before calling.
         * Returns true on success, false on failure.
         */
        async add(productId, quantity = 1, variantId = null) {
            try {
                await useApi().post('/cart', { product_id: productId, quantity, variant_id: variantId })
                await this.fetch()
                return true
            } catch (e) {
                this.error = e.message
                if (import.meta.client && window.$toast) {
                    window.$toast({ type: 'error', title: e.message })
                }
                return false
            }
        },

        async update(itemId, quantity) {
            try {
                await useApi().put(`/cart/${itemId}`, { quantity })
                await this.fetch()
            } catch (e) {
                this.error = e.message
            }
        },

        async remove(itemId) {
            // Optimistic: remove locally first for instant UI feedback
            const backup = [...this.items]
            this.items = this.items.filter((i) => i.id !== itemId)
            this.count = Math.max(0, this.count - 1)

            try {
                await useApi().delete(`/cart/${itemId}`)
                await this.fetch()   // sync real totals from server
            } catch (e) {
                this.items = backup  // rollback on failure
                this.count = backup.length
                this.error = e.message
            }
        },

        async clear() {
            try {
                await useApi().delete('/cart')
                this.items    = []
                this.subtotal = 0
                this.count    = 0
                this.coupon   = null
                this.discount = 0
            } catch (e) {
                this.error = e.message
            }
        },

        async applyCoupon(code) {
            try {
                const data = await useApi().post('/cart/apply-coupon', { code })
                this.coupon   = data.coupon
                this.discount = data.discount
                return { success: true, data }
            } catch (e) {
                return { success: false, message: e.message }
            }
        },

    },
})