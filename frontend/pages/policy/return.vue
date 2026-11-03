<script setup>
const { tr } = useLang()
const { shopName, helpline, helplineHref } = useShopName()
const cmsPage = useCmsPage('return')
definePageMeta({ layout: 'default', middleware: 'auth' })

const api    = useApi()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const dataLayer = useDataLayer()

const currentStep   = ref(0)
const selectedOrder = ref(null)
const returnType    = ref('return')
const returnReason  = ref('')
const returnDesc    = ref('')
const refundMethod  = ref('wallet')
const loading       = ref(true)
const submitting    = ref(false)
const error         = ref('')
const eligibleOrders = ref([])

const steps = [
  { label: 'অর্ডার নির্বাচন', icon: 'inventory_2'   },
  { label: 'কারণ ও বিবরণ',   icon: 'help_outline'   },
  { label: 'রিফান্ড পদ্ধতি',  icon: 'payments'       },
  { label: 'সম্পন্ন',         icon: 'check_circle'   },
]

const returnTypes = [
  { value: 'return',      label: 'রিটার্ন ও রিফান্ড', icon: 'assignment_return' },
  { value: 'exchange',    label: 'পণ্য পরিবর্তন',    icon: 'swap_horiz'        },
  { value: 'refund_only', label: 'শুধু রিফান্ড',       icon: 'currency_exchange' },
  { value: 'repair',      label: 'মেরামত',            icon: 'build'             },
]

const returnReasons = [
  'পণ্যটি ত্রুটিপূর্ণ বা ক্ষতিগ্রস্ত',
  'ভুল পণ্য পাঠানো হয়েছে',
  'পণ্যটি বিবরণের সাথে মিলছে না',
  'পণ্যটি আমার প্রত্যাশার মতো নয়',
  'সাইজ/রঙ সমস্যা',
  'অন্যান্য কারণ',
]

const refundMethods = [
  { id: 'wallet', name: 'শপ ওয়ালেট',  desc: 'পরবর্তী কেনাকাটায় ব্যবহার', icon: 'account_balance_wallet', bg: 'bg-primary/10', color: 'text-primary',     time: '১-৩ দিন' },
  { id: 'bkash',  name: 'bKash',          desc: 'সরাসরি আপনার bKash নম্বরে',   icon: 'phone_iphone',           bg: 'bg-pink-50',    color: 'text-pink-600',   time: '৩-৫ দিন' },
  { id: 'nagad',  name: 'Nagad',           desc: 'সরাসরি আপনার Nagad নম্বরে',   icon: 'phone_android',          bg: 'bg-orange-50',  color: 'text-orange-500', time: '৩-৫ দিন' },
  { id: 'bank',   name: 'ব্যাংক ট্রান্সফার', desc: 'আপনার ব্যাংক অ্যাকাউন্টে', icon: 'account_balance',        bg: 'bg-blue-50',    color: 'text-blue-600',   time: '৫-৭ দিন' },
]

const canNext = computed(() => {
  if (currentStep.value === 0) return !!selectedOrder.value
  if (currentStep.value === 1) return !!returnReason.value && returnDesc.value.length >= 20
  if (currentStep.value === 2) return !!refundMethod.value
  return true
})

const orderItemImage = (order) => {
  const img = order.item_image ?? order.items?.[0]?.product_image ?? ''
  return img ? `${apiBase}/storage/${img}` : '/images/placeholder.svg'
}

const submitReturn = async () => {
  error.value = ''
  submitting.value = true
  try {
    const payload = {
      order_id:      selectedOrder.value.id,
      type:          returnType.value,
      reason:        returnReason.value,
      description:   returnDesc.value,
      refund_method: refundMethod.value,
    }
    await api.post('/returns', payload)
    dataLayer.returnRequest(payload, selectedOrder.value)
    currentStep.value = 3
  } catch (e) {
    error.value = e.message || 'রিটার্ন রিকোয়েস্ট জমা দিতে সমস্যা হয়েছে।'
  } finally {
    submitting.value = false
  }
}

const goNext = () => {
  if (currentStep.value === 2) { submitReturn(); return }
  if (currentStep.value < 3) currentStep.value++
}

onMounted(async () => {
  loading.value = true
  try {
    const data = await api.get('/returns/eligible-orders')
    eligibleOrders.value = data ?? []
  } catch {}
  finally { loading.value = false }
})
</script>

