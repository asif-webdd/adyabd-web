<template>
  <section v-if="homeBanner.enabled" class="mt-12 flex justify-center">
    <component :is="homeBanner.link ? linkTag : 'div'" v-bind="linkAttrs" class="hib block" :style="vars">
      <img :src="homeBanner.image || '/images/home-banner-placeholder.svg'" alt="" loading="lazy" decoding="async"
           class="hib-img block w-full"/>
    </component>
  </section>
</template>

<script setup>
const { homeBanner } = useAppearance()
const isExternal = computed(() => /^https?:\/\//i.test(homeBanner.value.link || ''))
const linkTag = computed(() => isExternal.value ? 'a' : resolveComponent('NuxtLink'))
const linkAttrs = computed(() => !homeBanner.value.link ? {} : isExternal.value
  ? { href: homeBanner.value.link, target: '_blank', rel: 'noopener' }
  : { to: homeBanner.value.link })

// "400" -> "400px"; blank -> ''
const len = (v) => { v = String(v || '').trim(); return /^\d+(\.\d+)?$/.test(v) ? v + 'px' : v }
// Cascade: desktop -> tablet -> mobile (blank inherits from the larger device)
const vars = computed(() => {
  const b = homeBanner.value
  const w = len(b.width) || '100%'
  const wt = len(b.width_tablet) || w
  const wm = len(b.width_mobile) || wt
  const h = len(b.height) || 'auto'
  const ht = len(b.height_tablet) || h
  const hm = len(b.height_mobile) || ht
  return {
    '--w': w, '--wt': wt, '--wm': wm,
    '--h': h, '--ht': ht, '--hm': hm,
    '--fit': b.fit || 'cover', '--pos': b.position || 'center',
  }
})
</script>

<style scoped>
.hib { width: var(--w); max-width: 100%; }
.hib-img { height: var(--h); object-fit: var(--fit); object-position: var(--pos); }
@media (max-width: 1023px) {
  .hib { width: var(--wt); }
  .hib-img { height: var(--ht); }
}
@media (max-width: 767px) {
  .hib { width: var(--wm); }
  .hib-img { height: var(--hm); }
}
</style>
