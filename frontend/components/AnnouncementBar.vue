<template>
  <Transition name="slide-down">
    <div v-if="visible && announcement.show && announcement.messages.length" class="relative bg-accent text-white text-xs md:text-sm py-2 px-4 text-center font-bengali">
      <div :class="[containerWidth, 'mx-auto flex items-center justify-center gap-2 flex-wrap']">
        <span class="mi mi-sm">local_fire_department</span>
        <span>{{ announcement.messages[currentIndex] }}</span>
        <div class="flex gap-1 ml-2">
          <div v-for="(_, i) in announcement.messages" :key="i" class="w-1.5 h-1.5 rounded-full transition-colors" :class="i===currentIndex?'bg-white':'bg-white/40'"></div>
        </div>
      </div>
      <button @click="visible=false" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white">
        <span class="mi mi-sm">close</span>
      </button>
    </div>
  </Transition>
</template>

<script setup>
const { announcement, layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

const visible = ref(true)
const currentIndex = ref(0)

let interval
onMounted(() => {
  interval = setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % (announcement.value.messages.length || 1)
  }, 4000)
})
onUnmounted(() => clearInterval(interval))
</script>
