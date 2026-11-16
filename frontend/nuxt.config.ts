// https://nuxt.com/docs/api/configuration/nuxt-config
// @ts-ignore
import { fileURLToPath, URL } from "node:url"

// @ts-ignore
export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },

  modules: [
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss',
  ],
  alias: {
    // @ts-ignore
    "@": fileURLToPath(new URL(".", import.meta.url)),
  },
  runtimeConfig: {
    public: {
      // @ts-ignore
      apiBase:  process.env.NUXT_PUBLIC_API_BASE  || 'http://localhost:8000',
      // @ts-ignore
      googleClientId: process.env.NUXT_PUBLIC_GOOGLE_CLIENT_ID || '',
      // @ts-ignore
      facebookAppId: process.env.NUXT_PUBLIC_FACEBOOK_APP_ID || '',
      // @ts-ignore
      siteName: process.env.NUXT_PUBLIC_SITE_NAME || 'adyabd-web',
      // @ts-ignore
      siteUrl:  process.env.NUXT_PUBLIC_SITE_URL  || 'http://localhost:3000',
    },
  },

  css: ['~/assets/css/main.css', '~/assets/css/skeleton.css'],

  app: {
    head: {
      htmlAttrs: { lang: 'bn' },
      title: process.env.NUXT_PUBLIC_SITE_NAME || 'adyabd-web',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ],
      link: [
        // Preconnect to Google Fonts for performance
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        // Material Symbols Rounded (icons)
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block',
        },
        // Hind Siliguri (Bengali body font) + Inter (display/numbers)
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap',
        },
      ],
      script: [
        {
          innerHTML: `(function(){var d=document.documentElement,done=function(){d.classList.add('icons-ready')};
            try{document.fonts.load('24px "Material Symbols Rounded"','shopping_cart').then(done,done)}catch(e){done()}
            setTimeout(done,4000)})()`,
          tagPosition: 'head',
        },
      ],
    },
  },
})
