<template>
  <CmsContent v-if="cmsPage && cmsPage.content" :page="cmsPage"/>
  <div v-else class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl p-8 text-white text-center mb-8">
      <span class="mi text-white" style="font-size:56px">privacy_tip</span>
      <h1 class="font-display font-extrabold text-3xl mt-3 mb-2">{{ tr('গোপনীয়তা নীতি') }}</h1>
    </div>

    <div class="bg-primary/5 border border-primary/20 rounded-2xl p-4 mb-6 flex items-start gap-3">
      <span class="mi text-primary mt-0.5 shrink-0">shield</span>
      <p class="font-bengali text-primary text-sm leading-relaxed">{{ shopName }} আপনার ব্যক্তিগত তথ্যের গোপনীয়তাকে সর্বোচ্চ গুরুত্ব দেয়। আমরা কখনও আপনার তথ্য তৃতীয় পক্ষের কাছে বিক্রি করি না।</p>
    </div>

    <div class="bg-white rounded-2xl shadow-card p-6 md:p-8 space-y-8">
      <div v-for="section in sections" :key="section.title">
        <div class="flex items-center gap-3 mb-3">
          <span class="mi text-2xl text-primary">{{ section.icon }}</span>
          <h2 class="font-display font-bold text-xl text-gray-800">{{ section.title }}</h2>
        </div>
        <div class="pl-10 space-y-2">
          <p v-for="(para, i) in section.content" :key="i"
             class="font-bengali text-gray-600 text-sm leading-relaxed flex items-start gap-2">
            <span class="mi mi-sm text-primary mt-0.5 shrink-0">arrow_right</span>
            <span>{{ para }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="mt-6 text-center">
      <p class="font-bengali text-gray-500 text-sm flex items-center justify-center gap-1">
        {{ tr('প্রশ্ন আছে?') }}
        <NuxtLink to="/contact" class="text-primary underline flex items-center gap-0.5">
          <span class="mi mi-sm">mail</span>{{ tr('আমাদের সাথে যোগাযোগ করুন') }}
        </NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const cmsPage = useCmsPage('privacy')
definePageMeta({ layout: 'default' })
const { shopName } = useShopName()
useHead({ title: computed(() => `গোপনীয়তা নীতি — ${shopName.value}`) })

const sections = [
  {
    icon: 'analytics', title: 'আমরা কোন তথ্য সংগ্রহ করি',
    content: [
      'আপনার নাম, মোবাইল নম্বর, ইমেইল ঠিকানা ও ডেলিভারি ঠিকানা।',
      'লেনদেনের তথ্য: অর্ডার ইতিহাস, পেমেন্ট তথ্য।',
      'ব্যবহারের তথ্য: আপনি কোন পণ্য দেখেছেন, অনুসন্ধান করেছেন।',
      'ডিভাইস তথ্য: ব্রাউজারের ধরন, IP ঠিকানা।',
    ]
  },
  {
    icon: 'target', title: 'তথ্য কিভাবে ব্যবহার করা হয়',
    content: [
      'অর্ডার প্রক্রিয়াকরণ ও ডেলিভারি নিশ্চিত করতে।',
      'কাস্টমার সার্ভিস ও সাপোর্ট প্রদান করতে।',
      'আপনার পছন্দ অনুযায়ী পণ্য সুপারিশ করতে।',
      'জালিয়াতি প্রতিরোধ ও নিরাপত্তা নিশ্চিত করতে।',
    ]
  },
  {
    icon: 'security', title: 'তথ্য সুরক্ষা',
    content: [
      'আমরা SSL/TLS এনক্রিপশন প্রযুক্তি ব্যবহার করি।',
      'পেমেন্ট তথ্য PCI-DSS কমপ্লায়েন্ট পদ্ধতিতে প্রক্রিয়া করা হয়।',
      'নিয়মিত নিরাপত্তা অডিট পরিচালনা করা হয়।',
    ]
  },
  {
    icon: 'handshake', title: 'তৃতীয় পক্ষের সাথে তথ্য ভাগ',
    content: [
      'আমরা আপনার তথ্য বিক্রয়ের উদ্দেশ্যে কখনও তৃতীয় পক্ষকে দিই না।',
      'ডেলিভারি কোম্পানিগুলোকে শুধুমাত্র শিপিং প্রয়োজনীয় তথ্য দেওয়া হয়।',
      'আইনগত বাধ্যবাধকতার কারণে কর্তৃপক্ষকে তথ্য দিতে পারি।',
    ]
  },
  {
    icon: 'manage_accounts', title: 'আপনার অধিকার',
    content: [
      'আপনি যেকোনো সময় আপনার তথ্য দেখতে, সম্পাদনা করতে বা মুছতে পারবেন।',
      'মার্কেটিং ইমেইল/SMS থেকে আনসাবস্ক্রাইব করার অধিকার আপনার আছে।',
      'অ্যাকাউন্ট মুছলে ৩০ দিনের মধ্যে সব তথ্য মুছে দেওয়া হবে।',
    ]
  },
  {
    icon: 'cookie', title: 'কুকিজ নীতি',
    content: [
      'আমরা আপনার ব্রাউজিং অভিজ্ঞতা উন্নত করতে কুকিজ ব্যবহার করি।',
      'কুকিজ ব্যবহার করে আমরা লগইন অবস্থা ও পছন্দ সংরক্ষণ করি।',
      'ব্রাউজার সেটিংস থেকে কুকিজ বন্ধ করা যাবে।',
    ]
  },
]
</script>