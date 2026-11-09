<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <h1 class="section-title mb-6 flex items-center gap-2">
      <span class="mi text-primary">shopping_cart</span>
      {{ tr('আমার কার্ট') }}
      <span class="text-gray-400 text-lg font-normal">({{ cartStore.count }}টি পণ্য)</span>
    </h1>

    <!-- Initial page skeleton -->
    <div v-if="cartStore.loading && firstTimeLoad" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-3">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-4 shadow-card flex gap-4 items-start">
          <div class="w-20 h-20 rounded-xl bg-gray-200 animate-pulse shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-3/4"></div>
            <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-1/2"></div>
            <div class="flex items-center justify-between mt-3">
              <div class="h-5 bg-gray-200 animate-pulse rounded-lg w-24"></div>
              <div class="h-9 bg-gray-200 animate-pulse rounded-xl w-28"></div>
            </div>
          </div>
          <div class="w-6 h-6 bg-gray-200 animate-pulse rounded-full shrink-0"></div>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-card space-y-3">
          <div class="h-5 bg-gray-200 animate-pulse rounded-lg w-32"></div>
          <div class="flex gap-2">
            <div class="flex-1 h-11 bg-gray-200 animate-pulse rounded-xl"></div>
            <div class="w-24 h-11 bg-gray-200 animate-pulse rounded-xl"></div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl p-5 shadow-card space-y-4">
        <div class="h-6 bg-gray-200 animate-pulse rounded-lg w-40"></div>
        <div class="space-y-3">
          <div v-for="i in 3" :key="i" class="flex justify-between">
            <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-28"></div>
            <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-16"></div>
          </div>
          <div class="border-t border-gray-100 pt-3 flex justify-between">
            <div class="h-5 bg-gray-200 animate-pulse rounded-lg w-20"></div>
            <div class="h-5 bg-gray-200 animate-pulse rounded-lg w-24"></div>
          </div>
        </div>
        <div class="h-12 bg-gray-200 animate-pulse rounded-xl w-full"></div>
      </div>
    </div>

    <!-- Not logged in -->
    <div v-else-if="!authStore.isLoggedIn" class="text-center py-20">
      <span class="mi text-6xl text-gray-200 block mb-4">shopping_cart</span>
      <p class="font-bengali text-gray-500 mb-4">{{ tr('কার্ট দেখতে লগইন করুন') }}</p>
      <NuxtLink to="/auth/login?redirect=/cart" class="btn-primary">{{ tr('লগইন করুন') }}</NuxtLink>
    </div>

    <!-- Empty -->
    <div v-else-if="cartStore.isEmpty" class="text-center py-20">
      <span class="mi text-6xl text-gray-200 block mb-4">shopping_cart</span>
      <p class="font-bengali text-gray-500 mb-2">{{ tr('আপনার কার্ট খালি') }}</p>
      <p class="font-bengali text-gray-400 text-sm mb-6">{{ tr('এখনই পছন্দের পণ্য যোগ করুন') }}</p>
      <NuxtLink to="/" class="btn-primary">
        <span class="mi mi-sm">shopping_bag</span> {{ tr('কেনাকাটা শুরু করুন') }}
      </NuxtLink>
    </div>

    <!-- Cart content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Cart Items -->
      <div class="lg:col-span-2 space-y-3">
        <template v-for="item in cartStore.items" :key="item.id">
          <!-- Skeleton row — shown while this item is updating or being removed -->
          <div v-if="updatingIds.has(item.id) || removingIds.has(item.id)" class="bg-white rounded-2xl p-4 shadow-card flex gap-4 items-start">
            <div class="w-20 h-20 rounded-xl bg-gray-200 animate-pulse shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-3/4"></div>
              <div class="h-4 bg-gray-200 animate-pulse rounded-lg w-1/2"></div>
              <div class="flex items-center justify-between mt-3">
                <div class="h-5 bg-gray-200 animate-pulse rounded-lg w-24"></div>
                <div class="h-9 bg-gray-200 animate-pulse rounded-xl w-28"></div>
              </div>
            </div>
            <div class="w-6 h-6 bg-gray-200 animate-pulse rounded-full shrink-0"></div>
          </div>

          <!-- Real item row -->
          <div v-else class="bg-white rounded-2xl p-4 shadow-card flex gap-4 items-start">
            <NuxtLink :to="`/product/${item.product.slug}`" class="shrink-0">
              <img :src="item.product.image" :alt="item.product.name" class="w-20 h-20 object-cover rounded-xl bg-gray-50"/>
            </NuxtLink>

            <div class="flex-1 min-w-0">
              <NuxtLink :to="`/product/${item.product.slug}`" class="font-bengali font-medium text-gray-800 text-sm leading-snug line-clamp-2 mb-1 hover:text-primary transition-colors block">{{ item.product.name }}</NuxtLink>

              <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="font-display font-bold text-accent">৳{{ Number(item.total).toLocaleString() }}</span>

                <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden">
                  <button @click="handleUpdate(item, item.quantity - 1)" :disabled="item.quantity <= 1" class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center disabled:opacity-40 disabled:cursor-not-allowed">
                    <span class="mi mi-sm">remove</span>
                  </button>
                  <span class="px-3 py-1.5 font-display font-bold text-sm min-w-[2.5rem] text-center">{{ item.quantity }}</span>
                  <button @click="handleUpdate(item, item.quantity + 1)" class="px-3 py-1.5 bg-gray-50 hover:bg-gray-100 transition-colors flex items-center">
                    <span class="mi mi-sm">add</span>
                  </button>
                </div>
              </div>
            </div>

            <button @click="handleRemove(item.id)" class="text-gray-300 hover:text-accent transition-colors shrink-0" :aria-label="tr('পণ্য সরান')">
              <span class="mi">delete_outline</span>
            </button>
          </div>
        </template>

        <NuxtLink to="/" class="flex items-center gap-2 text-primary font-bengali text-sm font-medium hover:underline">
          <span class="mi mi-sm">arrow_back</span> {{ tr('কেনাকাটা চালিয়ে যান') }}
        </NuxtLink>

        <!-- Coupon -->
        <div class="bg-white rounded-2xl p-4 shadow-card">
          <p class="font-display font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span class="mi text-primary">confirmation_number</span> {{ tr('কুপন কোড') }}
          </p>
          <div class="flex gap-2">
            <input v-model="couponCode" type="text" :placeholder="tr('কুপন কোড লিখুন...')" :disabled="applyingCoupon" class="flex-1 border-2 border-dashed border-gray-200 focus:border-primary rounded-xl px-4 py-2.5 text-sm font-bengali outline-none transition-colors disabled:bg-gray-50" @keyup.enter="applyCoupon"/>
            <button @click="applyCoupon" :disabled="applyingCoupon || !couponCode" class="btn-secondary py-2.5 px-4 text-sm disabled:opacity-60 min-w-[5.5rem] flex items-center justify-center gap-1.5">
              <svg v-if="applyingCoupon" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
              </svg>
              <template v-else>
                <span class="mi mi-sm">check_circle</span> {{ tr('প্রয়োগ') }}
              </template>
            </button>
          </div>
          <p v-if="couponMsg" class="text-xs font-bengali mt-2 flex items-center gap-1" :class="couponSuccess ? 'text-green-600' : 'text-red-500'">
            <span class="mi mi-sm">{{ couponSuccess ? 'check_circle' : 'error' }}</span>
            {{ couponMsg }}
          </p>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="space-y-4">
        <div class="bg-white rounded-2xl p-5 shadow-card sticky top-24 transition-all duration-200" :class="cartStore.loading ? 'opacity-40' : ''">
          <h3 class="font-display font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
            <span class="mi text-primary">receipt_long</span> {{ tr('অর্ডার সারসংক্ষেপ') }}
          </h3>
          <div class="space-y-3 text-sm font-bengali">
            <div class="flex justify-between">
              <span class="text-gray-500">{{ tr('মোট পণ্য মূল্য') }}</span>
              <span class="font-semibold">৳{{ Number(cartStore.subtotal).toLocaleString() }}</span>
            </div>
            <div v-if="cartStore.discount > 0" class="flex justify-between text-green-600">
              <span>{{ tr('কুপন ছাড়') }}</span>
              <span class="font-semibold">− ৳{{ Number(cartStore.discount).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-green-600">
              <span class="flex items-center gap-1">
                <span class="mi mi-sm">local_shipping</span>{{ tr('ডেলিভারি') }}
              </span>
              <span class="font-semibold">{{ tr('বিনামূল্যে') }}</span>
            </div>
            <div class="border-t border-gray-100 pt-3 flex justify-between">
              <span class="font-display font-bold text-gray-800">{{ tr('সর্বমোট') }}</span>
              <span class="font-display font-bold text-accent text-xl">৳{{ Number(cartStore.total).toLocaleString() }}</span>
            </div>
          </div>

          <div v-if="cartStore.discount > 0" class="mt-4 p-3 bg-green-50 rounded-xl border border-green-100 text-center">
            <p class="text-green-700 font-bengali text-sm font-semibold flex items-center justify-center gap-1">
              <span class="mi mi-sm">savings</span> আপনি ৳{{ Number(cartStore.discount).toLocaleString() }} সাশ্রয় করছেন!
            </p>
          </div>

          <NuxtLink to="/checkout" class="btn-primary w-full justify-center mt-5 py-3.5 text-base rounded-xl">
            <span class="mi">arrow_forward</span> {{ tr('চেকআউট করুন') }}
          </NuxtLink>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
import { ref } from 'vue'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from '~/stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()
const dataLayer = useDataLayer()

const updatingIds    = ref(new Set())
const removingIds    = ref(new Set())
const couponCode     = ref('')
const couponMsg      = ref('')
const couponSuccess  = ref(false)
const applyingCoupon = ref(false)
const firstTimeLoad  = ref(true)

cartStore.fetch().finally(() => {
  firstTimeLoad.value = false
  dataLayer.viewCart(cartStore)
})

const handleUpdate = async (item, newQty) => {
  if (newQty < 1 || updatingIds.value.has(item.id)) return
  updatingIds.value = new Set(updatingIds.value).add(item.id)
  try {
    await cartStore.update(item.id, newQty)
  } finally {
    const next = new Set(updatingIds.value)
    next.delete(item.id)
    updatingIds.value = next
  }
}

const handleRemove = async (id) => {
  if (removingIds.value.has(id)) return
  removingIds.value = new Set(removingIds.value).add(id)
  const removedItem = cartStore.items.find(i => i.id === id)
  try {
    await cartStore.remove(id)
    if (removedItem) dataLayer.removeFromCart(removedItem)
  } finally {
    const next = new Set(removingIds.value)
    next.delete(id)
    removingIds.value = next
  }
}

const applyCoupon = async () => {
  if (!couponCode.value || applyingCoupon.value) return
  applyingCoupon.value = true
  couponMsg.value = ''
  try {
    const result = await cartStore.applyCoupon(couponCode.value)
    couponSuccess.value = result.success
    couponMsg.value = result.success ? `কুপন প্রয়োগ হয়েছে! ৳${result.data.discount} ছাড় পেয়েছেন।` : result.message
  } finally {
    applyingCoupon.value = false
  }
}
</script>