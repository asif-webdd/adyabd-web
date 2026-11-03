<template>
  <div class="max-w-7xl mx-auto px-4 py-6 pb-24 md:pb-8">

    <!-- Loading Skeleton -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-4 bg-gray-200 rounded w-64 mb-6"></div>
      <div class="h-8 bg-gray-200 rounded w-full"></div>
      <div class="h-6 bg-gray-200 rounded w-3/4"></div>
      <div class="h-64 bg-gray-200 rounded-2xl"></div>
      <div class="space-y-3">
        <div class="h-4 bg-gray-200 rounded"></div>
        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
        <div class="h-4 bg-gray-200 rounded w-4/6"></div>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-16 bg-white rounded-2xl shadow-card">
      <span class="mi text-5xl text-gray-200 block mb-3">error_outline</span>
      <p class="font-bengali text-gray-500 mb-4">{{ tr('পোস্টটি লোড করা যায়নি।') }}</p>
      <NuxtLink to="/blog" class="btn-primary font-bengali text-sm">{{ tr('ব্লগে ফিরুন') }}</NuxtLink>
    </div>

    <template v-else-if="post">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1 text-sm font-bengali text-gray-500 mb-6 flex-wrap">
        <NuxtLink to="/" class="hover:text-primary flex items-center gap-0.5">
          <span class="mi mi-sm">home</span>{{ tr('হোম') }}
        </NuxtLink>
        <span class="mi mi-sm">chevron_right</span>
        <NuxtLink to="/blog" class="hover:text-primary">{{ tr('ব্লগ') }}</NuxtLink>
        <span class="mi mi-sm">chevron_right</span>
        <NuxtLink v-if="post.category" :to="`/blog?category=${post.category.slug}`" class="hover:text-primary truncate max-w-[120px]">
          {{ post.category.name }}
        </NuxtLink>
        <span class="mi mi-sm">chevron_right</span>
        <span class="text-gray-700 truncate max-w-[160px]">{{ post.title }}</span>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Article -->
        <article class="lg:col-span-2">
          <div class="mb-6">
            <!-- Category Badge -->
            <NuxtLink v-if="post.category" :to="`/blog?category=${post.category.slug}`" class="badge mb-3 inline-flex items-center gap-1 hover:opacity-80 transition-opacity" :style="{ backgroundColor: (post.category.color ?? '#006A4E') + '1A', color: post.category.color ?? '#006A4E' }">
              <span class="mi mi-sm">category</span>{{ post.category.name }}
            </NuxtLink>

            <!-- Title -->
            <h1 class="font-display font-extrabold text-2xl md:text-3xl text-gray-900 leading-tight mb-4">{{ post.title }}</h1>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-3 text-sm font-bengali text-gray-500 mb-4">
              <div class="flex items-center gap-2">
                <img v-if="post.author?.avatar" :src="post.author.avatar" :alt="post.author.name" class="w-8 h-8 rounded-full object-cover"/>
                <div v-else class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="mi mi-sm text-primary">person</span>
                </div>
                <span>{{ post.author?.name ?? `${branding.value.shop_name_bn || branding.value.shop_name} টিম` }}</span>
              </div>
              <span v-if="post.published_at" class="flex items-center gap-1">
                <span class="mi mi-sm">calendar_today</span>{{ post.published_at }}
              </span>
              <span v-if="post.read_time" class="flex items-center gap-1">
                <span class="mi mi-sm">schedule</span>{{ post.read_time }} মিনিট পড়া
              </span>
              <span class="flex items-center gap-1">
                <span class="mi mi-sm">visibility</span>{{ post.view_count?.toLocaleString('bn-BD') }} ভিউ
              </span>
            </div>

            <!-- Featured Image -->
            <img v-if="post.featured_image" :src="postImage(post)" :alt="post.title" class="w-full h-64 object-cover rounded-2xl mb-6"/>
          </div>

          <!-- Content -->
          <div
              class="prose prose-bengali max-w-none font-bengali text-gray-700 leading-relaxed mb-6"
              v-html="post.content"
          ></div>

          <!-- Tags -->
          <div v-if="post.tags?.length" class="flex flex-wrap gap-2 mt-6">
            <NuxtLink v-for="tag in post.tags" :key="tag" :to="`/blog?tag=${encodeURIComponent(tag)}`" class="badge bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full hover:bg-primary hover:text-white transition-colors cursor-pointer flex items-center gap-0.5">
              <span class="mi mi-sm">tag</span>{{ tag }}
            </NuxtLink>
          </div>

          <!-- Like & Share -->
          <div class="mt-6 flex flex-wrap gap-3 items-center">
            <!-- Like Button -->
            <button
                @click="toggleLike"
                :disabled="likeLoading"
                class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 transition-all font-bengali text-sm font-semibold"
                :class="liked
                ? 'border-red-400 bg-red-50 text-red-500'
                : 'border-gray-200 text-gray-500 hover:border-red-300 hover:text-red-400'"
            >
              <span class="mi mi-sm">{{ liked ? 'favorite' : 'favorite_border' }}</span>
              {{ likeCount }} লাইক
            </button>

            <!-- Share -->
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-bengali text-sm text-gray-500 font-semibold flex items-center gap-1">
                <span class="mi mi-sm">share</span>{{ tr('শেয়ার:') }}
              </span>
              <a :href="facebookShareUrl" target="_blank" rel="noopener"
                 class="flex items-center gap-1.5 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-bengali hover:bg-blue-700 transition-colors">
                <span class="mi mi-sm">thumb_up</span>Facebook
              </a>
              <a :href="whatsappShareUrl" target="_blank" rel="noopener"
                 class="flex items-center gap-1.5 bg-green-500 text-white px-3 py-1.5 rounded-lg text-xs font-bengali hover:bg-green-600 transition-colors">
                <span class="mi mi-sm">chat</span>WhatsApp
              </a>
              <button @click="copyLink" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bengali transition-colors" :class="copied ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                <span class="mi mi-sm">{{ copied ? 'check' : 'link' }}</span>
                {{ copied ? 'কপি হয়েছে!' : 'লিংক কপি' }}
              </button>
            </div>
          </div>

          <!-- Author Box -->
          <div v-if="post.author" class="mt-6 bg-white rounded-2xl shadow-card p-5 flex items-start gap-4">
            <img v-if="post.author.avatar" :src="post.author.avatar" :alt="post.author.name" class="w-14 h-14 rounded-full object-cover shrink-0"/>
            <div v-else class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="mi text-2xl text-primary">person</span>
            </div>
            <div>
              <p class="font-display font-bold text-gray-800">{{ post.author.name }}</p>
              <p class="font-bengali text-xs text-primary mb-2 flex items-center gap-1">
                <span class="mi mi-sm">edit</span>{{ tr('লেখক') }}
              </p>
            </div>
          </div>

          <!-- Comments Section -->
          <div v-if="post.allow_comments" class="mt-8">
            <h2 class="font-display font-bold text-xl text-gray-800 mb-5 flex items-center gap-2">
              <span class="mi text-primary">chat_bubble_outline</span>
              মন্তব্য ({{ commentsTotal }})
            </h2>

            <!-- Comment Form -->
            <form @submit.prevent="submitComment" class="bg-white rounded-2xl shadow-card p-5 mb-6 space-y-3">
              <textarea
                  v-model="commentForm.comment"
                  rows="3"
                  :placeholder="tr('আপনার মন্তব্য লিখুন...')"
                  class="w-full border border-gray-200 rounded-xl p-3 font-bengali text-sm resize-none focus:outline-none focus:border-primary"
              ></textarea>
              <div v-if="!authUser" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input
                    v-model="commentForm.author_name"
                    type="text"
                    :placeholder="tr('আপনার নাম *')"
                    class="border border-gray-200 rounded-xl p-3 font-bengali text-sm focus:outline-none focus:border-primary"
                />
                <input
                    v-model="commentForm.author_email"
                    type="email"
                    :placeholder="tr('ইমেইল (ঐচ্ছিক)')"
                    class="border border-gray-200 rounded-xl p-3 font-bengali text-sm focus:outline-none focus:border-primary"
                />
              </div>
              <div v-if="commentError" class="text-red-500 text-xs font-bengali">{{ commentError }}</div>
              <button
                  type="submit"
                  :disabled="commentSubmitting"
                  class="btn-primary text-sm font-bengali flex items-center gap-2 disabled:opacity-60"
              >
                <span class="mi mi-sm">send</span>
                {{ commentSubmitting ? 'পাঠানো হচ্ছে...' : 'মন্তব্য পাঠান' }}
              </button>
            </form>

            <!-- Comments List -->
            <div v-if="commentsLoading" class="space-y-3">
              <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-4 shadow-card animate-pulse h-24"></div>
            </div>
            <div v-else class="space-y-4">
              <div
                  v-for="comment in comments"
                  :key="comment.id"
                  class="bg-white rounded-2xl shadow-card p-4"
              >
                <div class="flex items-start gap-3">
                  <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <span class="mi text-sm text-primary">person</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                      <span class="font-display font-bold text-sm text-gray-800">
                        {{ comment.user?.name ?? comment.author_name ?? 'অজ্ঞাত' }}
                      </span>
                      <span class="text-xs font-bengali text-gray-400">
                        {{ formatDate(comment.created_at) }}
                      </span>
                    </div>
                    <p class="font-bengali text-sm text-gray-600 leading-relaxed">{{ comment.comment }}</p>

                    <!-- Replies -->
                    <div v-if="comment.replies?.length" class="mt-3 space-y-3 pl-4 border-l-2 border-gray-100">
                      <div v-for="reply in comment.replies" :key="reply.id" class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                          <span class="mi text-xs text-gray-400">person</span>
                        </div>
                        <div>
                          <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-display font-bold text-xs text-gray-700">
                              {{ reply.user?.name ?? reply.author_name ?? 'অজ্ঞাত' }}
                            </span>
                            <span class="text-xs font-bengali text-gray-400">{{ formatDate(reply.created_at) }}</span>
                          </div>
                          <p class="font-bengali text-xs text-gray-600 leading-relaxed">{{ reply.comment }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More Comments -->
              <button v-if="commentsPage < commentsTotalPages" @click="loadMoreComments" :disabled="commentsLoading" class="w-full py-3 text-sm font-bengali text-primary border-2 border-primary/30 rounded-xl hover:bg-primary/5 transition-colors">
                {{ tr('আরও মন্তব্য দেখুন') }}
              </button>
            </div>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-5">
          <!-- CTA -->
          <div class="bg-gradient-to-br from-primary to-green-700 rounded-2xl p-5 text-white text-center">
            <span class="mi text-4xl text-white/80 mb-2 block">shopping_bag</span>
            <h3 class="font-display font-bold text-lg mb-2">{{ tr('এখনই কেনাকাটা করুন') }}</h3>
            <p class="font-bengali text-green-100 text-sm mb-4">{{ tr('হাজারো পণ্য সেরা দামে।') }}</p>
            <NuxtLink to="/" class="block bg-white text-primary font-display font-bold py-2.5 rounded-xl hover:bg-green-50 transition-colors flex items-center justify-center gap-1">
              <span class="mi mi-sm">storefront</span>{{ tr('কেনাকাটা শুরু করুন') }}
            </NuxtLink>
          </div>

          <!-- Related Posts -->
          <div v-if="related.length" class="bg-white rounded-2xl shadow-card p-5">
            <h3 class="font-display font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
              <span class="mi text-primary">article</span>{{ tr('আরও পড়ুন') }}
            </h3>
            <div class="space-y-3">
              <NuxtLink v-for="rp in related" :key="rp.id" :to="`/blog/${rp.slug}`" class="flex items-start gap-3 hover:bg-gray-50 rounded-xl p-2 transition-colors block">
                <img :src="postImage(rp)" :alt="rp.title" class="w-16 h-12 object-cover rounded-xl shrink-0"/>
                <div>
                  <p class="font-bengali font-semibold text-gray-800 text-sm line-clamp-2 leading-snug">{{ rp.title }}</p>
                  <p class="font-bengali text-xs text-gray-400 mt-0.5 flex items-center gap-0.5">
                    <span class="mi mi-sm">schedule</span>{{ rp.read_time ?? '৩' }} মিনিট
                  </p>
                </div>
              </NuxtLink>
            </div>
          </div>

          <!-- Tags Sidebar -->
          <div v-if="post.tags?.length" class="bg-white rounded-2xl shadow-card p-5">
            <h3 class="font-display font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
              <span class="mi text-primary">label</span>{{ tr('ট্যাগ') }}
            </h3>
            <div class="flex flex-wrap gap-2">
              <NuxtLink v-for="tag in post.tags" :key="tag" :to="`/blog?tag=${encodeURIComponent(tag)}`" class="badge bg-gray-100 text-gray-600 hover:bg-primary hover:text-white transition-colors cursor-pointer text-xs flex items-center gap-0.5">
                <span class="mi mi-sm">tag</span>{{ tag }}
              </NuxtLink>
            </div>
          </div>
        </aside>
      </div>
    </template>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { branding } = useAppearance()
import {useAuth} from "../../composables/useAuth.js";

definePageMeta({ layout: 'default' })
const route   = useRoute()
const api     = useApi()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

// ─── Auth ─────────────────────────────────────────────────
const authUser = useAuth() ?? null   // swap with your own composable if different

// ─── Post Data ────────────────────────────────────────────
const post     = ref(null)
const related  = ref([])
const loading  = ref(true)
const error    = ref(false)
const likeCount = ref(0)
const liked     = ref(false)
const likeLoading = ref(false)

// ─── Comments ─────────────────────────────────────────────
const comments           = ref([])
const commentsLoading    = ref(false)
const commentsPage       = ref(1)
const commentsTotalPages = ref(1)
const commentsTotal      = ref(0)

const commentForm = reactive({
  comment:      '',
  author_name:  '',
  author_email: '',
})
const commentError      = ref('')
const commentSubmitting = ref(false)

// ─── UI State ─────────────────────────────────────────────
const copied = ref(false)

// ─── Helpers ──────────────────────────────────────────────
const postImage = (p) => {
  const img = p.featured_image ?? p.image ?? ''
  if (!img) return '/images/placeholder.svg'
  return img.startsWith('http') ? img : `${apiBase}/storage/${img}`
}

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

const pageUrl = computed(() =>
    typeof window !== 'undefined' ? window.location.href : `${apiBase}/blog/${route.params.slug}`
)

const facebookShareUrl = computed(() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl.value)}`)
const whatsappShareUrl = computed(() => `https://api.whatsapp.com/send?text=${encodeURIComponent((post.value?.title ?? '') + ' ' + pageUrl.value)}`)