<template>
  <div class="max-w-2xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <!-- Admin-editable return policy (Admin → Content → Site Pages → return) -->
    <div v-if="cmsPage && cmsPage.content" class="bg-white rounded-2xl shadow-card p-5 mb-5 cms-body font-bengali text-sm text-gray-700" v-html="cmsPage.content"></div>
    <h1 class="section-title flex items-center gap-2 mb-5">
      <span class="mi text-primary">assignment_return</span>{{ tr('রিটার্ন / রিফান্ড রিকোয়েস্ট') }}
    </h1>

    <!-- Step Indicator -->
    <div class="flex items-center justify-between mb-6 relative">
      <div class="absolute left-0 right-0 top-4 h-0.5 bg-gray-200 -z-0">
        <div class="h-full bg-primary transition-all" :style="`width:${(currentStep / (steps.length - 1)) * 100}%`"></div>
      </div>
      <div v-for="(step, i) in steps" :key="i" class="flex flex-col items-center z-10">
        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all mb-1"
             :class="i < currentStep ? 'bg-primary border-primary text-white' : i === currentStep ? 'bg-white border-primary text-primary' : 'bg-white border-gray-200 text-gray-400'">
          <span class="mi mi-sm">{{ i < currentStep ? 'check' : step.icon }}</span>
        </div>
        <span class="text-xs font-bengali hidden sm:block" :class="i <= currentStep ? 'text-primary font-semibold' : 'text-gray-400'">{{ step.label }}</span>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card p-6">

      <!-- Step 0: Select Order -->
      <div v-if="currentStep === 0">
        <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
          <span class="mi text-primary">inventory_2</span>{{ tr('রিটার্নযোগ্য অর্ডার নির্বাচন করুন') }}
        </h2>
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 2" :key="i" class="h-20 bg-gray-100 animate-pulse rounded-xl"></div>
        </div>
        <div v-else-if="eligibleOrders.length === 0" class="text-center py-8">
          <span class="mi text-3xl text-gray-200 block mb-2">inventory_2</span>
          <p class="font-bengali text-gray-400">{{ tr('কোনো রিটার্নযোগ্য অর্ডার নেই।') }}</p>
          <p class="font-bengali text-gray-400 text-sm mt-1">{{ tr('শুধুমাত্র ডেলিভারির ৭ দিনের মধ্যে রিটার্ন করা যায়।') }}</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="order in eligibleOrders" :key="order.id"
               @click="selectedOrder = order"
               class="flex items-center gap-4 p-4 border-2 rounded-2xl cursor-pointer transition-all hover:border-primary"
               :class="selectedOrder?.id === order.id ? 'border-primary bg-primary/5' : 'border-gray-100'">
            <img :src="orderItemImage(order)" :alt="order.item_name ?? order.order_number"
                 class="w-14 h-14 object-cover rounded-xl bg-gray-50 shrink-0"/>
            <div class="flex-1 min-w-0">
              <p class="font-bengali font-semibold text-gray-800 text-sm line-clamp-1">{{ order.item_name }}</p>
              <p class="font-bengali text-xs text-gray-400 mt-0.5">অর্ডার #{{ order.order_number }} · {{ new Date(order.delivered_at ?? order.created_at).toLocaleDateString('bn-BD') }}</p>
              <p class="font-bengali text-xs flex items-center gap-1 mt-1"
                 :class="(order.return_days_left ?? 7) > 0 ? 'text-green-600' : 'text-accent'">
                <span class="mi mi-sm">{{ (order.return_days_left ?? 7) > 0 ? 'schedule' : 'cancel' }}</span>
                {{ (order.return_days_left ?? 7) > 0 ? `${order.return_days_left ?? 7} দিন বাকি` : 'মেয়াদ শেষ' }}
              </p>
            </div>
            <p class="font-display font-bold text-accent shrink-0">৳{{ Number(order.total_amount ?? order.price ?? 0).toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <!-- Step 1: Reason -->
      <div v-if="currentStep === 1">
        <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
          <span class="mi text-primary">help_outline</span>{{ tr('রিটার্নের ধরন ও কারণ') }}
        </h2>
        <div class="mb-4">
          <p class="font-bengali font-semibold text-gray-700 mb-2 text-sm">{{ tr('রিটার্নের ধরন:') }}</p>
          <div class="grid grid-cols-2 gap-2">
            <label v-for="t in returnTypes" :key="t.value"
                   class="flex items-center gap-2 border-2 rounded-xl p-3 cursor-pointer transition-colors"
                   :class="returnType === t.value ? 'border-primary bg-primary/5' : 'border-gray-100 hover:border-gray-200'">
              <input type="radio" :value="t.value" v-model="returnType" class="accent-primary"/>
              <span class="mi mi-sm text-primary">{{ t.icon }}</span>
              <span class="font-bengali text-sm">{{ t.label }}</span>
            </label>
          </div>
        </div>
        <div class="mb-4">
          <p class="font-bengali font-semibold text-gray-700 mb-2 text-sm">{{ tr('রিটার্নের কারণ: *') }}</p>
          <div class="space-y-2">
            <label v-for="r in returnReasons" :key="r"
                   class="flex items-center gap-2 border border-gray-100 rounded-xl px-4 py-2.5 cursor-pointer hover:bg-gray-50 transition-colors">
              <input type="radio" name="reason" :value="r" v-model="returnReason" class="accent-primary"/>
              <span class="font-bengali text-sm text-gray-700">{{ r }}</span>
            </label>
          </div>
        </div>
        <div>
          <p class="font-bengali font-semibold text-gray-700 mb-2 text-sm">{{ tr('বিস্তারিত বিবরণ: *') }} <span class="text-gray-400 font-normal">{{ tr('(কমপক্ষে ২০ অক্ষর)') }}</span></p>
          <textarea v-model="returnDesc" rows="4" :placeholder="tr('সমস্যাটি বিস্তারিতভাবে বর্ণনা করুন...')" class="input-field resize-none"></textarea>
          <p class="text-xs font-bengali text-gray-400 mt-1">{{ returnDesc.length }} অক্ষর</p>
        </div>
      </div>

      <!-- Step 2: Refund Method -->
      <div v-if="currentStep === 2">
        <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
          <span class="mi text-primary">payments</span>{{ tr('রিফান্ড পদ্ধতি বেছে নিন') }}
        </h2>
        <div class="space-y-3">
          <label v-for="method in refundMethods" :key="method.id"
                 class="flex items-center gap-4 border-2 rounded-2xl p-4 cursor-pointer transition-all hover:border-primary"
                 :class="refundMethod === method.id ? 'border-primary bg-primary/5' : 'border-gray-100'">
            <input type="radio" :value="method.id" v-model="refundMethod" class="accent-primary"/>
            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="method.bg">
              <span class="mi mi-sm" :class="method.color">{{ method.icon }}</span>
            </div>
            <div class="flex-1">
              <p class="font-bengali font-semibold text-gray-800 text-sm">{{ method.name }}</p>
              <p class="font-bengali text-gray-500 text-xs">{{ method.desc }}</p>
            </div>
            <span class="text-xs font-bengali text-gray-400 shrink-0">{{ method.time }}</span>
          </label>
        </div>
        <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali">{{ error }}</div>
      </div>

      <!-- Step 3: Success -->
      <div v-if="currentStep === 3" class="text-center py-6">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="mi text-5xl text-green-500">check_circle</span>
        </div>
        <h2 class="font-display font-extrabold text-2xl text-gray-800 mb-2">{{ tr('রিটার্ন রিকোয়েস্ট সম্পন্ন!') }}</h2>
        <p class="font-bengali text-gray-500 mb-2">{{ tr('আপনার রিটার্ন রিকোয়েস্ট সফলভাবে গ্রহণ করা হয়েছে।') }}</p>
        <p class="font-bengali text-gray-400 text-sm mb-6">{{ tr('আমাদের টিম ২৪-৪৮ ঘণ্টার মধ্যে যোগাযোগ করবে।') }}</p>
        <div class="flex gap-3 justify-center">
          <NuxtLink to="/account/orders" class="btn-primary"><span class="mi mi-sm">inventory_2</span>{{ tr('আমার অর্ডার') }}</NuxtLink>
          <NuxtLink to="/" class="btn-outline"><span class="mi mi-sm">home</span>{{ tr('হোম') }}</NuxtLink>
        </div>
      </div>

      <!-- Nav Buttons -->
      <div v-if="currentStep < 3" class="flex gap-3 mt-6">
        <button v-if="currentStep > 0" @click="currentStep--" class="btn-outline">
          <span class="mi mi-sm">arrow_back</span>{{ tr('পূর্ববর্তী') }}
        </button>
        <button @click="goNext" :disabled="!canNext || submitting"
                class="flex-1 btn-primary justify-center disabled:opacity-60">
          <span v-if="submitting" class="mi mi-sm animate-spin">refresh</span>
          <span v-else class="mi mi-sm">{{ currentStep === 2 ? 'send' : 'arrow_forward' }}</span>
          {{ submitting ? 'জমা হচ্ছে...' : currentStep === 2 ? 'রিটার্ন রিকোয়েস্ট জমা দিন' : 'পরবর্তী' }}
        </button>
      </div>
    </div>
  </div>
</template>