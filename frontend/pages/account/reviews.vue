<template>
  <div class="space-y-5">
    <h1 class="section-title flex items-center gap-2"><span class="mi text-primary">rate_review</span>{{ tr('আমার রিভিউ') }}</h1>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value" class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0" :class="activeTab === tab.value ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary'">
        {{ tr(tab.label) }}
        <span v-if="tab.count !== undefined" class="opacity-75">({{ tab.count }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-2xl shadow-card p-5 animate-pulse">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-14 h-14 rounded-xl bg-gray-200 shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3.5 w-2/3 bg-gray-200 rounded"></div>
            <div class="h-3 w-1/2 bg-gray-200 rounded"></div>
          </div>
        </div>

        <div class="h-3.5 w-24 bg-gray-200 rounded mb-2"></div>
        <div class="flex gap-1 mb-3">
          <div v-for="j in 5" :key="j" class="w-6 h-6 bg-gray-200 rounded-full"></div>
        </div>

        <div class="h-16 w-full bg-gray-200 rounded-xl mb-3"></div>
        <div class="h-9 w-32 bg-gray-200 rounded-xl"></div>
      </div>
    </div>

    <!-- Pending Reviews (products bought but not reviewed) -->
    <div v-else-if="activeTab === 'pending'" class="space-y-4">
      <div v-if="pendingItems.length === 0" class="bg-white rounded-2xl shadow-card p-12 text-center">
        <span class="mi text-4xl text-gray-200 block mb-2">check_circle</span>
        <p class="font-bengali text-gray-400">{{ tr('সব পণ্যের রিভিউ দেওয়া হয়েছে!') }}</p>
      </div>
      <div v-for="item in pendingItems" :key="item.id" class="bg-white rounded-2xl shadow-card p-5">
        <div class="flex items-center gap-4 mb-4">
          <NuxtLink :to="`/product/${item.product_slug}`">
            <img :src="productImage(item)" :alt="item.product_name" class="w-14 h-14 object-cover rounded-xl shrink-0 bg-gray-50"/>
          </NuxtLink>
          <div class="flex-1">
            <NuxtLink :to="`/product/${item.product_slug}`" class="font-bengali font-semibold text-gray-800 text-sm hover:text-primary transition-colors block line-clamp-1">{{ item.product_name }}</NuxtLink>
            <p class="font-bengali text-xs text-gray-400 flex items-center gap-1 mt-0.5">
              <span class="mi mi-sm">calendar_today</span>ক্রয়: {{ formatDate(item.created_at) }} · অর্ডার #{{ item.order_number }}
            </p>
          </div>
        </div>

        <div class="mb-3">
          <p class="font-bengali text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
            <span class="mi mi-sm text-primary">star</span>{{ tr('রেটিং দিন:') }}
          </p>
          <div class="flex gap-1">
            <button v-for="i in 5" :key="i" @click="item._rating = i" class="mi text-3xl transition-transform hover:scale-110" :class="i <= (item._rating || 0) ? 'mi-filled text-gold' : 'text-gray-300'">star</button>
          </div>
        </div>

        <textarea v-model="item._comment" rows="3" :placeholder="`${item.product_name} সম্পর্কে আপনার মতামত লিখুন...`" class="input-field resize-none mb-3"></textarea>

        <!-- Photo Upload -->
        <div class="flex items-center gap-3 mb-4">
          <label class="flex items-center gap-2 border-2 border-dashed border-gray-200 rounded-xl px-4 py-2 text-sm font-bengali text-gray-500 hover:border-primary hover:text-primary transition-colors cursor-pointer">
            <span class="mi mi-sm">add_photo_alternate</span>{{ tr('ছবি যোগ করুন') }}
            <input type="file" accept="image/*" multiple class="hidden" @change="(e) => item._images = Array.from(e.target.files)"/>
          </label>
          <span v-if="item._images?.length" class="text-xs font-bengali text-green-600">{{ item._images.length }}টি ছবি নির্বাচিত</span>
          <span v-else class="text-xs font-bengali text-gray-400">{{ tr('সর্বোচ্চ ৫টি ছবি') }}</span>
        </div>

        <div v-if="item._error" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs font-bengali mb-3">{{ item._error }}</div>

        <button @click="submitReview(item)" :disabled="item._submitting" class="btn-primary text-sm py-2.5 disabled:opacity-60">
          <span v-if="item._submitting" class="mi mi-sm animate-spin">refresh</span>
          <span v-else class="mi mi-sm">send</span>
          {{ item._submitting ? 'জমা হচ্ছে...' : 'রিভিউ জমা দিন' }}
        </button>
      </div>
    </div>

    <!-- Given Reviews -->
    <div v-else class="space-y-4">
      <div v-if="givenReviews.length === 0" class="bg-white rounded-2xl shadow-card p-12 text-center">
        <span class="mi text-4xl text-gray-200 block mb-2">rate_review</span>
        <p class="font-bengali text-gray-400">{{ tr('এখনো কোনো রিভিউ দেওয়া হয়নি।') }}</p>
      </div>
      <div v-for="review in givenReviews" :key="review.id" class="bg-white rounded-2xl shadow-card p-5">
        <div class="flex items-start gap-4">
          <NuxtLink :to="`/product/${review.product.slug}`">
            <img :src="reviewProductImage(review)" :alt="review.product?.name" class="w-14 h-14 object-cover rounded-xl shrink-0 bg-gray-50"/>
          </NuxtLink>
          <div class="flex-1">
            <NuxtLink :to="`/product/${review.product.slug}`" class="font-bengali font-semibold text-gray-800 text-sm hover:text-primary transition-colors block mb-1">{{ review.product?.name }}</NuxtLink>
            <div class="flex items-center gap-2 mb-2">
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" class="mi mi-sm" :class="i <= review.rating ? 'mi-filled text-gold' : 'text-gray-300'" style="font-size:14px">star</span>
              </div>
              <span class="text-xs text-gray-400 font-bengali flex items-center gap-0.5">
                <span class="mi mi-sm">calendar_today</span>{{ formatDate(review.created_at) }}
              </span>
              <span v-if="review.is_verified" class="badge bg-green-100 text-green-700 text-xs flex items-center gap-0.5">
                <span class="mi mi-sm">verified</span>{{ tr('যাচাইকৃত ক্রয়') }}
              </span>
            </div>
            <p class="font-bengali text-gray-600 text-sm leading-relaxed mb-2">{{ review.review }}</p>

            <!-- Review images -->
            <div v-if="review.images?.length" class="flex gap-2 mb-3 flex-wrap">
              <img v-for="img in review.images" :key="img" :src="img" class="w-16 h-16 object-cover rounded-xl"/>
            </div>

            <!-- Seller Reply -->
            <div v-if="review.reply" class="bg-gray-50 rounded-xl p-3 border-l-4 border-primary mb-3">
              <p class="text-xs font-bengali font-semibold text-primary mb-1 flex items-center gap-1">
                <span class="mi mi-sm">storefront</span>{{ tr('বিক্রেতার জবাব:') }}
              </p>
              <p class="text-xs font-bengali text-gray-600">{{ review.reply }}</p>
            </div>

            <div class="flex gap-3">
              <button @click="deleteReview(review.id)" class="text-xs font-bengali text-accent hover:underline flex items-center gap-0.5">
                <span class="mi mi-sm">delete</span>{{ tr('মুছুন') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="reviewTotalPages > 1" class="flex justify-center gap-2">
        <button @click="reviewPage--; loadGivenReviews()" :disabled="reviewPage <= 1" class="px-4 py-2 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
          <span class="mi mi-sm">arrow_back</span>
        </button>
        <span class="px-3 py-2 font-bengali text-sm text-gray-600">{{ reviewPage }} / {{ reviewTotalPages }}</span>
        <button @click="reviewPage++; loadGivenReviews()" :disabled="reviewPage >= reviewTotalPages" class="px-4 py-2 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
          <span class="mi mi-sm">arrow_forward</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const api    = useApi()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const loading       = ref(true)
const activeTab     = ref('pending')
const pendingItems  = ref([])
const givenReviews  = ref([])
const reviewPage    = ref(1)
const reviewTotalPages = ref(1)

const tabs = computed(() => [
  { label: 'রিভিউ বাকি',  value: 'pending', count: pendingItems.value.length },
  { label: 'দেওয়া রিভিউ', value: 'given',   count: undefined },
])

const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

const productImage = (item) => {
  const img = item.product_image ?? ''
  return img ? img : '/images/placeholder.svg'
}

const reviewProductImage = (review) => {
  const img = review.product?.primary_image?.full_url_image ?? review.product?.primary_image?.image_path ?? ''
  return img ? img : '/images/placeholder.svg'
}

// Load orders that have delivered items not yet reviewed
const loadPendingReviews = async () => {
  try {
    const data = await api.get('/reviews/pending')
    pendingItems.value = (data ?? []).map(item => ({
      ...item,
      _rating:     0,
      _comment:    '',
      _images:     [],
      _submitting: false,
      _error:      '',
    }))
  } catch {}
}

const loadGivenReviews = async () => {
  try {
    const data = await api.get('/reviews/my', { page: reviewPage.value })
    givenReviews.value    = data.data ?? data ?? []
    reviewTotalPages.value = data.last_page ?? 1
  } catch {}
}

const submitReview = async (item) => {
  item._error = ''
  if (!item._rating) {
    item._error = 'রেটিং দিন।';
    return
  }
  if (!item._comment.trim()) {
    item._error = 'Write a comment.';
    return
  }
  item._submitting = true
  try {
    const formData = new FormData()
    formData.append('order_item_id', item.id)
    //formData.append('order_id', item.order_id)
    formData.append('product_id', item.product_id)
    formData.append('rating', item._rating)
    formData.append('comment', item._comment)
    if (item._images?.length) {
      item._images.forEach(img => formData.append('images[]', img))
    }
    await api.upload('/reviews', formData)
    // Remove from pending list
    pendingItems.value = pendingItems.value.filter(i => i.id !== item.id)
    await loadGivenReviews()
    if (process.client && window.$toast) {
      window.$toast({ type: 'success', title: 'Review submitted successfully!', duration: 2500 })
    }
  } catch (e) {
    item._error = e.message || 'There was a problem submitting the review.'
  } finally {
    item._submitting = false
  }
}

const deleteReview = async (id) => {
  if (!confirm('এই রিভিউটি মুছে ফেলবেন?')) return
  try {
    await api.delete(`/reviews/${id}`)
    givenReviews.value = givenReviews.value.filter(r => r.id !== id)
  } catch (e) {
    if (process.client && window.$toast) window.$toast({ type: 'error', title: e.message })
  }
}

// Route query support: /account/reviews?product=123 autofocuses that pending item
const route = useRoute()

onMounted(async () => {
  loading.value = true
  await Promise.all([loadPendingReviews(), loadGivenReviews()])
  loading.value = false
  // If navigated from order page with a specific product
  if (route.query.product) {
    activeTab.value = 'pending'
  }
})

watch(activeTab, (tab) => {
  if (tab === 'given' && givenReviews.value.length === 0) loadGivenReviews()
})
</script>
