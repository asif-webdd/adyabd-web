<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <!-- Breadcrumb Skeleton -->
    <div v-if="loading" class="flex items-center gap-2 mb-4">
      <div class="h-4 w-14 bg-gray-200 rounded animate-pulse"></div>
      <div class="h-4 w-4 bg-gray-200 rounded animate-pulse"></div>
      <div class="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
    </div>

    <!-- Real Breadcrumb -->
    <nav v-else class="flex items-center gap-2 text-sm font-bengali text-gray-500 mb-4">
      <NuxtLink to="/" class="hover:text-primary flex items-center gap-1">
        <span class="mi mi-sm">home</span>
        {{ tr('হোম') }}
      </NuxtLink>

      <span class="mi mi-sm">chevron_right</span>
      <span class="text-gray-800 font-semibold">{{ category?.name }}</span>
    </nav>

    <!-- Category Header skeleton -->
    <div v-if="loading" class="h-44 bg-gray-200 animate-pulse rounded-2xl mb-6"></div>

    <!-- Category Header -->
    <div v-else-if="category" class="relative overflow-hidden rounded-2xl mb-6 text-white min-h-[180px] flex items-center" :style="`background: linear-gradient(135deg, ${catColor1}, ${catColor2})`">
      <img v-if="category.banner_image" :src="`${apiBase}/storage/${category.banner_image}`" :alt="category.name" class="absolute inset-0 w-full h-full object-cover opacity-20"/>
      <div class="relative p-6 md:p-8 flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 border-2 border-white/30">
          <span v-if="category.icon" class="mi text-4xl text-white">{{ category.icon }}</span>
          <img v-else-if="category.image" :src="`${apiBase}/storage/${category.image}`" class="w-full h-full object-cover rounded-xl"/>
        </div>
        <div>
          <h1 class="font-display font-extrabold text-3xl mb-1">{{ category.name }}</h1>
          <p class="font-bengali text-white/80">{{ totalResults }} টি পণ্য পাওয়া গেছে</p>
          <p v-if="category.description" class="font-bengali text-white/60 text-sm mt-1">{{ category.description }}</p>
        </div>
      </div>
    </div>
    <SubcategoryFilter :loading="loading" :subcategories="subcategories" v-model:selectedSub="selectedSub" :loadProducts="loadProducts"/>
    <div class="flex gap-6">
      <!-- Filters Sidebar -->
      <aside class="w-64 shrink-0 hidden lg:block space-y-4">
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-4 flex items-center gap-2"><span class="mi mi-sm text-primary">payments</span>{{ tr('মূল্য পরিসীমা') }}</h3>
          <div class="space-y-3">
            <input v-model.number="minPrice" type="number" :placeholder="tr('সর্বনিম্ন ৳')" class="input-field text-sm"/>
            <input v-model.number="maxPrice" type="number" :placeholder="tr('সর্বোচ্চ ৳')" class="input-field text-sm"/>
            <button @click="loadProducts" class="w-full btn-secondary text-sm py-2 justify-center"><span class="mi mi-sm">filter_list</span>{{ tr('প্রয়োগ') }}</button>
          </div>
          <div class="mt-3 space-y-1.5">
            <label v-for="range in priceRanges" :key="range.label" class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="price" class="accent-primary" v-model="selectedRange" :value="range"/>
              <span class="text-sm font-bengali text-gray-600">{{ range.label }}</span>
            </label>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-card">
          <h3 class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2"><span class="mi mi-sm text-primary">business</span>{{ tr('ব্র্যান্ড') }}</h3>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <label v-for="brand in brands" :key="brand.id" class="flex items-center justify-between cursor-pointer pr-1">
              <div class="flex items-center gap-2">
                <input type="checkbox" class="accent-primary rounded" v-model="selectedBrands" :value="brand.id"/>
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
              <input type="checkbox" class="accent-primary rounded" v-model="selectedRatings" :value="r"/>
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
          <p class="font-bengali text-gray-500 text-sm">{{ totalResults }} টি পণ্য</p>
          <div class="flex items-center gap-3">
            <select v-model="sortBy" @change="loadProducts"
              class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-bengali outline-none focus:border-primary bg-white">
              <option value="">{{ tr('সেরা মিল') }}</option>
              <option value="price_asc">{{ tr('দাম: কম → বেশি') }}</option>
              <option value="price_desc">{{ tr('দাম: বেশি → কম') }}</option>
              <option value="newest">{{ tr('নতুন আগে') }}</option>
              <option value="rating">{{ tr('সর্বোচ্চ রেটিং') }}</option>
            </select>
            <button @click="viewMode='grid'" class="p-2 rounded-lg" :class="viewMode==='grid'?'bg-primary text-white':'text-gray-400 hover:bg-gray-100'"><span class="mi mi-sm">grid_view</span></button>
            <button @click="viewMode='list'" class="p-2 rounded-lg" :class="viewMode==='list'?'bg-primary text-white':'text-gray-400 hover:bg-gray-100'"><span class="mi mi-sm">view_list</span></button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" :class="viewMode==='grid' ? ['grid gap-4', productGridColsNarrow] : 'space-y-3'">
          <ProductSkeleton v-for="i in 12" :key="i"/>
        </div>

        <!-- Empty -->
        <div v-else-if="products.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-card">
          <span class="mi text-6xl text-gray-200 block mb-4">search_off</span>
          <h3 class="font-display font-bold text-xl text-gray-700 mb-2">{{ tr('কোনো পণ্য পাওয়া যায়নি') }}</h3>
          <button @click="clearFilters" class="btn-outline mt-2">{{ tr('ফিল্টার মুছুন') }}</button>
        </div>

        <!-- Grid -->
        <div v-else :class="viewMode==='grid' ? ['grid gap-4', productGridColsNarrow] : 'space-y-3'">
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
const { tr } = useLang()
const { shopName, helpline, helplineHref } = useShopName()
const { layout, productGridColsNarrow } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import SubcategoryFilter from "../../components/Skeletons/SubcategoryFilter.vue";

