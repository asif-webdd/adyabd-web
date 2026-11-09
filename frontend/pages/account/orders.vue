<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="section-title flex items-center gap-2"><span class="mi text-primary">inventory_2</span>{{ tr('আমার অর্ডার') }}</h1>
      <div class="relative">
        <span class="mi mi-sm absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
        <input v-model="searchQ" type="text" :placeholder="tr('অর্ডার নম্বর খুঁজুন...')" @keyup.enter="doSearch" class="border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm font-bengali outline-none focus:border-primary w-48"/>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button v-for="tab in tabs" :key="tab.value" @click="activeStatus = tab.value; loadOrders()" class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0" :class="activeStatus === tab.value ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary'">{{ tr(tab.label) }}</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-2xl shadow-card overflow-hidden animate-pulse">
        <div class="flex items-center justify-between p-4 border-b border-gray-50 bg-gray-50/50 flex-wrap gap-2">
          <div class="flex items-center gap-3">
            <div class="h-4 w-20 bg-gray-200 rounded"></div>
            <div class="h-5 w-16 bg-gray-200 rounded-full"></div>
            <div class="h-3 w-24 bg-gray-200 rounded"></div>
          </div>
          <div class="h-4 w-16 bg-gray-200 rounded"></div>
        </div>

        <div class="p-4 space-y-3">
          <div v-for="j in 2" :key="j" class="flex items-center gap-3">
            <div class="w-14 h-14 rounded-xl bg-gray-200 shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3.5 w-3/4 bg-gray-200 rounded"></div>
              <div class="h-3 w-1/2 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>

        <div class="px-4 pb-4 flex flex-wrap gap-2">
          <div class="h-9 flex-1 min-w-[120px] bg-gray-200 rounded-xl"></div>
          <div class="h-9 w-24 bg-gray-200 rounded-xl"></div>
          <div class="h-9 w-24 bg-gray-200 rounded-xl"></div>
        </div>

        <div class="px-4 pb-4">
          <div class="flex items-center justify-between relative">
            <div class="absolute left-5 right-5 top-4 h-1 bg-gray-200"></div>
            <div v-for="k in 4" :key="k" class="flex flex-col items-center gap-1 z-10">
              <div class="w-8 h-8 rounded-full bg-gray-200"></div>
              <div class="h-2.5 w-10 bg-gray-200 rounded hidden sm:block"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders -->
    <div v-else class="space-y-4">
      <div v-for="order in orders" :key="order.id" class="bg-white rounded-2xl shadow-card overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-50 bg-gray-50/50 flex-wrap gap-2">
          <div class="flex items-center gap-3 flex-wrap">
            <span class="font-display font-bold text-sm text-gray-800 flex items-center gap-1">
              <span class="mi mi-sm text-primary">receipt</span>#{{ order.order_number }}
            </span>
            <span class="badge text-xs" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
            <span class="text-xs font-bengali text-gray-400 flex items-center gap-1"><span class="mi mi-sm">calendar_today</span>{{ formatDate(order.created_at) }}</span>
          </div>
          <p class="font-display font-bold text-accent">৳{{ Number(order.total_amount).toLocaleString() }}</p>
        </div>

        <div class="p-4 space-y-3">
          <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3">
            <NuxtLink :to="`/product/${item.product_id}`">
              <img :src="item.product_image" :alt="item.product_name" class="w-14 h-14 object-cover rounded-xl bg-gray-50 shrink-0"/>
            </NuxtLink>
            <div class="flex-1 min-w-0">
              <NuxtLink :to="`/product/${item.product.slug}`" class="font-bengali font-medium text-sm text-gray-800 line-clamp-1 hover:text-primary transition-colors">{{ item.product_name }}</NuxtLink>
              <p class="font-bengali text-xs text-gray-400">পরিমাণ: {{ item.quantity }} · ৳{{ Number(item.unit_price).toLocaleString() }}</p>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
              <button v-if="order.status === 'delivered'" @click="openReview(item)" class="text-xs border border-primary text-primary px-3 py-1 rounded-lg font-bengali hover:bg-primary hover:text-white transition-colors flex items-center gap-1">
                <span class="mi mi-sm">rate_review</span> {{ tr('রিভিউ') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-4 pb-4 flex flex-wrap gap-2">
          <NuxtLink :to="`/order/track?id=${order.order_number}`" class="flex-1 min-w-[120px] text-sm font-bengali text-primary border border-primary px-4 py-2 rounded-xl hover:bg-primary hover:text-white transition-colors flex items-center justify-center gap-1">
            <span class="mi mi-sm">location_searching</span> {{ tr('ট্র্যাক') }}
          </NuxtLink>
          <!--          <button v-if="['pending','confirmed','processing'].includes(order.status)" @click="cancelOrder(order.id)" :disabled="cancelling === order.id" class="text-sm font-bengali text-accent border border-accent px-4 py-2 rounded-xl hover:bg-accent hover:text-white transition-colors flex items-center gap-1 disabled:opacity-60">
                      <span class="mi mi-sm">cancel</span> {{ tr('বাতিল') }}
                    </button>-->
          <button v-if="['pending','confirmed','processing'].includes(order.status)" @click="openCancelModal(order)" :disabled="cancelling === order.id" class="text-sm font-bengali text-accent border border-accent px-4 py-2 rounded-xl hover:bg-accent hover:text-white transition-colors flex items-center gap-1 disabled:opacity-60">
            <span class="mi mi-sm">cancel</span> {{ tr('বাতিল') }}
          </button>
          <NuxtLink v-if="order.status === 'delivered'" to="/order/return" class="text-sm font-bengali text-gray-600 border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
            <span class="mi mi-sm">undo</span> {{ tr('রিটার্ন') }}
          </NuxtLink>
          <button type="button" @click="openInvoice(order)" class="text-sm font-bengali text-gray-600 border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-1">
            <span class="mi mi-sm">description</span> Invoice
          </button>
        </div>

        <!-- Progress Bar -->
        <div v-if="!['cancelled','returned'].includes(order.status)" class="px-4 pb-4">
          <div class="flex items-center justify-between relative">
            <div class="absolute left-5 right-5 top-4 h-1 bg-gray-200 -z-0">
              <div class="h-full bg-primary transition-all rounded-full" :style="`width:${trackWidth(order.status)}%`"></div>
            </div>
            <div v-for="(step, i) in trackSteps" :key="i" class="flex flex-col items-center gap-1 z-10">
              <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-colors" :class="isStepDone(order.status, i) ? 'bg-primary border-primary text-white' : 'bg-white border-gray-200 text-gray-400'">
                <span class="mi mi-sm">{{ step.icon }}</span>
              </div>
              <span class="text-xs font-bengali text-gray-500 hidden sm:block text-center">{{ step.label }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!loading && orders.length === 0" class="bg-white rounded-2xl shadow-card p-12 text-center">
      <span class="mi text-gray-200" style="font-size:64px">inbox</span>
      <h3 class="font-display font-bold text-xl text-gray-700 mt-4 mb-2">{{ tr('কোনো অর্ডার নেই') }}</h3>
      <NuxtLink to="/" class="btn-primary inline-flex mt-2"><span class="mi mi-sm">shopping_bag</span>{{ tr('কেনাকাটা শুরু করুন') }}</NuxtLink>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center gap-2">
      <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
        <span class="mi mi-sm">arrow_back</span>
      </button>
      <button v-for="p in visiblePages" :key="p" @click="goPage(p)" class="px-4 py-2 rounded-xl border text-sm" :class="p === currentPage ? 'bg-primary text-white border-primary' : 'border-gray-200 hover:border-primary'">{{ p }}</button>
      <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages" class="px-4 py-2 rounded-xl border border-gray-200 text-sm hover:border-primary disabled:opacity-40">
        <span class="mi mi-sm">arrow_forward</span>
      </button>
    </div>
  </div>
  <!-- Cancel Order Modal -->
  <Teleport to="body">
    <div v-if="showCancelModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeCancelModal">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
              <span class="mi text-accent" style="font-size:20px">cancel</span>
            </div>
            <div>
              <p class="font-display font-bold text-gray-800 text-sm">{{ tr('অর্ডার বাতিল করুন') }}</p>
              <p class="text-xs font-bengali text-gray-400">#{{ cancelTarget?.order_number }}</p>
            </div>
          </div>
          <button @click="closeCancelModal" class="text-gray-400 hover:text-gray-600">
            <span class="mi">close</span>
          </button>
        </div>

        <!-- Warning -->
        <div class="mx-4 mt-4 bg-yellow-50 border border-yellow-200 rounded-xl p-3 flex gap-2">
          <span class="mi mi-sm text-yellow-500 mt-0.5 shrink-0">warning</span>
          <p class="text-xs font-bengali text-yellow-700 leading-relaxed">{{ tr('এই অর্ডার বাতিল করলে পুনরায় চালু করা সম্ভব হবে না। স্টক স্বয়ংক্রিয়ভাবে ফেরত যাবে।') }}</p>
        </div>

        <!-- Body -->
        <div class="p-4 space-y-3">
          <p class="text-xs font-bengali text-gray-500 font-medium">{{ tr('বাতিলের কারণ বেছে নিন') }} <span class="text-accent">*</span></p>

          <!-- Preset reasons -->
          <div class="grid grid-cols-2 gap-2">
            <label v-for="preset in cancelReasons" :key="preset" class="flex items-center gap-2 px-3 py-2.5 border rounded-xl cursor-pointer text-xs font-bengali transition-colors" :class="cancelReason === preset ? 'border-accent bg-red-50 text-accent' : 'border-gray-200 text-gray-600 hover:border-gray-300'">
              <input type="radio" v-model="cancelReason" :value="preset" class="hidden"/>
              <span class="mi mi-sm" :class="cancelReason === preset ? 'text-accent' : 'text-gray-400'">{{ cancelReason === preset ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
              {{ preset }}
            </label>
          </div>

          <!-- Custom reason textarea -->
          <div>
            <label class="text-xs font-bengali text-gray-500 mb-1 block">
              {{ tr('বিস্তারিত লিখুন') }}
              <span class="text-gray-400">{{ tr('(ঐচ্ছিক)') }}</span>
            </label>
            <textarea v-model="cancelDetail" :placeholder="tr('বাতিলের কারণ বিস্তারিত লিখুন...')" maxlength="500" rows="3" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-bengali outline-none focus:border-primary resize-none"/>
            <p class="text-right text-xs text-gray-400 mt-1">{{ cancelDetail.length }}/৫০০</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex gap-2 px-4 pb-4">
          <button @click="closeCancelModal" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-bengali text-gray-600 hover:bg-gray-50 transition-colors">{{ tr('ফিরে যান') }}</button>
          <button @click="submitCancel" :disabled="!finalReason || cancelling === cancelTarget?.id" class="flex-1 py-2.5 rounded-xl text-sm font-bengali font-medium bg-red-50 text-accent border border-accent hover:bg-accent hover:text-white transition-colors flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="mi mi-sm">{{ cancelling === cancelTarget?.id ? 'hourglass_empty' : 'cancel' }}</span>
            {{ cancelling === cancelTarget?.id ? 'বাতিল হচ্ছে...' : 'অর্ডার বাতিল করুন' }}
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
const { tr } = useLang()
const api     = useApi()
const config  = useRuntimeConfig()
const apiBase = config.public.apiBase

// Invoice is served through a short-lived signed URL issued to the order's owner.
const openInvoice = async (order) => {
  const win = window.open('', '_blank')   // open synchronously so popup blockers allow it
  try {
    const data = await api.get(`/orders/${order.id}/invoice`)
    if (!data?.invoice_url) throw new Error('no url')
    win.location.href = data.invoice_url
  } catch {
    win?.close()
    alert('ইনভয়েস লোড করা যায়নি। আবার চেষ্টা করুন।')
  }
}
const dataLayer = useDataLayer()

const orders      = ref([])
const loading     = ref(true)
const activeStatus = ref('')
const searchQ     = ref('')
const currentPage = ref(1)
const totalPages  = ref(1)
const cancelling  = ref(null)

const tabs = [
  { label: 'All Orders',      value: '' },
  { label: 'Pending',         value: 'pending' },
  { label: 'Confirmed',       value: 'confirmed' },
  { label: 'Shipped',         value: 'shipped' },
  { label: 'Delivered',       value: 'delivered' },
  { label: 'Cancelled',       value: 'cancelled' },
]

const trackSteps = [
  { icon: 'check_circle', label: 'Confirm'  },
  { icon: 'inventory_2',  label: 'Packing'  },
  { icon: 'local_shipping', label: 'On The away'   },
  { icon: 'home',         label: 'Delivery' },
]

const statusOrder = { pending: 0, confirmed: 1, processing: 1, shipped: 2, delivered: 3 }
const trackWidth  = (s) => [0, 33, 66, 100][statusOrder[s] ?? 0]
const isStepDone  = (s, i) => (statusOrder[s] ?? -1) >= i

const statusClass = (s) => ({
  delivered:  'bg-green-100 text-green-700',
  shipped:    'bg-blue-100 text-blue-700',
  processing: 'bg-blue-100 text-blue-700',
  confirmed:  'bg-indigo-100 text-indigo-700',
  pending:    'bg-yellow-100 text-yellow-700',
  cancelled:  'bg-red-100 text-red-700',
  returned:   'bg-orange-100 text-orange-700',
}[s] || 'bg-gray-100 text-gray-600')

const statusLabel = (s) => ({
  delivered:  'Delivered',
  shipped:    'Shipped',
  processing: 'Processing',
  confirmed:  'Confirmed',
  pending:    'Pending',
  cancelled:  'Cancelled',
  returned:   'Returned',
}[s] || s)

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

const visiblePages = computed(() => {
  const pages = []; const start = Math.max(1, currentPage.value - 2); const end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i); return pages
})

const loadOrders = async () => {
  loading.value = true
  try {
    const params = { page: currentPage.value }
    if (activeStatus.value) params.status = activeStatus.value
    if (searchQ.value)      params.q      = searchQ.value
    const data     = await api.get('/orders', params)
    orders.value   = (data.data ?? data ?? [])
    totalPages.value = data.last_page ?? 1
  } catch {
    orders.value = []
  } finally {
    loading.value = false
  }
}

const doSearch = () => { currentPage.value = 1; loadOrders() }

const goPage = (p) => { currentPage.value = p; loadOrders(); window.scrollTo({ top: 0, behavior: 'smooth' }) }

// --- Cancel modal state ---
const showCancelModal = ref(false)
const cancelTarget    = ref(null)
const cancelReason    = ref('')
const cancelDetail    = ref('')

const cancelReasons = [
  'Ordered the wrong product',
  'Want to change the address',
  'No longer needed',
  'Other reason'
]

// computed: preset OR custom detail, whichever is filled
const finalReason = computed(() => {
  if (cancelDetail.value.trim()) return cancelDetail.value.trim()
  return cancelReason.value
})

const openCancelModal = (order) => {
  cancelTarget.value = order
  cancelReason.value = ''
  cancelDetail.value = ''
  showCancelModal.value = true
}

const closeCancelModal = () => {
  showCancelModal.value = false
  cancelTarget.value = null
}

const submitCancel = async () => {
  if (!finalReason.value || !cancelTarget.value) return
  cancelling.value = cancelTarget.value.id
  try {
    await api.put(`/orders/${cancelTarget.value.id}/cancel`, { reason: finalReason.value })
    await dataLayer.cancelOrder(cancelTarget.value, finalReason.value)
    if (process.client && window.$toast)
      window.$toast({ type: 'success', title: 'অর্ডার বাতিল হয়েছে' })
    closeCancelModal()
    await loadOrders()
  } catch (e) {
    if (process.client && window.$toast)
      window.$toast({ type: 'error', title: e.message ?? 'বাতিল করা যায়নি' })
  } finally {
    cancelling.value = null
  }
}

const openReview = (item) => navigateTo(`/account/reviews?product=${item.product_id}`)

onMounted(loadOrders)
</script>