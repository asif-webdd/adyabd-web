/**
 * useDataLayer.js
 *
 * Central GA4 / GTM dataLayer engine for the storefront.
 *
 * Everything is driven by the "Tracking & Analytics" tab in the Filament
 * Appearance page (see useAppearance().tracking):
 *  - tracking.datalayer_enabled   → master on/off switch
 *  - tracking.events.<event_name> → per-event on/off switch
 *  - tracking.currency             → currency code used in all `value` fields
 *  - tracking.gtm_id / ga4_id / fb_pixel_id / tiktok_pixel_id / gads_id
 *    → tracking scripts are auto-injected by useAppearance()._applyTracking
 *
 * Usage:
 *   const dl = useDataLayer()
 *   dl.viewItem(product)
 *   dl.addToCart(product, qty, variant)
 *   dl.purchase(order)
 *
 * All push helpers are safe to call on the server (they no-op) and safe to
 * call even when tracking is fully disabled — they simply become no-ops so
 * calling code never needs to branch on settings.
 */
export const useDataLayer = () => {
    const { tracking } = useAppearance()

    // ── Core guards ──────────────────────────────────────────────────────
    const isEnabled = (eventName) => {
        if (!import.meta.client) return false
        if (!tracking.value?.datalayer_enabled) return false
        const events = tracking.value?.events ?? {}
        // Unknown / unmapped events default to "on" if the master switch is on
        return events[eventName] !== false
    }

    const currency = computed(() => tracking.value?.currency || 'BDT')



    // প্রতিটা ইভেন্টের জন্য ইউনিক ID (ব্রাউজার Pixel আর server ইভেন্ট মেলাতে)
    const makeEventId = (eventName, payload) => {
        const txn = payload?.ecommerce?.transaction_id
        if (eventName === 'purchase' && txn) return `purchase_${txn}`
        const rand =
            globalThis.crypto?.randomUUID?.() ??
            `${Date.now()}${Math.random().toString(16).slice(2)}`
        return `${eventName}_${rand}`
    }



    /**
     * Push a raw event to window.dataLayer.
     * Automatically clears the previous `ecommerce` object first (GA4 best
     * practice — prevents data from a previous event "bleeding" into this one).
     */
    const push = (eventName, payload = {}) => {
        if (!isEnabled(eventName)) return

        window.dataLayer = window.dataLayer || []

        if (payload.ecommerce !== undefined) {
            window.dataLayer.push({ ecommerce: null })
        }

        //window.dataLayer.push({ event: eventName, ...payload })
        window.dataLayer.push({
            event: eventName,
            event_id: makeEventId(eventName, payload),
            ...payload,
        })
    }

    // ── Helpers: map a product (raw or normalized) into a GA4 "item" ───────
    const num = (v, fallback = 0) => {
        const n = Number(v)
        return Number.isFinite(n) ? n : fallback
    }

    /**
     * Accepts either a raw API product object or a normalizeProduct() result
     * and returns a GA4-shaped item object.
     *
     * extra can override/add fields, e.g. { quantity, item_variant, index }
     */
    const toItem = (product = {}, extra = {}) => {
        if (!product) return { ...extra }

        const id = product.id ?? product.product_id
        const name = product.name ?? product.title
        const price = num(product.price ?? product.activePrice ?? product.special_price)
        const categoryName =
            product.category?.name
            ?? product.category_name
            ?? product.categoryName
            ?? null
        const brandName =
            product.brand?.name
            ?? product.brand_name
            ?? product.brandName
            ?? null
        const sellerName =
            product.seller?.shop_name
            ?? product.seller
            ?? product.sellerName
            ?? null

        const item = {
            item_id: id !== undefined ? String(id) : undefined,
            item_name: name,
            price,
            quantity: extra.quantity ?? 1,
        }

        if (categoryName) item.item_category = categoryName
        if (brandName) item.item_brand = brandName
        if (sellerName) item.affiliation = sellerName
        if (product.sku) item.item_sku = product.sku

        return { ...item, ...extra }
    }

    const itemsValue = (items = []) =>
        items.reduce((sum, it) => sum + num(it.price) * num(it.quantity ?? 1), 0)

    // ── Cart item → GA4 item (cart store shape: { product, product_id, quantity, variant... }) ─
    const cartItemToItem = (cartItem = {}) => {
        const product = cartItem.product ?? cartItem
        return toItem(product, {
            item_id: String(cartItem.product_id ?? product.id ?? ''),
            quantity: num(cartItem.quantity ?? 1, 1),
            item_variant: cartItem.variant?.name
                ?? cartItem.variant_name
                ?? undefined,
            price: num(cartItem.price ?? product.price ?? 0),
        })
    }

    // ── Standard GA4 e-commerce events ──────────────────────────────────────

    /** Fires on every page navigation. */
    const pageView = (path, title) => {
        push('page_view', {
            page_path: path,
            page_title: title,
            page_location: import.meta.client ? window.location.href : undefined,
        })
    }

    /** Product detail page viewed. */
    const viewItem = (product) => {
        const items = [toItem(product)]
        push('view_item', {
            ecommerce: {
                currency: currency.value,
                value: itemsValue(items),
                items,
            },
        })
    }

    /** A list of products was shown (home, category, search results, etc). */
    const viewItemList = (products = [], listName = '') => {
        const items = products.map((p, i) =>
            toItem(p, { index: i + 1, item_list_name: listName })
        )
        push('view_item_list', {
            ecommerce: {
                item_list_name: listName,
                currency: currency.value,
                items,
            },
        })
    }

    /** User clicked a product from a list. */
    const selectItem = (product, listName = '', index = null) => {
        const items = [toItem(product, {
            item_list_name: listName,
            ...(index !== null ? { index: index + 1 } : {}),
        })]
        push('select_item', {
            ecommerce: {
                item_list_name: listName,
                items,
            },
        })
    }

    /** Product added to cart. */
    const addToCart = (product, quantity = 1, variant = null) => {
        const items = [toItem(product, {
            quantity: num(quantity, 1),
            item_variant: variant?.name ?? variant?.sku ?? undefined,
            price: num(variant?.price ?? product?.price ?? 0),
        })]
        push('add_to_cart', {
            ecommerce: {
                currency: currency.value,
                value: itemsValue(items),
                items,
            },
        })
    }

    /** Product / cart line removed from cart. */
    const removeFromCart = (cartItem) => {
        const items = [cartItemToItem(cartItem)]
        push('remove_from_cart', {
            ecommerce: {
                currency: currency.value,
                value: itemsValue(items),
                items,
            },
        })
    }

    /** Cart page viewed. */
    const viewCart = (cart) => {
        const items = (cart?.items ?? []).map(cartItemToItem)
        push('view_cart', {
            ecommerce: {
                currency: currency.value,
                value: num(cart?.subtotal ?? itemsValue(items)),
                items,
            },
        })
    }

    /** Product added to wishlist. */
    const addToWishlist = (product) => {
        const items = [toItem(product)]
        push('add_to_wishlist', {
            ecommerce: {
                currency: currency.value,
                value: itemsValue(items),
                items,
            },
        })
    }

    /** Checkout started. */
    const beginCheckout = (cart) => {
        const items = (cart?.items ?? []).map(cartItemToItem)
        push('begin_checkout', {
            ecommerce: {
                currency: currency.value,
                value: num(cart?.total ?? cart?.subtotal ?? itemsValue(items)),
                items,
                coupon: cart?.coupon?.code ?? undefined,
            },
        })
    }

    /** Shipping method selected at checkout. */
    const addShippingInfo = (cart, shippingTier = '') => {
        const items = (cart?.items ?? []).map(cartItemToItem)
        push('add_shipping_info', {
            ecommerce: {
                currency: currency.value,
                value: num(cart?.total ?? cart?.subtotal ?? itemsValue(items)),
                shipping_tier: shippingTier,
                items,
            },
        })
    }

    /** Payment method selected at checkout. */
    const addPaymentInfo = (cart, paymentType = '') => {
        const items = (cart?.items ?? []).map(cartItemToItem)
        push('add_payment_info', {
            ecommerce: {
                currency: currency.value,
                value: num(cart?.total ?? cart?.subtotal ?? itemsValue(items)),
                payment_type: paymentType,
                items,
            },
        })
    }

    /**
     * Order successfully placed.
     * `order` is expected to look like the response of POST /orders, e.g.
     * { id, order_number, items: [...], subtotal, shipping, discount, total, coupon }
     */
    const purchase = (order = {}) => {
        const items = (order.items ?? []).map((it) =>
            toItem(it.product ?? it, {
                item_id: String(it.product_id ?? it.id ?? ''),
                quantity: num(it.quantity ?? 1, 1),
                price: num(it.price ?? it.product?.price ?? 0),
                item_variant: it.variant?.name ?? it.variant_name ?? undefined,
            })
        )

        push('purchase', {
            ecommerce: {
                transaction_id: String(order.order_number ?? order.id ?? ''),
                currency: currency.value,
                value: num(order.total ?? itemsValue(items)),
                shipping: num(order.shipping ?? order.delivery_charge ?? 0),
                tax: num(order.tax ?? 0),
                coupon: order.coupon?.code ?? order.coupon_code ?? undefined,
                items,
            },
            // Convenience top-level fields some GTM templates expect
            gads_conversion_label: tracking.value?.gads_conversion_label || undefined,
        })
    }

    /** Search performed. */
    const search = (term = '') => {
        push('search', { search_term: term })
    }

    /** User registered. */
    const signUp = (method = 'phone') => {
        push('sign_up', { method })
    }

    /** User logged in. */
    const login = (method = 'phone') => {
        push('login', { method })
    }

    return {
        // raw
        push,
        toItem,
        cartItemToItem,
        currency,
        isEnabled,

        // events
        pageView,
        viewItem,
        viewItemList,
        selectItem,
        addToCart,
        removeFromCart,
        viewCart,
        addToWishlist,
        beginCheckout,
        addShippingInfo,
        addPaymentInfo,
        purchase,
        search,
        signUp,
        login,
    }
}
