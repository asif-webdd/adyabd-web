<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">

    <!-- Popular searches -->
    <div class="flex flex-wrap gap-2 mb-6">
      <span class="text-sm font-bengali text-gray-500 flex items-center gap-1">
        <span class="mi mi-sm">trending_up</span>{{ tr('জনপ্রিয়:') }}
      </span>
      <button
          v-for="s in popularSearches"
          :key="s"
          @click="pushSearch(s)"
          class="text-sm font-bengali text-primary bg-primary/10 px-3 py-1 rounded-full hover:bg-primary/20 transition-colors"
      >{{ s }}</button>
    </div>

    <!-- Results meta + sort -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <p class="font-bengali text-gray-600 flex items-center gap-2">
        <span class="mi text-primary">filter_list</span>
        "<span class="font-semibold text-gray-900">{{ store.query }}</span>" —
        <span class="font-semibold text-primary">{{ store.totalCount }}</span> {{ tr('টি পণ্য') }}
      </p>
      <select
          v-model="store.sortBy"
          @change="onSortChange"
          class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-bengali outline-none focus:border-primary bg-white"
      >
        <option value="">{{ tr('সেরা মিল') }}</option>
        <option value="price_asc">{{ tr('দাম: কম থেকে বেশি') }}</option>
        <option value="price_desc">{{ tr('দাম: বেশি থেকে কম') }}</option>
        <option value="newest">{{ tr('নতুন পণ্য আগে') }}</option>
        <option value="rating">{{ tr('সর্বোচ্চ রেটিং') }}</option>
      </select>
    </div>

    <div class="flex gap-6">
      <!-- Sidebar Filters -->
      <aside class="w-60 shrink-0 hidden lg:block space-y-4">

        <!-- Category filter -->
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span class="mi mi-sm text-primary">category</span>{{ tr('ক্যাটাগরি') }}
          </h3>
          <div class="space-y-1.5">
            <label
                v-for="cat in store.sidebarCategories"
                :key="cat.id"
                class="flex items-center justify-between cursor-pointer group"
            >
              <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    class="accent-primary"
                    :checked="store.selectedCategories.includes(cat.slug)"
                    @change="toggleCategory(cat.slug)"
                />
                <span class="text-sm font-bengali text-gray-700 group-hover:text-primary transition-colors">
                  {{ cat.name }}
                </span>
              </div>
              <span class="text-xs text-gray-400">({{ cat.products_count ?? 0 }})</span>
            </label>
          </div>
        </div>

        <!-- Price filter -->
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="text-primary w-5 h-5" fill="currentColor">
              <path d="M36 32.3C18.4 30.1 2.4 42.5 .3 60S10.5 93.6 28 95.8l7.9 1c16 2 28 15.6 28 31.8l0 31.5-40 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l40 0 0 176c0 53 43 96 96 96l32 0c106 0 192-86 192-192l0-32c0-53-43-96-96-96l-16 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l16 0c17.7 0 32 14.3 32 32l0 32c0 70.7-57.3 128-128 128l-32 0c-17.7 0-32-14.3-32-32l0-176 40 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-40 0 0-31.5C128 80.1 92 39.2 43.9 33.2l-7.9-1z"/>
            </svg>
            {{ tr('মূল্য পরিসীমা') }}
          </h3>
          <div class="space-y-2">
            <label
                v-for="(range, idx) in store.priceRanges"
                :key="range.label"
                class="flex items-center gap-2 cursor-pointer"
            >
              <input
                  type="radio"
                  name="price"
                  class="accent-primary"
                  :checked="selectedPriceIndex === idx"
                  @change="selectPriceRange(idx)"
              />
              <span class="text-sm font-bengali text-gray-600">{{ range.label }}</span>
            </label>
          </div>
        </div>

        <!-- Rating filter -->
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span class="mi mi-sm text-primary">star</span>{{ tr('রেটিং') }}
          </h3>
          <div class="space-y-2">
            <label v-for="r in [5,4,3,2]" :key="r" class="flex items-center gap-2 cursor-pointer">
              <input
                  type="checkbox"
                  class="accent-primary"
                  :checked="store.selectedRatings.includes(r)"
                  @change="toggleRating(r)"
              />
              <div class="flex gap-0.5">
                <span
                    v-for="i in 5"
                    :key="i"
                    class="mi mi-sm"
                    :class="i <= r ? 'mi-filled text-gold' : 'text-gray-300'"
                    style="font-size:14px"
                >star</span>
              </div>
              <span class="text-xs font-bengali text-gray-500">{{ tr('ও উপরে') }}</span>
            </label>
          </div>
        </div>

        <button @click="clearFilters" class="w-full btn-outline justify-center text-sm">
          <span class="mi mi-sm">clear</span>{{ tr('ফিল্টার মুছুন') }}
        </button>
      </aside>

      <!-- Results -->
      <div class="flex-1">
        <!-- Loading skeleton -->
        <div v-if="store.loading" class="grid gap-4" :class="productGridColsNarrow">
          <ProductSkeleton v-for="i in 12" :key="i" />
        </div>

        <!-- Empty state -->
        <div v-else-if="store.results.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-card">
          <span class="mi text-6xl text-gray-200 block mb-4">search_off</span>
          <h2 class="font-display font-bold text-xl text-gray-700 mb-2">{{ tr('কোনো পণ্য পাওয়া যায়নি') }}</h2>
          <p class="font-bengali text-gray-500 mb-4">"{{ store.query }}" এর জন্য কোনো ফলাফল নেই।</p>
          <button @click="clearFilters" class="btn-outline">{{ tr('ফিল্টার মুছুন') }}</button>
        </div>

        <!-- Product grid -->
        <div v-else class="grid gap-4" :class="productGridColsNarrow">
          <ProductCard
              v-for="product in products"
              :key="product.id"
              :product="product"
          />
        </div>

        <!-- Pagination -->
        <div v-if="store.totalPages > 1" class="flex justify-center gap-2 mt-8">
          <button
              @click="goPage(store.page - 1)"
              :disabled="store.page <= 1"
              class="px-4 py-2 rounded-xl border border-gray-200 font-bengali text-sm hover:border-primary disabled:opacity-40"
          >
            <span class="mi mi-sm">arrow_back</span>
          </button>
          <button
              v-for="p in visiblePages"
              :key="p"
              @click="goPage(p)"
              class="px-4 py-2 rounded-xl border text-sm font-bengali"
              :class="p === store.page ? 'bg-primary text-white border-primary' : 'border-gray-200 hover:border-primary'"
          >{{ p }}</button>
          <button
              @click="goPage(store.page + 1)"
              :disabled="store.page >= store.totalPages"
              class="px-4 py-2 rounded-xl border border-gray-200 font-bengali text-sm hover:border-primary disabled:opacity-40"
          >
            <span class="mi mi-sm">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout, productGridColsNarrow } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import {normalizeProduct} from "../utils/normalizeProduct.js";

