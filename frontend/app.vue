<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<script setup>
import {useAppearance} from "./composables/useAppearance.js";
import {useLandingPageStore} from "./stores/LandingPage.js";

// <html lang> follows the language switch (cookie `lang`, so the server renders the same language)
const { isEn: _isEn } = useLang()
useHead(() => ({ htmlAttrs: { lang: _isEn.value ? 'en' : 'bn' } }))



// ── Fetch appearance from backend, apply CSS vars + dynamic fonts ──
const appearance = useAppearance()

// Load on server (populates reactive state for SSR HTML)
// Load again on client mount (gets fresh data, applies CSS vars to DOM)
await appearance.load()
await useLandingPageStore().fetchHome()
if (import.meta.client) {
  onMounted(() => appearance.load())
}

// ── Tracking tags (GTM / GA4 / Meta Pixel / TikTok Pixel / Google Ads) ──
// Rendered server-side using Google's/Meta's exact official snippet format,
// in the positions they require:
//   - GTM script  → as early as possible in <head>
//   - GTM noscript → immediately after <body> opens
//   - GA4 / Ads gtag.js → <head>
//   - Meta Pixel / TikTok Pixel → <head>, with Meta's noscript pixel in <body>
useHead(() => {
  const t = appearance.tracking.value
  if (!t?.datalayer_enabled) return {}

  // Defensive trim — guards against stray whitespace in stored settings
  // (e.g. " GTM-XXXX") which would otherwise break the GTM/GA4/Pixel URLs.
  const gtmId      = (t.gtm_id || '').trim()
  //const fbPixelId  = (t.fb_pixel_id || '').trim()
  const tiktokId   = (t.tiktok_pixel_id || '').trim()

  const script = []
  const noscript = []

  // ── Google Tag Manager (official snippet) ─────────────────────────
  if (gtmId) {
    script.push({
      key: 'gtm-head',
      tagPosition: 'head',
      innerHTML:
          `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':` +
          `new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],` +
          `j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=` +
          `'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);` +
          `})(window,document,'script','dataLayer',${JSON.stringify(gtmId)});`,
    })
    noscript.push({
      key: 'gtm-noscript',
      tagPosition: 'bodyOpen',
      innerHTML: `<iframe src="https://www.googletagmanager.com/ns.html?id=${gtmId}" height="0" width="0" style="display:none;visibility:hidden"></iframe>`,
    })
  }

  

  // ── TikTok Pixel (official snippet) ─────────────────────────────────
  if (tiktokId) {
    script.push({
      key: 'tiktok-pixel',
      tagPosition: 'head',
      innerHTML:
          `!function (w, d, t) {` +
          `w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};` +
          `ttq.load(${JSON.stringify(tiktokId)});` +
          `ttq.page();` +
          `}(window, document, 'ttq');`,
    })
  }

  return { script, noscript }
})


if (import.meta.client) {
  const router = useRouter()
  const dataLayer = useDataLayer()

  router.afterEach((to) => {
    // Wait a tick so document.title from useHead() on the new page is set
    nextTick(() => dataLayer.pageView(to.fullPath, document.title))
  })

  // Initial page load
  onMounted(() => {
    nextTick(() => dataLayer.pageView(router.currentRoute.value.fullPath, document.title))
  })
}
</script>
