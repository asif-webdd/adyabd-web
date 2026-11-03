<script setup>
const { tr } = useLang()
const { shopName, helpline, helplineHref } = useShopName()
const { footer } = useAppearance()
definePageMeta({ layout: 'default' })

const api       = useApi()
const authStore = useAuthStore()
const dataLayer = useDataLayer()

const activeMode  = ref('chat')
const chatInput   = ref('')
const isTyping    = ref(false)
const messages    = ref([])
const ticketForm  = reactive({ subject: '', category: 'order', message: '', order_id: '' })
const submitting  = ref(false)
const ticketSent  = ref(false)
const ticketError = ref('')
const tickets     = ref([])
const activeTicket = ref(null)
const loadingTickets = ref(false)

const supportOptions = computed(() => [
  { id: 'chat',   title: 'লাইভ চ্যাট',   desc: 'তাৎক্ষণিক সহায়তা',  icon: 'support_agent', available: true  },
  { id: 'ticket', title: 'সাপোর্ট টিকেট', desc: 'বিস্তারিত সমস্যা',  icon: 'confirmation_number', available: true },
  ...(helpline.value ? [{ id: 'call', title: 'ফোন সাপোর্ট', desc: 'হেল্পলাইন: ' + helpline.value, icon: 'call', available: true }] : []),
])

const categories = [
  { value: 'order',   label: 'অর্ডার সমস্যা'   },
  { value: 'payment', label: 'পেমেন্ট সমস্যা'  },
  { value: 'delivery',label: 'ডেলিভারি সমস্যা' },
  { value: 'return',  label: 'রিটার্ন/রিফান্ড' },
  { value: 'account', label: 'অ্যাকাউন্ট সমস্যা' },
  { value: 'other',   label: 'অন্যান্য'         },
]

const quickReplies = [
  { label: 'অর্ডার ট্র্যাক',   icon: 'location_searching' },
  { label: 'রিটার্ন করতে চাই', icon: 'assignment_return'  },
  { label: 'পেমেন্ট সমস্যা',   icon: 'payments'           },
  { label: 'ডেলিভারি সমস্যা',  icon: 'local_shipping'     },
]

// Automated FAQ answers for the quick-reply buttons (clearly an automatic assistant, not a person).
const botReplies = {
  'অর্ডার ট্র্যাক':   'অর্ডার ট্র্যাক করতে /order/track পেজে আপনার অর্ডার নম্বর দিন।',
  'রিটার্ন করতে চাই': 'ডেলিভারির ৭ দিনের মধ্যে /order/return পেজ থেকে রিটার্ন রিকোয়েস্ট করুন।',
  'পেমেন্ট সমস্যা':   'সমস্যাটি বিস্তারিত লিখে পাঠান — আমরা একটি সাপোর্ট টিকেট খুলে দেব এবং প্রতিনিধি উত্তর দেবেন।',
  'ডেলিভারি সমস্যা':  'অর্ডার নম্বরসহ সমস্যাটি লিখে পাঠান — আমরা একটি সাপোর্ট টিকেট খুলে দেব এবং প্রতিনিধি উত্তর দেবেন।',
}

const now = () => {
  const d = new Date()
  return `${d.getHours()}:${String(d.getMinutes()).padStart(2, '0')}`
}

const botSay = (text) => messages.value.push({ from: 'bot', text, time: now() })

// Chat: quick replies get an automatic FAQ answer; any other message becomes a REAL support
// ticket (first message) or a reply on the open ticket. Nothing here pretends an agent is typing.
const sendMessage = async (text) => {
  if (!text?.trim()) return
  const msg = text.trim()
  messages.value.push({ from: 'user', text: msg, time: now() })
  chatInput.value = ''

  if (botReplies[msg]) { botSay(botReplies[msg]); return }

  if (!authStore.isLoggedIn) {
    botSay('একজন প্রতিনিধির সাথে কথা বলতে অনুগ্রহ করে লগইন করুন, অথবা যোগাযোগ পেজের ফর্ম ব্যবহার করুন।')
    return
  }

  isTyping.value = true
  try {
    if (!activeTicket.value) {
      const ticket = await api.post('/support', { subject: msg.slice(0, 80), category: 'other', message: msg })
      activeTicket.value = ticket
      botSay(`আপনার অনুরোধ নিবন্ধিত হয়েছে (টিকেট #${ticket.id})। আমাদের প্রতিনিধি উত্তর দিলে "সাপোর্ট টিকেট" ট্যাবে দেখতে পারবেন।`)
      await loadTickets()
    } else {
      await api.post(`/support/${activeTicket.value.id}/messages`, { message: msg })
      botSay('আপনার বার্তা প্রতিনিধির কাছে পাঠানো হয়েছে।')
    }
  } catch (e) {
    botSay(e.message || 'বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।')
  } finally {
    isTyping.value = false
  }
}