definePageMeta({ layout: 'default' })

const route   = useRoute()
const api     = useApi()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase
const dataLayer = useDataLayer()

const category       = ref(null)
const subcategories  = ref([])
const brands         = ref([])
const products       = ref([])
const totalResults   = ref(0)
const totalPages     = ref(1)
const currentPage    = ref(1)
const loading        = ref(true)
const loadingCategory = ref(true)
const viewMode       = ref('grid')
const sortBy         = ref('')
const selectedSub    = ref(null)
const minPrice       = ref(null)
const maxPrice       = ref(null)
const selectedRange  = ref(null)
const selectedBrands = ref([])
const selectedRatings = ref([])

// Gradient colours — fallback if no category
const catColor1 = computed(() => category.value?.color || '#166534')
const catColor2 = computed(() => category.value?.color2 || '#14532d')

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
  loadProducts()
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
  if (sortBy.value)              p.sort      = sortBy.value
  if (minPrice.value != null)    p.min_price = minPrice.value
  if (maxPrice.value != null)    p.max_price = maxPrice.value
  if (selectedBrands.value.length) p.brands = selectedBrands.value.join(',')
  if (selectedRatings.value.length) p.rating = Math.min(...selectedRatings.value)
  return p
}

const applyCategory = (data) => {
  category.value      = data
  subcategories.value = data.children ?? []
}

const applyProducts = (data) => {
  products.value     = normalizeProducts(data.data || [])
  totalResults.value = data.total     ?? products.value.length
  totalPages.value   = data.last_page ?? 1
  // Extract unique brands from results for sidebar
  if (!brands.value.length) {
    const seen = new Set()
    const brand = []
    ;(data.data || []).forEach(product => {
      if (product.brand && !seen.has(product.brand.name)) {
        seen.add(product.brand.name)
        brand.push({
          id: product.brand.id,
          name: product.brand.name,
          products_count: data.data.filter(prod => prod.brand_id === product.brand.id).length
        })
      }
    })
    brands.value = brand
  }
}

const trackList = (slug) => {
  if (import.meta.client && products.value.length) {
    dataLayer.viewItemList(products.value, category.value?.name ?? `Category: ${slug}`)
  }
}

const loadCategory = async () => {
  loadingCategory.value = true
  try {
    applyCategory(await api.get(`/categories/${route.params.slug}`))
  } catch {}
  finally {
    loadingCategory.value = false
  }
}

const loadProducts = async () => {
  loading.value = true
  try {
    const slug = selectedSub.value ?? route.params.slug
    applyProducts(await api.get(`/categories/${slug}/products`, buildParams()))
    trackList(slug)
  } catch(e) {
    products.value = []
    totalResults.value = 0
  } finally {
    loading.value = false
  }
}

// ── SEO: rendered on the server ───────────────────────────────────────────
const _siteUrl = useRuntimeConfig().public.siteUrl   // read in setup, not inside the head getter
useHead(computed(() => category.value ? {
  title: `${category.value.name} — ${shopName.value}`,
  meta: [
    { name: 'description', content: `${category.value.name} বিভাগে সেরা পণ্য কিনুন ${shopName.value}-এ।` },
    { property: 'og:title', content: category.value.name },
  ],
  link: [{ rel: 'canonical', href: `${_siteUrl}/category/${route.params.slug}` }],
} : {}))

const goPage = async (p) => {
  currentPage.value = p
  await loadProducts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const clearFilters = () => {
  minPrice.value       = null
  maxPrice.value       = null
  selectedRange.value  = null
  selectedBrands.value = []
  selectedRatings.value = []
  sortBy.value         = ''
  selectedSub.value    = null
  loadProducts()
}

// Server-side first page (default sort/filters) so crawlers and first paint see the products.
const { data: ssrCat } = await useAsyncData(`category:${route.params.slug}`, async () => {
  const slug = route.params.slug
  const [cat, prods] = await Promise.all([
    api.get(`/categories/${slug}`).catch(() => null),
    api.get(`/categories/${slug}/products`, { page: 1 }).catch(() => null),
  ])
  return { cat, prods }
})
const hadServerData = !!(ssrCat.value?.cat && ssrCat.value?.prods)
if (hadServerData) {
  applyCategory(ssrCat.value.cat)
  applyProducts(ssrCat.value.prods)
  loadingCategory.value = false
  loading.value = false
}

onMounted(async () => {
  if (hadServerData) { trackList(route.params.slug); return }
  await loadCategory()
  await loadProducts()
})

watch(() => route.params.slug, async () => {
  category.value = null
  products.value = []
  currentPage.value = 1
  await loadCategory()
  await loadProducts()
})
</script>
