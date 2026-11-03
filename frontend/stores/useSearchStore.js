import { defineStore } from 'pinia'

export const useSearchStore = defineStore('search', () => {

    // ─── Query & pagination ────────────────────────────────────────────────────
    const query       = ref('')
    const page        = ref(1)
    const sortBy      = ref('')
    const totalPages  = ref(1)
    const totalCount  = ref(0)

    // ─── Filters ───────────────────────────────────────────────────────────────
    const selectedCategories  = ref([])
    const selectedRatings     = ref([])
    const selectedPriceRange  = ref(null)

    const priceRanges = [
        { label: 'সর্বোচ্চ ৳৫০০',    min: 0,     max: 500   },
        { label: '৳৫০০ – ৳২,০০০',    min: 500,   max: 2000  },
        { label: '৳২,০০০ – ৳৫,০০০',  min: 2000,  max: 5000  },
        { label: '৳৫,০০০ – ৳১০,০০০', min: 5000,  max: 10000 },
        { label: '৳১০,০০০ এর বেশি',   min: 10000, max: null  },
    ]

    // ─── Results ───────────────────────────────────────────────────────────────
    const results           = ref([])
    const loading           = ref(false)
    const error             = ref(null)
    const sidebarCategories = ref([])

    // ─── Suggestions (dropdown in header) ────────────────────────────────────
    const suggestionQuery      = ref('')
    const suggestionProducts   = ref([])
    const suggestionCategories = ref([])
    const suggestionSellers    = ref([])
    const suggestLoading       = ref(false)
    const showSuggestions      = ref(false)

    // ─── Computed ──────────────────────────────────────────────────────────────
    const hasResults = computed(() =>
        suggestionProducts.value.length   > 0 ||
        suggestionCategories.value.length > 0 ||
        suggestionSellers.value.length    > 0
    )

    const activeFilterCount = computed(() => {
        let n = 0
        if (selectedCategories.value.length)  n++
        if (selectedRatings.value.length)     n++
        if (selectedPriceRange.value)         n++
        if (sortBy.value)                     n++
        return n
    })

    // Build URL query object — used by both layout and search page
    const buildRouteQuery = (overridePage = page.value) => ({
        ...(query.value                           && { q:          query.value }),
        ...(sortBy.value                          && { sort:       sortBy.value }),
        ...(overridePage > 1                      && { page:       overridePage }),
        ...(selectedCategories.value.length       && { categories: selectedCategories.value.join(',') }),
        ...(selectedRatings.value.length          && { rating:     Math.min(...selectedRatings.value) }),
        ...(selectedPriceRange.value              && { min_price:  selectedPriceRange.value.min }),
        ...(selectedPriceRange.value?.max != null && { max_price:  selectedPriceRange.value.max }),
    })

    // ─── Sync from URL (call on search page mount / route watch) ──────────────
    function syncFromRoute(routeQuery) {
        query.value  = routeQuery.q    || ''
        page.value   = Number(routeQuery.page) || 1
        sortBy.value = routeQuery.sort || ''

        selectedCategories.value = routeQuery.categories ? routeQuery.categories.split(',') : []
        selectedRatings.value = routeQuery.rating ? [Number(routeQuery.rating)] : []

        if (routeQuery.min_price) {
            const min = Number(routeQuery.min_price)
            const max = routeQuery.max_price ? Number(routeQuery.max_price) : null
            selectedPriceRange.value = priceRanges.find(r => r.min === min && r.max === max) ?? { label: 'Custom', min, max }
        } else {
            selectedPriceRange.value = null
        }
    }

    // ─── Merge products_count into sidebarCategories ──────────────────────────
    // Takes categories from the search response (which have counts for the
    // current query) and merges them into the full sidebarCategories list.
    // Categories not returned by the search response get count = 0.
    function mergeCategoryCounts(searchCats = []) {
        if (!searchCats.length) {
            // No category data from search — zero out all counts
            sidebarCategories.value = sidebarCategories.value.map(c => ({
                ...c,
                products_count: 0,
            }))
            return
        }

        // Build a slug→count map from whatever the search API returns
        const countMap = {}
        for (const cat of searchCats) {
            const key = cat.slug ?? cat.id
            countMap[key] = cat.products_count ?? cat.count ?? cat.total ?? 0
        }

        if (sidebarCategories.value.length) {
            // Merge counts into the already-loaded full category list
            sidebarCategories.value = sidebarCategories.value.map(c => ({
                ...c,
                products_count: countMap[c.slug ?? c.id] ?? 0,
            }))
        } else {
            // No full list yet — use whatever the search API gave us directly
            sidebarCategories.value = searchCats.map(c => ({
                ...c,
                products_count: c.products_count ?? c.count ?? c.total ?? 0,
            }))
        }
    }

    // ─── Fetch search results ─────────────────────────────────────────────────
    async function fetchResults(apiGet) {
        if (!query.value) return
        loading.value = true
        error.value   = null
        try {
            const params = {
                q:    query.value,
                page: page.value,
                ...(sortBy.value                          && { sort:       sortBy.value }),
                ...(selectedCategories.value.length       && { categories: selectedCategories.value.join(',') }),
                ...(selectedRatings.value.length          && { rating:     Math.min(...selectedRatings.value) }),
                ...(selectedPriceRange.value              && { min_price:  selectedPriceRange.value.min }),
                ...(selectedPriceRange.value?.max != null && { max_price:  selectedPriceRange.value.max }),
            }

            const res = await apiGet('/search', params)

            // Handle every possible nesting: raw[], {products[]}, {data:{products[]}}
            const payload = res?.data ?? res ?? {}
            const rawProducts = Array.isArray(res) ? res : Array.isArray(payload.products) ? payload.products : Array.isArray(payload.data?.products) ? payload.data.products : Array.isArray(payload.data)               ? payload.data : []

            results.value    = rawProducts
            totalCount.value = payload.total     ?? payload.data?.total     ?? rawProducts.length
            totalPages.value = payload.last_page ?? payload.data?.last_page ?? 1

            // Merge search-scoped products_count into sidebar categories
            const searchCats = payload.categories ?? payload.data?.categories ?? []
            mergeCategoryCounts(searchCats)

        } catch (e) {
            console.error('[search] fetchResults:', e)
            error.value   = e.message
            results.value = []
        } finally {
            loading.value = false
        }
    }

    // ─── Load full category list with products_count ──────────────────────────
    // Call once on mount. Fetches /categories (which should return counts for
    // the whole catalogue), then re-merges if a search is already active.
    async function fetchCategories(apiGet) {
        try {
            const res  = await apiGet('/categories')
            const list = res?.data ?? res ?? []

            sidebarCategories.value = list.map(c => ({
                ...c,
                products_count: c.products_count ?? c.count ?? c.total ?? 0,
            }))
        } catch (e) {
            console.error('[search] fetchCategories:', e)
        }
    }

    // ─── Fetch suggestions (debounced, called from layout) ────────────────────
    let _debounce = null
    async function fetchSuggestions(q, apiGet) {
        clearTimeout(_debounce)
        suggestionQuery.value = q
        const trimmed = q?.trim()
        if (!trimmed || trimmed.length < 2) {
            clearSuggestions()
            showSuggestions.value = false
            return
        }
        showSuggestions.value = true
        suggestLoading.value  = true

        _debounce = setTimeout(async () => {
            try {
                const res     = await apiGet('/search', { q: trimmed, limit: 6 })
                const payload = res?.data ?? res ?? {}

                suggestionProducts.value   = (payload.products   ?? []).slice(0, 6)
                suggestionCategories.value = (payload.categories ?? []).slice(0, 3)
                suggestionSellers.value    = (payload.sellers    ?? []).slice(0, 3)
            } catch {
                clearSuggestions()
            } finally {
                suggestLoading.value = false
            }
        }, 300)
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function clearSuggestions() {
        suggestionProducts.value   = []
        suggestionCategories.value = []
        suggestionSellers.value    = []
    }

    function closeSuggestions() {
        showSuggestions.value = false
    }

    function clearFilters() {
        selectedCategories.value = []
        selectedRatings.value    = []
        selectedPriceRange.value = null
        sortBy.value             = ''
        page.value               = 1
    }

    function resetAll() {
        clearFilters()
        query.value      = ''
        results.value    = []
        totalCount.value = 0
        totalPages.value = 1
    }

    return {
        // state
        query, page, sortBy, totalPages, totalCount,
        selectedCategories, selectedRatings, selectedPriceRange, priceRanges,
        results, loading, error, sidebarCategories,
        suggestionQuery, suggestionProducts, suggestionCategories, suggestionSellers,
        suggestLoading, showSuggestions,
        // computed
        hasResults, activeFilterCount,
        // actions
        syncFromRoute, buildRouteQuery,
        fetchResults, fetchCategories, fetchSuggestions,
        clearSuggestions, closeSuggestions, clearFilters, resetAll,
    }
})