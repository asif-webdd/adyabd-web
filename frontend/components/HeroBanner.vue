<template>
  <div class="relative overflow-hidden rounded-2xl min-h-[320px] md:min-h-[420px]">

    <!-- Slides -->
    <div class="relative w-full h-full" @mouseenter="pauseAuto" @mouseleave="resumeAuto">
      <transition-group name="slide-fade" tag="div">
        <div v-for="(slide, index) in sliders" v-show="currentIndex === index" :key="slide.id" class="absolute inset-0 w-full min-h-[320px] md:min-h-[420px] flex items-center">

          <!-- Background Image -->
          <div class="absolute inset-0">
            <picture>
              <source
                media="(max-width: 767px)"
                :srcset="slide.mobile_image"
              />

              <img
                :src="slide.image"
                :alt="slide.title"
                class="w-full h-full object-cover"
                :loading="index === 0 ? 'eager' : 'lazy'"
                :fetchpriority="index === 0 ? 'high' : 'auto'"
                decoding="async"
              />
            </picture>

            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/50 to-black/20"></div>

            <!-- Subtle dot pattern -->
            <div
              class="absolute inset-0 opacity-10"
              style="background-image: url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'20\' cy=\'20\' r=\'1\'/%3E%3C/g%3E%3C/svg%3E');"
            ></div>
          </div>


          <!-- Content -->
          <div class="relative z-10 w-full px-6 py-10 md:px-14 md:py-14 text-white">
            <div class="max-w-xl">
              <!-- Sale badge -->
              <div v-if="slide.ends_at" class="inline-flex items-center gap-2 backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold mb-4 border border-white/20 text-white animate-pulse-slow" style="background: linear-gradient(90deg, #F9A825cc, #e65100cc);">
                <span class="inline-block w-2 h-2 rounded-full bg-white animate-ping"></span>
                {{ tr('সীমিত সময়ের অফার!') }}
              </div>

              <!-- Title Bengali -->
              <h1 class="font-extrabold text-3xl md:text-5xl leading-tight mb-1 drop-shadow-lg font-bengali-display">{{ slide.title }}</h1>
              <!-- Title English -->
              <p class="text-yellow-300 font-semibold text-base md:text-lg mb-3 tracking-wide drop-shadow">{{ slide.title_bn }}</p>
              <!-- Subtitle -->
              <p class="text-white/80 text-sm md:text-base mb-7 leading-relaxed max-w-md">{{ slide.subtitle }}</p>
              <!-- CTA -->
              <div class="flex flex-wrap gap-3 z-[99]">
                <NuxtLink :to="slide.link" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold text-sm text-black shadow-lg transition-transform hover:scale-105 active:scale-95" style="background: linear-gradient(90deg, #F9A825, #f57f17);">
                  <span class="mi mi-sm">shopping_bag</span>
                  {{ tr('এখনই দেখুন') }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Prev / Next arrows -->
    <button v-if="sliders.length > 1" @click="prev" class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur text-white flex items-center justify-center transition-all hover:scale-110 active:scale-95" aria-label="Previous slide">
      <span class="mi mi-sm">chevron_left</span>
    </button>
    <button v-if="sliders.length > 1" @click="next" class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur text-white flex items-center justify-center transition-all hover:scale-110 active:scale-95" aria-label="Next slide">
      <span class="mi mi-sm">chevron_right</span>
    </button>

    <!-- Dot indicators + progress bar -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
      <button v-for="(slide, index) in sliders" :key="slide.id" @click="goTo(index)" :aria-label="`Go to slide ${index + 1}`" class="relative h-1.5 rounded-full overflow-hidden transition-[width] duration-300" :class="currentIndex === index ? 'w-8 bg-white/30' : 'w-1.5 bg-white/40 hover:bg-white/60'">
        <span v-if="currentIndex === index" :key="`fill-${index}-${fillKey}`" class="dot-fill" :style="{ animationDuration: slideDuration + 'ms' }"></span>
      </button>
    </div>

    <!-- Hover pause zone -->
    <!-- <div class="absolute inset-0 z-10" @mouseenter="pauseAuto" @mouseleave="resumeAuto"></div>-->
  </div>
</template>

<script setup>
const { tr } = useLang()
import { useLandingPageStore } from '../stores/LandingPage.js'

const landingStore  = useLandingPageStore()
const sliders       = computed(() => landingStore.sliders ?? [])

const currentIndex  = ref(0)
const fillKey       = ref(0)

let autoTimer       = null
// Tracks when the current slide started (ms), used for pause/resume.
let slideStartedAt  = 0
// How much of the duration was already consumed before the last pause.
let consumedMs      = 0

const currentSlider = computed(() => sliders.value[currentIndex.value])
// Duration in ms for the current slide — also drives the CSS animation.
const slideDuration = computed(() => (currentSlider.value?.slider_duration ?? 5) * 1000)

// ── Navigation ────────────────────────────────────────────────────────
function goTo(index) {
  if (index === currentIndex.value) return
  currentIndex.value = index
  resetAuto()
}

function next() {
  currentIndex.value = (currentIndex.value + 1) % sliders.value.length
  resetAuto()
}

function prev() {
  currentIndex.value = (currentIndex.value - 1 + sliders.value.length) % sliders.value.length
  resetAuto()
}

// ── Auto-advance ──────────────────────────────────────────────────────
function resetAuto() {
  clearTimeout(autoTimer)
  consumedMs     = 0
  fillKey.value++           // remount the CSS-animated span → clean restart
  slideStartedAt = Date.now()

  scheduleNext(slideDuration.value)
}

function scheduleNext(delay) {
  autoTimer = setTimeout(() => {
    currentIndex.value = (currentIndex.value + 1) % sliders.value.length
    resetAuto()
  }, delay)
}

function pauseAuto() {
  clearTimeout(autoTimer)
  // Record how much of the duration has been consumed so resumeAuto
  // can pick up exactly where it left off.
  consumedMs = Date.now() - slideStartedAt
}

function resumeAuto() {
  const remaining = Math.max(0, slideDuration.value - consumedMs)
  slideStartedAt  = Date.now() - consumedMs   // keep timeline consistent
  scheduleNext(remaining)
}

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(() => {
  if (sliders.value.length > 1) resetAuto()
})

onUnmounted(() => {
  clearTimeout(autoTimer)
})

// Handle async slider load
watch(
    () => sliders.value.length,
    (len) => { if (len > 1) resetAuto() },
    { immediate: false }
)
</script>

<style scoped>
/* ── Slide transition ──────────────────────────────────────────────── */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}
.slide-fade-enter-to,
.slide-fade-leave-from {
  opacity: 1;
  transform: translateX(0);
}
.slide-fade-leave-active {
  position: absolute;
  inset: 0;
}

/* ── Dot progress fill — pure CSS animation ───────────────────────── */
/*
  The fill span starts at width 0 and expands to 100% over the slide
  duration. animation-duration is set inline via :style so each slide
  can have its own timing. animation-fill-mode: forwards keeps it at
  100% when done (instead of snapping back to 0).
  The span is fully remounted on each slide change (via :key + fillKey)
  so the animation always starts fresh from 0.
*/
@keyframes dot-progress {
  from { width: 0%; }
  to   { width: 100%; }
}

.dot-fill {
  position: absolute;
  inset-block: 0;           /* top: 0; bottom: 0 */
  left: 0;
  background: #ffffff;
  border-radius: 999px;
  /* duration is overridden inline per slide */
  animation: dot-progress linear forwards;
  animation-duration: inherit;
}

/* ── Badge pulse ───────────────────────────────────────────────────── */
@keyframes pulse-slow {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.7; }
}
.animate-pulse-slow {
  animation: pulse-slow 2.5s ease-in-out infinite;
}
</style>