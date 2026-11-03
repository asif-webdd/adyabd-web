<template>
  <CmsContent v-if="cmsPage && cmsPage.content" :page="cmsPage"/>
  <div v-else class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-2xl p-8 text-white text-center mb-8">
      <span class="mi text-white" style="font-size:56px">gavel</span>
      <h1 class="font-display font-extrabold text-3xl mt-3 mb-2">{{ tr('শর্তাবলী ও নিয়মনীতি') }}</h1>
      <p class="font-bengali text-green-100 flex items-center justify-center gap-1"><span class="mi mi-sm">calendar_today</span>{{ tr('সর্বশেষ আপডেট: ১ জানুয়ারি ২০২৫') }}</p>
    </div>

    <!-- TOC -->
    <div class="bg-white rounded-2xl shadow-card p-5 mb-6">
      <p class="font-display font-bold text-gray-800 mb-3 flex items-center gap-2"><span class="mi text-primary">list</span>{{ tr('বিষয়সূচি') }}</p>
      <div class="grid grid-cols-2 gap-2">
        <a v-for="(section, i) in sections" :key="i" :href="`#section-${i}`"
          class="font-bengali text-sm text-primary hover:underline flex items-center gap-1">
          <span class="text-gray-400 text-xs">{{ i+1 }}.</span>{{ section.title }}
        </a>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card p-6 md:p-8 space-y-8">
      <div v-for="(section, i) in sections" :key="i" :id="`section-${i}`">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm shrink-0">{{ i+1 }}</div>
          <h2 class="font-display font-bold text-lg text-gray-800 flex items-center gap-2">
            <span class="mi text-primary">{{ section.icon }}</span>{{ section.title }}
          </h2>
        </div>
        <div class="pl-11 space-y-2">
          <p v-for="(para, j) in section.content" :key="j" class="font-bengali text-gray-600 text-sm leading-relaxed flex items-start gap-2">
            <span class="mi mi-sm text-primary mt-0.5 shrink-0">arrow_right</span><span>{{ fill(para) }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="mt-6 text-center">
      <p class="font-bengali text-gray-500 text-sm">{{ tr('প্রশ্ন আছে?') }} <NuxtLink to="/contact" class="text-primary underline flex items-center gap-0.5 inline-flex"><span class="mi mi-sm">contact_support</span>{{ tr('আমাদের সাথে যোগাযোগ করুন') }}</NuxtLink></p>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { shopName } = useShopName()
const fill = (t) => t.replaceAll('{shop}', shopName.value)
const cmsPage = useCmsPage('terms')
const sections = [
  { icon:'article', title:'সাধারণ শর্তাবলী', content:['{shop} প্ল্যাটফর্ম ব্যবহার করে আপনি এই শর্তাবলীর সাথে সম্মত হচ্ছেন।','১৮ বছরের কম বয়সীরা অভিভাবকের সম্মতি ছাড়া প্ল্যাটফর্ম ব্যবহার করতে পারবেন না।'] },
  { icon:'manage_accounts', title:'অ্যাকাউন্ট নিবন্ধন ও সুরক্ষা', content:['প্রতিটি ব্যবহারকারীর একটি মাত্র অ্যাকাউন্ট থাকতে পারবে।','আপনার অ্যাকাউন্টের নিরাপত্তার দায়িত্ব আপনার।','পাসওয়ার্ড অন্যের সাথে শেয়ার করবেন না।'] },
  { icon:'person', title:'ক্রেতার দায়িত্ব', content:['সঠিক ডেলিভারি ঠিকানা ও যোগাযোগের তথ্য প্রদান করতে হবে।','ডেলিভারি গ্রহণের সময় পণ্য যাচাই করুন।','জাল বা মিথ্যা তথ্য দিয়ে অর্ডার করলে আইনগত ব্যবস্থা নেওয়া হতে পারে।'] },
  { icon:'payments', title:'পেমেন্ট নীতি', content:['সকল মূল্য বাংলাদেশি টাকায় প্রকাশিত এবং VAT অন্তর্ভুক্ত।','পেমেন্ট নিশ্চিত হওয়ার পর অর্ডার কনফার্মেশন পাঠানো হয়।','মূল্য পরিবর্তনের ক্ষেত্রে অর্ডার করার সময়ের মূল্যই প্রযোজ্য।'] },
  { icon:'copyright', title:'মেধাস্বত্ব', content:['{shop} প্ল্যাটফর্মের সকল কন্টেন্ট, লোগো, ডিজাইন {shop}-এর মেধাস্বত্ব।','অনুমতি ছাড়া {shop}-এর কন্টেন্ট ব্যবহার, কপি বা বিতরণ করা যাবে না।'] },
  { icon:'balance', title:'বিরোধ নিষ্পত্তি', content:['যেকোনো বিরোধের ক্ষেত্রে প্রথমে আমাদের কাস্টমার সাপোর্টের সাথে যোগাযোগ করুন।','সকল বিরোধ বাংলাদেশের আদালতে নিষ্পত্তি হবে।'] },
]
</script>
