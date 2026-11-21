<template>
  <!-- 50x50 thumbnails -->
  <div v-if="imgs.length" class="flex gap-2 mt-2 flex-wrap">
    <button v-for="(img, i) in imgs" :key="img + i" type="button" @click="openAt(i)"
            class="w-[50px] h-[50px] rounded-lg overflow-hidden border border-gray-200 hover:border-primary transition-colors shrink-0"
            :aria-label="`Review photo ${i + 1}`">
      <img :src="img" alt="" loading="lazy" class="w-full h-full object-cover"/>
    </button>
  </div>

  <!-- Popup: image carousel (left) + review & product (right) -->
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[1000] bg-black/60 flex items-center justify-center p-3 md:p-6" @click.self="close">
      <div class="relative bg-white w-full max-w-4xl max-h-[92vh] md:h-[600px] overflow-hidden flex flex-col md:flex-row rounded-lg shadow-2xl">
        <!-- Image carousel -->
        <div class="relative md:w-1/2 h-72 md:h-full bg-gray-100 shrink-0">
          <img :src="imgs[idx]" alt="" class="w-full h-full object-cover"/>
          <button type="button" @click="close" aria-label="Close"
                  class="absolute top-3 left-3 w-9 h-9 rounded bg-black/50 hover:bg-black/70 text-white flex items-center justify-center">
            <span class="mi">close</span>
          </button>
          <template v-if="imgs.length > 1">
            <button type="button" @click="prev" aria-label="Previous"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 hover:bg-white shadow flex items-center justify-center">
              <span class="mi">chevron_left</span>
            </button>
            <button type="button" @click="next" aria-label="Next"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 hover:bg-white shadow flex items-center justify-center">
              <span class="mi">chevron_right</span>
            </button>
          </template>
        </div>

        <!-- Content -->
        <div class="md:w-1/2 flex flex-col min-h-0 flex-1">
          <div class="p-5 md:p-6 overflow-y-auto flex-1">
            <div class="flex items-center justify-between gap-2">
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" class="mi" :class="i <= review.rating ? 'mi-filled text-gold' : 'text-gray-300'">star</span>
              </div>
              <span class="text-xs text-gray-400">{{ dateText }}</span>
            </div>
            <p class="font-bold text-gray-900 mt-3 mb-2">{{ review.user?.name }}</p>
            <p v-if="review.title" class="font-semibold text-gray-800 text-sm mb-1">{{ review.title }}</p>
            <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ review.review || review.comment }}</p>
          </div>

          <!-- Reviewed product -->
          <div v-if="product" class="border-t border-gray-100 p-4 flex items-center gap-4">
            <img :src="productImg" :alt="product.name" class="w-16 h-16 rounded object-cover bg-gray-50 shrink-0"/>
            <div class="min-w-0 flex-1">
              <p class="text-sm text-gray-900 line-clamp-2">{{ product.name }}</p>
              <p v-if="product.price" class="text-sm font-bold text-primary mt-0.5">৳{{ Number(product.price).toLocaleString() }}</p>
              <NuxtLink :to="`/product/${product.slug}`" @click="close"
                        class="inline-block mt-2 px-4 py-1.5 border border-gray-400 hover:border-primary hover:text-primary text-sm transition-colors">
                {{ tr('Shop Now') }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
// review: { rating, review/comment, title, images[], user{name}, created_at }
// product: { name, slug, price, images[{full_url_image}] }  (the product that was reviewed)
const props = defineProps({
  review:  { type: Object, required: true },
  product: { type: Object, default: null },
})
const { tr } = useLang()

const imgs = computed(() => (props.review?.images || []).slice(0, 3))
const show = ref(false)
const idx  = ref(0)

const productImg = computed(() => {
  const p = props.product
  return p?.images?.[0]?.full_url_image || p?.primary_image?.full_url_image || p?.image || '/images/placeholder.svg'
})
const dateText = computed(() => props.review?.created_at ? new Date(props.review.created_at).toLocaleDateString('bn-BD') : '')

const openAt = (i) => { idx.value = i; show.value = true }
const close = () => { show.value = false }
const next  = () => { idx.value = (idx.value + 1) % imgs.value.length }
const prev  = () => { idx.value = (idx.value - 1 + imgs.value.length) % imgs.value.length }

const onKey = (e) => {
  if (e.key === 'Escape') close()
  else if (e.key === 'ArrowRight' && imgs.value.length > 1) next()
  else if (e.key === 'ArrowLeft' && imgs.value.length > 1) prev()
}
watch(show, (v) => {
  if (!import.meta.client) return
  document.body.style.overflow = v ? 'hidden' : ''
  v ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  if (!import.meta.client) return
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKey)
})
</script>
