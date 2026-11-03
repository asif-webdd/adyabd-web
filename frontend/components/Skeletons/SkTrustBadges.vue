<template>
  <div class="bg-white rounded-2xl shadow-sm p-4 md:p-5">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100">
        <!-- Icon circle -->
        <div class="sk sk-round shrink-0" style="width:40px; height:40px;"></div>

        <!-- Text lines -->
        <div class="flex flex-col gap-1.5 flex-1 min-w-0">
          <div class="sk sk-pill h-2.5 w-4/5"></div>
          <div class="sk sk-pill h-2 w-3/5"></div>
        </div>
      </div>
    </div>
  </div>
</template>
