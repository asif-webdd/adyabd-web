<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm font-bengali text-gray-500 mb-4">
      <NuxtLink to="/" class="hover:text-primary flex items-center gap-1">
        <span class="mi mi-sm">home</span>{{ tr('হোম') }}
      </NuxtLink>
      <span class="mi mi-sm">chevron_right</span>
      <span class="text-gray-800 font-semibold">{{ tr('All Products') }}</span>
    </nav>

    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl mb-6 text-white min-h-[140px] flex items-center bg-gradient-to-br from-primary to-primary-dark">
      <div class="relative p-6 md:p-8 flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border-2 border-white/30">
          <span class="mi text-4xl text-white">storefront</span>
        </div>
        <div>
          <h1 class="font-display font-extrabold text-3xl mb-1">{{ tr('All Products') }}</h1>
          <p class="font-bengali text-white/80">{{ totalResults }} {{ tr('টি পণ্য') }}</p>
        </div>
      </div>
    </div>

    <!-- Category chips (jump to a category page) -->
    <div v-if="categories.length" class="flex gap-2 overflow-x-auto scrollbar-hide -mx-4 px-4 pb-1 mb-5 md:mx-0 md:px-0">
      <span class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-bengali font-medium bg-primary text-white shrink-0">{{ tr('All Products') }}</span>
      <NuxtLink v-for="c in categories" :key="c.slug" :to="`/category/${c.slug}`"
        class="whitespace-nowrap px-4 py-1.5 rounded-full text-sm font-bengali font-medium bg-white text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
        {{ catLabel(c) }}
      </NuxtLink>
    </div>

    <div class="flex gap-6">
      <!-- Filters Sidebar -->
      <aside class="w-64 shrink-0 hidden lg:block space-y-4">
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-4 flex items-center gap-2"><span class="mi mi-sm text-primary">payments</span>{{ tr('মূল্য পরিসীমা') }}</h3>
          <div class="space-y-3">
            <input v-model.number="minPrice" type="number" :placeholder="tr('সর্বনিম্ন ৳')" class="input-field text-sm"/>
            <input v-model.number="maxPrice" type="number" :placeholder="tr('সর্বোচ্চ ৳')" class="input-field text-sm"/>
            <button @click="applyFilters" class="w-full btn-secondary text-sm py-2 justify-center"><span class="mi mi-sm">filter_list</span>{{ tr('প্রয়োগ') }}</button>
          </div>
          <div class="mt-3 space-y-1.5">
            <label v-for="range in priceRanges" :key="range.label" class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="price" class="accent-primary" v-model="selectedRange" :value="range"/>
              <span class="text-sm font-bengali text-gray-600">{{ range.label }}</span>
            </label>
          </div>
        </div>
        <div v-if="brands.length" class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2"><span class="mi mi-sm text-primary">business</span>{{ tr('ব্র্যান্ড') }}</h3>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <label v-for="brand in brands" :key="brand.id" class="flex items-center justify-between cursor-pointer pr-1">
              <div class="flex items-center gap-2">
                <input type="checkbox" class="accent-primary rounded" v-model="selectedBrands" :value="brand.id" @change="applyFilters"/>
                <span class="text-sm font-bengali text-gray-700">{{ brand.name }}</span>
              </div>
              <span class="text-xs text-gray-400">({{ brand.products_count ?? 0 }})</span>
            </label>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2"><span class="mi mi-sm text-primary">star</span>{{ tr('রেটিং') }}</h3>
          <div class="space-y-2">
            <label v-for="r in [5,4,3,2,1]" :key="r" class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="rating" class="accent-primary" v-model="selectedRating" :value="r" @change="applyFilters"/>
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i<=r?'mi-filled text-gold':'text-gray-300'" style="font-size:14px">star</span>
              </div>
              <span class="text-xs font-bengali text-gray-500">{{ tr('ও উপরে') }}</span>
            </label>
          </div>
        </div>

        <button @click="clearFilters" class="w-full btn-outline justify-center text-sm">
          <span class="mi mi-sm">clear</span>{{ tr('ফিল্টার মুছুন') }}
        </button>
      </aside>

      <!-- Products -->
      <div class="flex-1">
        <div class="flex items-center justify-between mb-4">
          <p class="font-bengali text-gray-500 text-sm">{{ totalResults }} {{ tr('টি পণ্য') }}</p>
          <select v-model="sortBy" @change="applyFilters"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-bengali outline-none focus:border-primary bg-white">
            <option value="">{{ tr('সেরা মিল') }}</option>
            <option value="price_asc">{{ tr('দাম: কম → বেশি') }}</option>
            <option value="price_desc">{{ tr('দাম: বেশি → কম') }}</option>
            <option value="newest">{{ tr('নতুন আগে') }}</option>
            <option value="rating">{{ tr('সর্বোচ্চ রেটিং') }}</option>
          </select>
        </div>

        <div v-if="loading" :class="['grid gap-4', productGridColsNarrow]">
          <ProductSkeleton v-for="i in 12" :key="i"/>
        </div>

        <div v-else-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-card">
          <span class="mi text-6xl text-gray-200 block mb-4">search_off</span>
          <h3 class="font-display font-bold text-xl text-gray-700 mb-2">{{ tr('কোনো পণ্য পাওয়া যায়নি') }}</h3>
          <button @click="clearFilters" class="btn-outline mt-2">{{ tr('ফিল্টার মুছুন') }}</button>
        </div>

        <div v-else :class="['grid gap-4', productGridColsNarrow]">
          <ProductCard v-for="p in products" :key="p.id" :product="p"/>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex justify-center gap-2 mt-8">
          <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
            <span class="mi mi-sm">arrow_back</span>
          </button>
          <button v-for="p in visiblePages" :key="p" @click="goPage(p)" class="px-4 py-2 rounded-xl border text-sm" :class="p === currentPage ? 'bg-primary text-white border-primary' : 'border-gray-200 hover:border-primary'">{{ p }}</button>
          <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
            <span class="mi mi-sm">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useLandingPageStore } from '~/stores/LandingPage'

