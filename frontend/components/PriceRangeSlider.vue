<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between text-sm font-bengali">
      <span class="text-gray-600">৳{{ minVal.toLocaleString('bn-BD') }}</span>
      <span class="text-gray-600">৳{{ maxVal.toLocaleString('bn-BD') }}</span>
    </div>

    <div class="relative h-2 bg-gray-200 rounded-full">
      <div class="absolute h-full bg-primary rounded-full"
        :style="`left: ${minPercent}%; right: ${100 - maxPercent}%`"></div>
      <input type="range" :min="min" :max="max" v-model.number="minVal"
        class="absolute w-full h-full opacity-0 cursor-pointer"
        @input="onMinChange" />
      <input type="range" :min="min" :max="max" v-model.number="maxVal"
        class="absolute w-full h-full opacity-0 cursor-pointer"
        @input="onMaxChange" />
      <!-- Thumbs -->
      <div class="absolute w-4 h-4 bg-primary border-2 border-white rounded-full shadow -translate-y-1/2 top-1/2 -translate-x-1/2 pointer-events-none"
        :style="`left: ${minPercent}%`"></div>
      <div class="absolute w-4 h-4 bg-primary border-2 border-white rounded-full shadow -translate-y-1/2 top-1/2 -translate-x-1/2 pointer-events-none"
        :style="`left: ${maxPercent}%`"></div>
    </div>

    <div class="flex gap-2">
      <input type="number" v-model.number="minVal" :min="min" :max="maxVal"
        class="w-full border-2 border-gray-200 focus:border-primary rounded-lg px-3 py-2 text-sm font-bengali outline-none text-center"/>
      <span class="text-gray-400 self-center">–</span>
      <input type="number" v-model.number="maxVal" :min="minVal" :max="max"
        class="w-full border-2 border-gray-200 focus:border-primary rounded-lg px-3 py-2 text-sm font-bengali outline-none text-center"/>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100000 },
  modelValue: { type: Array, default: () => [0, 100000] }
})
const emit = defineEmits(['update:modelValue'])

const minVal = ref(props.modelValue[0])
const maxVal = ref(props.modelValue[1])

const minPercent = computed(() => ((minVal.value - props.min) / (props.max - props.min)) * 100)
const maxPercent = computed(() => ((maxVal.value - props.min) / (props.max - props.min)) * 100)

const onMinChange = () => { if (minVal.value >= maxVal.value) minVal.value = maxVal.value - 1; emit('update:modelValue', [minVal.value, maxVal.value]) }
const onMaxChange = () => { if (maxVal.value <= minVal.value) maxVal.value = minVal.value + 1; emit('update:modelValue', [minVal.value, maxVal.value]) }
</script>
