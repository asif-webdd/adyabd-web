<template>
  <div class="flex gap-1">
    <button v-for="i in max" :key="i"
      @click="emit('update:modelValue', i)"
      @mouseenter="hovered = i"
      @mouseleave="hovered = 0"
      class="transition-transform hover:scale-110"
      :class="size === 'lg' ? 'text-3xl' : size === 'sm' ? 'text-sm' : 'text-xl'">
      <span :class="i <= (hovered || modelValue) ? 'text-gold' : 'text-gray-300'">★</span>
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Number, default: 0 },
  max: { type: Number, default: 5 },
  size: { type: String, default: 'md' }, // sm | md | lg
})
const emit = defineEmits(['update:modelValue'])
const hovered = ref(0)
</script>
