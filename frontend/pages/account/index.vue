<template>
  <div class="space-y-5">
    <h1 class="section-title flex items-center gap-2">
      <span class="mi text-primary">dashboard</span> {{ tr('আমার ড্যাশবোর্ড') }}
    </h1>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="card h-24 animate-pulse bg-gray-100"></div>
    </div>

    <!-- Quick Stats -->
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="card text-center">
        <span class="mi text-3xl mb-2 block" :class="stat.color">{{ stat.icon }}</span>
        <p class="font-display font-bold text-2xl text-gray-800">{{ stat.value }}</p>
        <p class="font-bengali text-xs text-gray-500 mt-0.5">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-2xl shadow-card overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-50">
        <h2 class="font-display font-bold text-lg text-gray-800 flex items-center gap-2"><span class="mi text-primary">inventory_2</span>{{ tr('সাম্প্রতিক অর্ডার') }}</h2>
        <NuxtLink to="/account/orders" class="text-primary text-sm font-bengali hover:underline flex items-center gap-1">
          {{ tr('সব দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
        </NuxtLink>
      </div>
      <div v-if="loadingOrders" class="p-4 space-y-3">
        <div v-for="i in 3" :key="i" class="h-14 bg-gray-100 animate-pulse rounded-xl"></div>
      </div>
      <div v-else-if="recentOrders.length === 0" class="p-8 text-center">
        <span class="mi text-3xl text-gray-200 block mb-2">inbox</span>
        <p class="font-bengali text-gray-400 text-sm">{{ tr('কোনো অর্ডার নেই।') }}</p>
      </div>
      <div v-else class="divide-y divide-gray-50">
        <!-- full_url_image -->
        <div v-for="order in recentOrders" :key="order.id" class="p-4 flex items-center gap-4">
          <img :src="order.items?.[0]?.product_image ? order.items[0].product_image : '/images/placeholder.svg'" :alt="order.items?.[0]?.product_name" class="w-12 h-12 object-cover rounded-xl bg-gray-50 shrink-0"/>
          <div class="flex-1 min-w-0">
            <p class="font-bengali font-semibold text-gray-800 text-sm line-clamp-1">{{ order.items?.[0]?.product_name }}</p>
            <p class="font-bengali text-xs text-gray-400">অর্ডার #{{ order.order_number }} · {{ formatDate(order.created_at) }}</p>
          </div>
          <div class="text-right shrink-0">
            <p class="font-display font-bold text-accent">৳{{ Number(order.total_amount).toLocaleString() }}</p>
            <span class="badge text-xs" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Wishlist Preview -->
    <div class="bg-white rounded-2xl shadow-card overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b border-gray-50">
        <h2 class="font-display font-bold text-lg text-gray-800 flex items-center gap-2"><span class="mi text-accent">favorite</span>{{ tr('উইশলিস্ট') }}</h2>
        <NuxtLink to="/account/wishlist" class="text-primary text-sm font-bengali hover:underline flex items-center gap-1">
          {{ tr('সব দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
        </NuxtLink>
      </div>
      <div v-if="wishlistItems.length === 0" class="p-6 text-center">
        <span class="mi text-3xl text-gray-200 block mb-2">heart_broken</span>
        <p class="font-bengali text-gray-400 text-sm">{{ tr('উইশলিস্ট খালি।') }}</p>
      </div>
      <div v-else class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        <NuxtLink v-for="item in wishlistItems.slice(0,4)" :key="item.product_id ?? item.id" :to="`/product/${item.product?.id ?? item.product_id}`" class="relative rounded-xl overflow-hidden cursor-pointer hover:-translate-y-0.5 transition-transform group">
          <img :src="item.product?.image" :alt="item.product?.name" class="w-full aspect-square object-cover"/>
          <div class="p-2">
            <p class="font-bengali text-xs text-gray-700 line-clamp-1 mb-0.5">{{ item.product?.name }}</p>
            <p class="font-display font-bold text-accent text-sm">৳{{ Number(item.product?.price ?? 0).toLocaleString() }}</p>
          </div>
          <button @click.prevent="addToCart(item.product?.id, item.product)" class="mt-1 w-full text-xs bg-primary text-white py-1.5 rounded-lg font-bengali hover:bg-green-800 transition-colors flex items-center justify-center gap-1">
            <span class="mi mi-sm">add_shopping_cart</span> কার্টে
          </button>
        </NuxtLink>
      </div>
    </div>

    <!-- Wallet Balance -->
    <div class="bg-gradient-to-r from-primary to-green-700 text-white rounded-2xl p-5 flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="font-bengali text-green-200 text-sm flex items-center gap-1"><span class="mi mi-sm">account_balance_wallet</span>{{ branding.shop_name_bn || branding.shop_name }} ওয়ালেট</p>
        <p class="font-display font-extrabold text-3xl">৳ {{ Number(walletBalance).toLocaleString('bn-BD', { minimumFractionDigits: 2 }) }}</p>
        <p class="font-bengali text-green-200 text-xs mt-0.5">{{ tr('সর্বশেষ আপডেট: এইমাত্র') }}</p>
      </div>
      <div class="flex flex-col gap-2">
        <NuxtLink to="/account/wallet" class="bg-white text-primary font-display font-bold px-5 py-2 rounded-xl text-sm hover:bg-green-50 transition-colors flex items-center gap-1 justify-center">
          <span class="mi mi-sm">account_balance_wallet</span> {{ tr('উত্তোলন করুন') }}
        </NuxtLink>
        <NuxtLink to="/account/wallet" class="bg-white/20 border border-white/30 text-white font-display font-semibold px-5 py-2 rounded-xl text-sm hover:bg-white/30 transition-colors flex items-center gap-1 justify-center">
          <span class="mi mi-sm">history</span> {{ tr('ইতিহাস') }}
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { branding } = useAppearance()
import { useAuthStore }     from '~/stores/auth'
import { useWishlistStore } from '~/stores/wishlist'
import { useCartStore }     from '~/stores/cart'

const api           = useAuthStore()
const wishlistStore = useWishlistStore()
const cartStore     = useCartStore()
const apiClient     = useApi()
const config        = useRuntimeConfig()
const apiBase       = config.public.apiBase

const loading       = ref(true)
const loadingOrders = ref(true)
const dashboard     = ref(null)
const recentOrders  = ref([])
const walletBalance = ref(0)

const stats = computed(() => [
  { icon: 'inventory_2',  value: dashboard.value?.total_orders     ?? 0, label: 'মোট অর্ডার',        color: 'text-primary'   },
  { icon: 'check_circle', value: dashboard.value?.completed_orders  ?? 0, label: 'ডেলিভারি সম্পন্ন',  color: 'text-green-500' },
  { icon: 'favorite',     value: wishlistStore.count,                     label: 'উইশলিস্ট',          color: 'text-accent'    },
  { icon: 'pending',      value: dashboard.value?.pending_orders    ?? 0, label: 'প্রক্রিয়াধীন',      color: 'text-yellow-500' },
])

const wishlistItems = computed(() => wishlistStore.items.slice(0, 4))

const statusClass = (s) => ({
  delivered:  'bg-green-100 text-green-700',
  processing: 'bg-blue-100 text-blue-700',
  confirmed:  'bg-blue-100 text-blue-700',
  pending:    'bg-yellow-100 text-yellow-700',
  cancelled:  'bg-red-100 text-red-700',
  returned:   'bg-orange-100 text-orange-700',
}[s] || 'bg-gray-100 text-gray-600')

const statusLabel = (s) => ({
  delivered:  'ডেলিভারি হয়েছে',
  processing: 'প্রক্রিয়াধীন',
  confirmed:  'নিশ্চিত হয়েছে',
  pending:    'অপেক্ষায়',
  cancelled:  'বাতিল',
  returned:   'রিটার্ন',
  shipped:    'ডেলিভারি চলছে',
}[s] || s)

const formatDate = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' })
}

//const addToCart = async (productId) => {
  //if (!productId) return
  //await cartStore.add(productId)
  //if (process.client && window.$toast) {
    //window.$toast({ type: 'success', title: 'কার্টে যোগ হয়েছে!', duration: 2000 })
  //}
//}

const addToCart = async (productId, product) => {
  if (!productId) return
  if (product?.has_variants) return navigateTo(`/product/${product.slug || productId}`)
  await cartStore.add(productId)
  if (process.client && window.$toast) {
    window.$toast({ type: 'success', title: 'কার্টে যোগ হয়েছে!', duration: 2000 })
  }
}



onMounted(async () => {
  // Dashboard stats
  try {
    const data = await apiClient.get('/user/dashboard')
    dashboard.value     = data
    walletBalance.value = data.wallet_balance ?? 0
    recentOrders.value  = data.recent_orders ?? []
  } catch {
    // If dashboard fails, try to fetch orders directly
    try {
      const o = await apiClient.get('/orders', { limit: 3 })
      recentOrders.value = o.data ?? o ?? []
    } catch {}
  } finally {
    loading.value       = false
    loadingOrders.value = false
  }

  // Wallet separately if needed
  if (walletBalance.value === 0) {
    try {
      const w = await apiClient.get('/user/wallet')
      walletBalance.value = w.balance ?? 0
    } catch {}
  }

  // Wishlist
  await wishlistStore.fetch()
})
</script>
