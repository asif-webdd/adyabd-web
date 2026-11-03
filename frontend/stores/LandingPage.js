import {defineStore} from "pinia"
import {normalizeProducts} from "./../utils/normalizeProduct.js"

export const useLandingPageStore = defineStore("landingPage", {
    state: () => ({
        sliders: [],
        banners: [],   // raw flat array from API — split by position in getters
        featured: [],
        trending: [],
        newArrivals: [],
        flashSale: [],
        categories: [],
        loading: false,
    }),

    getters: {
        // position=promo_top  → stacked cards beside the hero slider
        promoTopBanners: (state) => state.banners.filter(b => b.position === 'promo_top'),
        // position=promo_bottom → full-width strip below hero
        promoBottomBanners: (state) => state.banners.filter(b => b.position === 'promo_bottom'),
        // position=sidebar    → compact offer strip
        sidebarBanners: (state) => state.banners.filter(b => b.position === 'sidebar'),
        // position=popup      → modal, first one wins
        activePopup: (state) => state.banners.find(b => b.position === 'popup') ?? null,
    },

    actions: {
        async fetchHome() {
            this.loading = true
            const {$api} = useNuxtApp()
            try {
                const response = await $api.get("/landing-page")
                if (!response) return

                if (response.hasOwnProperty('categories')) {
                    this.categories = response.categories || []
                }

                if (response.hasOwnProperty('featured')) {
                    this.featured = normalizeProducts(response.featured || [])
                }

                if (response.hasOwnProperty('flash_sale')) {
                    this.flashSale = response.flash_sale || []
                }

                if (response.hasOwnProperty('new_arrivals')) {
                    this.newArrivals = normalizeProducts(response.new_arrivals || [])
                }

                if (response.hasOwnProperty('trending')) {
                    this.trending = normalizeProducts(response.trending || [])
                }

                if (response.hasOwnProperty('sliders')) {
                    this.sliders = response.sliders || []   // sliders are not products
                }

                if (response.hasOwnProperty('banners')) {
                    this.banners = response.banners || []   // banners are not products
                }
            } finally {
                this.loading = false
            }
        },
    },
})