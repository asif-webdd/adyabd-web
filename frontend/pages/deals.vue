<script setup>
const { tr } = useLang()
const { layout, productGridCols, flashSaleGridCols } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
// ── Countdown Timer ───────────────────────────────────────────────────
const toBn = n => String(n).padStart(2,'0').replace(/[0-9]/g, d=>'০১২৩৪৫৬৭৮৯'[d])
const countdown = ref([{v:'০৪',l:'h'},{v:'৩২',l:'m'},{v:'১৫',l:'s'}])
let s = 15, m = 32, h = 4, timer
onMounted(() => {
  timer = setInterval(() => {
    s--
    if (s < 0) { s = 59; m-- }
    if (m < 0) { m = 59; h-- }
    if (h < 0) { h = m = s = 0 }
    countdown.value[0].v = toBn(h)
    countdown.value[1].v = toBn(m)
    countdown.value[2].v = toBn(s)
  }, 1000)
})
onUnmounted(() => clearInterval(timer))

// ── API & Data ────────────────────────────────────────────────────────
const api    = useApi()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const { normalise } = useProduct()

const activeCat     = ref({ icon: 'flash_on', label: 'সব ডিল' })
const featuredDeal  = ref(null)
const flashDeals    = ref([])
const coupons       = ref([])
const moreProducts  = ref([])
const loading       = ref(true)

const dealCategories = [
  { icon: 'flash_on',    label: 'সব ডিল'    },
  { icon: 'smartphone',  label: 'ইলেকট্রনিক্স' },
  { icon: 'checkroom',   label: 'ফ্যাশন'    },
  { icon: 'home',        label: 'গৃহস্থালি'  },
  { icon: 'spa',         label: 'সৌন্দর্য'   },
]

const loadDeals = async () => {
  loading.value = true
  try {
    const [dealsData, couponsData, productsData] = await Promise.all([
      api.get('/deals/flash').catch(() => []),
      api.get('/coupons/public').catch(() => []),
      api.get('/products/trending', { limit: 12 }).catch(() => []),
    ])

    const deals = dealsData.data ?? dealsData ?? []
    if (deals.length > 0) {
      const [first, ...rest] = deals
      featuredDeal.value = first
      flashDeals.value   = rest
    }
    coupons.value      = couponsData.data ?? couponsData ?? []
    moreProducts.value = (productsData.data ?? productsData ?? []).map(normalise)
  } catch {}
  finally { loading.value = false }
}

const filterByCategory = async (cat) => {
  activeCat.value = cat
  if (cat.label === 'সব ডিল') { await loadDeals(); return }
  loading.value = true
  try {
    const catSlugMap = {
      'ইলেকট্রনিক্স': 'electronics',
      'ফ্যাশন':       'fashion',
      'গৃহস্থালি':    'home-living',
      'সৌন্দর্য':     'beauty',
    }
    const slug = catSlugMap[cat.label]
    const data = await api.get(`/deals/flash`, { category: slug })
    const deals = data.data ?? data ?? []
    if (deals.length > 0) {
      const [first, ...rest] = deals
      featuredDeal.value = first
      flashDeals.value   = rest
    } else {
      featuredDeal.value = null
      flashDeals.value   = []
    }
  } catch {}
  finally { loading.value = false }
}

const dealImage = (deal) => deal?.primary_image?.full_url_image ?? deal?.thumbnail ?? deal?.image ?? ''

const copyCoupon = (code) => {
  navigator.clipboard.writeText(code).catch(() => {})
  if (process.client && window.$toast) {
    window.$toast({ type: 'success', title: `কোড "${code}" কপি হয়েছে!`, duration: 2000 })
  }
}

// Coupon colours fallback
const couponColor = (i) => ['bg-accent','bg-bkash','bg-primary','bg-blue-500','bg-pink-500','bg-green-500'][i % 6]

onMounted(loadDeals)
</script>

