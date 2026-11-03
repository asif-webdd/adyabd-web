<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="section-title flex items-center gap-2"><span class="mi text-accent">favorite</span>{{ tr('আমার উইশলিস্ট') }}</h1>
        <p class="font-bengali text-gray-500 text-sm mt-1">{{ wishlistStore.count }}টি পণ্য সংরক্ষিত</p>
      </div>
      <button v-if="normalised.length" @click="addAllToCart" :disabled="addingAll" class="btn-primary text-sm py-2.5 disabled:opacity-60">
        <span v-if="addingAll" class="mi mi-sm animate-spin">refresh</span>
        <span v-else class="mi mi-sm">add_shopping_cart</span>
        {{ addingAll ? 'যোগ হচ্ছে...' : 'সব কার্টে যোগ করুন' }}
      </button>
    </div>

    <!-- Not logged in -->
    <div v-if="!authStore.isLoggedIn" class="text-center py-20 bg-white rounded-2xl shadow-card">
      <span class="mi text-6xl text-gray-200 block mb-4">favorite</span>
      <p class="font-bengali text-gray-500 mb-4">{{ tr('উইশলিস্ট দেখতে লগইন করুন') }}</p>
      <NuxtLink to="/auth/login?redirect=/wishlist" class="btn-primary">{{ tr('লগইন করুন') }}</NuxtLink>
    </div>

    <!-- Loading -->
    <div v-else-if="wishlistStore.loading" class="grid gap-4" :class="productGridColsNarrow">
      <ProductSkeleton v-for="i in 8" :key="i"/>
    </div>

    <template v-else>
      <!-- Promo -->
      <div v-if="normalised.length" class="bg-gradient-to-r from-accent to-accent-dark rounded-2xl p-4 mb-6 flex items-center justify-between text-white">
        <div>
          <p class="font-display font-bold text-lg flex items-center gap-2"><span class="mi">local_offer</span>{{ tr('উইশলিস্ট পণ্যে বিশেষ ছাড়!') }}</p>
          <p class="font-bengali text-red-100 text-sm">{{ tr('আজই কিনুন এবং অতিরিক্ত ৫% ছাড় পান।') }}</p>
        </div>
        <span class="mi text-4xl text-white/50">card_giftcard</span>
      </div>

      <div v-if="normalised.length" class="grid gap-4" :class="productGridColsNarrow">
        <div v-for="item in normalised" :key="item.id" class="product-card group">
          <div class="relative aspect-square overflow-hidden bg-gray-50">
            <NuxtLink :to="`/product/${item.id}`">
              <img :src="item.image" :alt="item.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"/>
            </NuxtLink>
            <button @click="removeItem(item.id)" class="absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-400 hover:text-accent transition-colors">
              <span class="mi mi-sm">close</span>
            </button>
            <span v-if="item.discount" class="absolute top-2 left-2 badge bg-accent text-white">{{ item.discount }}% ছাড়</span>
          </div>
          <div class="p-3">
            <p class="text-xs text-gray-400 font-bengali mb-1">{{ item.seller }}</p>
            <NuxtLink :to="`/product/${item.id}`" class="font-bengali font-medium text-gray-800 text-sm line-clamp-2 mb-2 hover:text-primary transition-colors block leading-snug">{{ item.name }}</NuxtLink>
            <div class="flex items-baseline gap-2 mb-1">
              <span class="font-display font-bold text-accent">৳{{ item.price.toLocaleString() }}</span>
              <span v-if="item.originalPrice > item.price" class="text-xs text-gray-400 line-through">৳{{ item.originalPrice.toLocaleString() }}</span>
            </div>
            <p class="text-xs font-bengali mb-3 flex items-center gap-1" :class="item.inStock ? 'text-green-600' : 'text-accent'">
              <span class="mi mi-sm">{{ item.inStock ? 'check_circle' : 'cancel' }}</span>
              {{ item.inStock ? 'স্টকে আছে' : 'স্টক নেই' }}
            </p>
            <button @click="addOne(item)" :disabled="!item.inStock"
              class="w-full text-sm font-bengali font-semibold py-2 rounded-xl transition-colors flex items-center justify-center gap-1.5"
              :class="item.inStock ? 'bg-primary text-white hover:bg-primary-dark' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
              <span class="mi mi-sm">add_shopping_cart</span>
              {{ item.inStock ? 'কার্টে যোগ করুন' : 'স্টক নেই' }}
            </button>
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-card p-16 text-center">
        <span class="mi text-gray-200" style="font-size:80px">heart_broken</span>
        <h2 class="font-display font-bold text-2xl text-gray-700 mt-4 mb-3">{{ tr('উইশলিস্ট খালি!') }}</h2>
        <p class="font-bengali text-gray-500 mb-6">{{ tr('পছন্দের পণ্যে') }} <span class="mi text-accent text-sm align-middle">favorite</span> চিহ্ন দিয়ে সংরক্ষণ করুন।</p>
        <NuxtLink to="/" class="btn-primary inline-flex"><span class="mi mi-sm">shopping_bag</span> {{ tr('কেনাকাটা শুরু করুন') }}</NuxtLink>
      </div>

      <!-- Suggestions -->
      <div v-if="normalised.length && suggestions.length" class="mt-10">
        <h2 class="section-title mb-5 flex items-center gap-2"><span class="mi text-primary">recommend</span>{{ tr('আপনি পছন্দ করতে পারেন') }}</h2>
        <div class="grid gap-4" :class="productGridCols">
          <ProductCard v-for="p in suggestions" :key="p.id" :product="p"/>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout, productGridCols, productGridColsNarrow } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import { useWishlistStore } from '~/stores/wishlist'
