<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">

    <!-- Loading skeleton -->
    <div v-if="loadingSeller" class="h-48 bg-gray-200 animate-pulse rounded-2xl mb-6"></div>

    <!-- Store Header -->
    <div v-else-if="seller" class="relative overflow-hidden rounded-2xl mb-6 min-h-[200px] flex items-end" :style="`background:linear-gradient(135deg,${seller.cover_color ?? '#006A4E'},${seller.cover_color2 ?? '#004d38'})`">

      <img v-if="seller.banner && !bannerFailed" :src="seller.banner" alt="" class="absolute inset-0 w-full h-full object-cover" @error="bannerFailed = true"/>
      <!-- colour overlay: keeps the text readable on any cover image -->
      <div class="absolute inset-0" :style="`background:linear-gradient(to top,${seller.cover_color ?? '#004d38'}f2 0%,${seller.cover_color ?? '#006A4E'}b3 45%,${seller.cover_color ?? '#006A4E'}66 100%)`"></div>
      <div class="relative w-full p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center gap-5">
        <img :src="logoFailed ? fallbackLogo : sellerLogo" :alt="seller.shop_name" @error="logoFailed = true" class="w-20 h-20 rounded-2xl bg-white shadow-lg border-4 border-white/30 object-cover shrink-0"/>
        <div class="flex-1 text-white">
          <div class="flex items-center gap-2 flex-wrap mb-1">
            <h1 class="font-display font-extrabold text-2xl">{{ seller.shop_name }}</h1>
            <span v-if="seller.is_verified" class="badge bg-white/20 text-white border border-white/30 flex items-center gap-1">
              <span class="mi mi-sm">verified</span>{{ tr('যাচাইকৃত') }}
            </span>
            <span v-if="seller.is_top_seller" class="badge bg-gold text-white flex items-center gap-1">
              <span class="mi mi-sm">emoji_events</span>{{ tr('টপ সেলার') }}
            </span>
          </div>
          <p class="font-bengali text-white/70 text-sm mb-3">{{ seller.description }}</p>
          <div class="flex flex-wrap gap-4 text-sm font-bengali text-white/80">
            <span class="flex items-center gap-1"><span class="mi mi-sm">inventory_2</span>{{ seller.stats.total_products ?? 0 }} পণ্য</span>
            <span class="flex items-center gap-1"><span class="mi mi-sm">star</span>{{ seller.stats.average_rating ?? '৪.৫' }} রেটিং</span>
            <span class="flex items-center gap-1"><span class="mi mi-sm">shopping_bag</span>{{ seller.stats.total_sales ?? 0 }}+ বিক্রয়</span>
            <span class="flex items-center gap-1"><span class="mi mi-sm">calendar_today</span>সদস্য {{ joinedDate }}</span>
          </div>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
          <button @click="toggleFollow" class="bg-white text-primary font-display font-bold px-5 py-2.5 rounded-xl text-sm hover:bg-primary/10 transition-colors flex items-center gap-1.5">
            <span class="mi mi-sm">{{ isFollowing ? 'favorite' : 'favorite_border' }}</span>
            {{ isFollowing ? 'ফলো করছেন' : 'ফলো করুন' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div v-for="stat in storeStats" :key="stat.label" class="bg-white rounded-2xl shadow-card p-4 text-center">
        <span class="mi text-2xl mb-1 block" :class="stat.color">{{ stat.icon }}</span>
        <p class="font-display font-bold text-xl text-gray-800">{{ stat.value }}</p>
        <p class="font-bengali text-xs text-gray-500">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Products toolbar -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <h2 class="section-title flex items-center gap-2"><span class="mi text-primary">inventory_2</span>{{ tr('বিক্রেতার পণ্যসমূহ') }}</h2>
      <select v-model="sortBy" @change="loadProducts" class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-bengali outline-none focus:border-primary bg-white">
        <option value="">{{ tr('জনপ্রিয়তা অনুযায়ী') }}</option>
        <option value="newest">{{ tr('নতুন পণ্য আগে') }}</option>
        <option value="price_asc">{{ tr('দাম: কম → বেশি') }}</option>
        <option value="price_desc">{{ tr('দাম: বেশি → কম') }}</option>
        <option value="rating">{{ tr('সর্বোচ্চ রেটিং') }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loadingProducts" class="grid gap-4 mb-8" :class="productGridCols">
      <ProductSkeleton v-for="i in 10" :key="i"/>
    </div>

    <div v-else class="grid gap-4 mb-8" :class="productGridCols">
      <ProductCard v-for="product in products" :key="product.id" :product="product"/>
    </div>

    <div v-if="totalPages > 1" class="flex justify-center gap-2 mb-8">
      <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
        <span class="mi mi-sm">arrow_back</span>
      </button>
      <button v-for="p in visiblePages" :key="p" @click="goPage(p)" class="px-4 py-2 rounded-xl border text-sm" :class="p === currentPage ? 'bg-primary text-white border-primary' : 'border-gray-200 hover:border-primary'">{{ p }}</button>
      <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
        <span class="mi mi-sm">arrow_forward</span>
      </button>
    </div>

    <!-- Policies -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div v-for="policy in policies" :key="policy.title" class="bg-white rounded-2xl shadow-card p-4 flex items-start gap-3">
        <span class="mi text-2xl mt-0.5 shrink-0" :class="policy.color">{{ policy.icon }}</span>
        <div>
          <h3 class="font-display font-bold text-gray-800 mb-1">{{ policy.title }}</h3>
          <p class="font-bengali text-gray-500 text-sm">{{ policy.desc }}</p>
        </div>
      </div>
    </div>

    <!-- Reviews -->
    <div class="bg-white rounded-2xl shadow-card p-6">
      <h2 class="font-display font-bold text-xl text-gray-800 flex items-center gap-2 mb-5">
        <span class="mi text-primary">rate_review</span>{{ tr('বিক্রেতার রিভিউ') }}
        <span class="text-base font-normal text-gray-400">({{ seller?.review_count ?? 0 }})</span>
      </h2>
      <div v-if="loadingReviews" class="space-y-4">
        <div v-for="i in 3" :key="i" class="h-16 bg-gray-100 animate-pulse rounded-xl"></div>
      </div>
      <div v-else class="space-y-4">
        <div v-for="review in sellerReviews" :key="review.id" class="border-b border-gray-50 pb-4 last:border-0">
          <div class="flex items-center gap-3 mb-2">
            <img :src="reviewAvatar(review)" :alt="review.customer?.name" class="w-9 h-9 rounded-full object-cover shrink-0"/>
            <div>
              <p class="font-bengali font-semibold text-sm text-gray-800">{{ review.customer?.name }}</p>
              <div class="flex items-center gap-1">
                <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i <= review.rating ? 'mi-filled text-gold' : 'text-gray-300'" style="font-size:13px">star</span>
                <span class="text-xs text-gray-400 font-bengali ml-1">{{ formatDate(review.created_at) }}</span>
              </div>
            </div>
          </div>
          <p class="font-bengali text-sm text-gray-600">{{ review.review }}</p>
        </div>
        <p v-if="sellerReviews.length === 0" class="text-center font-bengali text-gray-400 py-4">{{ tr('কোনো রিভিউ নেই।') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout, productGridCols } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
definePageMeta({ layout: 'default' })

const route   = useRoute()
const api     = useApi()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase
const { normalise } = useProduct()

const seller        = ref(null)
const products      = ref([])
const sellerReviews = ref([])
const loadingSeller  = ref(true)
const loadingProducts = ref(true)
const loadingReviews  = ref(false)
const sortBy        = ref('')
const currentPage   = ref(1)
const totalPages    = ref(1)
const isFollowing   = ref(false)

const bannerFailed = ref(false)
const logoFailed   = ref(false)
const fallbackLogo = computed(() => `https://ui-avatars.com/api/?name=${encodeURIComponent(seller.value?.shop_name ?? 'S')}&background=006A4E&color=fff&size=120`)
const sellerLogo = computed(() => seller.value?.logo || seller.value?.avatar || fallbackLogo.value)

const joinedDate = computed(() => {
  const d = seller.value?.member_since
  return d ? new Date(d).toLocaleDateString('bn-BD', { year: 'numeric', month: 'long' }) : ''
})

const storeStats = computed(() => [
  { icon:'star',           value: `${seller.value?.rating ?? '৪.৫'}/৫`, label:'গড় রেটিং',        color:'text-gold'      },
  { icon:'thumb_up',       value: `${seller.value?.positive_rate ?? '৯৮'}%`, label:'ইতিবাচক রিভিউ', color:'text-primary'   },
  { icon:'local_shipping', value: seller.value?.avg_delivery ?? '১-২ দিন', label:'ডেলিভারি সময়',   color:'text-blue-500'  },
  { icon:'assignment_return', value:'৭ দিন', label:'রিটার্ন পলিসি',     color:'text-orange-500' },
])

const policies = [
  { icon:'verified_user',    title:'অফিশিয়াল ওয়ারেন্টি', desc:'সকল পণ্যে প্রস্তুতকারকের ওয়ারেন্টি প্রযোজ্য।',   color:'text-primary'      },
  { icon:'assignment_return', title:'৭ দিনের রিটার্ন',    desc:'পণ্য পেয়ে সন্তুষ্ট না হলে ৭ দিনে ফেরত দিন।',     color:'text-orange-500'   },
  { icon:'local_shipping',   title:'দ্রুত ডেলিভারি',       desc:'ঢাকায় ১ দিন, সারাদেশে ২-৩ কার্যদিবসে ডেলিভারি।', color:'text-blue-500'     },
]

const visiblePages = computed(() => {
  const pages = [], start = Math.max(1, currentPage.value - 2), end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const reviewAvatar = (review) => {
  const avatar = review.customer?.avatar ?? ''
  return avatar ? avatar : `https://ui-avatars.com/api/?name=${encodeURIComponent(review.customer?.name ?? 'U')}&size=80`
}

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString('bn-BD', { day:'numeric', month:'short', year:'numeric' }) : ''

const loadSeller = async () => {
  loadingSeller.value = true
  try {
    seller.value = await api.get(`/sellers/${route.params.slug}`)
    isFollowing.value = !!seller.value?.is_following
  } catch {}
  finally { loadingSeller.value = false }
}

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const params = { page: currentPage.value }
    if (sortBy.value) params.sort = sortBy.value
    const data = await api.get(`/sellers/${route.params.slug}/products`, params)
    products.value  = data.data ?? data ?? []
    totalPages.value = data.pagination.last_page ?? 1
  } catch {}
  finally { loadingProducts.value = false }
}

const loadReviews = async () => {
  loadingReviews.value = true
  try {
    const data = await api.get(`/sellers/${route.params.slug}/reviews`)
    sellerReviews.value = data.data ?? data ?? []
  } catch {}
  finally { loadingReviews.value = false }
}

const goPage = (p) => { currentPage.value = p; loadProducts(); window.scrollTo({ top: 0, behavior: 'smooth' }) }

const toggleFollow = async () => {
  const auth = useAuthStore()
  if (!auth.isLoggedIn) { navigateTo(`/auth/login?redirect=/seller/${route.params.slug}`); return }
  try {
    const res = await api.post(`/sellers/${route.params.slug}/follow`)
    isFollowing.value = res?.is_following ?? !isFollowing.value
    if (seller.value && res?.followers_count !== undefined) seller.value.followers_count = res.followers_count
  } catch {}
}

onMounted(async () => {
  await loadSeller()
  await Promise.all([loadProducts(), loadReviews()])
})
</script>