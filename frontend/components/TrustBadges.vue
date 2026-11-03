<template>
  <div class="cus-4-points grid grid-cols-2 md:grid-cols-4 gap-4">
    <div v-for="badge in trustBadges" :key="badge.title" class="cus-4-point bg-white rounded-2xl p-4 shadow-card flex items-center gap-3">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" :class="badge.bg">
        <span class="mi text-2xl" :class="badge.color">{{ badge.icon }}</span>
      </div>
      <div>
        <div class="font-display font-bold text-sm text-gray-800">{{ badge.title }}</div>
        <div class="text-xs text-gray-500 font-bengali">{{ badge.desc }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { trustBadges } = useAppearance()
</script>

<style scoped>
@media screen and (max-width: 767px){
  .cus-4-points{
    gap: 8px;
    margin-top: 20px !important;
  }
  .cus-4-point{
    padding: 10px;
  }
}
</style>