const { tr, isEn } = useLang()
const { shopName } = useShopName()
const { layout, productGridColsNarrow } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

definePageMeta({ layout: 'default' })

const api = useApi()
const dataLayer = useDataLayer()
const landingStore = useLandingPageStore()

const categories    = computed(() => landingStore.categories || [])
const catLabel      = (c) => (isEn.value ? (c.name || c.name_bn) : (c.name_bn || c.name))

const products      = ref([])
const totalResults  = ref(0)
const totalPages    = ref(1)
const currentPage   = ref(1)
const loading       = ref(true)
const sortBy        = ref('')
const minPrice      = ref(null)
const maxPrice      = ref(null)
const selectedRange = ref(null)
const brands         = ref([])
const selectedBrands = ref([])
const selectedRating = ref(null)

const priceRanges = [
  { label: 'সর্বোচ্চ ৳৫০০',    min: 0,     max: 500   },
  { label: '৳৫০০ – ৳২,০০০',    min: 500,   max: 2000  },
  { label: '৳২,০০০ – ৳৫,০০০',  min: 2000,  max: 5000  },
  { label: '৳৫,০০০ – ৳১০,০০০', min: 5000,  max: 10000 },
  { label: '৳১০,০০০+',          min: 10000, max: null  },
]

watch(selectedRange, (r) => {
  if (r) { minPrice.value = r.min; maxPrice.value = r.max }
  else   { minPrice.value = null;  maxPrice.value = null  }
  applyFilters()
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end   = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const buildParams = () => {
  const p = { page: currentPage.value }
  if (sortBy.value)           p.sort      = sortBy.value
  if (minPrice.value != null && minPrice.value !== '') p.min_price = minPrice.value
  if (maxPrice.value != null && maxPrice.value !== '') p.max_price = maxPrice.value
  if (selectedBrands.value.length) p.brands = selectedBrands.value.join(',')
  if (selectedRating.value)        p.rating = selectedRating.value
  return p
}

const applyProducts = (data) => {
  products.value     = normalizeProducts(data?.data || [])
  totalResults.value = data?.total     ?? products.value.length
  totalPages.value   = data?.last_page ?? 1
}

const loadProducts = async () => {
  loading.value = true
  try {
    applyProducts(await api.get('/products', buildParams()))
    if (import.meta.client && products.value.length) dataLayer.viewItemList(products.value, 'All products')
  } catch (e) {
    products.value = []
    totalResults.value = 0
  } finally {
    loading.value = false
  }
}

const applyFilters = () => { currentPage.value = 1; loadProducts() }

const goPage = async (p) => {
  if (p < 1 || p > totalPages.value) return
  currentPage.value = p
  await loadProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const clearFilters = () => {
  minPrice.value = null
  maxPrice.value = null
  selectedRange.value = null
  selectedBrands.value = []
  selectedRating.value = null
  sortBy.value = ''
  applyFilters()
}

useHead(computed(() => ({
  title: `${tr('All Products')} — ${shopName.value}`,
})))

// Server-side first page so crawlers and first paint see the products.
const { data: ssrProds } = await useAsyncData('all-products', () => api.get('/products', { page: 1 }).catch(() => null))
const hadServerData = !!ssrProds.value
if (hadServerData) {
  applyProducts(ssrProds.value)
  loading.value = false
}

onMounted(async () => {
  api.get('/products/filters').then(r => { brands.value = r?.brands || [] }).catch(() => {})
  if (!categories.value.length) landingStore.fetchHome()
  if (!hadServerData) await loadProducts()
})
</script>