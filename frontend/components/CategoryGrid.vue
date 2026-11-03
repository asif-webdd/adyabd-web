<template>
  <section>
    <div class="flex items-center justify-between mb-5">
      <h2 class="section-title">{{ tr('ক্যাটাগরি অনুযায়ী কেনাকাটা') }}</h2>
      <NuxtLink to="/search" class="text-primary font-bengali text-sm font-medium flex items-center gap-1 hover:underline">
        {{ tr('সব দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
      </NuxtLink>
    </div>
    <!-- < 768px: horizontal slider (hidden scrollbar); md+: grid -->
    <div class="flex gap-3 overflow-x-auto scrollbar-hide snap-slider -mx-4 px-4 pb-1 md:mx-0 md:px-0 md:pb-0 md:overflow-visible md:grid" :class="categoryGridCols">
      <!-- API categories if loaded, else fallback -->
      <NuxtLink v-for="cat in categories" :key="cat.slug" :to="`/category/${cat.slug}`" class="flex flex-col items-center gap-2 p-3 bg-white rounded-2xl shrink-0 w-[88px] md:w-auto shadow-card hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 group">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" :class="cat.bg">
          <span v-if="cat.icon" class="mi mi-sm text-2xl" :class="cat.color">{{ cat.icon }}</span>
          <img v-else-if="cat.image" :src="cat.image" :alt="cat.name" class="w-8 h-8 object-contain"/>
        </div>
        <span class="text-xs font-bengali font-medium text-gray-700 text-center leading-tight">{{ isEn ? cat.name : (cat.name_bn || cat.name) }}</span>
      </NuxtLink>
    </div>
  </section>
</template>

<script setup>
const { tr, isEn } = useLang()
import {useLandingPageStore} from "../stores/LandingPage.js"
const { categoryGridCols } = useAppearance()
const store = useLandingPageStore()
const categories = computed(() => store.categories.map((category)=> {
  return {
    name:  category.name,
    name_bn: category.name_bn,
    slug:  category.slug,
    icon:  category.icon || 'category',
    bg:    category.bg || 'bg-gray-100',
    color: category.color || 'text-gray-600',
    image: category.image,
  }
}))
</script>
