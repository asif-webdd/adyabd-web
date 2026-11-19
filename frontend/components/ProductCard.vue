<template>
  <!-- ── LIST mode ───────────────────────────────────────────────────── -->
  <NuxtLink v-if="cardStyle === 'list'" :to="`/product/${product.slug || product.id}`" @click="trackSelect"
            class="flex gap-4 bg-white rounded-ap p-3 shadow-card hover:shadow-card-hover transition-all duration-200 group">

    <div class="relative shrink-0 w-24 h-24 rounded-ap-sm overflow-hidden bg-gray-50">
      
    <img
      :src="product.image && product.image.includes('cdn.adyabd.com/products/')
        ? product.image.replace(
            'https://cdn.adyabd.com/products/',
            'https://cdn.adyabd.com/cdn-cgi/image/width=368,height=368,fit=cover,quality=80,format=auto/products/'
          )
        : (product.image || '/images/placeholder.svg')"
      @error="$event.target.src='/images/placeholder.svg'"
      :alt="product.name"
      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      loading="lazy"
    />

      <span v-if="product.discount" class="absolute top-1 left-1 badge bg-accent text-white text-[10px] px-1.5 py-0.5">{{ product.discount }}%</span>
    </div>

    <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
      <div>
        <p class="text-xs text-gray-400 font-bengali truncate mb-0.5">{{ product.seller }}</p>
        <h3 class="font-bengali font-medium text-gray-800 text-sm leading-snug line-clamp-2 mb-1">{{ product.name }}</h3>
        <div class="flex items-center gap-1 mb-1">
          <span v-for="i in 5" :key="i" class="mi" style="font-size:12px" :class="i <= Math.round(product.rating) ? 'mi-filled text-gold' : 'text-gray-300'">star</span>
          <span class="text-xs text-gray-400 font-bengali">({{ product.reviews }})</span>
        </div>
      </div>
      <div class="flex items-center justify-between gap-2 flex-wrap">
        <div class="flex items-end gap-2">
          <span class="font-display font-bold text-base text-accent">৳{{ product.price?.toLocaleString('bn-BD') }}</span>
          <span v-if="product.originalPrice > product.price" class="text-xs text-gray-400 line-through">৳{{ product.originalPrice?.toLocaleString('bn-BD') }}</span>
        </div>
        <button @click.prevent="handleAddToCart" :disabled="product.quantity === 0 || cartLoading"
                class="shrink-0 flex items-center gap-1 px-3 py-1.5 rounded-ap-sm text-xs font-bengali font-semibold text-white transition-colors disabled:bg-gray-400"
                style="background-color: var(--color-primary);"
                @mouseenter="e => e.currentTarget.style.backgroundColor = 'var(--color-primary-dark)'"
                @mouseleave="e => e.currentTarget.style.backgroundColor = 'var(--color-primary)'">
          <span class="mi mi-sm">{{ cartJustAdded ? 'check_circle' : product.hasVariants ? 'tune' : 'add_shopping_cart' }}</span>
          {{ cartJustAdded ? tr('যোগ হয়েছে') : tr('কার্ট') }}
        </button>
      </div>
    </div>

    <!-- Wishlist -->
    <button @click.prevent="handleWishlist" :disabled="wishlistLoading"
            class="shrink-0 self-start w-7 h-7 bg-white border border-gray-100 rounded-full shadow-sm flex items-center justify-center hover:bg-accent/10 transition-colors">
      <span class="mi mi-sm transition-transform duration-200"
            :class="[isWishListed ? 'mi-filled text-accent' : 'text-gray-400', wishlistJustToggled ? 'scale-125' : 'scale-100']">favorite</span>
    </button>
  </NuxtLink>

  <!-- ── COMPACT mode ─────────────────────────────────────────────────── -->
  <NuxtLink v-else-if="cardStyle === 'compact'" :to="`/product/${product.slug || product.id}`" @click="trackSelect"
            class="product-card group block">

    <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:1">
      
    <img
      :src="product.image && product.image.includes('cdn.adyabd.com/products/')
        ? product.image.replace(
            'https://cdn.adyabd.com/products/',
            'https://cdn.adyabd.com/cdn-cgi/image/width=368,height=368,fit=cover,quality=80,format=auto/products/'
          )
        : (product.image || '/images/placeholder.svg')"
      @error="$event.target.src='/images/placeholder.svg'"
      :alt="product.name"
      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      loading="lazy"
    />

      <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
        <span v-if="product.discount" class="badge bg-accent text-white text-[10px] px-1.5 py-0.5">{{ product.discount }}%</span>
        <span v-if="product.isNew" class="badge bg-primary text-white text-[10px] px-1.5 py-0.5">{{ tr('নতুন') }}</span>
      </div>
      <button @click.prevent="handleWishlist" :disabled="wishlistLoading"
              class="absolute top-1.5 right-1.5 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center hover:bg-accent/10 transition-colors">
        <span class="mi mi-sm transition-transform duration-200"
              :class="[isWishListed ? 'mi-filled text-accent' : 'text-gray-400', wishlistJustToggled ? 'scale-125' : 'scale-100']">favorite</span>
      </button>
      <!-- Quick add -->
      <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
        <button @click.prevent="handleAddToCart" :disabled="product.quantity === 0 || cartLoading"
                class="w-full py-2 font-bengali font-semibold text-xs text-white transition-colors flex items-center justify-center gap-1 disabled:bg-gray-400"
                style="background-color: var(--color-primary);"
                @mouseenter="e => e.currentTarget.style.backgroundColor = 'var(--color-primary-dark)'"
                @mouseleave="e => e.currentTarget.style.backgroundColor = 'var(--color-primary)'">
          <span class="mi mi-sm">{{ cartJustAdded ? 'check_circle' : product.hasVariants ? 'tune' : 'add_shopping_cart' }}</span>
          {{ cartJustAdded ? tr('যোগ হয়েছে!') : product.quantity === 0 ? tr('স্টক নেই') : product.hasVariants ? tr('অপশন বেছে নিন') : tr('কার্টে যোগ') }}
        </button>
      </div>
    </div>

    <div class="p-2">
      <h3 class="font-bengali font-medium text-gray-800 text-xs leading-snug line-clamp-2 mb-1.5">{{ product.name }}</h3>
      <div class="flex items-end gap-1.5">
        <span class="font-display font-bold text-sm text-accent">৳{{ product.price?.toLocaleString('bn-BD') }}</span>
        <span v-if="product.originalPrice > product.price" class="text-[11px] text-gray-400 line-through">৳{{ product.originalPrice?.toLocaleString('bn-BD') }}</span>
      </div>
    </div>
  </NuxtLink>

  <!-- ── DETAILED mode ────────────────────────────────────────────────── -->
  <NuxtLink v-else-if="cardStyle === 'detailed'" :to="`/product/${product.slug || product.id}`" @click="trackSelect"
            class="product-card group block">

    <div class="relative overflow-hidden bg-gray-50" style="aspect-ratio:1">
      
    <img
      :src="product.image && product.image.includes('cdn.adyabd.com/products/')
        ? product.image.replace(
            'https://cdn.adyabd.com/products/',
            'https://cdn.adyabd.com/cdn-cgi/image/width=368,height=368,fit=cover,quality=80,format=auto/products/'
          )
        : (product.image || '/images/placeholder.svg')"
      @error="$event.target.src='/images/placeholder.svg'"
      :alt="product.name"
      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      loading="lazy"
    />

      <div class="absolute top-2 left-2 flex flex-col gap-1">
        <span v-if="product.discount" class="badge bg-accent text-white">{{ product.discount }}% ছাড়</span>
        <span v-if="product.isNew" class="badge bg-primary text-white">{{ tr('নতুন') }}</span>
        <span v-if="product.isTrending" class="badge bg-orange-500 text-white flex items-center gap-0.5">
          <span class="mi mi-sm">trending_up</span> {{ tr('ট্রেন্ডিং') }}
        </span>
      </div>
      <button @click.prevent="handleWishlist" :disabled="wishlistLoading"
              class="absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center hover:bg-accent/10 transition-colors">
        <span class="mi mi-sm transition-transform duration-200"
              :class="[isWishListed ? 'mi-filled text-accent' : 'text-gray-400', wishlistJustToggled ? 'scale-125' : 'scale-100']">favorite</span>
      </button>
      <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
        <button @click.prevent="handleAddToCart" :disabled="product.quantity === 0 || cartLoading"
                class="w-full py-2.5 font-bengali font-semibold text-sm text-white transition-colors flex items-center justify-center gap-1.5 disabled:bg-gray-400"
                style="background-color: var(--color-primary);"
                @mouseenter="e => e.currentTarget.style.backgroundColor = 'var(--color-primary-dark)'"
                @mouseleave="e => e.currentTarget.style.backgroundColor = 'var(--color-primary)'">
          <template v-if="cartLoading">
            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
            <span>{{ tr('যোগ হচ্ছে...') }}</span>
          </template>
          <template v-else-if="cartJustAdded">
            <span class="mi mi-sm animate-bounce-once">check_circle</span>
            <span>{{ tr('যোগ হয়েছে!') }}</span>
          </template>
          <template v-else>
            <span class="mi mi-sm">{{ product.quantity === 0 ? 'remove_shopping_cart' : product.hasVariants ? 'tune' : 'add_shopping_cart' }}</span>
            {{ product.quantity === 0 ? tr('স্টক নেই') : product.hasVariants ? tr('অপশন বেছে নিন') : tr('কার্টে যোগ করুন') }}
          </template>
        </button>
      </div>
    </div>

    <div class="p-3">
      <!-- Seller + rating row -->
      <div class="flex items-center justify-between mb-1">
        <p class="text-xs text-gray-400 font-bengali truncate max-w-[60%]">{{ product.seller }}</p>
        <div class="flex items-center gap-0.5">
          <span class="mi mi-sm mi-filled text-gold" style="font-size:13px">star</span>
          <span class="text-xs text-gray-500 font-display font-semibold">{{ product.rating }}</span>
          <span class="text-xs text-gray-400 font-bengali">({{ product.reviews }})</span>
        </div>
      </div>
      <h3 class="font-bengali font-medium text-gray-800 text-sm leading-snug line-clamp-2 mb-1">{{ product.name }}</h3>
      <div class="flex items-end gap-2 mb-1.5">
        <span class="font-display font-bold text-lg text-accent">৳{{ product.price?.toLocaleString('bn-BD') }}</span>
        <span v-if="product.originalPrice > product.price" class="text-xs text-gray-400 line-through font-bengali">৳{{ product.originalPrice?.toLocaleString('bn-BD') }}</span>
      </div>
      <div class="flex items-center gap-1">
        <span class="mi mi-sm text-primary" style="font-size:14px">local_shipping</span>
        <span class="text-xs text-primary font-bengali">{{ product.freeShipping ? 'বিনামূল্যে ডেলিভারি' : product.delivery }}</span>
      </div>
    </div>
  </NuxtLink>

  <!-- ── DEFAULT mode (fallback) ──────────────────────────────────────── -->
  <NuxtLink v-else :to="`/product/${product.slug || product.id}`" @click="trackSelect"
            class="product-card group block">

    <div class="relative overflow-hidden bg-gray-50 aspect-square">
      
    <img
      :src="product.image && product.image.includes('cdn.adyabd.com/products/')
        ? product.image.replace(
            'https://cdn.adyabd.com/products/',
            'https://cdn.adyabd.com/cdn-cgi/image/width=368,height=368,fit=cover,quality=80,format=auto/products/'
          )
        : (product.image || '/images/placeholder.svg')"
      @error="$event.target.src='/images/placeholder.svg'"
      :alt="product.name"
      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      loading="lazy"
    />

      <div class="absolute top-2 left-2 flex flex-col gap-1">
        <span v-if="product.discount" class="badge bg-accent text-white">{{ product.discount }}% ছাড়</span>
        <span v-if="product.isNew" class="badge bg-primary text-white">{{ tr('নতুন') }}</span>
        <span v-if="product.isTrending" class="badge bg-orange-500 text-white flex items-center gap-0.5">
          <span class="mi mi-sm">trending_up</span> {{ tr('ট্রেন্ডিং') }}
        </span>
      </div>
      <button @click.prevent="handleWishlist" :disabled="wishlistLoading"
              class="absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center hover:bg-accent/10 transition-colors disabled:cursor-not-allowed"
              :aria-label="isWishListed ? 'Remove from Wishlist' : 'Add to Wishlist'">
        <svg v-if="wishlistLoading" class="w-4 h-4 animate-spin text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <span v-else class="mi mi-sm transition-transform duration-200"
              :class="[isWishListed ? 'mi-filled text-accent' : 'text-gray-400', wishlistJustToggled ? 'scale-125' : 'scale-100']">favorite</span>
      </button>
      <div class="absolute bottom-0 left-0 right-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
        <button @click.prevent="handleAddToCart" :disabled="product.quantity === 0 || cartLoading"
                class="w-full py-2.5 font-bengali font-semibold text-sm text-white transition-colors flex items-center justify-center gap-1.5 disabled:bg-gray-400 disabled:cursor-not-allowed"
                style="background-color: var(--color-primary);"
                @mouseenter="e => e.currentTarget.style.backgroundColor = 'var(--color-primary-dark)'"
                @mouseleave="e => e.currentTarget.style.backgroundColor = 'var(--color-primary)'">
          <template v-if="cartLoading">
            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
            <span>{{ tr('যোগ হচ্ছে...') }}</span>
          </template>
          <template v-else-if="cartJustAdded">
            <span class="mi mi-sm animate-bounce-once">check_circle</span>
            <span>{{ tr('যোগ হয়েছে!') }}</span>
          </template>
          <template v-else>
            <span class="mi mi-sm">{{ product.quantity === 0 ? 'remove_shopping_cart' : product.hasVariants ? 'tune' : 'add_shopping_cart' }}</span>
            {{ product.quantity === 0 ? tr('স্টক নেই') : product.hasVariants ? tr('অপশন বেছে নিন') : tr('কার্টে যোগ করুন') }}
          </template>
        </button>
      </div>
    </div>

    <div class="p-3">
      <!-- <p class="text-xs text-gray-400 font-bengali mb-1 truncate">{{ product.seller }}</p> -->
      <h3 class="font-bengali font-medium text-gray-800 text-sm leading-snug line-clamp-2 mb-1">{{ product.name }}</h3>
      <div class="flex items-center gap-1 mb-1">
        <div class="flex">
          <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i <= Math.round(product.rating) ? 'mi-filled text-gold' : 'text-gray-300'" style="font-size: 14px">star</span>
        </div>
        <span class="text-xs text-gray-500 font-bengali">({{ product.reviews }})</span>
      </div>
      <div class="flex items-end gap-2">
        <span class="font-display font-bold text-lg text-accent">৳{{ product.price?.toLocaleString('bn-BD') }}</span>
        <span v-if="product.originalPrice > product.price" class="text-xs text-gray-400 line-through font-bengali">৳{{ product.originalPrice?.toLocaleString('bn-BD') }}</span>
      </div>
      <div class="mt-1.0 flex items-center gap-1">
        <span class="mi mi-sm text-primary" style="font-size: 14px">local_shipping</span>
        <span class="text-xs text-primary font-bengali">{{ product.freeShipping ? tr('বিনামূল্যে ডেলিভারি') : product.delivery }}</span>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup>
