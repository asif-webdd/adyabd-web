<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="section-title flex items-center gap-2">
        <span class="mi text-accent">favorite</span>{{ tr('উইশলিস্ট') }}
        <span class="text-gray-400 text-lg font-normal">({{ wishlistStore.count }})</span>
      </h1>
      <button v-if="normalised.length" @click="addAllToCart" :disabled="addingAll" class="btn-primary text-sm py-2.5 disabled:opacity-60">
        <span v-if="addingAll" class="mi mi-sm animate-spin">refresh</span>
        <span v-else class="mi mi-sm">add_shopping_cart</span>
        {{ addingAll ? 'যোগ হচ্ছে...' : 'সব কার্টে যোগ করুন' }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="wishlistStore.loading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <ProductSkeleton v-for="i in 6" :key="i"/>
    </div>

    <div v-else-if="normalised.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-4">
      <div v-for="item in normalised" :key="item.id" class="product-card group">
        <div class="relative aspect-square overflow-hidden bg-gray-50">
          <NuxtLink :to="`/product/${item.slug}`">
            <img :src="item.image" :alt="item.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"/>
          </NuxtLink>
          <button @click="wishlistStore.remove(item.id)"
            class="absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-gray-400 hover:text-accent transition-colors">
            <span class="mi mi-sm">close</span>
          </button>
          <span v-if="item.discount" class="absolute top-2 left-2 badge bg-accent text-white">{{ item.discount }}% ছাড়</span>
        </div>
        <div class="p-3">
          <p class="text-xs text-gray-400 font-bengali mb-1">{{ item.seller }}</p>
          <NuxtLink :to="`/product/${item.id}`" class="font-bengali font-medium text-gray-800 text-sm line-clamp-2 mb-2 hover:text-primary transition-colors block leading-snug">
            {{ item.name }}
          </NuxtLink>
          <div class="flex items-baseline gap-2 mb-1">
            <span class="font-display font-bold text-accent">৳{{ item.price.toLocaleString() }}</span>
            <span v-if="item.originalPrice > item.price" class="text-xs text-gray-400 line-through">
              ৳{{ item.originalPrice.toLocaleString() }}
            </span>
          </div>
          <p class="text-xs font-bengali mb-3 flex items-center gap-1" :class="item.inStock ? 'text-green-600' : 'text-accent'">
            <span class="mi mi-sm">{{ item.inStock ? 'check_circle' : 'cancel' }}</span>
            {{ item.inStock ? 'স্টকে আছে' : 'স্টক নেই' }}
          </p>
          <button @click="addOneToCart(item)" :disabled="!item.inStock" class="w-full text-sm font-bengali font-semibold py-2 rounded-xl transition-colors flex items-center justify-center gap-1.5"
            :class="item.inStock ? 'bg-primary text-white hover:bg-primary-dark' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
            <span class="mi mi-sm">add_shopping_cart</span>
            {{ item.inStock ? 'কার্টে যোগ করুন' : 'স্টক নেই' }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-card p-12 text-center">
      <span class="mi text-gray-200" style="font-size:72px">heart_broken</span>
      <h3 class="font-display font-bold text-xl text-gray-700 mt-4 mb-2">{{ tr('উইশলিস্ট খালি') }}</h3>
      <p class="font-bengali text-gray-500 mb-5">
        {{ tr('পছন্দের পণ্যে') }} <span class="mi text-accent text-sm align-middle">favorite</span> চিহ্ন দিন।
      </p>
      <NuxtLink to="/" class="btn-primary inline-flex">
        <span class="mi mi-sm">shopping_bag</span>{{ tr('কেনাকাটা শুরু করুন') }}
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
import { useWishlistStore } from '~/stores/wishlist'
import { useCartStore }     from '~/stores/cart'

const wishlistStore = useWishlistStore()
const cartStore     = useCartStore()
const config        = useRuntimeConfig()
const apiBase       = config.public.apiBase
const addingAll     = ref(false)

const normalised = computed(() =>
  (wishlistStore.items ?? []).map(w => {
    const p   = w.product ?? w
    const img = p.primary_image?.full_url_image ?? p.thumbnail ?? p.image ?? ''
    return {
      id:            p.id,
      slug:          p.slug,
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

const addOneToCart = async (item) => {
  if (!item.inStock) return
  await cartStore.add(item.id)
  if (process.client && window.$toast) {
    window.$toast({ type: 'success', title: 'কার্টে যোগ হয়েছে!', duration: 2000 })
  }
}

const addAllToCart = async () => {
  addingAll.value = true
  for (const item of normalised.value.filter(i => i.inStock)) {
    await cartStore.add(item.id)
  }
  addingAll.value = false
  if (process.client && window.$toast) {
    window.$toast({ type: 'success', title: 'সব পণ্য কার্টে যোগ হয়েছে!', duration: 2500 })
  }
}

onMounted(() => wishlistStore.fetch())
</script>
