<template>
  <div class="max-w-7xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <div class="text-center mb-10">
      <h1 class="section-title mb-2 flex items-center justify-center gap-2"><span class="mi text-primary">article</span>{{ branding.shop_name_bn || branding.shop_name }} ব্লগ</h1>
      <p class="font-bengali text-gray-500">{{ tr('কেনাকাটার টিপস, পণ্য রিভিউ ও টেক আপডেট') }}</p>
    </div>

    <!-- Featured post (first from API) -->
    <NuxtLink v-if="featuredPost" :to="`/blog/${featuredPost.slug}`" class="relative overflow-hidden rounded-2xl mb-10 grid grid-cols-1 md:grid-cols-2 group block">
      <div class="relative p-8 text-white flex flex-col justify-center min-h-[240px]" style="background:linear-gradient(135deg,#006A4E,#004d38)">
        <img v-if="featuredPost.image" :src="postImage(featuredPost)" class="absolute inset-0 w-full h-full object-cover opacity-20"/>
        <div class="relative">
          <span class="badge bg-white/20 border border-white/30 text-white mb-3 inline-flex items-center gap-1"><span class="mi mi-sm">push_pin</span>{{ tr('ফিচার্ড') }}</span>
          <h2 class="font-display font-extrabold text-2xl md:text-3xl mb-3 leading-tight group-hover:text-yellow-300 transition-colors">{{ featuredPost.title }}</h2>
          <p class="font-bengali text-green-100 text-sm mb-4 line-clamp-2">{{ featuredPost.excerpt }}</p>
          <div class="flex items-center gap-3 text-green-200 text-xs font-bengali flex-wrap">
            <span class="flex items-center gap-1"><span class="mi mi-sm">person</span>{{ featuredPost.author?.name ?? featuredPost.author }}</span>
            <span class="flex items-center gap-1"><span class="mi mi-sm">calendar_today</span>{{ formatDate(featuredPost.published_at) }}</span>
            <span v-if="featuredPost.read_time" class="flex items-center gap-1"><span class="mi mi-sm">schedule</span>{{ featuredPost.read_time }} মিনিট</span>
          </div>
        </div>
      </div>
      <div class="overflow-hidden min-h-[200px]">
        <img :src="postImage(featuredPost)" :alt="featuredPost.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
      </div>
    </NuxtLink>
    <!-- Category Filter -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
      <!-- Skeleton while loading -->
      <template v-if="categoriesLoading">
        <div v-for="i in 5" :key="i" class="h-9 w-24 rounded-full bg-gray-200 animate-pulse shrink-0"></div>
      </template>

      <template v-else>
        <button v-for="cat in categories" :key="cat.value" @click="activeCat = cat.value; currentPage = 1; loadPosts()"
                class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0 flex items-center gap-1"
                :class="activeCat === cat.value ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary'">
          {{ cat.label }}
          <span v-if="cat.count !== null" class="text-xs px-1.5 py-0.5 rounded-full" :class="activeCat === cat.value ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'">{{ cat.count }}</span>
        </button>
      </template>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
      <div v-for="i in 6" :key="i" class="bg-white rounded-2xl h-80 animate-pulse"></div>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
      <NuxtLink v-for="post in posts" :key="post.id" :to="`/blog/${post.slug}`" class="bg-white rounded-2xl shadow-card overflow-hidden hover:shadow-card-hover hover:-translate-y-1 transition-all group block">
        <div class="overflow-hidden h-44">
          <img :src="postImage(post)" :alt="post.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
        </div>
        <div class="p-5">
          <span v-if="post.category" class="badge text-xs mb-2 inline-block bg-primary/10 text-primary">{{ post.category?.name ?? post.category }}</span>
          <h3 class="font-display font-bold text-gray-800 text-base leading-snug mb-2 group-hover:text-primary transition-colors line-clamp-2">{{ post.title }}</h3>
          <p class="font-bengali text-gray-500 text-sm line-clamp-2 mb-3">{{ post.excerpt }}</p>
          <div class="flex items-center justify-between text-xs font-bengali text-gray-400">
            <span class="flex items-center gap-1"><span class="mi mi-sm">person</span>{{ post.author?.name ?? post.author }}</span>
            <span class="flex items-center gap-1"><span class="mi mi-sm">schedule</span>{{ post.read_time ?? '৩' }} মিনিট</span>
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Empty -->
    <div v-if="!loading && posts.length === 0" class="text-center py-12 bg-white rounded-2xl shadow-card">
      <span class="mi text-4xl text-gray-200 block mb-2">article</span>
      <p class="font-bengali text-gray-400">{{ tr('এই ক্যাটাগরিতে কোনো পোস্ট নেই।') }}</p>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center gap-2">
      <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="w-10 h-10 rounded-xl border-2 border-gray-200 text-gray-500 hover:border-primary flex items-center justify-center disabled:opacity-40">
        <span class="mi mi-sm">chevron_left</span>
      </button>
      <button v-for="p in visiblePages" :key="p" @click="goPage(p)" class="w-10 h-10 rounded-xl border-2 font-display font-bold transition-colors flex items-center justify-center" :class="p === currentPage ? 'border-primary bg-primary text-white' : 'border-gray-200 text-gray-500 hover:border-primary hover:text-primary'">
        {{ p }}
      </button>
      <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="w-10 h-10 rounded-xl border-2 border-gray-200 text-gray-500 hover:border-primary flex items-center justify-center disabled:opacity-40">
        <span class="mi mi-sm">chevron_right</span>
      </button>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { branding } = useAppearance()
