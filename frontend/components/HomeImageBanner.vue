<template>
  <section v-if="homeBanner.enabled" class="mt-12 flex justify-center">
    <component :is="homeBanner.link ? linkTag : 'div'" v-bind="linkAttrs" :style="{ width: homeBanner.width || '100%', maxWidth: '100%' }" class="block">
      <img :src="homeBanner.image || '/images/home-banner-placeholder.svg'" alt="" loading="lazy" decoding="async"
           class="block w-full" :style="imgStyle"/>
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
const imgStyle = computed(() => {
  const h = (homeBanner.value.height || '').trim()
  if (!h || h === 'auto') return { height: 'auto' }
  return { height: h, objectFit: homeBanner.value.fit, objectPosition: homeBanner.value.position }
})
</script>
