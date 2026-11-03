<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 bg-black/90 z-[999] flex items-center justify-center p-4"
        @click.self="$emit('close')">
        <div class="relative max-w-2xl w-full">
          <!-- Close -->
          <button @click="$emit('close')"
            class="absolute -top-12 right-0 text-white text-3xl hover:text-gray-300 transition-colors">✕</button>

          <!-- Main Image -->
          <div class="bg-white rounded-2xl overflow-hidden aspect-square flex items-center justify-center text-9xl p-8 mb-4">
            {{ images[activeIndex] }}
          </div>

          <!-- Thumbnails -->
          <div class="flex gap-3 justify-center">
            <button v-for="(img, i) in images" :key="i" @click="activeIndex = i"
              class="w-14 h-14 rounded-xl border-2 bg-white flex items-center justify-center text-2xl transition-colors"
              :class="activeIndex === i ? 'border-white' : 'border-white/30 hover:border-white/60'">
              {{ img }}
            </button>
          </div>

          <!-- Navigation -->
          <button @click="prev"
            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-16 w-12 h-12 bg-white/20 hover:bg-white/40 rounded-full text-white text-xl flex items-center justify-center transition-colors">
            ‹
          </button>
          <button @click="next"
            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-16 w-12 h-12 bg-white/20 hover:bg-white/40 rounded-full text-white text-xl flex items-center justify-center transition-colors">
            ›
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  isOpen: Boolean,
  images: { type: Array, default: () => ['📱', '📲', '🖥️', '📦'] },
  startIndex: { type: Number, default: 0 }
})
defineEmits(['close'])

const activeIndex = ref(props.startIndex)
watch(() => props.startIndex, (v) => activeIndex.value = v)

const prev = () => activeIndex.value = (activeIndex.value - 1 + props.images.length) % props.images.length
const next = () => activeIndex.value = (activeIndex.value + 1) % props.images.length
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
