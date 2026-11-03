<template>
  <Teleport to="body">
    <Transition name="popup">
      <div v-if="visible && banner" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" @click.self="close">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl overflow-hidden shadow-2xl">
          <NuxtLink :to="banner.link || '#'" @click="close">
            <img :src="banner.image" :alt="banner.title" class="w-full object-cover max-h-[420px]" />
          </NuxtLink>
          <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 to-transparent p-5 text-white">
            <p v-if="banner.subtitle" class="text-xs text-white/70 mb-1 font-bengali">{{ banner.subtitle }}</p>
            <h3 class="font-display font-bold text-xl leading-snug">{{ banner.title_bn || banner.title }}</h3>
            <NuxtLink v-if="banner.link" :to="banner.link" @click="close" class="inline-flex items-center gap-1 mt-3 px-4 py-2 rounded-full text-sm font-semibold bg-primary text-white hover:bg-primary/90 transition-colors">
              {{ tr('এখনই দেখুন') }} <span class="mi mi-sm">arrow_forward</span>
            </NuxtLink>
          </div>
          <button @click="close" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white flex items-center justify-center transition-colors backdrop-blur" aria-label="Close popup">
            <span class="mi mi-sm">close</span>
          </button>
          <button v-if="allowDismissForever" @click="closeForever" class="absolute bottom-3 right-4 text-xs text-white/50 hover:text-white/80 transition-colors underline underline-offset-2">{{ tr('আর দেখাবেন না') }}</button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const { tr } = useLang()
const props = defineProps({
  banner:              { type: Object,  default: null  },
  delay:               { type: Number,  default: 1500  },
  frequency:           { type: String,  default: 'once_session' }, // once_ever | once_session | every_24h | every_visit
  allowDismissForever: { type: Boolean, default: true  },
})

const DAY = 24 * 60 * 60 * 1000
const id = computed(() => props.banner?.id)
const kForever = computed(() => `popup_dismissed_${id.value}`)   // "don't show again"
const kSeen    = computed(() => `popup_seen_${id.value}`)        // last time it was shown

const visible = ref(false)

// Storage can be blocked (private mode) – never let that break the page.
const read  = (store, k) => { try { return store.getItem(k) } catch { return null } }
const write = (store, k, v) => { try { store.setItem(k, v) } catch { /* ignore */ } }

function shouldShow() {
  if (!props.banner) return false
  if (read(localStorage, kForever.value)) return false
  switch (props.frequency) {
    case 'every_visit':  return true
    case 'every_24h': {
      const t = Number(read(localStorage, kSeen.value) || 0)
      return !t || Date.now() - t > DAY
    }
    case 'once_ever':    return !read(localStorage, kSeen.value)
    default:             return !read(sessionStorage, kSeen.value)   // once_session
  }
}

// Record the display as soon as it appears, so a reload or any way of closing
// (X button, backdrop, link click) counts – the old code only remembered "don't show again".
function markSeen() {
  write(localStorage, kSeen.value, String(Date.now()))
  write(sessionStorage, kSeen.value, '1')
}

function close() { visible.value = false }

function closeForever() {
  write(localStorage, kForever.value, '1')
  visible.value = false
}

onMounted(() => {
  if (!shouldShow()) return
  setTimeout(() => { visible.value = true; markSeen() }, props.delay)
})
</script>

<style scoped>
.popup-enter-active, .popup-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.popup-enter-from, .popup-leave-to { opacity: 0; transform: scale(0.95); }
</style>