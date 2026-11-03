<template>
  <section class="bg-gradient-to-r from-accent to-accent-dark rounded-2xl p-6 text-white">
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
      <div class="flex items-center gap-3">
        <span class="mi mi-lg mi-filled text-yellow-300">bolt</span>
        <div>
          <h2 class="font-display font-extrabold text-2xl">{{ tr('ফ্ল্যাশ সেল') }}</h2>
          <p class="text-white/70 text-xs font-bengali">{{ tr('সীমিত সময়ের অফার') }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="mi mi-sm text-white/60">timer</span>
        <span class="text-xs font-bengali text-white/70">{{ tr('শেষ হচ্ছে:') }}</span>
        <div class="flex gap-1.5">
          <div v-for="(unit, i) in countdown" :key="i" class="text-center">
            <div class="bg-white/20 backdrop-blur rounded-lg px-2.5 py-1 font-display font-bold text-base min-w-[2.2rem] text-center">{{ unit.value }}</div>
            <div class="text-xs text-white/70 font-bengali mt-0.5">{{ unit.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid gap-3" :class="flashSaleGridCols">
      <div v-for="i in 4" :key="i" class="bg-white/20 rounded-xl h-48 animate-pulse"></div>
    </div>

    <div v-else-if="products.length > 0" class="grid gap-3" :class="flashSaleGridCols">
      <NuxtLink v-for="product in products" :key="product.id" :to="`/product/${product.slug || product.id}`" class="bg-white rounded-xl overflow-hidden cursor-pointer group hover:-translate-y-1 transition-all duration-300 block">
        <div class="relative aspect-square overflow-hidden">
          <img :src="product.full_url_image" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"/>
          <div class="absolute top-2 left-2 badge bg-accent text-white">{{ product.discount }}% ছাড়</div>
        </div>
        <div class="p-3">
          <p class="text-gray-800 font-bengali text-xs font-medium line-clamp-2 mb-1.5">{{ product.name }}</p>
          <div class="flex items-baseline gap-1.5 mb-2">
            <span class="text-accent font-display font-bold">৳{{ Number(product.price).toLocaleString() }}</span>
            <span v-if="product.originalPrice" class="text-gray-400 text-xs line-through">৳{{ Number(product.originalPrice).toLocaleString() }}</span>
          </div>
          <div v-if="product.sold !== undefined">
            <div class="flex justify-between text-xs font-bengali mb-1">
              <span class="text-gray-500">বিক্রিত: {{ product.sold }}%</span>
              <span class="text-accent font-semibold">{{ 100 - product.sold }}% বাকি</span>
            </div>
            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
              <div class="h-full bg-gradient-to-r from-accent to-orange-400 rounded-full" :style="`width:${product.sold}%`"></div>
            </div>
          </div>
        </div>
      </NuxtLink>
    </div>

    <div class="text-center mt-4">
      <NuxtLink to="/deals" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 border border-white/30 text-white font-display font-semibold px-6 py-2.5 rounded-xl transition-colors text-sm">
        {{ tr('সব ফ্ল্যাশ ডিল দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
      </NuxtLink>
    </div>
  </section>
</template>

<script setup>
const { tr } = useLang()
const { flashSaleGridCols } = useAppearance()
import { useProductsStore } from '~/stores/products'

const productsStore = useProductsStore()
const loading = ref(false)

const products = computed(() => {
  const sale = productsStore.flashSale
  if (!sale) return []
  // API returns flash_sale.products array, or we use the products store
  const items = sale.products ?? sale.items ?? []
  return items.map(p => {
    const base = productsStore.normalise(p.product ?? p)
    return {
      ...base,
      discount: p.discount_percentage ?? base.discount,
      price:    p.special_price       ?? base.price,
      sold:     p.sold_percentage     ?? Math.floor(Math.random() * 60 + 20),
    }
  })
})

// Countdown derived from flash sale end time or local timer
const endsAt = computed(() => productsStore.flashSale?.ends_at)

const toBn = n => String(n).padStart(2,'0').replace(/[0-9]/g, d=>'০১২৩৪৫৬৭৮৯'[d])
const countdown = ref([{ value:'০৫', label:'ঘণ্টা' }, { value:'৪২', label:'মিনিট' }, { value:'১৮', label:'সেকেন্ড' }])

let timer
onMounted(() => {
  let remaining = endsAt.value ? Math.floor((new Date(endsAt.value) - Date.now()) / 1000) : 5 * 3600 + 42 * 60 + 18

  timer = setInterval(() => {
    if (remaining <= 0) { clearInterval(timer); return }
    remaining--
    const h = Math.floor(remaining / 3600)
    const m = Math.floor((remaining % 3600) / 60)
    const s = remaining % 60
    countdown.value[0].value = toBn(h)
    countdown.value[1].value = toBn(m)
    countdown.value[2].value = toBn(s)
  }, 1000)
})
onUnmounted(() => clearInterval(timer))
</script>