definePageMeta({ layout: 'default' })

const route  = useRoute()
const router = useRouter()
const api    = useApi()
const store  = useSearchStore()

const popularSearches = ['Samsung মোবাইল', 'পাঞ্জাবি', 'ল্যাপটপ', 'শাড়ি', 'হেডফোন', 'ব্যাগ', 'জুতা']
const products = computed(()=> store.results.map(normalizeProduct))
// ── Pagination ──────────────────────────────────────────────────────────────
const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, store.page - 2)
  const end   = Math.min(store.totalPages, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

// ── Price range: index-based so :checked works correctly after syncFromRoute ─
const selectedPriceIndex = computed(() => {
  if (!store.selectedPriceRange) return -1
  return store.priceRanges.findIndex(
      r => r.min === store.selectedPriceRange.min && r.max === store.selectedPriceRange.max
  )
})

// ── Filter toggle helpers ───────────────────────────────────────────────────
const toggleCategory = (slug) => {
  const idx = store.selectedCategories.indexOf(slug)
  if (idx === -1) store.selectedCategories.push(slug)
  else            store.selectedCategories.splice(idx, 1)
  applyFilters()
}

const toggleRating = (r) => {
  const idx = store.selectedRatings.indexOf(r)
  if (idx === -1) store.selectedRatings.push(r)
  else            store.selectedRatings.splice(idx, 1)
  applyFilters()
}

const selectPriceRange = (idx) => {
  store.selectedPriceRange.value = selectedPriceIndex.value === idx ? null : store.priceRanges[idx]
  applyFilters()
}

// ── Navigation helpers ──────────────────────────────────────────────────────
const pushSearch = (q) => {
  store.clearFilters()
  store.query = q
  router.push({ path: '/search', query: store.buildRouteQuery(1) })
}

const onSortChange = () => {
  router.replace({ query: store.buildRouteQuery(1) })
}

const applyFilters = () => {
  router.replace({ query: store.buildRouteQuery(1) })
}

const clearFilters = () => {
  store.clearFilters()
  router.replace({ path: '/search', query: { q: store.query } })
}

const goPage = (p) => {
  if (p < 1 || p > store.totalPages) return
  router.replace({ query: store.buildRouteQuery(p) })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ── Single watcher: URL → sync store → fetch ───────────────────────────────
const dataLayer = useDataLayer()

watch(
    () => route.query,
    (q) => {
      store.syncFromRoute(q)
      store.fetchResults(api.get).then(() => {
        if (store.query) dataLayer.search(store.query)
        if (products.value.length) {
          dataLayer.viewItemList(products.value, `Search: ${store.query}`)
        }
      })
    },
    { immediate: true, deep: true }
)

// ── Load full category list once (with global products_count) ───────────────
// fetchCategories sets sidebarCategories from /categories endpoint.
// Every subsequent fetchResults call then merges query-scoped counts on top.
useHead({ meta: [{ name: 'robots', content: 'noindex, follow' }] })

onMounted(() => {
  store.fetchCategories(api.get)
})
</script>