/*
 // Fully integrated with backend API — all mocks replaced
 export const useProduct = () => {
 const api = useApi()
 const config = useRuntimeConfig()
 const loading = ref(false)
 const error = ref(null)

 /!** Normalise a product from the API to match ProductCard's expected shape *!/
 const normalise = (p) => {
 const img = p.primary_image?.full_url_image ?? p.thumbnail ?? p.image ?? ''
 return {
 id:            p.id,
 name:          p.name,
 slug:          p.slug,
 seller:        p.seller?.shop_name ?? p.seller ?? '',
 sellerId:      p.seller?.id ?? p.seller_id,
 price:         Number(p.price),
 originalPrice: Number(p.original_price ?? p.compare_price ?? p.price),
 discount:      p.discount_percentage ?? 0,
 rating:        Number(p.rating ?? 0),
 reviews:       Number(p.total_reviews ?? p.reviews_count ?? 0),
 delivery:      '২-৩ দিনে',
 image:         img ? img : '',
 isNew:         !!p.is_new,
 isTrending:    !!p.is_trending,
 inStock:       p.is_active && (p.quantity ?? 1) > 0,
 stockCount:    p.quantity ?? 0,
 }
 }

 /!** Fetch single product by slug or ID *!/
 const fetchProduct = async (slugOrId) => {
 loading.value = true
 error.value = null
 try {
 const data = await api.get(`/products/${slugOrId}`)
 return data
 } catch (e) {
 error.value = e.message
 return null
 } finally {
 loading.value = false
 }
 }

 /!** Fetch products by category slug with optional filters *!/
 const fetchByCategory = async (slug, options = {}) => {
 loading.value = true
 error.value = null
 try {
 const params = {}
 if (options.page)     params.page     = options.page
 if (options.limit)    params.limit    = options.limit
 if (options.sort)     params.sort     = options.sort
 if (options.minPrice) params.min_price = options.minPrice
 if (options.maxPrice) params.max_price = options.maxPrice
 if (options.brands)   params.brands   = options.brands
 if (options.rating)   params.rating   = options.rating
 const data = await api.get(`/categories/${slug}/products`, params)
 return {
 products: (data.data ?? data).map(normalise),
 total:    data.total    ?? 0,
 pages:    data.last_page ?? 1,
 meta:     data.meta,
 }
 } catch (e) {
 error.value = e.message
 return { products: [], total: 0, pages: 1 }
 } finally {
 loading.value = false
 }
 }

 /!** Search products *!/
 const searchProducts = async (query, filters = {}) => {
 loading.value = true
 error.value = null
 try {
 const params = { q: query, ...filters }
 const data = await api.get('/search', params)
 return {
 products: (data.data ?? data).map(normalise),
 total:    data.total ?? 0,
 }
 } catch (e) {
 error.value = e.message
 return { products: [], total: 0 }
 } finally {
 loading.value = false
 }
 }

 /!** Get related products *!/
 const getRelated = async (productId, limit = 5) => {
 try {
 const data = await api.get(`/products/${productId}/related`, { limit })
 return (data ?? []).map(normalise)
 } catch {
 return []
 }
 }

 /!** Fetch product reviews *!/
 const fetchReviews = async (productId, page = 1) => {
 try {
 const data = await api.get(`/products/${productId}/reviews`, { page })
 return data
 } catch {
 return { data: [], total: 0 }
 }
 }

 return { loading, error, normalise, fetchProduct, fetchByCategory, searchProducts, getRelated, fetchReviews }
 }
 */
export const useProduct = () => {
    const {$api} = useNuxtApp()

    async function getProducts(params = {}) {
        return $api.get('/products', params)
    }

    async function getProduct(slug) {
        return $api.get(`/products/${slug}`)
    }

    async function getFeatured(limit = 10) {
        return $api.get('/products/featured', {limit})
    }

    async function getTrending(limit = 10) {
        return $api.get('/products/trending', {limit})
    }

    async function getNewArrivals(limit = 10) {
        return $api.get('/products/new-arrivals', {limit})
    }

    async function getCategories() {
        return $api.get('/categories')
    }

    async function getCategoryProducts(slug, params = {}) {
        return $api.get(`/categories/${slug}/products`, params)
    }

    async function getBanners(position = 'hero') {
        return $api.get('/banners', {position})
    }

    async function getFlashSale() {
        return $api.get('/flash-sales/active')
    }

    async function search(query, params = {}) {
        return $api.get('/search', {q: query, ...params})
    }

    async function getPublicSettings() {
        return $api.get('/settings/public')
    }

    return {
        getProducts,
        getProduct,
        getFeatured,
        getTrending,
        getNewArrivals,
        getCategories,
        getCategoryProducts,
        getBanners,
        getFlashSale,
        search,
        getPublicSettings
    }
}