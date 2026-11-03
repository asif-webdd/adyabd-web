<template>
  <!-- Skeleton -->
  <div v-if="loading" class="flex gap-2 overflow-x-auto pb-2 mb-6">
    <div
        v-for="i in skeletonCount"
        :key="'sk-sub-' + i"
        class="shrink-0 h-9 rounded-full bg-gray-200 animate-pulse"
        :style="{ width: skeletonWidths[i % skeletonWidths.length] }"
    ></div>
  </div>

  <!-- Real pills -->
  <div v-else-if="subcategories.length" class="flex gap-2 overflow-x-auto pb-2 mb-6">
    <button
        @click="select(null)"
        class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0"
        :class="!selectedSub ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary'"
    >{{ tr('সব') }}</button>

    <button
        v-for="sub in subcategories"
        :key="sub.id"
        @click="select(sub.slug)"
        class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0"
        :class="selectedSub === sub.slug ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary'"
    >{{ sub.name }}</button>
  </div>
</template>

<script setup>
const { tr } = useLang()
const props = defineProps({
  loading:       { type: Boolean,  default: false },
  subcategories: { type: Array,    default: () => [] },
  selectedSub:   { type: String,   default: null },
  loadProducts:  { type: Function, default: () => {} },
  skeletonCount: { type: Number,   default: 6 },
})

const emit = defineEmits(['update:selectedSub'])

const skeletonWidths = ['64px', '88px', '72px', '96px', '56px', '80px']

function select(slug) {
  emit('update:selectedSub', slug)  // tell parent to update selectedSub
  props.loadProducts()              // trigger reload in parent
}
</script>