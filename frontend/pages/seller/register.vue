<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <!-- Hero -->
    <div class="relative overflow-hidden rounded-2xl p-8 md:p-12 text-white text-center mb-10" style="background:linear-gradient(135deg,#006A4E,#004d38)">
      <img src="/images/banner-pattern.svg" class="absolute inset-0 w-full h-full object-cover opacity-20"/>
      <div class="relative">
        <span class="mi text-5xl mb-4 block text-yellow-300">storefront</span>
        <h1 class="font-display font-extrabold text-3xl md:text-4xl mb-3">{{ shopName }}-এ সেলার হোন</h1>
        <p class="font-bengali text-green-100 text-base md:text-lg mb-6 max-w-2xl mx-auto">{{ tr('কোটি কোটি ক্রেতার কাছে আপনার পণ্য পৌঁছে দিন।') }}</p>
        <button v-if="!showForm" @click="openForm"
                class="bg-accent hover:bg-accent-dark text-white font-display font-bold px-8 py-4 rounded-2xl text-lg transition-all inline-flex items-center gap-2">
          <span class="mi">rocket_launch</span>{{ tr('এখনই শুরু করুন — বিনামূল্যে') }}
        </button>
      </div>
    </div>

    <!-- Benefits -->
    <div class="mb-10">
      <h2 class="section-title text-center mb-6 flex items-center justify-center gap-2"><span class="mi text-primary">star</span>কেন {{ shopName }}-এ বিক্রি করবেন?</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div v-for="benefit in benefits" :key="benefit.title" class="bg-white rounded-2xl shadow-card p-6 text-center">
          <div class="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3" :class="benefit.bg">
            <span class="mi text-2xl" :class="benefit.color">{{ benefit.icon }}</span>
          </div>
          <h3 class="font-display font-bold text-lg text-gray-800 mb-2">{{ benefit.title }}</h3>
          <p class="font-bengali text-gray-500 text-sm">{{ benefit.desc }}</p>
        </div>
      </div>
    </div>

    <!-- Stats Banner -->
    <div class="bg-gradient-to-r from-primary to-primary-dark rounded-2xl p-6 text-white grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
      <div v-for="stat in sellerStats" :key="stat.label" class="text-center">
        <span class="mi text-3xl text-yellow-300 mb-1 block">{{ stat.icon }}</span>
        <p class="font-display font-extrabold text-2xl text-yellow-300">{{ stat.value }}</p>
        <p class="font-bengali text-green-200 text-xs mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- How it works -->
    <div class="mb-10">
      <h2 class="section-title text-center mb-6 flex items-center justify-center gap-2"><span class="mi text-primary">help_outline</span>{{ tr('কিভাবে শুরু করবেন?') }}</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="(step, i) in howItWorks" :key="i" class="text-center">
          <div class="w-16 h-16 rounded-full bg-primary/10 border-2 border-primary/20 flex items-center justify-center mx-auto mb-3">
            <span class="mi text-2xl text-primary">{{ step.icon }}</span>
          </div>
          <p class="font-display font-bold text-gray-800 mb-1">{{ step.title }}</p>
          <p class="font-bengali text-gray-500 text-sm">{{ step.desc }}</p>
        </div>
      </div>
    </div>

    <!-- Registration Form -->
    <div v-if="showForm" class="bg-white rounded-2xl shadow-card p-6 md:p-8">
      <h2 class="font-display font-bold text-2xl text-gray-800 mb-6 flex items-center gap-2">
        <span class="mi text-primary">edit_note</span>{{ tr('সেলার রেজিস্ট্রেশন') }}
      </h2>

      <!-- Error / Success -->
      <div v-if="submitError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali">{{ submitError }}</div>

      <!-- Step indicators -->
      <div class="flex gap-2 mb-6">
        <div v-for="(s, i) in formStepLabels" :key="i" class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                 :class="formStep >= i ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'">{{ i+1 }}</div>
            <span class="text-xs font-bengali font-medium hidden sm:block"
                  :class="formStep >= i ? 'text-primary' : 'text-gray-400'">{{ s }}</span>
          </div>
          <div class="h-1 rounded-full" :class="formStep > i ? 'bg-primary' : 'bg-gray-200'"></div>
        </div>
      </div>

      <!-- ── Step 0: Business Info ── -->
      <div v-if="formStep === 0" class="space-y-4">
        <h3 class="font-display font-bold text-lg text-gray-700 flex items-center gap-2"><span class="mi text-primary">business</span>{{ tr('ব্যবসার তথ্য') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('দোকানের নাম *') }}</label>
            <input v-model="formData.shop_name" type="text" :placeholder="tr('আপনার দোকানের নাম')" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ব্যবসার ধরন *') }}</label>
            <select v-model="formData.business_type" class="input-field">
              <option value="">{{ tr('বেছে নিন') }}</option>
              <option value="individual">{{ tr('ব্যক্তিগত ব্যবসা') }}</option>
              <option value="company">{{ tr('নিবন্ধিত কোম্পানি') }}</option>
              <option value="manufacturer">{{ tr('উৎপাদনকারী') }}</option>
              <option value="wholesaler">{{ tr('পাইকারি বিক্রেতা') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পণ্যের ক্যাটাগরি *') }}</label>
            <select v-model="formData.category" class="input-field">
              <option value="">{{ tr('বেছে নিন') }}</option>
              <option v-for="cat in productCategories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মাসিক আনুমানিক বিক্রয়') }}</label>
            <select v-model="formData.monthly_sales" class="input-field">
              <option value="<100k">{{ tr('১ লাখের কম') }}</option>
              <option value="100k-500k">{{ tr('১-৫ লাখ') }}</option>
              <option value="500k-2m">{{ tr('৫-২০ লাখ') }}</option>
              <option value=">2m">{{ tr('২০ লাখের বেশি') }}</option>
            </select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('দোকানের বিবরণ') }}</label>
          <textarea v-model="formData.description" rows="3" :placeholder="tr('আপনার দোকান ও পণ্য সম্পর্কে...')" class="input-field resize-none"></textarea>
        </div>
        <button @click="formStep=1" :disabled="!formData.shop_name || !formData.business_type" class="btn-primary disabled:opacity-60">
          {{ tr('পরবর্তী') }} <span class="mi mi-sm">arrow_forward</span>
        </button>
      </div>

      <!-- ── Step 1: Personal Info ── -->
      <div v-if="formStep === 1" class="space-y-4">
        <h3 class="font-display font-bold text-lg text-gray-700 flex items-center gap-2"><span class="mi text-primary">person</span>{{ tr('ব্যক্তিগত তথ্য') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('পূর্ণ নাম *') }}</label>
            <input v-model="formData.owner_name" type="text" class="input-field" :placeholder="tr('মালিকের নাম')"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল নম্বর *') }}</label>
            <input v-model="formData.phone" type="tel" placeholder="01XXXXXXXXX" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ইমেইল *') }}</label>
            <input v-model="formData.email" type="email" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('NID নম্বর *') }}</label>
            <input v-model="formData.nid" type="text" :placeholder="tr('জাতীয় পরিচয়পত্র নম্বর')" class="input-field"/>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ব্যবসার ঠিকানা *') }}</label>
            <textarea v-model="formData.address" rows="2" class="input-field resize-none" :placeholder="tr('সম্পূর্ণ ব্যবসার ঠিকানা')"></textarea>
          </div>
        </div>
        <div class="flex gap-3">
          <button @click="formStep=0" class="btn-outline"><span class="mi mi-sm">arrow_back</span>{{ tr('পেছনে') }}</button>
          <button @click="formStep=2" :disabled="!formData.owner_name || !formData.phone || !formData.email" class="btn-primary disabled:opacity-60">
            {{ tr('পরবর্তী') }} <span class="mi mi-sm">arrow_forward</span>
          </button>
        </div>
      </div>

      <!-- ── Step 2: Payment Info ── -->
      <div v-if="formStep === 2" class="space-y-4">
        <h3 class="font-display font-bold text-lg text-gray-700 flex items-center gap-2"><span class="mi text-primary">payments</span>{{ tr('পেমেন্ট তথ্য') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-2">{{ tr('পেমেন্ট পদ্ধতি *') }}</label>
            <div class="flex gap-3 flex-wrap">
              <label v-for="m in paymentMethods" :key="m.value"
                     class="flex items-center gap-2 border-2 rounded-xl px-4 py-2 cursor-pointer transition-colors"
                     :class="formData.payment_method === m.value ? 'border-primary bg-primary/5' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" :value="m.value" v-model="formData.payment_method" class="accent-primary"/>
                <span class="mi mi-sm" :class="m.color">{{ m.icon }}</span>
                <span class="font-bengali text-sm font-medium">{{ m.label }}</span>
              </label>
            </div>
          </div>
          <div v-if="formData.payment_method !== 'bank'">
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল ব্যাংকিং নম্বর *') }}</label>
            <input v-model="formData.mfs_number" type="tel" placeholder="01XXXXXXXXX" class="input-field"/>
          </div>
          <div v-if="formData.payment_method === 'bank'">
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ব্যাংক অ্যাকাউন্ট নম্বর *') }}</label>
            <input v-model="formData.bank_account" type="text" class="input-field" :placeholder="tr('অ্যাকাউন্ট নম্বর')"/>
          </div>
          <div v-if="formData.payment_method === 'bank'">
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ব্যাংক ও শাখার নাম') }}</label>
            <input v-model="formData.bank_name" type="text" class="input-field" :placeholder="tr('যেমন: Dutch-Bangla Bank, Gulshan Branch')"/>
          </div>
        </div>
        <label class="flex items-start gap-2 cursor-pointer">
          <input type="checkbox" v-model="formData.agreed" class="accent-primary mt-0.5"/>
          <span class="text-sm font-bengali text-gray-600">
            আমি {{ shopName }}-এর <NuxtLink to="/policy/terms" class="text-primary underline">{{ tr('শর্তাবলী') }}</NuxtLink> {{ tr('পড়েছি এবং সম্মত আছি।') }}
          </span>
        </label>
        <div class="flex gap-3">
          <button @click="formStep=1" class="btn-outline"><span class="mi mi-sm">arrow_back</span>{{ tr('পেছনে') }}</button>
          <button @click="submitRegistration" :disabled="!formData.agreed || submitting" class="btn-primary disabled:opacity-60">
            <span v-if="submitting" class="mi mi-sm animate-spin">refresh</span>
            <span v-else class="mi mi-sm">check_circle</span>
            {{ submitting ? 'জমা হচ্ছে...' : 'রেজিস্ট্রেশন সম্পন্ন করুন' }}
          </button>
        </div>
      </div>

      <!-- ── Step 3: Success ── -->
      <div v-if="formStep === 3" class="text-center py-8">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-5xl text-green-500">check_circle</span>
        </div>
        <h3 class="font-display font-extrabold text-2xl text-gray-800 mb-2">{{ tr('রেজিস্ট্রেশন সম্পন্ন!') }}</h3>
        <p class="font-bengali text-gray-500 mb-2">{{ tr('আপনার আবেদন গৃহীত হয়েছে। যাচাই সম্পন্ন হলে SMS পাবেন।') }}</p>
        <p class="font-bengali text-gray-400 text-sm mb-6">{{ tr('সাধারণত ২৪-৪৮ ঘণ্টার মধ্যে অনুমোদন দেওয়া হয়।') }}</p>
        <NuxtLink to="/" class="btn-primary inline-flex"><span class="mi mi-sm">home</span>{{ tr('হোমে যান') }}</NuxtLink>
      </div>
    </div>

    <!-- Static CTA when form is hidden -->
    <div v-else class="bg-white rounded-2xl shadow-card p-8 text-center">
      <span class="mi text-primary" style="font-size:48px">work</span>
      <h3 class="font-display font-bold text-xl text-gray-800 mt-3 mb-2">{{ tr('আজই শুরু করুন') }}</h3>
      <p class="font-bengali text-gray-500 mb-4">{{ tr('মাত্র ৫ মিনিটে রেজিস্ট্রেশন সম্পন্ন করুন।') }}</p>
      <button @click="openForm" class="btn-primary"><span class="mi mi-sm">edit_note</span>{{ tr('রেজিস্ট্রেশন ফর্ম খুলুন') }}</button>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { shopName, helpline, helplineHref } = useShopName()
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
definePageMeta({ layout: 'default' })

import { useAuthStore } from '~/stores/auth'
const api      = useApi()
const showForm = ref(false)
const formStep = ref(0)
const submitting  = ref(false)
const submitError = ref('')

const formStepLabels = ['ব্যবসার তথ্য', 'যোগাযোগ', 'পেমেন্ট']

const formData = reactive({
  shop_name: '', business_type: '', category: '', monthly_sales: '<100k', description: '',
  owner_name: '', phone: '', email: '', nid: '', address: '',
  payment_method: 'bkash', mfs_number: '', bank_account: '', bank_name: '',
  agreed: false,
})

const productCategories = ['ইলেকট্রনিক্স','ফ্যাশন','গৃহস্থালি','সৌন্দর্য','খাদ্য','কৃষি','বই ও শিক্ষা','স্বাস্থ্য','শিশু পণ্য','অন্যান্য']

const paymentMethods = [
  { value:'bkash',  label:'bKash',            icon:'phone_iphone',    color:'text-bkash'      },
  { value:'nagad',  label:'Nagad',             icon:'phone_android',   color:'text-orange-500' },
  { value:'bank',   label:'ব্যাংক ট্রান্সফার', icon:'account_balance', color:'text-blue-600'   },
]

const authStore = useAuthStore()
const openForm = () => {
  if (!authStore.isLoggedIn) { navigateTo('/auth/login?redirect=/seller/register'); return }
  showForm.value = true
}

const submitRegistration = async () => {
  if (!authStore.isLoggedIn) { navigateTo('/auth/login?redirect=/seller/register'); return }
  submitError.value = ''
  submitting.value  = true
  try {
    await api.post('/seller/register', { ...formData })
    formStep.value = 3
  } catch (e) {
    submitError.value = e.message || 'রেজিস্ট্রেশন জমা দিতে সমস্যা হয়েছে।'
  } finally {
    submitting.value = false
  }
}

const benefits = [
  { icon:'group',                 title:'১ কোটি+ ক্রেতা',    desc:'কোটি কোটি ক্রেতার কাছে আপনার পণ্য পৌঁছে দিন।',         bg:'bg-blue-50',   color:'text-blue-600'  },
  { icon:'account_balance_wallet', title:'সহজ পেমেন্ট',      desc:'bKash, Nagad, ব্যাংক ট্রান্সফারে সাপ্তাহিক পেমেন্ট।',   bg:'bg-pink-50',   color:'text-bkash'     },
  { icon:'bar_chart',             title:'বিশ্লেষণ ড্যাশবোর্ড', desc:'বিক্রয়, দর্শক ও পণ্যের পারফরম্যান্স ট্র্যাক করুন।',  bg:'bg-green-50',  color:'text-green-600' },
  { icon:'local_shipping',        title:'ডেলিভারি সহায়তা',  desc:'আমাদের লজিস্টিক পার্টনারদের মাধ্যমে পণ্য পাঠান।',       bg:'bg-orange-50', color:'text-orange-600'},
  { icon:'security',              title:'বিক্রেতা সুরক্ষা',  desc:'প্রতারণামূলক লেনদেন থেকে সম্পূর্ণ সুরক্ষা।',            bg:'bg-red-50',    color:'text-red-600'   },
  { icon:'campaign',              title:'বিনামূল্যে প্রচার',  desc:'প্রথম ৩ মাস বিনামূল্যে বিজ্ঞাপন সুবিধা।',              bg:'bg-purple-50', color:'text-purple-600'},
]
const sellerStats = [
  { icon:'storefront', value:'৫০,০০০+', label:'সক্রিয় বিক্রেতা' },
  { icon:'group',      value:'১ কোটি+', label:'সক্রিয় ক্রেতা'   },
  { icon:'inventory_2',value:'৫০ লাখ+', label:'মাসিক অর্ডার'     },
  { icon:'location_on',value:'৬৪ জেলা', label:'ডেলিভারি কভারেজ'  },
]
const howItWorks = [
  { icon:'edit_note',          title:'রেজিস্ট্রেশন',  desc:'বিনামূল্যে অ্যাকাউন্ট তৈরি করুন' },
  { icon:'add_photo_alternate', title:'পণ্য যোগ করুন', desc:'পণ্যের ছবি ও বিবরণ দিন'         },
  { icon:'account_balance_wallet', title:'পেমেন্ট সেটআপ', desc:'bKash বা ব্যাংক তথ্য দিন'    },
  { icon:'rocket_launch',      title:'বিক্রি শুরু',   desc:'আজই বিক্রি শুরু করুন!'           },
]
</script>