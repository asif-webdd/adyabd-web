<template>
  <!-- <div class="grid" :class="gridClass">-->
  <div :class="gridClass" class="space-y-6">
    <div v-for="i in count" :key="i" class="bg-white rounded-2xl overflow-hidden shadow-card animate-pulse">
      <div class="aspect-square bg-gray-200"></div>
      <div class="p-3 space-y-2">
        <div class="h-2.5 bg-gray-200 rounded-full w-1/3"></div>
        <div class="h-3 bg-gray-200 rounded-full w-full"></div>
        <div class="h-3 bg-gray-200 rounded-full w-4/5"></div>
        <div class="h-2.5 bg-gray-200 rounded-full w-1/2 mt-1"></div>
        <div class="flex items-baseline gap-2 pt-1">
          <div class="h-5 bg-gray-200 rounded-full w-20"></div>
          <div class="h-3 bg-gray-200 rounded-full w-12"></div>
        </div>
        <div class="h-2 bg-gray-200 rounded-full w-24"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  count: { type: Number, default: 5 },
  columns: { type: Number, default: 5 }
})

const gridClass = computed(() => {
  const map = { 2: 'grid-cols-2', 3: 'grid-cols-2 md:grid-cols-3', 4: 'grid-cols-2 md:grid-cols-4', 5: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5' }
  return (map[props.columns] || 'grid-cols-2 md:grid-cols-5') + ' gap-4'
})
</script>
