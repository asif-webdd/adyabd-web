<template>
  <div :class="[containerWidth, 'mx-auto px-4 py-6 pb-24 md:pb-8']">
    <h1 class="section-title mb-2 flex items-center gap-2"><span class="mi text-primary">contact_support</span>{{ tr('যোগাযোগ করুন') }}</h1>
    <p class="font-bengali text-gray-500 mb-8">{{ tr('আমরা সবসময় আপনার সেবায় প্রস্তুত।') }}</p>

    <!-- Contact Options -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <div v-for="option in contactOptions" :key="option.title" class="bg-white rounded-2xl shadow-card p-6 text-center hover:shadow-card-hover transition-shadow">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3" :class="option.bgClass">
          <span class="mi text-2xl" :class="option.color">{{ option.icon }}</span>
        </div>
        <h3 class="font-display font-bold text-gray-800 mb-1">{{ option.title }}</h3>
        <p class="font-bengali text-gray-500 text-sm mb-3">{{ option.desc }}</p>
        <a :href="option.link" class="font-bengali font-bold text-primary text-sm hover:underline flex items-center justify-center gap-1">
          <span class="mi mi-sm">open_in_new</span>{{ option.value }}
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Form -->
      <div class="bg-white rounded-2xl shadow-card p-6">
        <h2 class="font-display font-bold text-xl text-gray-800 mb-5 flex items-center gap-2">
          <span class="mi text-primary">send</span>{{ tr('বার্তা পাঠান') }}
        </h2>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('নাম *') }}</label>
              <input v-model="form.name" type="text" :placeholder="tr('আপনার নাম')" class="input-field"/>
            </div>
            <div>
              <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('মোবাইল *') }}</label>
              <input v-model="form.phone" type="tel" placeholder="01XXXXXXXXX" class="input-field"/>
            </div>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('ইমেইল') }}</label>
            <input v-model="form.email" type="email" placeholder="email@example.com" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বিষয় *') }}</label>
            <select v-model="form.subject" class="input-field">
              <option>{{ tr('অর্ডার সম্পর্কিত') }}</option>
              <option>{{ tr('পেমেন্ট সমস্যা') }}</option>
              <option>{{ tr('রিটার্ন ও রিফান্ড') }}</option>
              <option>{{ tr('অ্যাকাউন্ট সমস্যা') }}</option>
              <option>{{ tr('বিক্রেতা সম্পর্কিত') }}</option>
              <option>{{ tr('অন্যান্য') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('অর্ডার নম্বর (যদি থাকে)') }}</label>
            <input v-model="form.order_id" type="text" placeholder="BD-XXXXX" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বার্তা *') }}</label>
            <textarea v-model="form.message" rows="5" :placeholder="tr('আপনার সমস্যা বিস্তারিত লিখুন...')" class="input-field resize-none"></textarea>
          </div>
          <p v-if="formError" class="text-sm font-bengali text-red-600 bg-red-50 rounded-xl px-3 py-2">{{ formError }}</p>
          <p v-if="sent" class="text-sm font-bengali text-green-700 bg-green-50 rounded-xl px-3 py-2">
            {{ tr('ধন্যবাদ! আপনার বার্তা পাঠানো হয়েছে। আমরা শীঘ্রই যোগাযোগ করব।') }}
          </p>
          <button type="button" @click="submitForm" :disabled="sending"
                  class="w-full btn-primary justify-center py-3.5 disabled:opacity-60 disabled:cursor-not-allowed">
            <span class="mi" :class="sending ? 'animate-spin' : ''">{{ sending ? 'refresh' : 'send' }}</span>
            {{ sending ? 'পাঠানো হচ্ছে...' : 'বার্তা পাঠান' }}
          </button>
        </div>
      </div>

      <!-- Info -->
      <div class="space-y-5">
        <div v-if="officeHours.length" class="bg-white rounded-2xl shadow-card p-5">
          <h3 class="font-display font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
            <span class="mi text-primary">schedule</span>{{ tr('কাস্টমার সার্ভিস সময়') }}
          </h3>
          <div class="space-y-3">
            <div v-for="hours in officeHours" :key="hours.day" class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
              <span class="font-bengali text-gray-700 text-sm">{{ hours.day }}</span>
              <span class="font-bengali font-semibold text-sm flex items-center gap-1" :class="hours.open?'text-green-600':'text-red-500'">
                <span class="mi mi-sm">{{ hours.open ? 'check_circle' : 'cancel' }}</span>{{ hours.time }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="faqs.length" class="bg-white rounded-2xl shadow-card p-5">
          <h3 class="font-display font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
            <span class="mi text-primary">quiz</span>{{ tr('সাধারণ প্রশ্নোত্তর') }}
          </h3>
          <div class="space-y-3">
            <div v-for="faq in faqs" :key="faq.q" class="border border-gray-100 rounded-xl overflow-hidden">
              <button @click="faq.open=!faq.open"
                class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors">
                <span class="font-bengali font-semibold text-gray-800 text-sm pr-4">{{ faq.q }}</span>
                <span class="mi text-primary transition-transform" :class="faq.open?'rotate-180':''">expand_more</span>
              </button>
              <div v-if="faq.open" class="px-4 pb-4 border-t border-gray-50">
                <p class="font-bengali text-gray-600 text-sm leading-relaxed pt-3">{{ faq.a }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="socials.length" class="bg-white rounded-2xl shadow-card p-5">
          <h3 class="font-display font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
            <span class="mi text-primary">share</span>{{ tr('সোশ্যাল মিডিয়া') }}
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <a v-for="social in socials" :key="social.name" :href="social.link" target="_blank" rel="noopener"
              class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors">
              <span class="mi text-2xl" :class="social.color">{{ social.icon }}</span>
              <div>
                <p class="font-bengali font-semibold text-gray-800 text-sm">{{ social.name }}</p>
                <p class="font-bengali text-xs text-gray-400">{{ social.handle }}</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const { layout } = useAppearance()
const containerWidth = computed(() => layout.value.container_width || 'max-w-7xl')
const api = useApi()
const form = reactive({ name: '', phone: '', email: '', subject: 'অর্ডার সম্পর্কিত', order_id: '', message: '' })
const sending   = ref(false)
const sent      = ref(false)
const formError = ref('')

const submitForm = async () => {
  formError.value = ''
  sent.value = false
  if (!form.name.trim() || !form.phone.trim() || form.message.trim().length < 10) {
    formError.value = 'নাম, মোবাইল নম্বর এবং কমপক্ষে ১০ অক্ষরের বার্তা দিন।'
    return
  }
  sending.value = true
  try {
    await api.post('/contact', {
      name: form.name.trim(), phone: form.phone.trim(),
      email: form.email.trim() || undefined, subject: form.subject,
      order_id: form.order_id.trim() || undefined, message: form.message.trim(),
    })
    sent.value = true
    form.message = ''; form.order_id = ''
  } catch (e) {
    formError.value = e.message || 'বার্তা পাঠানো যায়নি। কিছুক্ষণ পর আবার চেষ্টা করুন।'
  } finally {
    sending.value = false
  }
}

const { footer } = useAppearance()
const digits = (v) => String(v || '').replace(/[^\d+]/g, '')

// Everything below comes from Admin → Settings (General / Social) and Admin → Content → FAQs.
const contactOptions = computed(() => {
  const f = footer.value
  const out = []
  if (f.phone) out.push({ icon:'call', title:'হেল্পলাইন', desc:'সরাসরি কথা বলুন', value:f.phone, link:'tel:' + digits(f.phone), bgClass:'bg-green-50', color:'text-green-600' })
  if (f.whatsapp) out.push({ icon:'chat', title:'WhatsApp', desc:'মেসেজ করুন', value:f.whatsapp, link:'https://wa.me/' + digits(f.whatsapp).replace('+',''), bgClass:'bg-emerald-50', color:'text-emerald-600' })
  out.push({ icon:'support_agent', title:'লাইভ চ্যাট', desc:'তাৎক্ষণিক সহায়তা', value:'চ্যাট শুরু করুন', link:'/support', bgClass:'bg-blue-50', color:'text-blue-600' })
  if (f.email) out.push({ icon:'mail', title:'ইমেইল', desc:'২৪ ঘণ্টায় উত্তর', value:f.email, link:'mailto:' + f.email, bgClass:'bg-orange-50', color:'text-orange-600' })
  return out
})

const officeHours = computed(() => (footer.value.office_hours || []).map(h => ({
  day: h.day, time: h.time || '', open: !/বন্ধ|closed|off/i.test(h.time || ''),
})))

const socials = computed(() => [
  { icon:'thumb_up',   name:'Facebook',  link: footer.value.facebook,  color:'text-blue-600' },
  { icon:'camera_alt', name:'Instagram', link: footer.value.instagram, color:'text-pink-600' },
  { icon:'play_circle',name:'YouTube',   link: footer.value.youtube,   color:'text-red-600' },
  { icon:'chat',       name:'Twitter / X', link: footer.value.twitter, color:'text-sky-600' },
].filter(x => x.link).map(x => ({ ...x, handle: x.link.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '') })))

const faqs = ref([])
onMounted(async () => {
  try {
    const rows = await api.get('/faqs')
    faqs.value = (Array.isArray(rows) ? rows : (rows?.data ?? [])).map(r => ({ q: r.question, a: r.answer, open: false }))
  } catch { /* section stays hidden */ }
})
</script>
