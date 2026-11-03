<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="section-title flex items-center gap-2">
        <span class="mi text-primary">notifications</span>{{ tr('নোটিফিকেশন') }}
        <span v-if="unreadCount > 0" class="badge bg-accent text-white text-xs">{{ unreadCount }}</span>
      </h1>
      <button @click="markAllRead" :disabled="unreadCount === 0"
        class="text-sm font-bengali text-primary hover:underline flex items-center gap-1 disabled:opacity-40">
        <span class="mi mi-sm">done_all</span>{{ tr('সব পড়া হিসেবে চিহ্নিত করুন') }}
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value"
        class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-bengali font-medium border-2 transition-colors shrink-0"
        :class="activeTab === tab.value ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary'">
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-2xl shadow-card divide-y divide-gray-50">
      <div v-for="i in 5" :key="i" class="flex gap-4 p-4 animate-pulse">
        <div class="w-12 h-12 bg-gray-200 rounded-full shrink-0"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 rounded w-2/3"></div>
          <div class="h-3 bg-gray-200 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white rounded-2xl shadow-card overflow-hidden divide-y divide-gray-50">
      <div v-for="notif in filtered" :key="notif.id"
        @click="openNotif(notif)"
        class="flex items-start gap-4 p-4 transition-colors cursor-pointer hover:bg-gray-50"
        :class="!notif.read_at ? 'bg-primary/5' : ''">
        <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
          :class="iconBg(notif.type)">
          <span class="mi" :class="iconColor(notif.type)">{{ iconName(notif.type) }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-bengali font-semibold text-gray-800 text-sm">{{ notif.data?.title ?? notifTitle(notif.type) }}</p>
              <p class="font-bengali text-gray-500 text-xs mt-0.5">{{ notif.data?.message ?? notif.data?.body ?? '' }}</p>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0">
              <p class="text-xs font-bengali text-gray-400 flex items-center gap-0.5">
                <span class="mi mi-sm">access_time</span>{{ timeAgo(notif.created_at) }}
              </p>
              <div v-if="!notif.read_at" class="w-2.5 h-2.5 rounded-full bg-primary"></div>
            </div>
          </div>
          <div v-if="notif.data?.action_url" class="mt-2">
            <NuxtLink :to="notif.data.action_url" @click.stop class="text-xs font-bengali text-primary border border-primary px-3 py-1 rounded-lg hover:bg-primary hover:text-white transition-colors inline-flex items-center gap-0.5">
              <span class="mi mi-sm">arrow_forward</span>{{ notif.data.action_label ?? 'দেখুন' }}
            </NuxtLink>
          </div>
        </div>
      </div>

      <div v-if="filtered.length === 0" class="p-12 text-center">
        <span class="mi text-gray-200" style="font-size:64px">notifications_none</span>
        <h3 class="font-display font-bold text-lg text-gray-700 mt-4">{{ tr('কোনো নোটিফিকেশন নেই') }}</h3>
      </div>
    </div>

    <!-- Notification details popup -->
    <div v-if="sel" class="fixed inset-0 z-50 bg-black/50 flex items-end md:items-center justify-center p-0 md:p-4" @click.self="sel = null">
      <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" :class="iconBg(sel.type)">
              <span class="mi" :class="iconColor(sel.type)">{{ iconName(sel.type) }}</span>
            </div>
            <h3 class="font-display font-bold text-lg text-gray-800">{{ sel.data?.title ?? notifTitle(sel.type) }}</h3>
          </div>
          <button @click="sel = null" class="text-2xl leading-none text-gray-400 hover:text-gray-600">×</button>
        </div>
        <p class="font-bengali text-gray-700 text-sm whitespace-pre-line leading-relaxed">{{ sel.data?.message ?? sel.data?.body ?? '' }}</p>
        <p class="text-xs text-gray-400 mt-4 flex items-center gap-1">
          <span class="mi mi-sm">access_time</span>{{ fullDate(sel.created_at) }}
        </p>
        <div class="flex gap-2 mt-5">
          <NuxtLink v-if="selLink" :to="selLink" @click="sel = null"
            class="btn-primary text-sm inline-flex items-center gap-1"><span class="mi mi-sm">arrow_forward</span>{{ selLabel }}</NuxtLink>
          <button @click="sel = null" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-primary">{{ tr('বন্ধ করুন') }}</button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center gap-2">
      <button @click="goPage(currentPage - 1)" :disabled="currentPage <= 1"
        class="px-4 py-2 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
        <span class="mi mi-sm">arrow_back</span>
      </button>
      <span class="px-4 py-2 font-bengali text-sm text-gray-600">{{ currentPage }} / {{ totalPages }}</span>
      <button @click="goPage(currentPage + 1)" :disabled="currentPage >= totalPages"
        class="px-4 py-2 rounded-xl border border-gray-200 text-sm disabled:opacity-40 hover:border-primary">
        <span class="mi mi-sm">arrow_forward</span>
      </button>
    </div>
  </div>
</template>

<script setup>
const { tr } = useLang()
const api = useApi()

const notifications = ref([])
const loading       = ref(true)
const activeTab     = ref('all')
const currentPage   = ref(1)
const totalPages    = ref(1)

const tabs = [
  { label: 'সব',      value: 'all'    },
  { label: 'অর্ডার', value: 'order'  },
  { label: 'অফার',   value: 'promo'  },
  { label: 'সিস্টেম', value: 'system' },
]

const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)