const submitTicket = async () => {
  ticketError.value = ''
  if (!ticketForm.subject || !ticketForm.message) {
    ticketError.value = 'বিষয় এবং বার্তা আবশ্যক।'; return
  }
  submitting.value = true
  try {
    const payload = {
      subject:  ticketForm.subject,
      category: ticketForm.category,
      message:  ticketForm.message,
    }
    if (ticketForm.order_id) payload.order_id = ticketForm.order_id
    await api.post('/support', payload)
    dataLayer.generateLead('support_ticket', { form_category: payload.category })
    ticketSent.value = true
    ticketForm.subject = ''; ticketForm.message = ''; ticketForm.order_id = ''
    await loadTickets()
  } catch (e) {
    ticketError.value = e.message || 'টিকেট জমা দিতে সমস্যা হয়েছে।'
  } finally {
    submitting.value = false
  }
}

// ── Ticket conversation (customer sees the team's replies here) ──────────
const openTicketData = ref(null)
const replyText = ref('')
const replying = ref(false)
const openTicket = async (t) => {
  try { openTicketData.value = await api.get(`/support/${t.id}`) } catch (e) { ticketError.value = e.message || 'টিকেট খোলা যায়নি।' }
}
const closeTicket = () => { openTicketData.value = null; replyText.value = '' }
const sendReply = async () => {
  const text = replyText.value.trim()
  if (!text || !openTicketData.value) return
  replying.value = true
  try {
    await api.post(`/support/${openTicketData.value.id}/messages`, { message: text })
    replyText.value = ''
    await openTicket(openTicketData.value)
  } catch (e) { ticketError.value = e.message || 'বার্তা পাঠানো যায়নি।' }
  finally { replying.value = false }
}
const fmtTime = (d) => { try { return new Date(d).toLocaleString('bn-BD', { dateStyle: 'medium', timeStyle: 'short' }) } catch { return '' } }

const loadTickets = async () => {
  if (!authStore.isLoggedIn) return
  loadingTickets.value = true
  try {
    const data = await api.get('/support')
    tickets.value = data ?? []
  } catch {}
  finally { loadingTickets.value = false }
}

const statusClass = (s) => ({
  open:     'bg-green-100 text-green-700',
  pending:  'bg-yellow-100 text-yellow-700',
  resolved: 'bg-blue-100 text-blue-700',
  closed:   'bg-gray-100 text-gray-600',
}[s] || 'bg-gray-100 text-gray-600')

const statusLabel = (s) => ({ open:'খোলা', pending:'প্রক্রিয়াধীন', resolved:'সমাধান হয়েছে', closed:'বন্ধ' }[s] || s)

onMounted(() => {
  messages.value.push({ from: 'bot', text: 'নমস্কার! আমি স্বয়ংক্রিয় সহকারী। নিচের বাটন বেছে নিন, অথবা সমস্যা লিখুন — আমরা সাপোর্ট টিকেট খুলে দেব।', time: now() })
  loadTickets()
})

