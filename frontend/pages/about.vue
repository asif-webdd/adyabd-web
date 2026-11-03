<template>
  <CmsContent v-if="cmsPage && cmsPage.content" :page="cmsPage"/>
  <div v-else :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <!-- Hero -->
    <div class="rounded-2xl mb-10 text-white text-center min-h-[240px] flex items-center justify-center bg-gradient-to-br from-primary to-primary-dark">
      <div class="p-10">
        <div class="w-20 h-20 rounded-2xl bg-white/15 border-2 border-white/25 flex items-center justify-center mx-auto mb-4 font-display font-bold text-4xl">{{ shopName.charAt(0) }}</div>
        <h1 class="font-display font-extrabold text-3xl md:text-5xl mb-3">{{ shopName }} সম্পর্কে</h1>
        <p v-if="footer.tagline" class="font-bengali text-green-100 text-lg max-w-2xl mx-auto">{{ footer.tagline }}</p>
      </div>
    </div>

    <!-- Mission -->
    <div class="max-w-3xl mx-auto mb-12 text-center">
      <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-4 flex items-center justify-center gap-2">
        <span class="mi text-primary">flag</span> {{ tr('আমাদের লক্ষ্য') }}
      </h2>
      <p class="font-bengali text-gray-600 leading-relaxed">{{ shopName }}-এ আমরা ক্রেতা ও বিক্রেতাকে একটি নিরাপদ ও সহজ অনলাইন মার্কেটপ্লেসে যুক্ত করি — যাতে আপনি সহজে পণ্য খুঁজে পান, নিরাপদে পেমেন্ট করেন এবং দ্রুত ডেলিভারি পান।</p>
    </div>

    <!-- Values -->
    <div class="mb-12">
      <h2 class="section-title text-center mb-6 flex items-center justify-center gap-2">
        <span class="mi text-primary">diamond</span> {{ tr('আমাদের মূল্যবোধ') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div v-for="value in values" :key="value.title" class="bg-white rounded-2xl shadow-card p-6">
          <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-4" :class="value.bgClass">
            <span class="mi text-2xl" :class="value.color">{{ value.icon }}</span>
          </div>
          <h3 class="font-display font-bold text-lg text-gray-800 mb-2">{{ value.title }}</h3>
          <p class="font-bengali text-gray-500 text-sm leading-relaxed">{{ value.desc }}</p>
        </div>
      </div>
    </div>

    <!-- CTA -->
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-2xl p-8 text-white text-center">
      <h2 class="font-display font-extrabold text-2xl mb-3 flex items-center justify-center gap-2">
        <span class="mi">contact_support</span> {{ tr('আমাদের সাথে যোগাযোগ করুন') }}
      </h2>
      <p class="font-bengali text-green-100 mb-6">{{ tr('যেকোনো প্রশ্নের জন্য আমরা আপনার পাশে আছি।') }}</p>
      <div class="flex flex-wrap gap-4 justify-center">
        <a v-if="helpline" :href="helplineHref" class="bg-white text-primary font-display font-bold px-6 py-3 rounded-xl hover:bg-primary/10 transition-colors flex items-center gap-2">
          <span class="mi">call</span> {{ helpline }}
        </a>
        <NuxtLink to="/contact" class="bg-white/20 border border-white/30 text-white font-display font-semibold px-6 py-3 rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2">
          <span class="mi">mail</span> {{ tr('বার্তা পাঠান') }}
        </NuxtLink>
      </div>
    </div>
    <p class="text-center text-xs text-gray-400 mt-6">{{ tr('এই পেজের লেখা অ্যাডমিন প্যানেলের Content → Site Pages থেকে নিজের মতো করে বদলানো যায়।') }}</p>
  </div>
</template>

<script setup>
const { tr } = useLang()
const cmsPage = useCmsPage('about')
const { layout, footer } = useAppearance()
const { shopName, helpline, helplineHref } = useShopName()
useHead({ title: computed(() => `${shopName.value} সম্পর্কে`) })
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')

const values = [
  { icon:'handshake', title:'বিশ্বাস ও স্বচ্ছতা', desc:'সৎ ব্যবসায়িক সম্পর্কই দীর্ঘমেয়াদী সাফল্যের ভিত্তি।', bgClass:'bg-green-50', color:'text-green-600' },
  { icon:'security', title:'নিরাপত্তা', desc:'প্রতিটি লেনদেন ও ব্যক্তিগত তথ্যের নিরাপত্তাকে আমরা সর্বোচ্চ গুরুত্ব দিই।', bgClass:'bg-blue-50', color:'text-blue-600' },
  { icon:'support_agent', title:'গ্রাহকসেবা', desc:'কোনো সমস্যায় পড়লে আমাদের সাপোর্ট টিম পাশে আছে।', bgClass:'bg-orange-50', color:'text-orange-600' },
]
</script>