// ─── SEO ──────────────────────────────────────────────────
useHead(() => {
  const p = post.value
  if (!p) return {}
  const t = p.meta_title ?? p.title
  const d = p.meta_description ?? p.excerpt ?? ''
  return {
    title: t,
    meta: [
      { name: 'description', content: d },
      { property: 'og:title',       content: t },
      { property: 'og:description', content: d },
      { property: 'og:image',       content: postImage(p) },
      { property: 'og:type',        content: 'article' },
    ],
  }
})

// ─── Load Post ────────────────────────────────────────────
const loadPost = async () => {
  loading.value = true
  error.value   = false
  try {
    const data    = await api.get(`/blog/${route.params.slug}`)
    post.value    = data.post ?? data
    related.value = data.related ?? []
    likeCount.value = data.like_count ?? 0
    liked.value     = data.liked     ?? false

    if (post.value.allow_comments) {
      await loadComments()
    }
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

// ─── Load Comments ────────────────────────────────────────
const loadComments = async (append = false) => {
  commentsLoading.value = true
  try {
    const data = await api.get(`/blog/${post.value.id}/comments`, { page: commentsPage.value })
    const list = data.data ?? data ?? []
    comments.value        = append ? [...comments.value, ...list] : list
    commentsTotalPages.value = data.last_page ?? 1
    commentsTotal.value      = data.total     ?? list.length
  } catch {
    // silent fail — comments not critical
  } finally {
    commentsLoading.value = false
  }
}

const loadMoreComments = () => {
  commentsPage.value++
  loadComments(true)
}

// ─── Like ─────────────────────────────────────────────────
const toggleLike = async () => {
  if (likeLoading.value) return
  likeLoading.value = true
  try {
    const data   = await api.post(`/blog/${post.value.id}/like`)
    liked.value  = data.liked  ?? !liked.value
    likeCount.value = data.count ?? likeCount.value
  } catch {
    // silent fail
  } finally {
    likeLoading.value = false
  }
}

// ─── Comment Submit ───────────────────────────────────────
const submitComment = async () => {
  commentError.value = ''
  if (!commentForm.comment.trim()) {
    commentError.value = 'মন্তব্য লিখুন।'
    return
  }
  if (!authUser && !commentForm.author_name.trim()) {
    commentError.value = 'নাম লিখুন।'
    return
  }
  commentSubmitting.value = true
  try {
    await api.post(`/blog/${post.value.id}/comments`, { ...commentForm })
    commentForm.comment      = ''
    commentForm.author_name  = ''
    commentForm.author_email = ''
    commentsPage.value = 1
    await loadComments()   // refresh first page
  } catch (e) {
    commentError.value = e?.data?.message ?? 'মন্তব্য পাঠানো যায়নি।'
  } finally {
    commentSubmitting.value = false
  }
}

// ─── Copy Link ────────────────────────────────────────────
const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(pageUrl.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    // fallback: do nothing
  }
}

// ─── Init ─────────────────────────────────────────────────
// Server-side post + related, so crawlers / social previews get the real title, text and og tags.
const { data: ssrPost } = await useAsyncData(`blog:${route.params.slug}`, () =>
  api.get(`/blog/${route.params.slug}`).catch(() => null)
)
const hadServerData = !!ssrPost.value
if (hadServerData) {
  post.value      = ssrPost.value.post ?? ssrPost.value
  related.value   = ssrPost.value.related ?? []
  likeCount.value = ssrPost.value.like_count ?? 0
  loading.value   = false
}

onMounted(async ()=> {
  commentForm.author_email = authUser?.user?.email ?? ''
  commentForm.author_name = authUser?.user?.name ?? ''
  if (hadServerData) {
    // refresh per-user state (liked) and load comments in the browser
    try { const d = await api.get(`/blog/${route.params.slug}`); liked.value = d.liked ?? false; likeCount.value = d.like_count ?? likeCount.value } catch {}
    if (post.value?.allow_comments) await loadComments()
    return
  }
  await loadPost()
})
</script>