<template>
  <section>
    <!-- Header row: title + countdown timer + see-all -->
    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-3">
        <!-- "Flash Sale" title -->
        <div class="sk sk-pill h-7 w-32"></div>
        <!-- Countdown blocks -->
        <div class="flex items-center gap-1">
          <div
            v-for="i in 3"
            :key="i"
            class="flex items-center gap-1"
          >
            <div class="sk sk-rect rounded-lg h-8 w-9"></div>
            <span v-if="i < 3" class="text-gray-300 font-bold text-lg leading-none">:</span>
          </div>
        </div>
      </div>
      <!-- "See all" link -->
      <div class="sk sk-pill h-4 w-16"></div>
    </div>

    <!-- Product cards row -->
    <div
      class="grid gap-4"
      :style="{ gridTemplateColumns: `repeat(${count}, minmax(0, 1fr))` }"
    >
      <div
        v-for="i in count"
        :key="i"
        class="flex flex-col gap-2"
      >
        <!-- Thumbnail with discount badge placeholder -->
        <div class="relative">
          <div class="sk w-full rounded-xl" style="aspect-ratio: 3/4;"></div>
          <!-- Badge -->
          <div class="absolute top-2 left-2 sk sk-rect h-5 w-10 rounded-md"></div>
        </div>
        <!-- Title -->
        <div class="sk sk-pill h-2.5 w-5/6"></div>
        <div class="sk sk-pill h-2.5 w-2/3"></div>
        <!-- Price -->
        <div class="flex items-center gap-2">
          <div class="sk sk-rect h-4 w-20 rounded-md"></div>
          <div class="sk sk-pill h-2.5 w-12 opacity-60"></div>
        </div>
        <!-- Progress bar (sold indicator) -->
        <div class="sk h-1.5 w-full rounded-full mt-1"></div>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  count: {
    type: Number,
    default: 5
  }
})
</script>
