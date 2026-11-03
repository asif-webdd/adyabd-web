<template>
  <div class="flex flex-col gap-2">
    <!-- Thumbnail -->
    <div class="sk w-full rounded-xl" :style="{ aspectRatio: aspectRatio }"></div>

    <!-- Title line 1 -->
    <div class="sk sk-pill h-2.5 w-5/6"></div>
    <!-- Title line 2 -->
    <div class="sk sk-pill h-2.5 w-3/4"></div>

    <!-- Rating -->
    <div class="flex items-center gap-1.5">
      <div class="sk sk-pill h-2 w-16"></div>
      <div class="sk sk-pill h-2 w-8"></div>
    </div>

    <!-- Price row -->
    <div class="flex items-center gap-2 mt-1">
      <div class="sk sk-rect h-4 w-20 rounded-md"></div>
      <div class="sk sk-pill h-2.5 w-12 opacity-60"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  aspectRatio: {
    type: String,
    default: '3/4'
  }
})
</script>