watch(activeMode, (mode) => {
  if (mode === 'ticket') loadTickets()
})
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-8">
    <h1 class="section-title flex items-center gap-2 mb-5">
      <span class="mi text-primary">support_agent</span>{{ tr('কাস্টমার সাপোর্ট') }}
    </h1>

    <!-- Mode Selector -->
    <div class="grid gap-3 mb-6" :class="supportOptions.length === 3 ? 'grid-cols-3' : 'grid-cols-2'">
      <button v-for="opt in supportOptions" :key="opt.id" @click="activeMode = opt.id"
              class="p-4 rounded-2xl border-2 transition-all text-center"
              :class="activeMode === opt.id ? 'border-primary bg-primary/5' : 'border-gray-100 bg-white hover:border-gray-200'">
        <span class="mi text-2xl mb-1 block" :class="activeMode === opt.id ? 'text-primary' : 'text-gray-400'">{{ opt.icon }}</span>
        <p class="font-display font-bold text-sm" :class="activeMode === opt.id ? 'text-primary' : 'text-gray-700'">{{ opt.title }}</p>
        <p class="font-bengali text-xs text-gray-400">{{ opt.desc }}</p>
      </button>
    </div>

    <!-- Chat Mode -->
    <div v-if="activeMode === 'chat'" class="bg-white rounded-2xl shadow-card overflow-hidden">
      <!-- Header -->
      <div class="flex items-center gap-3 p-4 bg-primary text-white">
        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center"><span class="mi">support_agent</span></div>
        <div>
          <p class="font-display font-bold">{{ shopName }} সহকারী</p>
          <p class="font-bengali text-green-200 text-xs flex items-center gap-1"><span class="mi mi-sm">circle</span>{{ tr('অনলাইন') }}</p>
        </div>
      </div>

      <!-- Messages -->
      <div class="h-96 overflow-y-auto p-4 space-y-3 bg-gray-50">
        <div v-for="(msg, i) in messages" :key="i"
             class="flex gap-2" :class="msg.from === 'user' ? 'flex-row-reverse' : ''">
          <div v-if="msg.from === 'bot'" class="w-8 h-8 bg-primary rounded-full flex items-center justify-center shrink-0">
            <span class="mi mi-sm text-white">support_agent</span>
          </div>
          <div class="max-w-[75%] px-4 py-2.5 rounded-2xl text-sm font-bengali"
               :class="msg.from === 'user' ? 'bg-primary text-white rounded-tr-sm' : 'bg-white shadow text-gray-800 rounded-tl-sm'">
            {{ msg.text }}
            <p class="text-xs mt-1 opacity-60">{{ msg.time }}</p>
          </div>
        </div>
        <!-- Typing -->
        <div v-if="isTyping" class="flex gap-2 items-center">
          <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center shrink-0"><span class="mi mi-sm text-white">support_agent</span></div>
          <div class="bg-white shadow px-4 py-3 rounded-2xl rounded-tl-sm flex gap-1">
            <span v-for="i in 3" :key="i" class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" :style="`animation-delay:${i*0.15}s`"></span>
          </div>
        </div>
      </div>

      <!-- Quick Replies -->
      <div class="flex gap-2 overflow-x-auto p-3 border-t border-gray-100 bg-white">
        <button v-for="q in quickReplies" :key="q.label" @click="sendMessage(q.label)"
                class="whitespace-nowrap text-xs font-bengali text-primary border border-primary/30 bg-primary/5 px-3 py-1.5 rounded-full hover:bg-primary hover:text-white transition-colors shrink-0 flex items-center gap-1">
          <span class="mi mi-sm">{{ q.icon }}</span>{{ q.label }}
        </button>
      </div>

      <!-- Input -->
      <div class="flex gap-2 p-3 border-t border-gray-100 bg-white">
        <input v-model="chatInput" type="text" :placeholder="tr('বার্তা লিখুন...')"
               class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bengali outline-none focus:border-primary"
               @keyup.enter="sendMessage(chatInput)"/>
        <button @click="sendMessage(chatInput)" :disabled="!chatInput.trim()"
                class="btn-primary px-4 py-2.5 disabled:opacity-60 disabled:cursor-not-allowed">
          <span class="mi mi-sm">send</span>
        </button>
      </div>
    </div>

    <!-- Ticket Mode -->
    <div v-if="activeMode === 'ticket'" class="space-y-5">
      <!-- Success -->
      <div v-if="ticketSent" class="bg-green-50 border border-green-200 rounded-2xl p-5 text-center">
        <span class="mi text-3xl text-green-500 block mb-2">check_circle</span>
        <p class="font-bengali font-semibold text-green-700">{{ tr('টিকেট সফলভাবে জমা হয়েছে!') }}</p>
        <p class="font-bengali text-green-600 text-sm mt-1">{{ tr('আমরা ২৪ ঘণ্টার মধ্যে উত্তর দেব।') }}</p>
        <button @click="ticketSent = false" class="mt-3 text-sm font-bengali text-primary hover:underline">{{ tr('নতুন টিকেট করুন') }}</button>
      </div>

      <!-- New Ticket Form -->
      <div v-else class="bg-white rounded-2xl shadow-card p-6">
        <h2 class="font-display font-bold text-lg mb-4 flex items-center gap-2">
          <span class="mi text-primary">add_circle</span>{{ tr('নতুন সাপোর্ট টিকেট') }}
        </h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বিষয় *') }}</label>
            <input v-model="ticketForm.subject" type="text" :placeholder="tr('সংক্ষেপে সমস্যাটি লিখুন')" class="input-field"/>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বিভাগ') }}</label>
            <select v-model="ticketForm.category" class="input-field">
              <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('অর্ডার নম্বর (ঐচ্ছিক)') }}</label>
            <div class="relative"><span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">receipt</span>
              <input v-model="ticketForm.order_id" type="text" placeholder="BD-XXXXX" class="input-field pl-10"/></div>
          </div>
          <div>
            <label class="block text-sm font-bengali font-semibold text-gray-700 mb-1.5">{{ tr('বিস্তারিত বার্তা *') }}</label>
            <textarea v-model="ticketForm.message" rows="5" :placeholder="tr('সমস্যাটি বিস্তারিতভাবে বর্ণনা করুন...')" class="input-field resize-none"></textarea>
          </div>
          <div v-if="ticketError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm font-bengali">{{ ticketError }}</div>
          <button @click="submitTicket" :disabled="submitting" class="btn-primary w-full justify-center disabled:opacity-60">
            <span v-if="submitting" class="mi mi-sm animate-spin">refresh</span>
            <span v-else class="mi mi-sm">send</span>
            {{ submitting ? 'জমা হচ্ছে...' : 'টিকেট জমা দিন' }}
          </button>
        </div>
      </div>

      <!-- My Tickets -->
      <div v-if="authStore.isLoggedIn" class="bg-white rounded-2xl shadow-card overflow-hidden">
        <h2 class="font-display font-bold text-base p-4 border-b border-gray-50 flex items-center gap-2">
          <span class="mi text-primary">list_alt</span>{{ tr('আমার টিকেটসমূহ') }}
        </h2>
        <div v-if="loadingTickets" class="p-4 space-y-2">
          <div v-for="i in 3" :key="i" class="h-10 bg-gray-100 animate-pulse rounded-xl"></div>
        </div>
        <div v-else-if="tickets.length === 0" class="p-8 text-center">
          <span class="mi text-3xl text-gray-200 block mb-2">inbox</span>
          <p class="font-bengali text-gray-400 text-sm">{{ tr('কোনো টিকেট নেই।') }}</p>
        </div>
        <div v-else-if="openTicketData" class="p-4">
          <button @click="closeTicket" class="text-sm font-bengali text-primary hover:underline mb-3 flex items-center gap-1"><span class="mi mi-sm">arrow_back</span>{{ tr('সব টিকেট') }}</button>
          <p class="font-bengali font-bold text-gray-800">{{ openTicketData.subject }}</p>
          <p class="font-bengali text-xs text-gray-400 mb-3">#{{ openTicketData.ticket_number || openTicketData.id }} · {{ statusLabel(openTicketData.status) }}</p>
          <div class="space-y-3 max-h-96 overflow-y-auto bg-gray-50 rounded-xl p-3">
            <div v-for="m in openTicketData.messages || []" :key="m.id" class="flex" :class="m.sender_type === 'admin' ? 'justify-start' : 'justify-end'">
              <div class="max-w-[80%] px-3 py-2 rounded-2xl text-sm font-bengali whitespace-pre-line"
                   :class="m.sender_type === 'admin' ? 'bg-white shadow text-gray-800 rounded-tl-sm' : 'bg-primary text-white rounded-tr-sm'">
                <p v-if="m.sender_type === 'admin'" class="text-[10px] font-bold text-primary mb-0.5">{{ tr('সাপোর্ট টিম') }}</p>
                {{ m.message }}
                <p class="text-[10px] mt-1 opacity-60">{{ fmtTime(m.created_at) }}</p>
              </div>
            </div>
          </div>
          <div v-if="openTicketData.status !== 'closed'" class="flex gap-2 mt-3">
            <input v-model="replyText" type="text" :placeholder="tr('উত্তর লিখুন...')" class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bengali outline-none focus:border-primary" @keyup.enter="sendReply"/>
            <button @click="sendReply" :disabled="replying || !replyText.trim()" class="btn-primary px-4 py-2.5 disabled:opacity-60"><span class="mi mi-sm">send</span></button>
          </div>
        </div>
        <div v-else class="divide-y divide-gray-50">
          <div v-for="ticket in tickets" :key="ticket.id" @click="openTicket(ticket)" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-gray-50">
            <div class="flex-1 min-w-0">
              <p class="font-bengali font-semibold text-gray-800 text-sm line-clamp-1">{{ ticket.subject }}</p>
              <p class="font-bengali text-xs text-gray-400 mt-0.5">#{{ ticket.id }} · {{ new Date(ticket.created_at).toLocaleDateString('bn-BD') }}</p>
            </div>
            <span class="badge text-xs shrink-0" :class="statusClass(ticket.status)">{{ statusLabel(ticket.status) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Call Mode -->
    <div v-if="activeMode === 'call' && helpline" class="bg-white rounded-2xl shadow-card p-8 text-center">
      <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
        <span class="mi text-4xl text-primary">call</span>
      </div>
      <h2 class="font-display font-extrabold text-2xl mb-2">{{ tr('ফোন করুন') }}</h2>
      <p class="font-display font-bold text-4xl text-primary mb-3">{{ helpline }}</p>
      <p v-for="h in (footer.office_hours || [])" :key="h.day" class="font-bengali text-gray-500 mb-1">{{ h.day }}<template v-if="h.time">: {{ h.time }}</template></p>
      <a :href="helplineHref" class="btn-primary inline-flex text-lg px-8 py-4 mt-5">
        <span class="mi">call</span>{{ tr('এখনই কল করুন') }}
      </a>
    </div>
  </div>
</template>