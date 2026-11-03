<template>
  <section>
    <!-- Section header -->
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-2">
        <!-- Title -->
        <div class="sk sk-pill h-6 w-40"></div>
        <!-- Optional badge -->
        <div v-if="showBadge" class="sk sk-pill h-5 w-12"></div>
      </div>

      <!-- Right side: tabs OR "see all" link -->
      <div v-if="showTabs" class="flex gap-2 overflow-x-auto">
        <div
          v-for="i in tabCount"
          :key="i"
          class="sk sk-pill h-7 shrink-0"
          :style="{ width: `${60 + (i % 3) * 20}px` }"
        ></div>
      </div>
      <div v-else class="sk sk-pill h-4 w-16"></div>
    </div>

    <!-- Product grid -->
    <div
      class="grid gap-4"
      :class="gridClass"
    >
      <SkProductCard
        v-for="i in count"
        :key="i"
        :aspect-ratio="aspectRatio"
      />
    </div>

    <!-- "Load more" button placeholder -->
    <div v-if="showMore" class="flex justify-center mt-6">
      <div class="sk sk-rect h-10 w-40 rounded-xl"></div>
    </div>
  </section>
</template>

<script setup>
import SkProductCard from './SkProductCard.vue'

defineProps({
  /** Number of skeleton cards to render */
  count: {
    type: Number,
    default: 10
  },
  /** Tailwind grid cols class */
  gridClass: {
    type: String,
    default: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5'
  },
  /** Show tab filter buttons on the right */
  showTabs: {
    type: Boolean,
    default: false
  },
  /** Number of tab pills to show */
  tabCount: {
    type: Number,
    default: 4
  },
  /** Show badge next to title (e.g. "নতুন") */
  showBadge: {
    type: Boolean,
    default: false
  },
  /** Show "see more" button at bottom */
  showMore: {
    type: Boolean,
    default: false
  },
  /** Card image aspect ratio */
  aspectRatio: {
    type: String,
    default: '3/4'
  }
})
</script>