<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 md:pb-8']">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl mb-8 p-5 md:p-10 text-white text-center" style="background:linear-gradient(135deg,#F42A41,#c4001f)">
      <div class="absolute inset-0 opacity-20">
        <img src="/images/banner-pattern.svg" class="w-full h-full object-cover"/>
      </div>
      <div class="relative">
        <span class="mi text-yellow-300" style="font-size:48px">local_fire_department</span>
        <h1 class="font-display font-extrabold text-3xl md:text-4xl mb-2">{{ tr('সেরা ডিলস ও অফার') }}</h1>
        <p class="font-bengali text-red-100 mb-4">{{ tr('প্রতিদিন নতুন অফার — সীমিত সময়, সীমিত স্টক!') }}</p>
        <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur px-5 py-2.5 rounded-full border border-white/30">
          <span class="mi mi-sm">timer</span>
          <span class="font-bengali text-sm">{{ tr('পরবর্তী ডিল শুরু হবে:') }}</span>
          <div class="flex gap-1.5 font-display font-bold">
            <span v-for="u in countdown" :key="u.l" class="bg-white/30 px-2 py-0.5 rounded">{{ u.v }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Filter -->
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6">
      <button v-for="cat in dealCategories" :key="cat.label" @click="filterByCategory(cat)"
        class="whitespace-nowrap flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0"
        :class="activeCat.label===cat.label ? 'bg-accent text-white border-accent' : 'bg-white text-gray-600 border-gray-200 hover:border-accent'">
        <span class="mi mi-sm">{{ cat.icon }}</span>{{ cat.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-6">
      <div class="h-48 bg-gray-200 animate-pulse rounded-2xl"></div>
      <div class="grid gap-4" :class="flashSaleGridCols">
        <div v-for="i in 8" :key="i" class="h-40 bg-gray-200 animate-pulse rounded-2xl"></div>
      </div>
    </div>

    <template v-else>
      <!-- Featured Deal -->
      <div v-if="featuredDeal" class="bg-gradient-to-r from-orange-500 to-yellow-500 rounded-2xl overflow-hidden mb-6 flex flex-col md:flex-row items-center">
        <div class="md:w-64 shrink-0 py-3">
          <img :src="dealImage(featuredDeal)" :alt="featuredDeal.name" class="rounded-xl w-full h-48 md:h-full object-cover"/>
        </div>
        <div class="flex-1 p-3 text-white text-center md:text-left">
          <span class="badge bg-white/20 border border-white/30 text-white mb-2 inline-flex items-center gap-1">
            <span class="mi mi-sm">bolt</span>{{ tr('আজকের ডিল অফ দ্য ডে') }}
          </span>
          <h2 class="font-display font-extrabold text-2xl md:text-3xl mb-2">{{ featuredDeal.name }}</h2>
          <p v-if="featuredDeal.quantity" class="font-bengali text-orange-100 mb-3 flex items-center gap-1 justify-center md:justify-start">
            <span class="mi mi-sm">inventory</span>মাত্র {{ featuredDeal.quantity }}টি স্টক বাকি!
          </p>
          <div class="flex items-baseline gap-3 justify-center md:justify-start mb-4">
            <span class="font-display font-extrabold text-3xl">৳{{ Number(featuredDeal.price).toLocaleString() }}</span>
            <span v-if="featuredDeal.compare_price > featuredDeal.price" class="text-orange-200 line-through text-xl">৳{{ Number(featuredDeal.compare_price).toLocaleString() }}</span>
            <span v-if="featuredDeal.discount_percentage" class="badge bg-white text-orange-500 font-bold">{{ featuredDeal.discount_percentage }}% ছাড়</span>
          </div>
          <div v-if="featuredDeal.sold_percentage" class="max-w-xs mx-auto md:mx-0 mb-4">
            <div class="flex justify-between text-xs font-bengali text-orange-100 mb-1">
              <span>বিক্রিত: {{ featuredDeal.sold_percentage }}%</span>
              <span class="font-bold">মাত্র {{ featuredDeal.quantity ?? '?' }}টি বাকি!</span>
            </div>
            <div class="h-2 bg-white/30 rounded-full">
              <div class="h-full bg-white rounded-full" :style="`width:${featuredDeal.sold_percentage}%`"></div>
            </div>
          </div>
        </div>
        <NuxtLink :to="`/product/${featuredDeal.slug ?? featuredDeal.id}`" class="m-4 shrink-0 bg-white text-orange-500 font-display font-bold px-8 py-3 rounded-xl hover:bg-orange-50 transition-colors flex items-center gap-2">
          <span class="mi">shopping_cart</span>{{ tr('এখনই কিনুন') }}
        </NuxtLink>
      </div>

      <!-- Flash Deals -->
      <div v-if="flashDeals.length" class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="section-title flex items-center gap-2"><span class="mi text-accent">bolt</span>{{ tr('ফ্ল্যাশ ডিল') }}</h2>
          <span class="badge bg-accent text-white animate-pulse flex items-center gap-1"><span class="mi mi-sm">circle</span>{{ tr('লাইভ') }}</span>
        </div>
        <div class="grid gap-4" :class="flashSaleGridCols">
          <div v-for="deal in flashDeals" :key="deal.id" class="bg-white rounded-2xl shadow-card overflow-hidden group hover:-translate-y-1 transition-transform">
            <div class="relative aspect-square overflow-hidden bg-gray-50">
              <NuxtLink :to="`/product/${deal.slug ?? deal.id}`">
                <img :src="dealImage(deal)" :alt="deal.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
              </NuxtLink>
              <div class="absolute top-2 left-2 flex flex-col gap-1">
                <span class="badge bg-accent text-white text-xs">{{ deal.discount_percentage ?? 0 }}% ছাড়</span>
              </div>
            </div>
            <div class="p-3">
              <NuxtLink :to="`/product/${deal.slug ?? deal.id}`" class="font-bengali font-medium text-gray-800 text-sm line-clamp-2 mb-2 hover:text-primary transition-colors block leading-snug">{{ deal.name }}</NuxtLink>
              <div class="flex items-baseline gap-1.5 mb-2">
                <span class="font-display font-bold text-accent text-sm">৳{{ Number(deal.price).toLocaleString() }}</span>
                <span v-if="deal.compare_price > deal.price" class="text-xs text-gray-400 line-through">৳{{ Number(deal.compare_price).toLocaleString() }}</span>
              </div>
              <div v-if="deal.sold_percentage" class="mb-2">
                <div class="flex justify-between text-xs font-bengali text-gray-500 mb-1">
                  <span>বিক্রিত {{ deal.sold_percentage }}%</span>
                  <span class="text-accent font-semibold">{{ deal.quantity ?? 0 }}টি বাকি</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-accent rounded-full" :style="`width:${deal.sold_percentage}%`"></div>
                </div>
              </div>
              <NuxtLink :to="`/product/${deal.slug ?? deal.id}`" class="w-full text-sm bg-accent text-white py-2 rounded-xl font-bengali font-semibold hover:bg-accent-dark transition-colors flex items-center justify-center gap-1">
                <span class="mi mi-sm">shopping_cart</span>{{ tr('কিনুন') }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Coupons -->
      <div v-if="coupons.length" class="mb-8">
        <h2 class="section-title flex items-center gap-2 mb-4"><span class="mi text-primary">confirmation_number</span>{{ tr('কুপন কোড') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="(coupon, i) in coupons" :key="coupon.code" class="text-white rounded-2xl p-4 flex items-center gap-4 relative overflow-hidden cursor-pointer hover:-translate-y-0.5 transition-transform" :class="couponColor(i)">
            <div class="absolute right-0 top-0 bottom-0 w-20 opacity-10">
              <div class="w-20 h-20 bg-white rounded-full -translate-x-4 -translate-y-4"></div>
              <div class="w-20 h-20 bg-white rounded-full -translate-x-4 translate-y-4"></div>
            </div>
            <div class="flex-1 relative">
              <p class="font-display font-extrabold text-lg mb-0.5">{{ coupon.code }}</p>
              <p class="font-bengali text-white/80 text-xs mb-1">{{ coupon.description }}</p>
              <p v-if="coupon.expires_at" class="font-bengali text-white/60 text-xs flex items-center gap-0.5">
<!--                <span class="mi mi-sm">schedule</span>{{ formatExpiry(coupon.expires_at) }} পর্যন্ত-->
                <span class="mi mi-sm">schedule</span>{{ coupon.expires_at }} পর্যন্ত
              </p>
            </div>
            <button @click="copyCoupon(coupon.code)"
              class="bg-white/20 border border-white/30 px-3 py-2 rounded-xl text-xs font-bengali font-semibold hover:bg-white/30 transition-colors shrink-0 flex items-center gap-1 relative">
              <span class="mi mi-sm">content_copy</span>{{ tr('কপি') }}
            </button>
          </div>
        </div>
      </div>

      <!-- More Products -->
      <div v-if="moreProducts.length">
        <h2 class="section-title flex items-center gap-2 mb-4"><span class="mi text-primary">local_fire_department</span>{{ tr('আরও অফার') }}</h2>
        <div class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in moreProducts" :key="p.id" :product="p"/>
        </div>
      </div>
    </template>
  </div>
</template>
