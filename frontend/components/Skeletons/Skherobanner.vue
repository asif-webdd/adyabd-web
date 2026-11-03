<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- Main hero block -->
    <div class="lg:col-span-2 relative overflow-hidden rounded-2xl min-h-[300px] sk">
      <!-- Overlay content shimmer pills -->
      <div class="absolute bottom-6 left-6 flex flex-col gap-3">
        <div class="sk sk-pill h-5 w-28 opacity-60"></div>
        <div class="sk sk-pill h-8 w-56 opacity-70"></div>
        <div class="sk sk-pill h-4 w-40 opacity-50"></div>
        <div class="sk sk-rect h-9 w-32 rounded-xl opacity-70 mt-1"></div>
      </div>
    </div>

    <!-- Right promo stacks -->
    <div class="grid gap-4" style="grid-template-rows: 1fr 1fr;">

      <!-- Promo 1 -->
      <div class="relative overflow-hidden rounded-2xl min-h-[145px] sk">
        <div class="absolute bottom-5 left-5 flex flex-col gap-2">
          <div class="sk sk-pill h-4 w-24 opacity-60"></div>
          <div class="sk sk-pill h-6 w-36 opacity-70"></div>
          <div class="sk sk-pill h-3 w-20 opacity-50"></div>
          <div class="sk sk-rect h-7 w-20 rounded-lg opacity-60 mt-1"></div>
        </div>
      </div>

      <!-- Promo 2 -->
      <div class="relative overflow-hidden rounded-2xl min-h-[145px] sk">
        <div class="absolute bottom-5 left-5 flex flex-col gap-2">
          <div class="sk sk-pill h-4 w-24 opacity-60"></div>
          <div class="sk sk-pill h-6 w-36 opacity-70"></div>
          <div class="sk sk-pill h-3 w-20 opacity-50"></div>
          <div class="sk sk-rect h-7 w-20 rounded-lg opacity-60 mt-1"></div>
        </div>
      </div>

    </div>
  </div>
</template>
