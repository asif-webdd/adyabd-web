<template>
  <div class="flex items-center gap-1.5">
    <div v-for="(unit, i) in units" :key="unit.label" class="flex items-center gap-1.5">
      <div class="text-center">
        <div class="font-display font-extrabold rounded-lg px-2.5 py-1.5 min-w-[2.5rem] text-center"
          :class="darkMode ? 'bg-white/20 text-white text-lg' : 'bg-gray-900 text-white text-base'">
          {{ unit.value }}
        </div>
        <p class="text-xs mt-0.5 font-bengali" :class="darkMode ? 'text-white/70' : 'text-gray-500'">{{ unit.label }}</p>
      </div>
      <span v-if="i < units.length - 1" class="font-bold text-lg mb-4" :class="darkMode ? 'text-white' : 'text-gray-400'">:</span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  endTime: { type: Date, default: () => new Date(Date.now() + 5 * 3600 * 1000) },
  darkMode: { type: Boolean, default: false }
})

const toBn = (n) => String(n).padStart(2, '0').replace(/[0-9]/g, d => '০১২৩৪৫৬৭৮৯'[d])

const units = ref([
  { label: 'ঘণ্টা', value: '০৫' },
  { label: 'মিনিট', value: '০০' },
  { label: 'সেকেন্ড', value: '০০' },
])

let timer
const updateCountdown = () => {
  const diff = Math.max(0, props.endTime - Date.now())
  const h = Math.floor(diff / 3600000)
  const m = Math.floor((diff % 3600000) / 60000)
  const s = Math.floor((diff % 60000) / 1000)
  units.value[0].value = toBn(h)
  units.value[1].value = toBn(m)
  units.value[2].value = toBn(s)
}

onMounted(() => { updateCountdown(); timer = setInterval(updateCountdown, 1000) })
onUnmounted(() => clearInterval(timer))
</script>