definePageMeta({ layout: 'default' })

const api     = useApi()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

const posts        = ref([])
const featuredPost = ref(null)
const loading      = ref(true)
const activeCat    = ref('')
const currentPage  = ref(1)
const totalPages   = ref(1)

// ─── Categories (dynamic) ─────────────────────────────────
const categoriesLoading = ref(false)
const categories = ref([{ value: '', label: 'সব', count: null }]) // "All" is always first

const loadCategories = async () => {
  categoriesLoading.value = true
  try {
    const data = await api.get('/blog/categories')
    const list = data.data ?? data ?? []
    categories.value = [
      { value: '', label: 'সব', count: null },
      ...list.map(cat => ({
        value: cat.slug,
        label: cat.name,
        count: cat.posts_count ?? null,
      }))
    ]
  } catch {
    // silent fail — keep the "সব" fallback
  } finally {
    categoriesLoading.value = false
  }
}

// ─── Pages ────────────────────────────────────────────────
const visiblePages = computed(() => {
  const arr = [], start = Math.max(1, currentPage.value - 2), end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) arr.push(i)
  return arr
})

// ─── Helpers ──────────────────────────────────────────────
const postImage = (post) => {
  const img = post.featured_image ?? post.image ?? post.thumbnail ?? ''
  if (!img) return '/images/placeholder.svg'
  return img.startsWith('http') ? img : `${apiBase}/storage/${img}`
}

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

// ─── Load Posts ───────────────────────────────────────────
const loadPosts = async () => {
  loading.value = true
  try {
    const params = { page: currentPage.value }
    if (activeCat.value) params.category = activeCat.value
    const data = await api.get('/blog', params)
    const all  = data.data ?? data ?? []

    if (currentPage.value === 1 && !activeCat.value && all.length > 0) {
      featuredPost.value = all[0]
      posts.value = all.slice(1)
    } else {
      featuredPost.value = null
      posts.value = all
    }
    totalPages.value = data.last_page ?? 1
  } catch {
    posts.value = []
  } finally {
    loading.value = false
  }
}

const goPage = (p) => {
  currentPage.value = p
  loadPosts()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ─── Init ─────────────────────────────────────────────────
// Server-side first page (all categories) so crawlers and the first paint already see the posts.
const { data: ssrBlog } = await useAsyncData('blog:index', async () => {
  const [list, cats] = await Promise.all([
    api.get('/blog', { page: 1 }).catch(() => null),
    api.get('/blog/categories').catch(() => null),
  ])
  return { list, cats }
})
const applyList = (data) => {
  const all = data?.data ?? data ?? []
  if (currentPage.value === 1 && !activeCat.value && all.length > 0) {
    featuredPost.value = all[0]
    posts.value = all.slice(1)
  } else {
    featuredPost.value = null
    posts.value = all
  }
  totalPages.value = data?.last_page ?? 1
}
const applyCats = (data) => {
  const list = data?.data ?? data ?? []
  categories.value = [{ value: '', label: 'সব', count: null }, ...list.map(c => ({ value: c.slug, label: c.name, count: c.posts_count ?? null }))]
}
const hadServerData = !!ssrBlog.value?.list
if (hadServerData) {
  applyList(ssrBlog.value.list)
  if (ssrBlog.value.cats) applyCats(ssrBlog.value.cats)
  loading.value = false
}

useHead({
  title: `ব্লগ | ${branding.value?.shop_name_bn || branding.value?.shop_name || ''}`,
  meta: [{ name: 'description', content: 'কেনাকাটার টিপস, পণ্য রিভিউ ও টেক আপডেট' }],
})

onMounted(() => {
  if (hadServerData) return
  loadCategories()
  loadPosts()
})
</script>