const filtered = computed(() => {
  if (activeTab.value === 'all') return notifications.value
  return notifications.value.filter(n =>
    (n.type ?? '').toLowerCase().includes(activeTab.value) ||
    (n.data?.category ?? '').toLowerCase().includes(activeTab.value)
  )
})

const iconBg    = (type) => type?.includes('order') ? 'bg-blue-50' : type?.includes('promo') ? 'bg-yellow-50' : 'bg-gray-100'
const iconColor = (type) => type?.includes('order') ? 'text-blue-500' : type?.includes('promo') ? 'text-yellow-500' : 'text-gray-500'
const iconName  = (type) => type?.includes('order') ? 'local_shipping' : type?.includes('promo') ? 'local_offer' : 'notifications'

const notifTitle = (type) => ({
  'order.confirmed':  'অর্ডার নিশ্চিত হয়েছে',
  'order.shipped':    'ডেলিভারি শুরু হয়েছে',
  'order.delivered':  'ডেলিভারি সম্পন্ন',
  'order.cancelled':  'অর্ডার বাতিল',
  'promo.flash':      'ফ্ল্যাশ অফার!',
  'system.security':  'সিকিউরিটি অ্যালার্ট',
  'wallet.credit':    'ওয়ালেটে টাকা জমা হয়েছে',
}[type] || 'নোটিফিকেশন')

const timeAgo = (iso) => {
  if (!iso) return ''
  const diff = Math.floor((Date.now() - new Date(iso)) / 1000)
  if (diff < 60)      return 'এইমাত্র'
  if (diff < 3600)    return `${Math.floor(diff / 60)} মিনিট আগে`
  if (diff < 86400)   return `${Math.floor(diff / 3600)} ঘণ্টা আগে`
  if (diff < 604800)  return `${Math.floor(diff / 86400)} দিন আগে`
  return new Date(iso).toLocaleDateString('bn-BD', { day: 'numeric', month: 'short' })
}

const loadNotifications = async () => {
  loading.value = true
  try {
    const data = await api.get('/notifications', { page: currentPage.value })
    notifications.value = data.data ?? data ?? []
    totalPages.value    = data.last_page ?? 1
  } catch {}
  finally { loading.value = false }
}

const sel = ref(null)
const selLink = computed(() => {
  const d = sel.value?.data
  if (!d) return ''
  if (d.action_url) return d.action_url
  if (d.order_id && String(d.type ?? '').startsWith('seller_')) return `/seller/dashboard?order=${d.order_id}`
  return ''
})
const selLabel = computed(() => sel.value?.data?.action_label ?? (sel.value?.data?.order_id ? tr('অর্ডার দেখুন') : tr('দেখুন')))
const fullDate = (iso) => { try { return iso ? new Date(iso).toLocaleString('bn-BD', { dateStyle: 'medium', timeStyle: 'short' }) : '' } catch { return '' } }
const openNotif = (notif) => { sel.value = notif; markRead(notif) }

const markRead = async (notif) => {
  if (notif.read_at) return
  try {
    await api.put(`/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
  } catch {}
}

const markAllRead = async () => {
  try {
    await api.put('/notifications/read-all')
    notifications.value.forEach(n => { n.read_at = new Date().toISOString() })
  } catch {}
}

const goPage = (p) => {
  currentPage.value = p
  loadNotifications()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(loadNotifications)
</script>
