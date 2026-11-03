<template>
  <section>
    <!-- Section header -->
    <div class="flex items-center justify-between mb-5">
      <div class="sk sk-pill h-6 w-36"></div>
      <div class="sk sk-pill h-4 w-16"></div>
    </div>

    <!-- Category chips grid -->
    <div class="grid gap-3 md:gap-4" :class="categoryGridCols">
      <div
        v-for="i in count"
        :key="i"
        class="flex flex-col items-center gap-2"
      >
        <!-- Image circle / square -->
        <div class="sk w-full rounded-2xl" style="aspect-ratio: 1;"></div>
        <!-- Label -->
        <div class="sk sk-pill h-2 w-4/5"></div>
      </div>
    </div>
  </section>
</template>

<script setup>
const { categoryGridCols } = useAppearance()
defineProps({
  count: {
    type: Number,
    default: 8
  }
})
</script>