import { useAuthStore }     from '~/stores/auth'
import { useCartStore }     from '~/stores/cart'

definePageMeta({ layout: 'default', middleware: 'auth' })

const wishlistStore = useWishlistStore()
const authStore     = useAuthStore()
const cartStore     = useCartStore()
const api           = useApi()
const config        = useRuntimeConfig()
const apiBase       = config.public.apiBase
const { normalise } = useProduct()

const addingAll  = ref(false)
const suggestions = ref([])

// Normalise wishlist items from API shape → ProductCard shape
const normalised = computed(() => (wishlistStore.items ?? []).map(w => {
    const p = w.product ?? w
    const img = p.primary_image?.full_url_image ?? p.thumbnail ?? p.image ?? ''
    return {
      id:            p.id,
      name:          p.name,
      seller:        p.seller?.shop_name ?? '',
      price:         Number(p.price ?? 0),
      originalPrice: Number(p.compare_price ?? p.price ?? 0),
      discount:      p.discount_percentage ?? 0,
      image:         img ? img : '',
      inStock:       p.is_active !== false && (p.quantity ?? 1) > 0,
    }
  })
)

const removeItem = async (id) => {
  await wishlistStore.remove(id)
}

const addOne = async (item) => {
  if (!item.inStock) return
  const ok = await cartStore.add(item.id)
  if (ok && process.client && window.$toast) {
    window.$toast({ type: 'success', title: 'কার্টে যোগ হয়েছে!', duration: 2000 })
  }
}

const addAllToCart = async () => {
  addingAll.value = true
  const inStockItems = normalised.value.filter(i => i.inStock)
  for (const item of inStockItems) {
    await cartStore.add(item.id)
  }
  addingAll.value = false
  if (process.client && window.$toast) {
    window.$toast({ type: 'success', title: `${inStockItems.length}টি পণ্য কার্টে যোগ হয়েছে!`, duration: 2500 })
  }
}

// Load trending as suggestions
const loadSuggestions = async () => {
  try {
    const data = await api.get('/products/trending', { limit: 5 })
    suggestions.value = (data ?? []).map(normalise)
  } catch {}
}

onMounted(async () => {
  await wishlistStore.fetch()
  await loadSuggestions()
})
</script>