const { tr } = useLang()
import { ref, computed } from 'vue'
import { useCartStore }      from '~/stores/cart'
import { useWishlistStore }  from '~/stores/wishlist'
import { useAuthStore }      from '~/stores/auth'
import { useAuthPopupStore } from '~/stores/AuthPopup'

const props = defineProps({
  product:   { type: Object,  required: true },
  cardStyle: { type: String,  default: 'default' }, // default | compact | detailed | list
  listName:  { type: String,  default: '' }, // for select_item / view_item_list context
})

const cartStore     = useCartStore()
const wishlistStore = useWishlistStore()
const authStore     = useAuthStore()
const authPopup     = useAuthPopupStore()
const dataLayer     = useDataLayer()

const cartLoading         = ref(false)
const cartJustAdded       = ref(false)
const wishlistLoading     = ref(false)
const wishlistJustToggled = ref(false)

const isWishListed = computed(() => wishlistStore.has(props.product.id))

// ── dataLayer: user clicked through to the product page ────────────────────
const trackSelect = () => {
  dataLayer.selectItem(props.product, props.listName)
}

// ── Wishlist ──────────────────────────────────────────────────────────────
const doToggleWishlist = async () => {
  if (wishlistLoading.value) return
  wishlistLoading.value = true
  try {
    const wasAdded = await wishlistStore.toggle(props.product.id)
    wishlistJustToggled.value = true
    setTimeout(() => (wishlistJustToggled.value = false), 300)
    if (wasAdded) {
      dataLayer.addToWishlist(props.product)
    }
    if (import.meta.client && window.$toast) {
      window.$toast({ type: 'success', title: wasAdded ? 'উইশলিস্টে যোগ হয়েছে!' : 'উইশলিস্ট থেকে সরানো হয়েছে', duration: 2000 })
    }
  } finally { wishlistLoading.value = false }
}
const handleWishlist = () => {
  if (!authStore.isLoggedIn) { authPopup.open(() => doToggleWishlist()); return }
  doToggleWishlist()
}

// ── Cart ──────────────────────────────────────────────────────────────────
const doAddToCart = async () => {
  if (props.product.quantity === 0 || cartLoading.value) return
  cartLoading.value = true
  try {
    const ok = await cartStore.add(props.product.id, 1)
    if (ok) {
      cartJustAdded.value = true
      setTimeout(() => (cartJustAdded.value = false), 1500)
      dataLayer.addToCart(props.product, 1)
      if (import.meta.client && window.$toast) { window.$toast({ type: 'success', title: 'কার্টে যোগ হয়েছে!' }) }
    }
  } finally { cartLoading.value = false }
}
const handleAddToCart = () => {
  // Variable product: customer must pick options on the details page first
  if (props.product.hasVariants) {
    trackSelect()
    navigateTo(`/product/${props.product.slug || props.product.id}`)
    return
  }
  if (!authStore.isLoggedIn) { authPopup.open(() => doAddToCart()); return }
  doAddToCart()
}
</script>

<style scoped>
@keyframes bounce-once {
  0%, 100% { transform: scale(1); }
  40%       { transform: scale(1.3); }
  70%       { transform: scale(0.9); }
}
.animate-bounce-once { animation: bounce-once 0.5s ease; }
</style>