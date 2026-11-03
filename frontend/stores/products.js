import {defineStore} from 'pinia'
import {normalizeProduct, normalizeProducts} from "../utils/normalizeProduct.js";

export const useProductsStore = defineStore('products', {
    state: () => ({
        trustBadges: [],
        featured: [],
        trending: [],
        newArrivals: [],
        flashSale: null,
        categories: [],
        banners: [],
        loading: false,
        flashSaleLoading: true,
        error: null,
    }),

    actions: {
        async fetchHome() {
            const api = useApi()
            this.loading = true
            this.error = null
            try {
                const [featuresResponse, trend, newArrivalResponse, flash, categoriesResponse, banners] = await Promise.allSettled([
                    api.get('/products/featured', {limit: 10}),
                    api.get('/products/trending', {limit: 10}),
                    api.get('/products/new-arrivals', {limit: 10}),
                    api.get('/flash-sales/active'),
                    api.get('/categories'),
                    api.get('/banners'),
                ])

                if (featuresResponse.status === 'fulfilled') {
                    this.featured = normalizeProducts(featuresResponse.value ?? [])
                }
                if (trend.status === 'fulfilled') {
                    this.trending = normalizeProducts(trend.value ?? [])
                }
                if (newArrivalResponse.status === 'fulfilled') {
                    this.newArrivals = normalizeProducts(newArrivalResponse.value ?? [])
                }
                if (flash.status === 'fulfilled'){
                    this.flashSale = flash.value
                    this.flashSaleLoading = false
                }
                if (categoriesResponse.status === 'fulfilled') {
                    this.categories = categoriesResponse.value ?? categoriesResponse.value ?? []
                }
                if (banners.status === 'fulfilled'){
                    this.banners = banners.value?.data ?? banners.value ?? []
                }
            } catch (e) {
                this.error = e.message
                console.log(e.message)
            } finally {
                this.loading = false
            }
        },
        normalise(product){
            return normalizeProduct(product)
        }
    },
})
