<template>
  <Transition name="fade">
    <div v-if="isOpen" @click="$emit('close')" class="fixed inset-0 bg-black/50 z-50 md:hidden"></div>
  </Transition>

  <Transition name="slide-left">
    <div v-if="isOpen" class="fixed left-0 top-0 bottom-0 w-72 bg-white z-50 md:hidden overflow-y-auto shadow-2xl flex flex-col">
      <!-- Header -->
      <div class="bg-primary p-4 flex items-center justify-between sticky top-0">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white font-bold font-display text-sm">বা</div>
          <span class="text-white font-display font-bold text-lg">{{ tr('সব ক্যাটাগরি') }}</span>
        </div>
        <button @click="$emit('close')" class="text-white/80 hover:text-white">
          <span class="mi">close</span>
        </button>
      </div>

      <!-- User Quick -->
      <div class="bg-primary/5 border-b border-gray-100 p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary">
          <span class="mi">person</span>
        </div>
        <div>
          <NuxtLink to="/auth/login" @click="$emit('close')" class="font-bengali font-semibold text-gray-800 text-sm hover:text-primary">{{ tr('লগইন করুন') }}</NuxtLink>
          <p class="font-bengali text-xs text-gray-500">{{ tr('অ্যাকাউন্ট না থাকলে') }} <NuxtLink to="/auth/login" @click="$emit('close')" class="text-primary underline">{{ tr('নিবন্ধন') }}</NuxtLink></p>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="p-3 border-b border-gray-100 grid grid-cols-3 gap-2">
        <NuxtLink v-for="q in quickLinks" :key="q.href" :to="q.href" @click="$emit('close')"
          class="flex flex-col items-center gap-1 p-2 bg-gray-50 rounded-xl hover:bg-primary/10 transition-colors">
          <span class="mi text-primary">{{ q.icon }}</span>
          <span class="text-xs font-bengali text-gray-600">{{ q.label }}</span>
        </NuxtLink>
      </div>

      <!-- Categories -->
      <div class="flex-1 overflow-y-auto p-3">
        <p class="font-display font-bold text-xs text-gray-400 uppercase tracking-wide mb-2 px-2">{{ tr('সকল ক্যাটাগরি') }}</p>
        <div v-for="cat in categories" :key="cat.slug">
          <button @click="toggleCat(cat.slug)"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="cat.bg">
              <span class="mi" :class="cat.color">{{ cat.icon }}</span>
            </div>
            <span class="flex-1 font-bengali font-medium text-gray-800 text-sm text-left">{{ cat.name }}</span>
            <span class="mi mi-sm text-gray-400 transition-transform" :class="openCat === cat.slug ? 'rotate-90' : ''">chevron_right</span>
          </button>
          <div v-if="openCat === cat.slug" class="ml-12 mb-1 space-y-0.5">
            <NuxtLink v-for="sub in cat.subs" :key="sub.href" :to="sub.href" @click="$emit('close')"
              class="flex items-center gap-1.5 font-bengali text-sm text-gray-600 hover:text-primary py-1.5 px-3 rounded-lg hover:bg-primary/5 transition-colors">
              <span class="mi mi-sm">chevron_right</span> {{ sub.label }}
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- Bottom Links -->
      <div class="border-t border-gray-100 p-3 space-y-0.5">
        <NuxtLink v-for="link in bottomLinks" :key="link.href" :to="link.href" @click="$emit('close')"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
          <span class="mi text-gray-500">{{ link.icon }}</span>
          <span class="font-bengali text-sm text-gray-700">{{ link.label }}</span>
        </NuxtLink>
      </div>
    </div>
  </Transition>
</template>

<script setup>
const { tr } = useLang()
defineProps({ isOpen: Boolean })
defineEmits(['close'])

const openCat = ref(null)
const toggleCat = (slug) => { openCat.value = openCat.value === slug ? null : slug }

const quickLinks = [
  { href: '/account/orders', icon: 'inventory_2', label: 'অর্ডার' },
  { href: '/wishlist', icon: 'favorite', label: 'উইশলিস্ট' },
  { href: '/order/track', icon: 'location_searching', label: 'ট্র্যাক' },
  { href: '/deals', icon: 'local_offer', label: 'অফার' },
  { href: '/account/wallet', icon: 'account_balance_wallet', label: 'ওয়ালেট' },
  { href: '/support', icon: 'support_agent', label: 'সাপোর্ট' },
]

const categories = [
  { name: 'ইলেকট্রনিক্স', slug: 'electronics', icon: 'devices', bg: 'bg-blue-50', color: 'text-blue-600', subs: [{ label: 'মোবাইল ও ট্যাব', href: '/category/mobile' }, { label: 'ল্যাপটপ', href: '/category/laptop' }, { label: 'স্মার্টওয়াচ', href: '/category/smartwatch' }] },
  { name: 'ফ্যাশন', slug: 'fashion', icon: 'checkroom', bg: 'bg-pink-50', color: 'text-pink-600', subs: [{ label: 'মহিলাদের পোশাক', href: '/category/womens' }, { label: 'পুরুষদের পোশাক', href: '/category/mens' }, { label: 'জুতা', href: '/category/shoes' }] },
  { name: 'গৃহস্থালি', slug: 'home', icon: 'home', bg: 'bg-amber-50', color: 'text-amber-600', subs: [{ label: 'রান্নাঘর', href: '/category/kitchen' }, { label: 'ফার্নিচার', href: '/category/furniture' }] },
  { name: 'সৌন্দর্য', slug: 'beauty', icon: 'spa', bg: 'bg-rose-50', color: 'text-rose-600', subs: [{ label: 'স্কিনকেয়ার', href: '/category/skincare' }, { label: 'হেয়ারকেয়ার', href: '/category/haircare' }] },
  { name: 'বই ও শিক্ষা', slug: 'books', icon: 'menu_book', bg: 'bg-violet-50', color: 'text-violet-600', subs: [{ label: 'শিক্ষামূলক বই', href: '/category/textbooks' }, { label: 'উপন্যাস', href: '/category/novels' }] },
  { name: 'শিশু পণ্য', slug: 'baby', icon: 'child_care', bg: 'bg-sky-50', color: 'text-sky-600', subs: [{ label: 'শিশুর পোশাক', href: '/category/baby-clothes' }, { label: 'খেলনা', href: '/category/toys' }] },
  { name: 'খেলাধুলা', slug: 'sports', icon: 'sports_soccer', bg: 'bg-lime-50', color: 'text-lime-700', subs: [{ label: 'ক্রিকেট', href: '/category/cricket' }, { label: 'ফুটবল', href: '/category/football' }] },
  { name: 'কৃষি পণ্য', slug: 'agriculture', icon: 'grass', bg: 'bg-teal-50', color: 'text-teal-600', subs: [{ label: 'সার ও বীজ', href: '/category/seeds' }, { label: 'কৃষি সরঞ্জাম', href: '/category/tools' }] },
]

const bottomLinks = [
  { href: '/seller/register', icon: 'storefront', label: 'সেলার হন' },
  { href: '/about', icon: 'info', label: 'আমাদের সম্পর্কে' },
  { href: '/blog', icon: 'article', label: 'ব্লগ' },
  { href: '/support', icon: 'support_agent', label: 'সাপোর্ট' },
  { href: '/policy/terms', icon: 'gavel', label: 'শর্তাবলী' },
]
</script>
