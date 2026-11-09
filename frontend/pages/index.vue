<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-0 md:pb-8 space-y-10']">

    <!-- ─── Popup Banner ─────────────────────────────────── -->
    <PopupBanner v-if="sections.show_popup_banner" :banner="activePopup" :delay="popup.delay" :frequency="popup.frequency" :allow-dismiss-forever="popup.allow_dismiss_forever"/>

    <!-- ─── Hero Slider + Promo Top ─────────────────────── -->
    <template v-if="hero.show_hero">
      <SkHeroBanner v-if="pending.hero" />
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Hero Slider -->
        <div :class="hero.show_promo_top ? 'lg:col-span-2' : 'lg:col-span-3'">
          <HeroBanner />
        </div>

        <!-- Promo Top Banners — 2 stacked tiles -->
        <div v-if="hero.show_promo_top" class="grid grid-rows-2 gap-4">
          <div v-if="!promoTopBanners.length" v-for="i in 2" :key="'sk-pt-' + i" class="rounded-2xl bg-gray-200 animate-pulse min-h-[150px]"></div>
          <NuxtLink v-for="(banner, idx) in promoTopBanners.slice(0, 2)" :key="banner.id" :to="banner.link || '#'" class="relative overflow-hidden rounded-2xl text-white p-5 flex flex-col justify-between min-h-[150px] group" :style="{ background: palette[idx % palette.length].gradient }">
            <img :src="banner.mobile_image || banner.image" :alt="banner.title" class="absolute inset-0 w-full h-full object-cover opacity-25 transition-transform duration-500 group-hover:scale-105"/>
            <div class="relative">
              <span class="badge mb-2 inline-block border border-white/30 bg-white/20 text-white text-xs">{{ banner.subtitle }}</span>
              <h3 class="font-display font-bold text-xl leading-snug">{{ banner.title_bn || banner.title }}</h3>
            </div>
            <span class="relative inline-flex items-center gap-1 font-display font-bold px-3 py-1.5 rounded-lg text-sm w-fit mt-3 bg-white/90 transition-transform group-hover:scale-105" :class="palette[idx % palette.length].ctaText">
              {{ tr('দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
            </span>
          </NuxtLink>
        </div>
      </div>
    </template>

    <!-- ─── Promo Bottom Banners ──────────────────────────── -->
    <div v-if="hero.show_promo_bottom && promoBottomBanners.length" class="grid gap-4" :class="promoBottomBanners.length === 1 ? 'grid-cols-1' : promoBottomBanners.length === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'">
      <NuxtLink v-for="banner in promoBottomBanners" :key="banner.id" :to="banner.link || '#'" class="relative overflow-hidden rounded-2xl group min-h-[120px] md:min-h-[160px]">
        <img :src="banner.mobile_image || banner.image" :alt="banner.title" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 min-h-[120px] md:min-h-[160px]"/>
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent flex items-center px-6">
          <div class="text-white">
            <p v-if="banner.subtitle" class="text-xs text-white/70 mb-1 font-bengali">{{ banner.subtitle }}</p>
            <h3 class="font-display font-bold text-lg leading-snug">{{ banner.title_bn || banner.title }}</h3>
            <span class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-yellow-300">{{ tr('দেখুন') }} <span class="mi mi-sm">arrow_forward</span></span>
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- ─── Trust Badges ─────────────────────────────────── -->
    <template v-if="sections.show_trust_badges">
      <SkTrustBadges v-if="pending.trust" />
      <TrustBadges v-else />
    </template>

    <!-- ─── Categories ───────────────────────────────────── -->
    <template v-if="sections.show_categories">
      <SkCategoryGrid v-if="pending.categories" :count="8" />
      <CategoryGrid v-else />
    </template>

    <!-- ─── Flash Sale ────────────────────────────────────── -->
    <template v-if="sections.show_flash_sale">
      <SkFlashSale v-if="pending.flashSale" :count="5" />
      <FlashSale v-else-if="flashSaleProducts.length" />
    </template>

    <!-- ─── Featured / Popular Products ─────────────────── -->
    <section v-if="sections.show_featured">
      <SkProductSection v-if="pending.featured" :count="10" :show-tabs="true" :tab-count="4" :show-more="true" :grid-class="productGridCols"/>
      <div v-else>
        <div class="flex items-center justify-between mb-5">
          <h2 class="section-title">{{ tr(labels.label_featured) }}</h2>
        </div>
        <!-- Category filter tabs: names + products come from real categories; scrolls sideways, scrollbar hidden -->
        <div v-if="productTabs.length > 1" class="flex gap-2 overflow-x-auto scrollbar-hide snap-slider -mx-4 px-4 pb-1 mb-5 md:mx-0 md:px-0">
          <button v-for="tab in productTabs" :key="tab.key" type="button" @click="activeTab = tab.key" class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-bengali font-medium transition-colors shrink-0" :class="activeTab === tab.key ? 'bg-primary text-white' : 'bg-white text-gray-600 hover:bg-gray-100'">{{ tr(tab.label) }}</button>
        </div>
        <div v-if="tabLoading" class="grid gap-4" :class="productGridCols">
          <ProductSkeleton v-for="i in 5" :key="i" />
        </div>
        <div v-else-if="tabProducts.length" class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in tabProducts" :key="p.id" :product="p" />
        </div>
        <p v-else class="text-center text-gray-400 font-bengali py-10">{{ tr('এই ক্যাটাগরিতে এখনো কোনো পণ্য নেই') }}</p>
        <div class="text-center mt-6">
          <NuxtLink :to="seeMoreLink" class="btn-outline">
            <span class="mi mi-sm">grid_view</span> {{ tr('আরও পণ্য দেখুন') }}
          </NuxtLink>
        </div>
      </div>
    </section>

    <!-- ─── Sidebar / Special Offer Banners ──────────────── -->
    <div v-if="sections.show_sidebar_banners && sidebarBanners.length" class="space-y-3">
      <div class="flex items-center gap-2 mb-1">
        <span class="mi text-primary">local_offer</span>
        <h2 class="section-title">{{ tr(labels.label_special_offers) }}</h2>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <NuxtLink v-for="(banner, idx) in sidebarBanners" :key="banner.id" :to="banner.link || '#'" class="relative overflow-hidden rounded-xl group flex items-center gap-4 p-4 text-white min-h-[100px]" :style="{ background: palette[(idx + 2) % palette.length].gradient }">
          <img :src="banner.mobile_image || banner.image" :alt="banner.title" class="absolute inset-0 w-full h-full object-cover opacity-20 transition-transform duration-500 group-hover:scale-105"/>
          <div class="relative flex-1">
            <p v-if="banner.subtitle" class="text-xs text-white/70 font-bengali mb-0.5">{{ banner.subtitle }}</p>
            <h3 class="font-display font-bold text-base leading-snug">{{ banner.title_bn || banner.title }}</h3>
          </div>
          <span class="relative shrink-0 inline-flex items-center gap-1 bg-white/90 font-bold px-3 py-1.5 rounded-lg text-xs transition-transform group-hover:scale-105" :class="palette[(idx + 2) % palette.length].ctaText">
            {{ tr('দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
          </span>
        </NuxtLink>
      </div>
    </div>

    <!-- ─── Local Brands CTA Banner ──────────────────────── -->
    <div v-if="sections.show_local_brands_banner" class="relative overflow-hidden bg-gradient-to-r from-primary to-primary-dark rounded-2xl p-8 text-white flex flex-col md:flex-row items-center gap-6">
      <img src="/images/placeholder.svg" alt="Bangladesh" class="w-20 h-20 rounded-2xl object-cover border-4 border-white/20 shrink-0"/>
      <div class="flex-1 text-center md:text-left">
        <h3 class="font-display font-extrabold text-2xl mb-1">{{ tr(localBrands.heading) }}</h3>
        <p class="font-bengali text-green-100">{{ tr(localBrands.subtext) }}</p>
      </div>
      <NuxtLink :to="localBrands.button_link" class="btn-primary shrink-0">
        <span class="mi mi-sm">storefront</span> {{ tr(localBrands.button_label) }}
      </NuxtLink>
    </div>

    <!-- ─── New Arrivals ───────────────────────────────────── -->
    <template v-if="sections.show_new_arrivals">
      <SkProductSection v-if="pending.newArrivals" :count="10" :show-badge="true" :grid-class="productGridCols"/>
      <section v-else>
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-2">
            <h2 class="section-title">{{ tr(labels.label_new_arrivals) }}</h2>
            <span class="badge bg-primary text-white">{{ tr('নতুন') }}</span>
          </div>
          <NuxtLink to="/search?sort=newest" class="text-primary font-bengali text-sm font-medium flex items-center gap-1 hover:underline">
            {{ tr('সব দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
          </NuxtLink>
        </div>
        <div class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in newProducts" :key="p.id" :product="p" />
        </div>
      </section>
    </template>

    <!-- ─── Trending ──────────────────────────────────────── -->
    <template v-if="sections.show_trending">
      <SkProductSection v-if="pending.trending" :count="10" :show-badge="true" :grid-class="productGridCols"/>
      <section v-else>
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-2">
            <h2 class="section-title">{{ tr(labels.label_trending) }}</h2>
            <span class="badge bg-primary text-white">🔥</span>
          </div>
          <NuxtLink to="/search?sort=trending" class="text-primary font-bengali text-sm font-medium flex items-center gap-1 hover:underline">
            {{ tr('সব দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
          </NuxtLink>
        </div>
        <div class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in trendingProducts" :key="p.id" :product="p" />
        </div>
      </section>
    </template>

    <!-- ─── Payment Methods ───────────────────────────────── -->
    <div v-if="sections.show_payment_methods" class="cus-payment-menthods-sec bg-white rounded-2xl p-6 shadow-card">
      <h3 class="font-display font-bold text-lg text-center text-gray-800 mb-5 flex items-center justify-center gap-2">
        <span class="mi text-primary">payments</span> {{ tr(paymentMethods.strip_heading) }}
      </h3>
      <div class="cus-payment-menthods flex flex-wrap justify-center gap-3">
        <div v-for="method in visiblePaymentMethods" :key="method.name" class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl hover:border-primary transition-colors">
          <span class="mi text-xl" :class="method.color">{{ method.icon }}</span>
          <span class="text-sm font-bengali font-medium text-gray-700">{{ method.name }}</span>
        </div>
      </div>
    </div>

    <!-- ─── App Download ───────────────────────────────────── -->
    <div v-if="sections.show_app_download" class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-8 text-white flex flex-col md:flex-row items-center gap-8">
      <img :src="appDownload.image || '/images/placeholder.svg'" alt="App" class="w-32 h-32 rounded-2xl object-cover border-4 border-white/10 shrink-0"/>
      <div class="flex-1 text-center md:text-left">
        <h3 class="font-display font-extrabold text-2xl mb-2 flex items-center gap-2">
          <span class="mi text-primary">install_mobile</span> {{ tr(appDownload.heading) }}
        </h3>
        <p class="font-bengali text-gray-400 mb-5">{{ tr(appDownload.subtext) }}</p>
        <div class="flex gap-3 flex-wrap justify-center md:justify-start">
          <a v-if="appDownload.app_store_url" :href="appDownload.app_store_url" target="_blank" rel="noopener" class="flex items-center gap-3 bg-white text-gray-900 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
            <span class="mi text-2xl">apple</span>
            <div class="text-left">
              <div class="text-xs text-gray-500">Download on the</div>
              <div class="font-display font-bold text-sm">App Store</div>
            </div>
          </a>
          <a v-if="appDownload.play_store_url" :href="appDownload.play_store_url" target="_blank" rel="noopener" class="flex items-center gap-3 bg-white text-gray-900 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
            <span class="mi text-2xl text-green-600">android</span>
            <div class="text-left">
              <div class="text-xs text-gray-500">Get it on</div>
              <div class="font-display font-bold text-sm">Google Play</div>
            </div>
          </a>
          <!-- fallback buttons if no URLs set yet -->
          <template v-if="!appDownload.app_store_url && !appDownload.play_store_url">
            <button class="flex items-center gap-3 bg-white text-gray-900 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
              <span class="mi text-2xl">apple</span>
              <div class="text-left">
                <div class="text-xs text-gray-500">Download on the</div>
                <div class="font-display font-bold text-sm">App Store</div>
              </div>
            </button>
            <button class="flex items-center gap-3 bg-white text-gray-900 px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
              <span class="mi text-2xl text-green-600">android</span>
              <div class="text-left">
                <div class="text-xs text-gray-500">Get it on</div>
                <div class="font-display font-bold text-sm">Google Play</div>
              </div>
            </button>
          </template>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@media screen and (max-width: 767px){
  .cus-payment-menthods-sec{
    padding: 20px;
  }
  .cus-payment-menthods{
    gap: 7px;
  }
  .cus-payment-menthods div{
    padding: 6px 10px;
    border-radius: 8px;
  }
  .cus-payment-menthods .mi{
    font-size: 20px;
  }
}
</style>

<script setup>
const { tr } = useLang()
import { useProductsStore }    from '~/stores/products'
import { useLandingPageStore } from '~/stores/LandingPage'

import SkHeroBanner     from '~/components/Skeletons/Skherobanner.vue'
import SkTrustBadges    from '~/components/Skeletons/SkTrustBadges.vue'
import SkCategoryGrid   from '~/components/Skeletons/SkCategoryGrid.vue'
import SkFlashSale      from '~/components/Skeletons/SkFlashSale.vue'
import SkProductSection from '~/components/Skeletons/SkProductSection.vue'
import PopupBanner      from '~/components/PopupBanner.vue'
import { normalizeProducts } from '~/utils/normalizeProduct.js'

const { shopName } = useShopName()
const { footer: _footer } = useAppearance()
const _siteUrl = useRuntimeConfig().public.siteUrl   // read in setup: useHead getters run later, outside the Nuxt context
// Home-page SEO (name + tagline come from Admin → Settings / Appearance)
useHead(() => {
  const title = _footer.value.tagline ? `${shopName.value} — ${_footer.value.tagline}` : shopName.value
  return {
    title,
    meta: [
      { name: 'description', content: _footer.value.tagline || shopName.value },
      { property: 'og:title', content: title },
      { property: 'og:site_name', content: shopName.value },
      { property: 'og:type', content: 'website' },
    ],
    link: [{ rel: 'canonical', href: _siteUrl + '/' }],
  }
})

const store        = useProductsStore()
const landingStore = useLandingPageStore()

// ─── Appearance ────────────────────────────────────────────────────────────
const {
  sections, layout, hero, labels,
  localBrands, appDownload, paymentMethods, popup,
  productGridCols,
} = useAppearance()

const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

// ─── Pending flags ──────────────────────────────────────────────────────────
const pending = reactive({
  hero:        computed(() => landingStore.loading),
  trust:       computed(() => landingStore.loading),
  categories:  computed(() => landingStore.loading),
  flashSale:   computed(() => landingStore.loading),
  featured:    computed(() => landingStore.loading),
  newArrivals: computed(() => landingStore.loading),
  trending:    computed(() => landingStore.loading),
})

// ─── Banner data by position ────────────────────────────────────────────────
const promoTopBanners    = computed(() => landingStore.promoTopBanners)
const promoBottomBanners = computed(() => landingStore.promoBottomBanners)
const sidebarBanners     = computed(() => landingStore.sidebarBanners)
const activePopup        = computed(() => landingStore.activePopup)

// ─── Product data ────────────────────────────────────────────────────────────
// ─── Featured product tabs (dynamic) ───────────────────────────────────────
// Admin → Appearance → "Featured Products Tabs" holds up to 4 entries: "all", a category slug,
// or (legacy) a category name. Labels always come from the real category, so renaming a category
// updates the tab. If nothing usable is configured, tabs = All + the first categories.
const { isEn } = useLang()
const api = useApi()
const _norm = (s) => String(s ?? '').trim().toLowerCase()
const _catLabel = (c) => (isEn.value ? (c.name || c.name_bn) : (c.name_bn || c.name))
const _allCats = computed(() => (landingStore.categories || []).flatMap(c => [c, ...(c.children || [])]))
const _allTab = () => ({ key: 'all', slug: null, label: 'All Products' })

const productTabs = computed(() => {
  const cats = _allCats.value
  const out = []
  const push = (t) => { if (!out.some(x => x.key === t.key)) out.push(t) }
  const configured = [1, 2, 3, 4].map(i => labels.value['featured_tab_' + i]).filter(v => v && String(v).trim())
  configured.forEach((v, i) => {
    const n = _norm(v)
    if (n === 'all' || n === '__all__') return push(_allTab())
    const c = cats.find(c => _norm(c.slug) === n || _norm(c.name) === n || _norm(c.name_bn) === n)
    if (c) return push({ key: c.slug, slug: c.slug, label: _catLabel(c) })
    if (i === 0) push(_allTab())          // first tab is the "all products" tab by convention
  })
  if (out.length < 2) {
    out.length = 0
    push(_allTab())
    ;(landingStore.categories || []).slice(0, 8).forEach(c => push({ key: c.slug, slug: c.slug, label: _catLabel(c) }))
  }
  return out
})

const activeTab = ref('all')
watch(productTabs, (tabs) => {
  if (tabs.length && !tabs.some(t => t.key === activeTab.value)) activeTab.value = tabs[0].key
}, { immediate: true })

const _tabCache = reactive({})   // slug -> { items, loading }
const _loadTab = async (slug) => {
  if (!slug || _tabCache[slug]) return
  _tabCache[slug] = { items: [], loading: true }
  try {
    const res = await api.get(`/categories/${slug}/products`, { per_page: 10 })
    _tabCache[slug] = { items: normalizeProducts(res?.data || []), loading: false }
  } catch (e) {
    _tabCache[slug] = { items: [], loading: false }
  }
}
watch(activeTab, (key) => { if (key !== 'all') _loadTab(key) }, { immediate: true })

const tabLoading  = computed(() => activeTab.value !== 'all' && (_tabCache[activeTab.value]?.loading ?? true))
const tabProducts = computed(() => activeTab.value === 'all' ? landingStore.featured : (_tabCache[activeTab.value]?.items || []))
const seeMoreLink = computed(() => activeTab.value === 'all' ? '/products' : `/category/${activeTab.value}`)

const featuredProducts  = computed(() => landingStore.featured)
const newProducts       = computed(() => landingStore.newArrivals)
const trendingProducts  = computed(() => landingStore.trending)
const flashSaleProducts = computed(() => landingStore.flashSale)

// ─── Payment methods list (filtered by toggle settings) ──────────────────────
const allPaymentMethods = [
  { key: 'show_bkash',      name: 'bKash',              icon: 'phone_iphone',   color: 'text-bkash'      },
  { key: 'show_nagad',      name: 'Nagad',              icon: 'phone_android',  color: 'text-orange-500' },
  { key: 'show_rocket',     name: 'Rocket',             icon: 'rocket_launch',  color: 'text-purple-600' },
  //{ key: 'show_visa',       name: 'Visa',               icon: 'credit_card',    color: 'text-blue-600'   },
  //{ key: 'show_mastercard', name: 'Mastercard',         icon: 'credit_card',    color: 'text-red-600'    },
  { key: 'show_cod',        name: 'ক্যাশ অন ডেলিভারি', icon: 'payments',       color: 'text-green-600'  },
  //{ key: 'show_emi',        name: 'EMI কিস্তি',         icon: 'calendar_month', color: 'text-indigo-600' },
]
const visiblePaymentMethods = computed(() =>
    allPaymentMethods.filter(m => paymentMethods.value[m.key] !== false)
)

// ─── Colour palette for banners ───────────────────────────────────────────────
const palette = [
  { gradient: 'linear-gradient(135deg,#ff9933,#ff6600)', ctaText: 'text-orange-600'  },
  { gradient: 'linear-gradient(135deg,#7c3aed,#4c1d95)', ctaText: 'text-purple-600'  },
  { gradient: 'linear-gradient(135deg,#0ea5e9,#0369a1)', ctaText: 'text-sky-600'     },
  { gradient: 'linear-gradient(135deg,#10b981,#065f46)', ctaText: 'text-emerald-600' },
  { gradient: 'linear-gradient(135deg,#f43f5e,#be123c)', ctaText: 'text-rose-600'    },
  { gradient: 'linear-gradient(135deg,#f59e0b,#b45309)', ctaText: 'text-amber-600'   },
]
</script>